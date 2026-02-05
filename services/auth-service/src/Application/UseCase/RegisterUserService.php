<?php

declare(strict_types=1);

namespace AuthService\Application\UseCase;

use AuthService\Application\DTO\RegisterRequest;
use AuthService\Application\Port\PasswordHasherInterface;
use AuthService\Domain\Entity\User;
use AuthService\Domain\Repository\UserRepositoryInterface;
use AuthService\Domain\Validator\UserValidator;
use AuthService\Domain\ValueObject\Email;
use Exception;

/**
 * Register User Use Case
 * Handles user registration with validation and password hashing
 */
final class RegisterUserService
{
    private UserRepositoryInterface $userRepository;
    private PasswordHasherInterface $passwordHasher;
    private UserValidator $validator;

    public function __construct(
        UserRepositoryInterface $userRepository,
        PasswordHasherInterface $passwordHasher,
        UserValidator $validator
    ) {
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
        $this->validator = $validator;
    }

    /**
     * @throws Exception
     */
    public function execute(RegisterRequest $request): User
    {
        // Valida los datos de entrada
        $errors = $this->validator->validateRegistration([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
        ]);

        if (!empty($errors)) {
            throw new Exception(json_encode([
                'code' => 'VALIDATION_ERROR',
                'message' => 'Invalid registration data',
                'details' => $errors,
            ]));
        }

        // Crea los objetos de valor
        $email = new Email($request->email);

        // Verifica si el email ya está registrado
        if ($this->userRepository->emailExists($email)) {
            throw new Exception(json_encode([
                'code' => 'EMAIL_EXISTS',
                'message' => 'Email already registered',
            ]));
        }

        // Hashea la contraseña con Argon2id
        $hashedPassword = $this->passwordHasher->hash($request->password);

        // Crea la entidad de usuario
        $user = User::create($request->name, $email, $hashedPassword);

        // Persiste el usuario en la base de datos
        return $this->userRepository->save($user);
    }
}
