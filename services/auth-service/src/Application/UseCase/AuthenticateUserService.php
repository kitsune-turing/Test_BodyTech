<?php

declare(strict_types=1);

namespace AuthService\Application\UseCase;

use AuthService\Application\DTO\AuthResponse;
use AuthService\Application\DTO\LoginRequest;
use AuthService\Application\Port\JwtProviderInterface;
use AuthService\Application\Port\PasswordHasherInterface;
use AuthService\Domain\Repository\UserRepositoryInterface;
use AuthService\Domain\Validator\UserValidator;
use AuthService\Domain\ValueObject\Email;
use Exception;

/**
 * Authenticate User Use Case
 * Handles user login and JWT generation
 */
final class AuthenticateUserService
{
    private UserRepositoryInterface $userRepository;
    private PasswordHasherInterface $passwordHasher;
    private JwtProviderInterface $jwtProvider;
    private UserValidator $validator;

    public function __construct(
        UserRepositoryInterface $userRepository,
        PasswordHasherInterface $passwordHasher,
        JwtProviderInterface $jwtProvider,
        UserValidator $validator
    ) {
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
        $this->jwtProvider = $jwtProvider;
        $this->validator = $validator;
    }

    /**
     * @throws Exception
     */
    public function execute(LoginRequest $request): AuthResponse
    {
        // Valida los datos de entrada
        $errors = $this->validator->validateLogin([
            'email' => $request->email,
            'password' => $request->password,
        ]);

        if (!empty($errors)) {
            throw new Exception(json_encode([
                'code' => 'VALIDATION_ERROR',
                'message' => 'Invalid login data',
                'details' => $errors,
            ]));
        }

        // Busca el usuario por email
        $email = new Email($request->email);
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            throw new Exception(json_encode([
                'code' => 'INVALID_CREDENTIALS',
                'message' => 'Invalid email or password',
            ]));
        }

        // Verifica la contraseña con Argon2id
        if (!$this->passwordHasher->verify($request->password, $user->getPasswordHash())) {
            throw new Exception(json_encode([
                'code' => 'INVALID_CREDENTIALS',
                'message' => 'Invalid email or password',
            ]));
        }

        // Genera el token JWT
        $tokenData = $this->jwtProvider->generate($user->getId());

        return new AuthResponse(
            $tokenData['token'],
            $tokenData['expires_in'],
            $user->toArray()
        );
    }
}
