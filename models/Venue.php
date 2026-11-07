<?php

class Venue {
    public static function allActive(): array {
        $statement = db()->query(
            'SELECT VenueID, Name, Description, HourlyRate, MaxCapacity
             FROM Venues
             WHERE IsActive = 1
             ORDER BY Name'
        );
        return $statement->fetchAll();
    }
}