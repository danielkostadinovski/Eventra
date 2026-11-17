
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
}
