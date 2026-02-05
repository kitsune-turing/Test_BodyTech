<?php

declare(strict_types=1);

namespace TaskService\Tests\Unit\Infrastructure\Http\Middleware;

use PHPUnit\Framework\TestCase;
use TaskService\Infrastructure\Http\Middleware\RateLimitMiddleware;

final class RateLimitMiddlewareTest extends TestCase
{
    private $redis;
    private RateLimitMiddleware $middleware;

    protected function setUp(): void
    {
        $this->redis = $this->createMock(\Redis::class);
        $config = [
            'enabled' => true,
            'limit' => 10,
            'window' => 60,
        ];
        $this->middleware = new RateLimitMiddleware($this->redis, $config);
    }

    public function testHandleFirstRequest(): void
    {
        $this->redis
            ->expects($this->once())
            ->method('get')
            ->willReturn(false);

        $this->redis
            ->expects($this->once())
            ->method('setex')
            ->with(
                $this->stringContains('rate_limit:user1:'),
                60,
                '1'
            );

        $this->redis
            ->expects($this->once())
            ->method('ttl')
            ->willReturn(60);

        $result = $this->middleware->handle('user1', 'GET:/api/tasks');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('headers', $result);
        $this->assertEquals('10', $result['headers']['X-RateLimit-Limit']);
        $this->assertEquals('9', $result['headers']['X-RateLimit-Remaining']);
    }

    public function testHandleWithinLimit(): void
    {
        $this->redis
            ->expects($this->once())
            ->method('get')
            ->willReturn('5');

        $this->redis
            ->expects($this->once())
            ->method('setex')
            ->with(
                $this->stringContains('rate_limit:'),
                $this->greaterThan(0),
                '6'
            );

        $this->redis
            ->method('ttl')
            ->willReturn(45);

        $result = $this->middleware->handle('user1', 'GET:/api/tasks');

        $this->assertTrue($result['success']);
        $this->assertEquals('4', $result['headers']['X-RateLimit-Remaining']);
    }

    public function testHandleExceedsLimit(): void
    {
        $this->redis
            ->expects($this->once())
            ->method('get')
            ->willReturn('10');

        $this->redis
            ->method('ttl')
            ->willReturn(30);

        $result = $this->middleware->handle('user1', 'GET:/api/tasks');

        $this->assertFalse($result['success']);
        $this->assertEquals(429, $result['status']);
        $this->assertEquals('RATE_LIMIT_EXCEEDED', $result['error']['code']);
        $this->assertStringContainsString('Too many requests', $result['error']['message']);
        $this->assertEquals('0', $result['headers']['X-RateLimit-Remaining']);
    }

    public function testHandleWhenDisabled(): void
    {
        $redis = $this->createMock(\Redis::class);
        $config = [
            'enabled' => false,
            'limit' => 10,
            'window' => 60,
        ];
        $middleware = new RateLimitMiddleware($redis, $config);

        $redis->expects($this->never())->method('get');
        $redis->expects($this->never())->method('setex');

        $result = $middleware->handle('user1', 'GET:/api/tasks');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('headers', $result);
    }

    public function testHandleResetTimeCorrect(): void
    {
        $currentTime = time();
        $ttl = 45;

        $this->redis
            ->expects($this->once())
            ->method('get')
            ->willReturn('5');

        $this->redis
            ->method('ttl')
            ->willReturn($ttl);

        $result = $this->middleware->handle('user1', 'GET:/api/tasks');

        $this->assertTrue($result['success']);
        $resetTime = (int) $result['headers']['X-RateLimit-Reset'];
        $this->assertGreaterThanOrEqual($currentTime + $ttl - 2, $resetTime);
        $this->assertLessThanOrEqual($currentTime + $ttl + 2, $resetTime);
    }

    public function testGetLimitForEndpoint(): void
    {
        $createLimit = RateLimitMiddleware::getLimitForEndpoint('POST', '/v1/api/tasks');
        $listLimit = RateLimitMiddleware::getLimitForEndpoint('GET', '/v1/api/tasks');
        $exportLimit = RateLimitMiddleware::getLimitForEndpoint('GET', '/v1/api/tasks/export/csv');
        $defaultLimit = RateLimitMiddleware::getLimitForEndpoint('GET', '/v1/api/unknown');

        $this->assertEquals(20, $createLimit);
        $this->assertEquals(60, $listLimit);
        $this->assertEquals(10, $exportLimit);
        $this->assertEquals(100, $defaultLimit);
    }

    public function testHandleDifferentEndpointsIndependently(): void
    {
        $middleware1 = new RateLimitMiddleware($this->redis, [
            'enabled' => true,
            'limit' => 10,
            'window' => 60,
        ]);

        $this->redis
            ->method('get')
            ->willReturnOnConsecutiveCalls('5', '3');

        $this->redis
            ->method('ttl')
            ->willReturn(45);

        $result1 = $middleware1->handle('user1', 'GET:/api/tasks');
        $result2 = $middleware1->handle('user1', 'POST:/api/tasks');

        $this->assertTrue($result1['success']);
        $this->assertTrue($result2['success']);
    }

    public function testHandleSpecialCharactersInEndpoint(): void
    {
        $this->redis
            ->expects($this->once())
            ->method('get')
            ->willReturn(false);

        $this->redis
            ->expects($this->once())
            ->method('setex')
            ->with(
                $this->matchesRegularExpression('/^rate_limit:user1:[a-zA-Z0-9_-]+$/'),
                $this->anything(),
                $this->anything()
            );

        $this->redis
            ->method('ttl')
            ->willReturn(60);

        $result = $this->middleware->handle('user1', 'GET:/api/tasks?page=1&limit=20');

        $this->assertTrue($result['success']);
    }
}
