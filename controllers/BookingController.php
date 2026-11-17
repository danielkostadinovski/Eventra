
<?php

class BookingController {
    const START_TIMES = [
        '08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00',
        '16:00', '17:00', '18:00', '19:00', '20:00', '21:00', '22:00', '23:00',
    ];

    // step 1 - they send the form from the venue page and see the price before they submit
    public static function review(): void {
        $user = requireRole('Client');
        $form = self::readForm();
        $venue = Venue::findActive($form['venue_id']);

        if ($venue === null) {
            self::notFound('Venue not found');
            return;
        }

        $catalog = Equipment::allActive();
        $error = self::validate($user, $venue, $form, $catalog);

        if ($error !== null) {
            VenueController::show($form, $error);
            return;
        }

        $rates = Booking::currentRates();
        $booking = [
            'HoursBooked' => $form['hours'],
            'GuestCount' => $form['guests'],
            'StaffCount' => $form['staff_count'],
            'WantsFood' => $form['wants_food'],
            'VenueHourlyRate' => $venue['HourlyRate'],
            'HourlyStaffRate' => $rates['HourlyStaffRate'],
            'FoodRatePerPerson' => $rates['FoodRatePerPerson'],
        ];

        $equipment = [];
        foreach ($form['equipment'] as $equipmentId => $quantity) {
            if ($quantity > 0) {
                $equipment[] = [
                    'Name' => $catalog[$equipmentId]['Name'],
                    'Quantity' => $quantity,
                    'UnitPrice' => $catalog[$equipmentId]['UnitPrice'],
                ];
            }
        }

        view('bookings/review', [
            'title' => 'Review your booking - Eventra',
            'venue' => $venue,
            'form' => $form,
            'equipment' => $equipment,
            'price' => Booking::calculatePrice($booking, $equipment),
        ]);
    }

    // go back and change - shows the venue page again with the form filled in
    public static function change(): void {
        requireRole('Client');
        VenueController::show(self::readForm());
    }

    //step 2 - submitting the booking and it's saved in one transaction
    public static function submit(): void {
        $user = requireRole('Client');
        $form = self::readForm();
        $venue = Venue::findActive($form['venue_id']);

        if ($venue === null) {
            self::notFound('Venue not found');
            return;
        }

        // BEGIN IMMEDIATE locks the database for writing right away so if two clients submit at the
        // same time the second one waits (up to 5 seconds) until the first booking gets saved
        // and then its own check below sees that the date is already taken
        $db = db();
        $db->exec('BEGIN IMMEDIATE');

        try {
            $catalog = Equipment::allActive();
            $error = self::validate($user, $venue, $form, $catalog);

            if ($error === null) {
                $bookingId = Booking::create($user['UserID'], $venue, $form, Booking::currentRates(), $catalog);
                $db->exec('COMMIT');
                setFlash('Your booking request was sent to the venue manager.');
                redirect('booking&id=' . $bookingId);
            }

            $db->exec('ROLLBACK');
        } catch (PDOException $e) {
            $db->exec('ROLLBACK');
            $error = 'Your booking could not be saved, please try again.';
        }

        VenueController::show($form, $error);
    }

    // the booking details page
    public static function show(): void {
        $user = requireRole('Client');
        $booking = Booking::findForClient((int) ($_GET['id'] ?? 0), $user['UserID']);

        if ($booking === null) {
            self::notFound('Booking not found');
            return;
        }

        $equipment = Booking::equipment($booking['BookingID']);

        view('bookings/show', [
            'title' => 'Your booking - Eventra',
            'booking' => $booking,
            'equipment' => $equipment,
            'price' => Booking::calculatePrice($booking, $equipment),
        ]);
    }

    private static function readForm(): array {
        return [
            'venue_id' => (int) ($_POST['venue_id'] ?? 0),
            'event_date' => trim($_POST['event_date'] ?? ''),
            'start_time' => $_POST['start_time'] ?? '',
            'hours' => (int) ($_POST['hours'] ?? 0),
            'guests' => (int) ($_POST['guests'] ?? 0),
            'staff_count' => (int) ($_POST['staff_count'] ?? 0),
            'wants_food' => isset($_POST['wants_food']) ? 1 : 0,
            'equipment' => array_map('intval', (array) ($_POST['equipment'] ?? [])),
        ];
    }

    // makes the checks for an overdue payment, for valid date, a date after today, guests, start time, hours, if it ends by midnight, waitstaff, and equipment
    // it returns an error message or null if everything is fine
    private static function validate(array $user, array $venue, array $form, array $catalog): ?string {
        $date = $form['event_date'];

        if (Booking::hasOverduePayment($user['UserID'])) {
            return 'You have an unpaid booking that is more than 14 days overdue - please pay it first before making a new booking.';
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || date('Y-m-d', strtotime($date)) !== $date) {
            return 'Please choose a date in the calendar.';
        }
        if ($date <= date('Y-m-d')) {
            return 'The event date must be after today.';
        }
        if (!Venue::isDateFree($venue['VenueID'], $date)) {
            return 'This date is not available anymore, please choose another date.';
        }
        if ($form['guests'] < 1 || $form['guests'] > $venue['MaxCapacity']) {
            return 'The number of guests must be between 1 and ' . $venue['MaxCapacity'] . '.';
        }
        if (!in_array($form['start_time'], self::START_TIMES, true)) {
            return 'Please choose a start time.';
        }
        if ($form['hours'] < 1 || $form['hours'] > 12) {
            return 'An event can last between 1 and 12 hours.';
        }
        if ((int) substr($form['start_time'], 0, 2) + $form['hours'] > 24) {
            return 'The event must end by midnight, please choose an earlier start time or fewer hours.';
        }
        if ($form['staff_count'] < 0 || $form['staff_count'] > 25) {
            return 'You can request between 0 and 25 servers.';
        }
        foreach ($form['equipment'] as $equipmentId => $quantity) {
            if (!isset($catalog[$equipmentId])) {
                return 'One of the selected equipment items is no longer available.';
            }
            if ($quantity < 0 || $quantity > 10) {
                return 'You can choose up to 10 of each equipment item.';
            }
        }

        return null;
    }

    private static function notFound(string $heading): void {
        http_response_code(404);
        view('error', [
            'heading' => $heading,
            'message' => 'It does not exist or is no longer available.',
        ]);
    }
}
