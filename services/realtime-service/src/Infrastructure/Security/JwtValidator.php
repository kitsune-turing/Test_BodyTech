<?php

declare(strict_types=1);

namespace RealtimeService\Infrastructure\Security;

/**
 * JWT Validator
 * Validates JWT tokens for WebSocket connections
 */
final class JwtValidator
{
    private string $secret;

    public function __construct(string $secret)
    {
        if (strlen($secret) < 32) {
            throw new \InvalidArgumentException('JWT secret must be at least 32 characters');
        }

        $this->secret = $secret;
    }

    public function validate(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new \Exception('Invalid token format');
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        // Verifica la firma del token
        $signingInput = $headerB64 . '.' . $payloadB64;
        $expectedSignature = $this->sign($signingInput);

        if (!hash_equals($expectedSignature, $signatureB64)) {
            throw new \Exception('Invalid token signature');
        }

        // Decodifica el payload del token
        $payload = json_decode($this->urlSafeB64Decode($payloadB64), true);

        if (!$payload) {
            throw new \Exception('Invalid token payload');
        }

        // Verifica que el token no haya expirado
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new \Exception('Token has expired');
        }

        return $payload;
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
}
