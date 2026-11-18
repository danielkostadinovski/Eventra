<?php

class Booking {
    public static function currentRates(): array {
        $statement = db()->query(
            'SELECT HourlyStaffRate, FoodRatePerPerson
             FROM PricingSettings
             WHERE SettingID = 1'
        );
        return $statement->fetch();
    }

    //payment is overdue if a confirmed or completed booking is still unpaid
    // more than 14 days after the event date
    public static function hasOverduePayment(int $clientId): bool {
        $statement = db()->prepare(
            "SELECT 1
             FROM Bookings AS b
             JOIN Payments AS p ON p.BookingID = b.BookingID
             WHERE b.ClientID = ?
               AND b.Status IN ('Confirmed', 'Completed')
               AND p.Status = 'Unpaid'
               AND b.EventDate < date('now', 'localtime', '-14 days')
             LIMIT 1"
        );
        $statement->execute([$clientId]);
        return $statement->fetch() !== false;
    }

    // saves a new booking (as pending) and its equipment, with the rates in force right now
    public static function create(int $clientId, array $venue, array $form, array $rates, array $catalog): int {
        $statement = db()->prepare(
            'INSERT INTO Bookings (VenueID, ClientID, EventDate, StartTime, HoursBooked, GuestCount,
                                   StaffCount, WantsFood, VenueHourlyRate, HourlyStaffRate, FoodRatePerPerson)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([
            $venue['VenueID'], $clientId, $form['event_date'], $form['start_time'], $form['hours'],
            $form['guests'], $form['staff_count'], $form['wants_food'],
            $venue['HourlyRate'], $rates['HourlyStaffRate'], $rates['FoodRatePerPerson'],
        ]);
        $bookingId = (int) db()->lastInsertId();

        $addEquipment = db()->prepare(
            'INSERT INTO BookingEquipment (BookingID, EquipmentID, Quantity, UnitPrice)
             VALUES (?, ?, ?, ?)'
        );
        foreach ($form['equipment'] as $equipmentId => $quantity) {
            if ($quantity > 0) {
                $addEquipment->execute([$bookingId, $equipmentId, $quantity, $catalog[$equipmentId]['UnitPrice']]);
            }
        }

        return $bookingId;
    }

    public static function findForClient(int $bookingId, int $clientId): ?array {
        $statement = db()->prepare(
            'SELECT b.BookingID, b.VenueID, b.EventDate, b.StartTime, s.EndTime, b.HoursBooked,
                    b.GuestCount, b.StaffCount, b.WantsFood, b.VenueHourlyRate, b.HourlyStaffRate,
                    b.FoodRatePerPerson, b.Status, b.CreatedAt, v.Name AS VenueName, p.Status AS PaymentStatus,
                    p.PaidAt, r.Rating AS ReviewRating, r.Comment AS ReviewComment
             FROM Bookings AS b
             JOIN Venues AS v ON v.VenueID = b.VenueID
             JOIN BookingSchedule AS s ON s.BookingID = b.BookingID
             LEFT JOIN Payments AS p ON p.BookingID = b.BookingID
             LEFT JOIN Reviews AS r ON r.BookingID = b.BookingID
             WHERE b.BookingID = ? AND b.ClientID = ?'
        );
        $statement->execute([$bookingId, $clientId]);
        return $statement->fetch() ?: null;
    }

    public static function equipment(int $bookingId): array {
        $statement = db()->prepare(
            'SELECT e.Name, be.Quantity, be.UnitPrice
             FROM BookingEquipment AS be
             JOIN Equipment AS e ON e.EquipmentID = be.EquipmentID
             WHERE be.BookingID = ?
             ORDER BY e.Name'
        );
        $statement->execute([$bookingId]);
        return $statement->fetchAll();
    }

    // price formula (venue + waiting staff + catering + equipment)
    // it's used both on the review page (before saving) and on the booking page (after saving) so the client always sees the same price
    public static function calculatePrice(array $booking, array $equipment): array {
        $lines = [[
            'label' => 'Venue, ' . plural($booking['HoursBooked'], 'hour') . ' at ' . money($booking['VenueHourlyRate']) . ' per hour',
            'amount' => $booking['HoursBooked'] * $booking['VenueHourlyRate'],
        ]];

        if ($booking['StaffCount'] > 0) {
            $lines[] = [
                'label' => 'Waiting staff, ' . plural($booking['StaffCount'], 'server') . ' for ' . plural($booking['HoursBooked'], 'hour')
                    . ' at ' . money($booking['HourlyStaffRate']) . ' per hour',
                'amount' => $booking['StaffCount'] * $booking['HoursBooked'] * $booking['HourlyStaffRate'],
            ];
        }

        if ($booking['WantsFood']) {
            $lines[] = [
                'label' => 'Catering, ' . plural($booking['GuestCount'], 'guest') . ' at ' . money($booking['FoodRatePerPerson']) . ' per guest',
                'amount' => $booking['GuestCount'] * $booking['FoodRatePerPerson'],
            ];
        }

        foreach ($equipment as $item) {
            $lines[] = [
                'label' => $item['Name'] . ', ' . $item['Quantity'] . ' at ' . money($item['UnitPrice']) . ' each',
                'amount' => $item['Quantity'] * $item['UnitPrice'],
            ];
        }

        return [
            'lines' => $lines,
            'total' => array_sum(array_column($lines, 'amount')),
        ];
    }

