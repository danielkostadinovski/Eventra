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

    // $form and $error are given only when the booking form is shown again
    // so after an error or after "go back and change" so that the client answers are kept
    public static function show(array $form = [], ?string $error = null): void {
        requireRole('Client');

        $venue = Venue::findActive((int) ($form['venue_id'] ?? $_GET['id'] ?? 0));

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
        $month = $_GET['month'] ?? substr($form['event_date'] ?? '', 0, 7);

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
            'title' => $venue['Name'] . ' - Eventra',
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
            'equipment' => Equipment::allActive(),
            'rates' => Booking::currentRates(),
            'startTimes' => BookingController::START_TIMES,
            'form' => $form,
            'error' => $error,
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
