<?php

class Venue {
    public static function allActive(): array {
        $statement = db()->query(
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

    // all venues for the administrator with the name of their venue manager and their number of photos
    public static function all(): array {
        $statement = db()->query(
            'SELECT v.VenueID, v.Name, v.HourlyRate, v.MaxCapacity, v.IsActive,
                    u.FirstName, u.LastName,
                    (SELECT COUNT(*) FROM VenuePhotos WHERE VenueID = v.VenueID) AS PhotoCount
             FROM Venues AS v
             JOIN Users AS u ON u.UserID = v.ManagerID
             ORDER BY v.Name'
        );
        return $statement->fetchAll();
    }

    // any venue (active or not (for the administrator))
    public static function find(int $venueId): ?array {
        $statement = db()->prepare(
            'SELECT VenueID, ManagerID, Name, Description, HourlyRate, MaxCapacity, IsActive
             FROM Venues
             WHERE VenueID = ?'
        );
        $statement->execute([$venueId]);
        return $statement->fetch() ?: null;
    }

    // a new venue starts inactive because it has no photos yet
    public static function create(int $managerId, string $name, string $description, int $hourlyRate, int $maxCapacity): int {
        $statement = db()->prepare(
            'INSERT INTO Venues (ManagerID, Name, Description, HourlyRate, MaxCapacity, IsActive)
             VALUES (?, ?, ?, ?, ?, 0)'
        );
        $statement->execute([$managerId, $name, $description, $hourlyRate, $maxCapacity]);
        return (int) db()->lastInsertId();
    }

    public static function update(int $venueId, int $managerId, string $name, string $description, int $hourlyRate, int $maxCapacity, int $isActive): void {
        $statement = db()->prepare(
            'UPDATE Venues
             SET ManagerID = ?, Name = ?, Description = ?, HourlyRate = ?, MaxCapacity = ?, IsActive = ?
             WHERE VenueID = ?'
        );
        $statement->execute([$managerId, $name, $description, $hourlyRate, $maxCapacity, $isActive, $venueId]);
    }

    // true if another venue already has this name (the Name column ignores upper and lower case tho)
    public static function nameTaken(string $name, int $venueId): bool {
        $statement = db()->prepare('SELECT COUNT(*) FROM Venues WHERE Name = ? AND VenueID <> ?');
        $statement->execute([$name, $venueId]);
        return $statement->fetchColumn() > 0;
    }

    // the number of bookings that are still pending, approved, or confirmed
    public static function openBookingCount(int $venueId): int {
        $statement = db()->prepare(
            "SELECT COUNT(*)
             FROM Bookings
             WHERE VenueID = ? AND Status IN ('Pending', 'Approved', 'Confirmed')"
        );
        $statement->execute([$venueId]);
        return (int) $statement->fetchColumn();
    }

    // gives a venue to another venue manager (it's used when the administrator makes a manager account)
    public static function setManager(int $venueId, int $managerId): void {
        $statement = db()->prepare('UPDATE Venues SET ManagerID = ? WHERE VenueID = ?');
        $statement->execute([$managerId, $venueId]);
    }

    // the number of active venues that a venue manager runs
    public static function activeCountForManager(int $managerId): int {
        $statement = db()->prepare('SELECT COUNT(*) FROM Venues WHERE ManagerID = ? AND IsActive = 1');
        $statement->execute([$managerId]);
        return (int) $statement->fetchColumn();
    }
}
