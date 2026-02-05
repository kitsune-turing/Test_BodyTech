<?php

declare(strict_types=1);

namespace AuthService\Infrastructure\Http\Controller;

use AuthService\Application\DTO\LoginRequest;
use AuthService\Application\DTO\RegisterRequest;
use AuthService\Application\UseCase\AuthenticateUserService;
use AuthService\Application\UseCase\RegisterUserService;
use AuthService\Application\UseCase\RevokeTokenService;
use AuthService\Infrastructure\Cache\RedisCacheManager;

/**
 * Auth Controller
 * Handles HTTP requests for authentication endpoints
 */
final class AuthController
{
    private RegisterUserService $registerService;
    private AuthenticateUserService $authenticateService;
    private RevokeTokenService $revokeTokenService;
    private RedisCacheManager $cacheManager;

    public function __construct(
        RegisterUserService $registerService,
        AuthenticateUserService $authenticateService,
        RevokeTokenService $revokeTokenService,
        RedisCacheManager $cacheManager
    ) {
        $this->registerService = $registerService;
        $this->authenticateService = $authenticateService;
        $this->revokeTokenService = $revokeTokenService;
        $this->cacheManager = $cacheManager;
    }

    public function register(array $requestData): array
    {
        try {
            $request = RegisterRequest::fromArray($requestData);
            $user = $this->registerService->execute($request);

            // Cachea la información del usuario tras el registro (TTL de 1 hora)
            if ($user && isset($user->id)) {
                $this->cacheManager->setEncoded(
                    RedisCacheManager::getUserKey($user->id),
                    $user->toArray(),
                    3600
                );
                // También cachea por email para búsqueda rápida
                $this->cacheManager->setEncoded(
                    RedisCacheManager::getUserEmailKey($user->email),
                    $user->toArray(),
                    3600
                );
            }

            return [
                'status' => 201,
                'data' => $user->toArray(),
            ];
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    public function login(array $requestData): array
    {
        try {
            $request = LoginRequest::fromArray($requestData);
            $authResponse = $this->authenticateService->execute($request);

            // Cachea la respuesta de autenticación (TTL de 5 minutos).
            // Incluye la información del usuario y el token.
            if ($authResponse && isset($authResponse->user) && is_array($authResponse->user) && isset($authResponse->user['id'])) {
                $this->cacheManager->setEncoded(
                    RedisCacheManager::getUserKey($authResponse->user['id']),
                    $authResponse->user,
                    300
                );
            }

            return [
                'status' => 200,
                'data' => $authResponse->toArray(),
            ];
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    public function logout(string $token): array
    {
        try {
            $this->revokeTokenService->execute($token);

            return [
                'status' => 204,
                'data' => null,
            ];
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    private function handleError(\Exception $e): array
    {
       
        $errorData = json_decode($e->getMessage(), true);

        if (is_array($errorData) && isset($errorData['code'])) {
            $statusCode = 500; // default
            
            switch($errorData['code']) {
                case 'VALIDATION_ERROR':
                    $statusCode = 400;
                    break;
                case 'EMAIL_EXISTS':
                    $statusCode = 409;
                    break;
                case 'INVALID_CREDENTIALS':
                    $statusCode = 401;
                    break;
                case 'INVALID_TOKEN':
                    $statusCode = 401;
                    break;
            }

            return [
                'status' => $statusCode,
                'error' => $errorData,
            ];
        }

        return [
            'status' => 500,
            'error' => [
                'code' => 'INTERNAL_ERROR',
                'message' => 'An unexpected error occurred',
            ],
        ];
    }
}
