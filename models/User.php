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
}
