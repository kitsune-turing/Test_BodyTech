<?php

declare(strict_types=1);

namespace AuthService\Tests\Integration\Infrastructure\Security;

use AuthService\Infrastructure\Security\Argon2PasswordHasher;
use AuthService\Domain\ValueObject\HashedPassword;
use PHPUnit\Framework\TestCase;

class Argon2PasswordHasherTest extends TestCase
{
    private Argon2PasswordHasher $hasher;

    protected function setUp(): void
    {
        $this->hasher = new Argon2PasswordHasher();
    }

    public function test_hashes_password_with_argon2id(): void
    {
        $plainPassword = 'MySecureP@ssw0rd!';

        $hashedPassword = $this->hasher->hash($plainPassword);

        $this->assertInstanceOf(HashedPassword::class, $hashedPassword);
        $this->assertNotEquals($plainPassword, $hashedPassword->getHash());
        $this->assertStringStartsWith('$argon2id$', $hashedPassword->getHash());
    }

    public function test_verifies_correct_password(): void
    {
        $plainPassword = 'MySecureP@ssw0rd!';
        $hashedPassword = $this->hasher->hash($plainPassword);

        $result = $this->hasher->verify($plainPassword, $hashedPassword);

        $this->assertTrue($result);
    }

    public function test_rejects_incorrect_password(): void
    {
        $plainPassword = 'MySecureP@ssw0rd!';
        $hashedPassword = $this->hasher->hash($plainPassword);

        $result = $this->hasher->verify('WrongPassword123!', $hashedPassword);

        $this->assertFalse($result);
    }

    public function test_generates_different_hashes_for_same_password(): void
    {
        $plainPassword = 'MySecureP@ssw0rd!';

        $hash1 = $this->hasher->hash($plainPassword);
        $hash2 = $this->hasher->hash($plainPassword);

        $this->assertNotEquals($hash1->getHash(), $hash2->getHash());
        $this->assertTrue($this->hasher->verify($plainPassword, $hash1));
        $this->assertTrue($this->hasher->verify($plainPassword, $hash2));
    }

    public function test_hash_respects_custom_parameters(): void
    {
        $hasher = new Argon2PasswordHasher(32768, 2, 1);
        $plainPassword = 'TestPassword123!';

        $hashedPassword = $hasher->hash($plainPassword);

        $this->assertTrue($hasher->verify($plainPassword, $hashedPassword));
    }
}
