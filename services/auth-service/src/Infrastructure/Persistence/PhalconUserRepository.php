<?php

declare(strict_types=1);

namespace AuthService\Infrastructure\Persistence;

use AuthService\Domain\Entity\User;
use AuthService\Domain\Repository\UserRepositoryInterface;
use AuthService\Domain\ValueObject\Email;
use AuthService\Domain\ValueObject\HashedPassword;
use AuthService\Infrastructure\Persistence\Model\UserModel;
use PDO;

/**
 * Phalcon User Repository
 * Implements persistence using PDO (simplified for microservice)
 */
final class PhalconUserRepository implements UserRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function save(User $user): User
    {
        if ($user->getId() === null) {
            return $this->insert($user);
        }

        return $this->update($user);
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare('
            SELECT id, name, email, password_hash, created_at, updated_at
            FROM users
            WHERE id = :id
        ');

        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->mapToEntity($row);
    }

    public function findByEmail(Email $email): ?User
    {
        $stmt = $this->pdo->prepare('
            SELECT id, name, email, password_hash, created_at, updated_at
            FROM users
            WHERE email = :email
        ');

        $stmt->execute(['email' => $email->getValue()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->mapToEntity($row);
    }

    public function emailExists(Email $email): bool
    {
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) as count
            FROM users
            WHERE email = :email
        ');

        $stmt->execute(['email' => $email->getValue()]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['count'] > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    private function insert(User $user): User
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO users (name, email, password_hash, created_at, updated_at)
            VALUES (:name, :email, :password_hash, NOW(), NOW())
            RETURNING id, name, email, password_hash, created_at, updated_at
        ');

        $stmt->execute([
            'name' => $user->getName(),
            'email' => $user->getEmail()->getValue(),
            'password_hash' => $user->getPasswordHash()->getHash(),
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $this->mapToEntity($row);
    }

    private function update(User $user): User
    {
        $stmt = $this->pdo->prepare('
            UPDATE users
            SET name = :name,
                email = :email,
                password_hash = :password_hash,
                updated_at = NOW()
            WHERE id = :id
            RETURNING id, name, email, password_hash, created_at, updated_at
        ');

        $stmt->execute([
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail()->getValue(),
            'password_hash' => $user->getPasswordHash()->getHash(),
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $this->mapToEntity($row);
    }

    private function mapToEntity(array $row): User
    {
        return new User(
            (int) $row['id'],
            $row['name'] ?? '',
            new Email($row['email']),
            HashedPassword::fromHash($row['password_hash']),
            new \DateTimeImmutable($row['created_at']),
            new \DateTimeImmutable($row['updated_at'])
        );
    }
}
