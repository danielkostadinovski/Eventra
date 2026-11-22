<?php

class Equipment {
    // returns the active equipment items with each item's ID as its key
    // so an item can be found quickly for example $catalog[3]['UnitPrice']
    public static function allActive(): array {
        $statement = db()->query(
            'SELECT EquipmentID, Name, UnitPrice
             FROM Equipment
             WHERE IsActive = 1
             ORDER BY UnitPrice'
        );
        return array_column($statement->fetchAll(), null, 'EquipmentID');
    }

    // the whole catalog for the administrator (the offered items first then the retired ones)
    public static function all(): array {
        $statement = db()->query(
            'SELECT EquipmentID, Name, UnitPrice, IsActive
             FROM Equipment
             ORDER BY IsActive DESC, Name'
        );
        return $statement->fetchAll();
    }

    public static function find(int $equipmentId): ?array {
        $statement = db()->prepare('SELECT EquipmentID, Name, UnitPrice, IsActive FROM Equipment WHERE EquipmentID = ?');
        $statement->execute([$equipmentId]);
        return $statement->fetch() ?: null;
    }

    public static function add(string $name, int $unitPrice): void {
        $statement = db()->prepare('INSERT INTO Equipment (Name, UnitPrice) VALUES (?, ?)');
        $statement->execute([$name, $unitPrice]);
    }

    // bookings that already have the item keep the price they were made with (BookingEquipment.UnitPrice)
    public static function update(int $equipmentId, string $name, int $unitPrice): void {
        $statement = db()->prepare('UPDATE Equipment SET Name = ?, UnitPrice = ? WHERE EquipmentID = ?');
        $statement->execute([$name, $unitPrice, $equipmentId]);
    }

    // 0 = retired (clients can't choose it anymore) 
    // 1 = offered
    public static function setActive(int $equipmentId, int $isActive): void {
        $statement = db()->prepare('UPDATE Equipment SET IsActive = ? WHERE EquipmentID = ?');
        $statement->execute([$isActive, $equipmentId]);
    }

    // true if another item already has this name (the Name column ignores upper and lower case)
    public static function nameTaken(string $name, int $equipmentId): bool {
        $statement = db()->prepare('SELECT COUNT(*) FROM Equipment WHERE Name = ? AND EquipmentID <> ?');
        $statement->execute([$name, $equipmentId]);
        return $statement->fetchColumn() > 0;
    }
}
