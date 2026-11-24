<?php

// the administrator's company reports (every report is calculated from the database (nothing is stored))
// $from and $to limit the bookings by their event date ('YYYY-MM-DD')
class Report {
    // the numbers on the four cards at the top of the page
    public static function summary(string $from, string $to): array {
        $statement = db()->prepare(
            "SELECT
                 COALESCE(SUM(CASE WHEN p.Status = 'Paid' THEN pr.TotalPrice END), 0) AS Revenue,
                 COUNT(CASE WHEN b.Status <> 'Cancelled' THEN 1 END) AS Bookings,
                 COALESCE(CAST(ROUND(AVG(CASE WHEN b.Status = 'Completed' THEN pr.TotalPrice END)) AS INTEGER), 0) AS AverageBooking
             FROM Bookings AS b
             JOIN BookingPrices AS pr ON pr.BookingID = b.BookingID
             LEFT JOIN Payments AS p ON p.BookingID = b.BookingID
             WHERE b.EventDate BETWEEN ? AND ?"
        );
        $statement->execute([$from, $to]);
        return $statement->fetch();
    }

    // how many bookings have each status, as atatus => count
    public static function bookingsByStatus(string $from, string $to): array {
        $statement = db()->prepare(
            'SELECT Status, COUNT(*) AS Total
             FROM Bookings
             WHERE date(CreatedAt) BETWEEN ? AND ?
             GROUP BY Status'
        );
        $statement->execute([$from, $to]);
        return array_column($statement->fetchAll(), 'Total', 'Status');
    }

    // every venue with its paid revenue, bookings, and its reviews (from the highest revenue down)
    public static function venues(string $from, string $to): array {
        $statement = db()->prepare(
            "SELECT v.Name,
                    COALESCE(SUM(CASE WHEN p.Status = 'Paid' THEN pr.TotalPrice END), 0) AS Revenue,
                    COUNT(CASE WHEN b.Status <> 'Cancelled' THEN 1 END) AS Bookings,
                    AVG(r.Rating) AS AverageRating,
                    COUNT(r.BookingID) AS Reviews
             FROM Venues AS v
             LEFT JOIN Bookings AS b ON b.VenueID = v.VenueID AND b.EventDate BETWEEN ? AND ?
             LEFT JOIN BookingPrices AS pr ON pr.BookingID = b.BookingID
             LEFT JOIN Payments AS p ON p.BookingID = b.BookingID
             LEFT JOIN Reviews AS r ON r.BookingID = b.BookingID
             GROUP BY v.VenueID
             ORDER BY Revenue DESC, v.Name"
        );
        $statement->execute([$from, $to]);
        return $statement->fetchAll();
    }

    // the paid revenue of each of the last 12 months as 'YYYY-MM' => cents (months that have no revenue are 0)
    public static function revenuePerMonth(): array {
        $statement = db()->query(
            "SELECT strftime('%Y-%m', b.EventDate) AS Month, SUM(pr.TotalPrice) AS Total
             FROM Bookings AS b
             JOIN Payments AS p ON p.BookingID = b.BookingID
             JOIN BookingPrices AS pr ON pr.BookingID = b.BookingID
             WHERE p.Status = 'Paid'
               AND b.EventDate >= date('now', 'localtime', 'start of month', '-11 months')
               AND b.EventDate <= date('now', 'localtime')
             GROUP BY Month"
        );
        $totals = array_column($statement->fetchAll(), 'Total', 'Month');

        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("first day of -$i month"));
            $months[$month] = $totals[$month] ?? 0;
        }
        return $months;
    }

    // unpaid bookings more than 14 days after their event, the oldest first
    public static function overduePayments(): array {
        $statement = db()->query(
            "SELECT b.BookingID, b.EventDate, v.Name AS VenueName, u.FirstName, u.LastName, u.Email, pr.TotalPrice,
                    CAST(julianday(date('now', 'localtime')) - julianday(b.EventDate) AS INTEGER) - 14 AS DaysOverdue
             FROM Bookings AS b
             JOIN Payments AS p ON p.BookingID = b.BookingID
             JOIN Venues AS v ON v.VenueID = b.VenueID
             JOIN Users AS u ON u.UserID = b.ClientID
             JOIN BookingPrices AS pr ON pr.BookingID = b.BookingID
             WHERE b.Status IN ('Confirmed', 'Completed')
               AND p.Status = 'Unpaid'
               AND b.EventDate < date('now', 'localtime', '-14 days')
             ORDER BY b.EventDate"
        );
        return $statement->fetchAll();
    }
}
