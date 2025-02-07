<?php

declare(strict_types=1);

namespace App\Service;

use App\Database\DatabaseConnection;
use App\Validation\Validator;
use InvalidArgumentException;
use PDO;
use Exception;
use PDOException;
use RuntimeException;

class UserManager
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = DatabaseConnection::getInstance();
    }

    public function addUser(string $name, string $email, array $groupIds = []): int
    {
        try {
            $validatedName = Validator::validateUserName($name);
            $validatedEmail = Validator::validateEmail($email);
        } catch (InvalidArgumentException $e) {
            throw new RuntimeException($e->getMessage());
        }

        $this->checkEmailUnique($validatedEmail);

        $now = date('Y-m-d H:i:s');

        $stmt = $this->pdo->prepare("
            INSERT INTO users (name, email, created_at, updated_at)
            VALUES (:name, :email, :created_at, :updated_at)
        ");
        $stmt->execute([
            ':name' => $validatedName,
            ':email' => $validatedEmail,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $userId = (int)$this->pdo->lastInsertId();

        if (!empty($groupIds)) {
            $this->assignGroupsToUser($userId, $groupIds);
        }

        return $userId;
    }

    public function getUsers(): array
    {
        return $this->pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateUser(int $id, string $name = null, string $email = null, array $groupIds = null): bool
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            throw new RuntimeException("Пользователь с ID $id не найден");
        }

        $fields = [];
        $params = [':id' => $id];

        if ($name !== null) {
            try {
                $validatedName = Validator::validateUserName($name);
                $params[':name'] = $validatedName;
                $fields[] = "name = :name";
            } catch (InvalidArgumentException $e) {
                throw new RuntimeException($e->getMessage());
            }
        }

        if ($email !== null) {
            try {
                $validatedEmail = Validator::validateEmail($email);
                if ($validatedEmail !== $user['email']) {
                    $this->checkEmailUnique($validatedEmail, $id);
                }
                $params[':email'] = $validatedEmail;
                $fields[] = "email = :email";
            } catch (InvalidArgumentException $e) {
                throw new RuntimeException($e->getMessage());
            }
        }

        if (empty($fields)) {
            throw new RuntimeException('Нет данных для обновления');
        }

        $fields[] = "updated_at = :updated_at";
        $params[':updated_at'] = date('Y-m-d H:i:s');

        $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute($params);

        if (is_array($groupIds)) {
            $this->assignGroupsToUser($id, $groupIds);
        }

        return $result;
    }

    public function deleteUser(int $id): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => $id]);

            if ($stmt->rowCount() === 0) {
                throw new RuntimeException("Пользователя с ID $id не существует");
            }

            return true;
        } catch (PDOException $e) {
            throw new RuntimeException("Ошибка при удалении пользователя: " . $e->getMessage());
        }
    }

    private function assignGroupsToUser(int $userId, array $groupIds): void
    {
        $this->pdo->beginTransaction();

        try {
            foreach ($groupIds as $groupId) {
                $groupId = (int)$groupId;
                if ($groupId <= 0) {
                    continue;
                }

                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_groups WHERE user_id = :user_id AND group_id = :group_id");
                $stmt->execute([
                    ':user_id'  => $userId,
                    ':group_id' => $groupId
                ]);
                if ($stmt->fetchColumn() > 0) {
                    throw new RuntimeException("Пользователь с ID $userId уже входит в группу с ID $groupId");
                }
            }

            $stmt = $this->pdo->prepare("INSERT INTO user_groups (user_id, group_id) VALUES (:user_id, :group_id)");
            foreach ($groupIds as $groupId) {
                $groupId = (int)$groupId;
                if ($groupId > 0) {
                    $stmt->execute([
                        ':user_id'  => $userId,
                        ':group_id' => $groupId
                    ]);
                }
            }
            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new RuntimeException("Ошибка при добавлении в группу: " . $e->getMessage());
        }
    }

    public function addGroup(string $name): int
    {
        try {
            $validatedName = Validator::validateGroupName($name);
        } catch (InvalidArgumentException $e) {
            throw new RuntimeException($e->getMessage());
        }

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `groups` WHERE name = :name");
        $stmt->execute([':name' => $validatedName]);
        if ($stmt->fetchColumn() > 0) {
            throw new RuntimeException("Группа с именем '$validatedName' уже существует");
        }

        $stmt = $this->pdo->prepare("INSERT INTO `groups` (name) VALUES (:name)");
        $stmt->execute([':name' => $validatedName]);
        $lastId = $this->pdo->lastInsertId();

        if ($lastId === false) {
            throw new RuntimeException('Не удалось получить ID при создании группы');
        }

        return (int)$lastId;
    }

    public function getUserGroups(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT g.*
            FROM `groups` g
            INNER JOIN user_groups ug ON g.id = ug.group_id
            WHERE ug.user_id = :user_id
        ");
        $stmt->execute([':user_id' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function checkEmailUnique(string $email, ?int $excludeUserId = null): void
    {
        $sql = "SELECT COUNT(*) FROM users WHERE email = :email";
        $params = [':email' => $email];

        if ($excludeUserId !== null) {
            $sql .= " AND id != :id";
            $params[':id'] = $excludeUserId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        if ($stmt->fetchColumn() > 0) {
            throw new RuntimeException('Пользователь с таким email уже существует');
        }
    }
}
