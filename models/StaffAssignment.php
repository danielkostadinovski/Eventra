
<?php

class StaffAssignment {
    public static function forBooking(int $bookingId): array {
        $statement = db()->prepare(
            'SELECT sa.AssignmentID, sa.Status, u.FirstName, u.LastName
             FROM StaffAssignments AS sa
             JOIN Users AS u ON u.UserID = sa.ServerID
             WHERE sa.BookingID = ?
             ORDER BY u.FirstName, u.LastName'
        );
        $statement->execute([$bookingId]);
        return $statement->fetchAll();
    }

    // active servers who aren't on this booking and aren't working another event at the same time
    public static function availableServers(int $bookingId): array {
        $statement = db()->prepare(
            "SELECT u.UserID, u.FirstName, u.LastName
             FROM Users AS u
             WHERE u.Role = 'Server' AND u.IsActive = 1
               AND u.UserID NOT IN (SELECT ServerID FROM StaffAssignments WHERE BookingID = ?)
               AND NOT EXISTS (
                   SELECT 1
                   FROM StaffAssignments AS sa
                   JOIN BookingSchedule AS other ON other.BookingID = sa.BookingID
                   JOIN BookingSchedule AS this ON this.BookingID = ?
                   WHERE sa.ServerID = u.UserID
                     AND sa.Status <> 'Declined'
                     AND other.Status <> 'Cancelled'
                     AND other.EventDate = this.EventDate
                     AND other.StartMinute < this.EndMinute
                     AND this.StartMinute < other.EndMinute)
             ORDER BY u.FirstName, u.LastName"
        );
        $statement->execute([$bookingId, $bookingId]);
        return $statement->fetchAll();
    }

    // a new assignment starts as pending until the server accepts or declines it
    public static function assign(int $bookingId, int $serverId): void {
        $statement = db()->prepare('INSERT INTO StaffAssignments (BookingID, ServerID) VALUES (?, ?)');
        $statement->execute([$bookingId, $serverId]);
    }

    // only a pending assignment can be withdrawn (TRG_StaffAssignments_BeforeDelete checks it too)
    public static function withdraw(int $assignmentId, int $bookingId): bool {
        $statement = db()->prepare(
            "DELETE FROM StaffAssignments WHERE AssignmentID = ? AND BookingID = ? AND Status = 'Pending'"
        );
        $statement->execute([$assignmentId, $bookingId]);
        return $statement->rowCount() === 1;
    }
}
