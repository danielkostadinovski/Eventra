
<?php

class Venue {
    public static function allActive(): array {
        $statement = db()->query (
            'SELECT v.VenueID, v.Name, v.HourlyRate, v.MaxCapacity,
                    (SELECT FilePath FROM VenuePhotos
                     WHERE VenueID = v.VenueID
                     ORDER BY PhotoID
                     LIMIT 1) AS CoverPhoto
             FROM Venues AS v
             WHERE v.IsActive = 1
             ORDER BY v.Name'
        );
        return $statement->fetchAll();
    }

    public static function findActive(int $venueId): ?array {
        $statement = db()->prepare(
            'SELECT VenueID, Name, Description, HourlyRate, MaxCapacity
             FROM Venues
             WHERE VenueID = ? AND IsActive = 1'
        );
        $statement->execute([$venueId]);
        return $statement->fetch() ?: null;
    }

    public static function photos(int $venueId): array {
        $statement = db()->prepare(
            'SELECT FilePath
             FROM VenuePhotos
             WHERE VenueID = ?
             ORDER BY PhotoID'
        );
        $statement->execute([$venueId]);
        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function reviews(int $venueId): array {
        $statement = db()->prepare(
            'SELECT r.Rating, r.Comment, b.EventDate, u.FirstName
             FROM Reviews AS r
             JOIN Bookings AS b ON b.BookingID = r.BookingID
             JOIN Users AS u ON u.UserID = b.ClientID
             WHERE b.VenueID = ?
             ORDER BY b.EventDate DESC'
        );
        $statement->execute([$venueId]);
        return $statement->fetchAll();
    }

    public static function unavailableDates(int $venueId): array {
        $statement = db()->prepare(
            "SELECT EventDate FROM Bookings
             WHERE VenueID = ? AND Status <> 'Cancelled'
             UNION
             SELECT BlackoutDate FROM BlackoutDates
             WHERE VenueID = ?"
        );
        $statement->execute([$venueId, $venueId]);
        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }
}
