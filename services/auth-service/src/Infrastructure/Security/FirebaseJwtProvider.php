<?php

declare(strict_types=1);

namespace AuthService\Infrastructure\Security;

use AuthService\Application\Port\JwtProviderInterface;

/**
 * Firebase JWT Provider
 * Note: This is a simplified implementation. In production, use firebase/php-jwt library
 */
final class FirebaseJwtProvider implements JwtProviderInterface
{
    private string $secret;
    private int $exp;

    public function __construct(string $secret, int $exp = 3600)
    {
        if (strlen($secret) < 32) {
            throw new \InvalidArgumentException('JWT secret must be at least 32 characters long');
        }

        $this->secret = $secret;
        $this->exp = $exp;
    }

    public function generate(int $userId): array
    {
        $jti = $this->generateUuid();
        $iat = time();
        $exp = $iat + $this->exp;

        $payload = [
            'sub' => $userId,
            'iat' => $iat,
            'exp' => $exp,
            'jti' => $jti,
        ];

        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256',
        ];

        $segments = [];
        $segments[] = $this->urlSafeB64Encode(json_encode($header));
        $segments[] = $this->urlSafeB64Encode(json_encode($payload));

        $signingInput = implode('.', $segments);
        $signature = $this->sign($signingInput);
        $segments[] = $signature;

        $token = implode('.', $segments);

        return [
            'token' => $token,
            'expires_in' => $this->exp,
            'jti' => $jti,
        ];
    }

    public function validate(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new \Exception('Invalid token format');
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;
        $signingInput = $headerB64 . '.' . $payloadB64;
        $expectedSignature = $this->sign($signingInput);

        if (!hash_equals($expectedSignature, $signatureB64)) {
            throw new \Exception('Invalid token signature');
        }
        $payload = json_decode($this->urlSafeB64Decode($payloadB64), true);

        if (!$payload) {
            throw new \Exception('Invalid token payload');
        }
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new \Exception('Token has expired');
        }

        return $payload;
    }

    public function extractJti(string $token): ?string
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }

            $payload = json_decode($this->urlSafeB64Decode($parts[1]), true);
            return $payload['jti'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function sign(string $input): string
    {
        $signature = hash_hmac('sha256', $input, $this->secret, true);
        return $this->urlSafeB64Encode($signature);
    }

    private function urlSafeB64Encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function urlSafeB64Decode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $padLen = 4 - $remainder;
            $data .= str_repeat('=', $padLen);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }

    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
