<?php

class User
{
    public static function findById(int $id): ?array {
        $statement = db()->prepare('SELECT * FROM Users WHERE UserID = ?');
        $statement->execute([$id]);
        return $statement->fetch() ?: null;
    }

    public static function findByEmail(string $email): ?array {
        $statement = db()->prepare('SELECT * FROM Users WHERE Email = ?');
        $statement->execute([$email]);
        return $statement->fetch() ?: null;
    }

    public static function create(string $firstName, string $lastName, string $email, string $password, string $role = 'Client'): int {
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

    // all venue managers with their ID as the key for the dropdown on the venue form
    public static function managers(): array {
        $statement = db()->query(
            "SELECT UserID, FirstName, LastName, IsActive
             FROM Users
             WHERE Role = 'Manager'
             ORDER BY FirstName, LastName"
        );
        return array_column($statement->fetchAll(), null, 'UserID');
    }

    // all accounts for the administrator (staff first)
    //  $role = '' shows every role
    public static function all(string $role): array
    {
        $statement = db()->prepare(
            "SELECT UserID, FirstName, LastName, Email, Role, IsActive
             FROM Users
             WHERE ? = '' OR Role = ?
             ORDER BY CASE Role WHEN 'Admin' THEN 1 WHEN 'Manager' THEN 2 WHEN 'Server' THEN 3 ELSE 4 END,
                      FirstName, LastName"
        );
        $statement->execute([$role, $role]);
        return $statement->fetchAll();
    }

    // 0 = deactivated (the person is logged out and can't log in anymore)
    // 1 = active
    public static function setActive(int $userId, int $isActive): void
    {
        $statement = db()->prepare('UPDATE Users SET IsActive = ? WHERE UserID = ?');
        $statement->execute([$isActive, $userId]);
    }
}
