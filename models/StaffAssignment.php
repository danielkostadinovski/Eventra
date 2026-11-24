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

    // every assignment of one server with the event's details and what the event pays
    public static function forServer(int $serverId): array {
        $statement = db()->prepare(
            'SELECT sa.AssignmentID, sa.Status, b.BookingID, b.EventDate, b.StartTime, s.EndTime,
                    b.HoursBooked, b.GuestCount, b.HourlyStaffRate, b.Status AS BookingStatus,
                    b.HoursBooked * b.HourlyStaffRate AS Earnings, v.Name AS VenueName
             FROM StaffAssignments AS sa
             JOIN Bookings AS b ON b.BookingID = sa.BookingID
             JOIN BookingSchedule AS s ON s.BookingID = b.BookingID
             JOIN Venues AS v ON v.VenueID = b.VenueID
             WHERE sa.ServerID = ?
             ORDER BY b.EventDate, b.StartTime'
        );
        $statement->execute([$serverId]);
        return $statement->fetchAll();
    }

    // the server's total earnings and number of events (counting only completed events)
    public static function totalEarnings(int $serverId): array {
        $statement = db()->prepare(
            "SELECT COALESCE(SUM(Earnings), 0) AS Total, COUNT(*) AS Events
             FROM ServerEarnings
             WHERE ServerID = ? AND BookingStatus = 'Completed'"
        );
        $statement->execute([$serverId]);
        return $statement->fetch();
    }

    // the server accepts or declines one of their own pending assignments (returns false if nothing changed) 
    // the triggers check that the booking is still confirmed
    public static function respond(int $assignmentId, int $serverId, string $status): bool {
        $statement = db()->prepare(
            "UPDATE StaffAssignments
             SET Status = ?
             WHERE AssignmentID = ? AND ServerID = ? AND Status = 'Pending'"
        );
        $statement->execute([$status, $assignmentId, $serverId]);
        return $statement->rowCount() === 1;
    }

    // the amount of events that a server still has to work 
    // the assignments that aren't declined on upcoming confirmed bookings
    public static function upcomingCount(int $serverId): int {
        $statement = db()->prepare(
            "SELECT COUNT(*)
             FROM StaffAssignments AS sa
             JOIN Bookings AS b ON b.BookingID = sa.BookingID
             WHERE sa.ServerID = ?
               AND sa.Status <> 'Declined'
               AND b.Status = 'Confirmed'
               AND b.EventDate >= date('now', 'localtime')"
        );
        $statement->execute([$serverId]);
        return (int) $statement->fetchColumn();
    }
}