
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
                    b.FoodRatePerPerson, b.Status, b.CreatedAt, v.Name AS VenueName, p.Status AS PaymentStatus
             FROM Bookings AS b
             JOIN Venues AS v ON v.VenueID = b.VenueID
             JOIN BookingSchedule AS s ON s.BookingID = b.BookingID
             LEFT JOIN Payments AS p ON p.BookingID = b.BookingID
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
}
