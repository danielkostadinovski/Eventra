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

    public static function isDateFree(int $venueId, string $date): bool {
        return !in_array($date, self::unavailableDates($venueId), true);
    }

    public static function forManager(int $managerId): array {
        $statement = db()->prepare(
            'SELECT VenueID, Name, HourlyRate, MaxCapacity, IsActive
             FROM Venues
             WHERE ManagerID = ?
             ORDER BY Name'
        );
        $statement->execute([$managerId]);
        return $statement->fetchAll();
    }

    // the venue (but only if this manager manages it (active or not))
    public static function findForManager(int $venueId, int $managerId): ?array {
        $statement = db()->prepare(
            'SELECT VenueID, Name, Description, HourlyRate, MaxCapacity, IsActive
             FROM Venues
             WHERE VenueID = ? AND ManagerID = ?'
        );
        $statement->execute([$venueId, $managerId]);
        return $statement->fetch() ?: null;
    }

    public static function updateDetails(int $venueId, string $description, int $hourlyRate, int $maxCapacity): void {
        $statement = db()->prepare(
            'UPDATE Venues
             SET Description = ?, HourlyRate = ?, MaxCapacity = ?
             WHERE VenueID = ?'
        );
        $statement->execute([$description, $hourlyRate, $maxCapacity, $venueId]);
    }

    // the largest guest count of the venue's open bookings (0 if there's none)
    // because the capacity can't be lowered below it
    public static function largestOpenGuestCount(int $venueId): int {
        $statement = db()->prepare(
            "SELECT COALESCE(MAX(GuestCount), 0)
             FROM Bookings
             WHERE VenueID = ? AND Status IN ('Pending', 'Approved', 'Confirmed')"
        );
        $statement->execute([$venueId]);
        return (int) $statement->fetchColumn();
    }

    // the photos with their IDs so that the manager can remove one
    public static function photoList(int $venueId): array {
        $statement = db()->prepare(
            'SELECT PhotoID, FilePath
             FROM VenuePhotos
             WHERE VenueID = ?
             ORDER BY PhotoID'
        );
        $statement->execute([$venueId]);
        return $statement->fetchAll();
    }

    public static function findPhoto(int $photoId, int $venueId): ?array {
        $statement = db()->prepare('SELECT PhotoID, FilePath FROM VenuePhotos WHERE PhotoID = ? AND VenueID = ?');
        $statement->execute([$photoId, $venueId]);
        return $statement->fetch() ?: null;
    }

    public static function addPhoto(int $venueId, string $filePath): void {
        $statement = db()->prepare('INSERT INTO VenuePhotos (VenueID, FilePath) VALUES (?, ?)');
        $statement->execute([$venueId, $filePath]);
    }

    public static function deletePhoto(int $photoId): void {
        $statement = db()->prepare('DELETE FROM VenuePhotos WHERE PhotoID = ?');
        $statement->execute([$photoId]);
    }

    // blackout dates from today on (the earliest first)
    public static function upcomingBlackouts(int $venueId, string $today): array {
        $statement = db()->prepare(
            'SELECT BlackoutID, BlackoutDate, Reason
             FROM BlackoutDates
             WHERE VenueID = ? AND BlackoutDate >= ?
             ORDER BY BlackoutDate'
        );
        $statement->execute([$venueId, $today]);
        return $statement->fetchAll();
    }

    public static function addBlackout(int $venueId, string $date, string $reason): void {
        $statement = db()->prepare('INSERT INTO BlackoutDates (VenueID, BlackoutDate, Reason) VALUES (?, ?, ?)');
        $statement->execute([$venueId, $date, $reason]);
    }

    // only future blackout dates can be removed (returns false if nothing was removed)
    public static function deleteBlackout(int $blackoutId, int $venueId, string $today): bool {
        $statement = db()->prepare(
            'DELETE FROM BlackoutDates
             WHERE BlackoutID = ? AND VenueID = ? AND BlackoutDate > ?'
        );
        $statement->execute([$blackoutId, $venueId, $today]);
        return $statement->rowCount() === 1;
    }
}
