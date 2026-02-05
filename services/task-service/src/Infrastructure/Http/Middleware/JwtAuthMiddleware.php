<?php

declare(strict_types=1);

namespace TaskService\Infrastructure\Http\Middleware;

/**
 * JWT Auth Middleware
 * Validates JWT tokens (simplified version for Task Service)
 */
final class JwtAuthMiddleware
{
    private string $jwtSecret;

    public function __construct(string $jwtSecret)
    {
        $this->jwtSecret = $jwtSecret;
    }

    public function handle(string $authHeader): array
    {
        error_log('DEBUG: Auth header received: ' . ($authHeader ? 'Present' : 'Missing'));
        if ($authHeader) {
            error_log('DEBUG: Auth header value: ' . substr($authHeader, 0, 30) . '...');
        }
        
        // Extrae el token del header Authorization
        if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            error_log('DEBUG: Failed to extract Bearer token from header');
            return [
                'success' => false,
                'status' => 401,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Missing or invalid authorization header',
                ],
            ];
        }

        $token = $matches[1];
        error_log('DEBUG: Token extracted, length: ' . strlen($token));

        try {
            // Valida el token JWT
            $payload = $this->validateToken($token);
            
            error_log('DEBUG: Token validated successfully. User ID: ' . $payload['sub']);

            return [
                'success' => true,
                'user_id' => $payload['sub'],
                'payload' => $payload,
            ];

        } catch (\Exception $e) {
            error_log('DEBUG: Token validation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'status' => 401,
                'error' => [
                    'code' => 'INVALID_TOKEN',
                    'message' => $e->getMessage(),
                ],
            ];
        }
    }

    private function validateToken(string $token): array
    {
        $parts = explode('.', $token);
        
        error_log('DEBUG: Token parts count: ' . count($parts));
        error_log('DEBUG: Token first 50 chars: ' . substr($token, 0, 50));

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
        $signature = hash_hmac('sha256', $input, $this->jwtSecret, true);
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