    // all of the the client's bookings for the My Bookings page with the total from the BookingPrices view
    public static function allForClient(int $clientId): array {
        $statement = db()->prepare(
            'SELECT b.BookingID, b.EventDate, b.StartTime, b.Status, v.Name AS VenueName,
                    pr.TotalPrice, p.Status AS PaymentStatus, r.BookingID IS NOT NULL AS HasReview,
                    (SELECT FilePath FROM VenuePhotos
                     WHERE VenueID = b.VenueID
                     ORDER BY PhotoID
                     LIMIT 1) AS CoverPhoto
             FROM Bookings AS b
             JOIN Venues AS v ON v.VenueID = b.VenueID
             JOIN BookingPrices AS pr ON pr.BookingID = b.BookingID
             LEFT JOIN Payments AS p ON p.BookingID = b.BookingID
             LEFT JOIN Reviews AS r ON r.BookingID = b.BookingID
             WHERE b.ClientID = ?
             ORDER BY b.EventDate'
        );
        $statement->execute([$clientId]);
        return $statement->fetchAll();
    }

    // a client can cancel only a pending or approved booking ( returns false if nothing was cancelled)
    public static function cancel(int $bookingId): bool {
        $statement = db()->prepare(
            "UPDATE Bookings SET Status = 'Cancelled'
             WHERE BookingID = ? AND Status IN ('Pending', 'Approved')"
        );
        $statement->execute([$bookingId]);
        return $statement->rowCount() === 1;
    }

    // simulated Pay Now (an unpaid payment of a confirmed or completed booking becomes paid)
    public static function pay(int $bookingId): bool {
        $statement = db()->prepare(
            "UPDATE Payments SET Status = 'Paid', PaidAt = datetime('now', 'localtime')
             WHERE BookingID = ? AND Status = 'Unpaid'
               AND BookingID IN (SELECT BookingID FROM Bookings WHERE Status IN ('Confirmed', 'Completed'))"
        );
        $statement->execute([$bookingId]);
        return $statement->rowCount() === 1;
    }

    public static function addReview(int $bookingId, int $rating, string $comment): void {
        $statement = db()->prepare('INSERT INTO Reviews (BookingID, Rating, Comment) VALUES (?, ?, ?)');
        $statement->execute([$bookingId, $rating, $comment]);
    }

    // pending or approved booking whose event date has arrived without being confirmed
    // is cancelled automatically - it runs in the start of every request 
    public static function cancelExpired(): void {
        db()->exec(
            "UPDATE Bookings SET Status = 'Cancelled'
             WHERE Status IN ('Pending', 'Approved')
               AND EventDate <= date('now', 'localtime')"
        );
    }

    // all bookings of the venues that a venue manager is assigned to, for the manager's dashboard
    public static function allForManager(int $managerId): array {
        $statement = db()->prepare(
            "SELECT b.BookingID, b.EventDate, b.StartTime, b.Status, b.GuestCount, b.StaffCount,
                    v.Name AS VenueName, u.FirstName || ' ' || u.LastName AS ClientName,
                    pr.TotalPrice, p.Status AS PaymentStatus,
                    (SELECT COUNT(*) FROM StaffAssignments AS sa
                     WHERE sa.BookingID = b.BookingID AND sa.Status = 'Accepted') AS AcceptedServers
             FROM Bookings AS b
             JOIN Venues AS v ON v.VenueID = b.VenueID
             JOIN Users AS u ON u.UserID = b.ClientID
             JOIN BookingPrices AS pr ON pr.BookingID = b.BookingID
             LEFT JOIN Payments AS p ON p.BookingID = b.BookingID
             WHERE v.ManagerID = ?
             ORDER BY b.EventDate"
        );
        $statement->execute([$managerId]);
        return $statement->fetchAll();
    }

    // one booking, but only if it belongs to one of the manager's venues
    public static function findForManager(int $bookingId, int $managerId): ?array {
        $statement = db()->prepare(
            'SELECT b.BookingID, b.VenueID, b.EventDate, b.StartTime, s.EndTime, b.HoursBooked,
                    b.GuestCount, b.StaffCount, b.WantsFood, b.VenueHourlyRate, b.HourlyStaffRate,
                    b.FoodRatePerPerson, b.Status, b.ConsultationNotes, b.AttachmentPath, b.CreatedAt,
                    v.Name AS VenueName, u.FirstName, u.LastName, u.Email,
                    p.Status AS PaymentStatus, p.PaidAt
             FROM Bookings AS b
             JOIN Venues AS v ON v.VenueID = b.VenueID
             JOIN Users AS u ON u.UserID = b.ClientID
             JOIN BookingSchedule AS s ON s.BookingID = b.BookingID
             LEFT JOIN Payments AS p ON p.BookingID = b.BookingID
             WHERE b.BookingID = ? AND v.ManagerID = ?'
        );
        $statement->execute([$bookingId, $managerId]);
        return $statement->fetch() ?: null;
    }

    // changes the status only if the booking still has the expected status - returns false if nothing changed
    public static function changeStatus(int $bookingId, string $from, string $to): bool {
        $statement = db()->prepare('UPDATE Bookings SET Status = ? WHERE BookingID = ? AND Status = ?');
        $statement->execute([$to, $bookingId, $from]);
        return $statement->rowCount() === 1;
    }

    public static function saveNotes(int $bookingId, ?string $notes, ?string $attachmentPath): void {
        $statement = db()->prepare('UPDATE Bookings SET ConsultationNotes = ?, AttachmentPath = ? WHERE BookingID = ?');
        $statement->execute([$notes, $attachmentPath, $bookingId]);
    }
}
