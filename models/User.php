<?php

class User
{
    public static function findById(int $id): ?array
    {
        $statement = db()->prepare('SELECT * FROM Users WHERE UserID = ?');
        $statement->execute([$id]);
        return $statement->fetch() ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $statement = db()->prepare('SELECT * FROM Users WHERE Email = ?');
        $statement->execute([$email]);
        return $statement->fetch() ?: null;
    }

        public static function create(string $firstName, string $lastName, string $email, string $password, string $role = 'Client'): int
    {
        $statement = db()->prepare(
            'INSERT INTO Users (FirstName, LastName, Email, PasswordHash, Role)
             VALUES (?, ?, ?, ?, ?)'
        );
        $statement->execute([
            $firstName,
            $lastName,
            $email,
            password_hash($password, PASSWORD_BCRYPT),
            $role,
        ]);
        return (int) db()->lastInsertId();
    }
}
