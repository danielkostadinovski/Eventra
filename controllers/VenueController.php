
<?php

class VenueController {
    public static function index(): void {
        requireRole('Client');
        view('venues/index', [
            'title' => 'Venues - Eventra',
            'mainClass' => '',
            'venues' => Venue::allActive(),
        ]);
    }

    public static function show(): void {
        requireRole('Client');

        $venue = Venue::findActive((int) ($_GET['id'] ?? 0));

        if ($venue === null) {
            http_response_code(404);
            view('error', [
                'heading' => 'Venue not found',
                'message' => 'This venue does not exist or is no longer available.',
            ]);
            return;
        }

        // the calendar shows two months- the current one and the next one
        // Clients can look up to 12 months ahead
        $thisMonth = date('Y-m');
        $lastMonth = date('Y-m', strtotime('first day of +11 months'));
        $month = $_GET['month'] ?? $thisMonth;

        if (!preg_match('/^\d{4}-\d{2}$/', $month) || strtotime($month . '-01') === false || $month < $thisMonth) {
            $month = $thisMonth;
        }
        if ($month > $lastMonth) {
            $month = $lastMonth;
        }

        $nextMonth = date('Y-m', strtotime($month . '-01 +1 month'));
        $unavailable = array_flip(Venue::unavailableDates($venue['VenueID']));

        $reviews = Venue::reviews($venue['VenueID']);
        $averageRating = $reviews ? array_sum(array_column($reviews, 'Rating')) / count($reviews) : null;

        view('venues/show', [
            'title' => $venue['Name'] . ' – Eventra',
            'venue' => $venue,
            'photos' => Venue::photos($venue['VenueID']),
            'reviews' => $reviews,
            'averageRating' => $averageRating,
            'months' => [
                self::buildMonth($month, $unavailable),
                self::buildMonth($nextMonth, $unavailable),
            ],
            'previousMonth' => $month > $thisMonth ? date('Y-m', strtotime($month . '-01 -1 month')) : null,
            'followingMonth' => $month < $lastMonth ? $nextMonth : null,
        ]);
    }

    // builds one month of the calendar: the title, the empty cells before day 1 and every day
    // with a flag that says if it can be booked (if it's after today, not booked and not blacked out)
    private static function buildMonth(string $month, array $unavailable): array {
        $firstDay = strtotime($month . '-01');
        $today = date('Y-m-d');
        $days = [];

        for ($day = 1; $day <= (int) date('t', $firstDay); $day++) {
            $date = $month . '-' . sprintf('%02d', $day);
            $days[] = [
                'number' => $day,
                'date' => $date,
                'available' => $date > $today && !isset($unavailable[$date]),
            ];
        }

        return [
            'title' => date('F Y', $firstDay),
            'emptyCells' => (int) date('N', $firstDay) - 1,
            'days' => $days,
        ];
    }
}
