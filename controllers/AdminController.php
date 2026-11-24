<?php

class AdminController {
    // the largest capacity a venue can have (the same limit as on the venue manager's page)
    const MAX_CAPACITY = 500;

    // the periods of the reports page (as value => label)
    const PERIODS = [
        'all' => 'All time',
        'year' => 'Last 12 months',
        'half-year' => 'Last 6 months',
        'month' => 'Last 30 days',
    ];

    // the roles the administrator can filter the users by (as Role => label)
    const ROLE_FILTERS = [
        '' => 'All',
        'Client' => 'Clients',
        'Manager' => 'Venue managers',
        'Server' => 'Servers',
        'Admin' => 'Administrators',
    ];

    // the administrator's overview page with a box for each part of the company
    public static function dashboard(): void {
        $user = requireRole('Admin');
        $venues = Venue::all();
        $items = Equipment::all();
        $activeVenues = count(array_filter($venues, fn ($venue) => $venue['IsActive']));
        $offeredItems = count(array_filter($items, fn ($item) => $item['IsActive']));

        view('admin/dashboard', [
            'title' => 'Overview - Eventra',
            'mainClass' => '',
            'user' => $user,
            'activeVenues' => $activeVenues,
            'inactiveVenues' => count($venues) - $activeVenues,
            'offeredItems' => $offeredItems,
            'retiredItems' => count($items) - $offeredItems,
            'rates' => Booking::currentRates(),
            'clients' => count(User::all('Client')),
            'staff' => count(User::all('')) - count(User::all('Client')),
            'confirmedBookings' => count(Booking::confirmedUpcoming()),
            'yearRevenue' => Report::summary(...self::periodDates('year'))['Revenue'],
        ]);
    }

    // all venues
    public static function venues(): void {
        requireRole('Admin');

        view('admin/venues', [
            'title' => 'Venues - Eventra',
            'mainClass' => '',
            'venues' => Venue::all(),
        ]);
    }

    // the form for a new venue (no id) or for an existing one ($form and $error are only given when the form is shown again after an error, so the answers are kept)
    public static function venue(array $form = [], ?string $error = null): void {
        requireRole('Admin');
        $venueId = (int) ($form['venue_id'] ?? $_GET['id'] ?? 0);
        $venue = null;

        if ($venueId !== 0) {
            $venue = Venue::find($venueId);

            if ($venue === null) {
                self::notFound('Venue not found');
                return;
            }
        }

        view('admin/venue', [
            'title' => ($venue['Name'] ?? 'New venue') . ' - Eventra',
            'venue' => $venue,
            'managers' => User::managers(),
            'form' => $form,
            'error' => $error,
        ]);
    }

    // add a new venue or save the changes to an existing one 
    public static function saveVenue(): void {
        requireRole('Admin');
        $venueId = (int) ($_POST['venue_id'] ?? 0);
        $venue = $venueId === 0 ? null : Venue::find($venueId);

        if ($venueId !== 0 && $venue === null) {
            self::notFound('Venue not found');
            return;
        }

        $form = [
            'venue_id' => $venueId,
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'hourly_rate' => trim($_POST['hourly_rate'] ?? ''),
            'max_capacity' => trim($_POST['max_capacity'] ?? ''),
            'manager_id' => (int) ($_POST['manager_id'] ?? 0),
            // a new venue always starts inactive because it has no photos yet
            'is_active' => $venue !== null && ($_POST['is_active'] ?? '') === '1' ? 1 : 0,
        ];
        $error = self::venueError($form, $venue);

        if ($error === null) {
            $hourlyRate = self::cents($form['hourly_rate'], 10000);

            // the triggers check the manager, the capacity, and the open bookings again
            try {
                if ($venue === null) {
                    $venueId = Venue::create($form['manager_id'], $form['name'], $form['description'], $hourlyRate, (int) $form['max_capacity']);
                    setFlash('The venue has been added. Its venue manager can now add the photos and then you can make it active.');
                } else {
                    Venue::update($venueId, $form['manager_id'], $form['name'], $form['description'], $hourlyRate, (int) $form['max_capacity'], $form['is_active']);
                    setFlash('The venue has been saved.');
                }
                redirect('admin-venue&id=' . $venueId);
            } catch (PDOException $e) {
                $error = 'The venue could not be saved because a booking changed in the meantime. Please try again.';
            }
        }

        self::venue($form, $error);
    }

    // the equipment catalog ($form and $error belong to the "Add an item" form)
    public static function equipment(array $form = [], ?string $error = null): void {
        requireRole('Admin');

        view('admin/equipment', [
            'title' => 'Equipment - Eventra',
            'mainClass' => '',
            'items' => Equipment::all(),
            'form' => $form,
            'error' => $error,
        ]);
    }

    public static function addEquipment(): void {
        requireRole('Admin');
        $form = [
            'name' => trim($_POST['name'] ?? ''),
            'price' => trim($_POST['price'] ?? ''),
        ];
        $error = self::equipmentError($form['name'], $form['price'], 0);

        if ($error === null) {
            Equipment::add($form['name'], self::cents($form['price'], 10000));
            setFlash('The item has been added to the catalog.');
            redirect('admin-equipment');
        }

        self::equipment($form, $error);
    }

    // change an item's name or price (bookings that already have it keep the price they were made with)
    public static function saveEquipment(): void {
        requireRole('Admin');
        $item = Equipment::find((int) ($_POST['equipment_id'] ?? 0));

        if ($item === null) {
            self::notFound('Item not found');
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $price = trim($_POST['price'] ?? '');
        $error = self::equipmentError($name, $price, $item['EquipmentID']);

        if ($error !== null) {
            setFlash($error, 'danger');
            redirect('admin-equipment');
        }

        Equipment::update($item['EquipmentID'], $name, self::cents($price, 10000));
        setFlash('"' . $name . '" has been saved.');
        redirect('admin-equipment');
    }

    // retire an item (clients can't choose it anymore, past bookings keep it) or offer it again
    public static function equipmentStatus(): void {
        requireRole('Admin');
        $item = Equipment::find((int) ($_POST['equipment_id'] ?? 0));

        if ($item === null) {
            self::notFound('Item not found');
            return;
        }

        if (($_POST['action'] ?? '') === 'retire') {
            Equipment::setActive($item['EquipmentID'], 0);
            setFlash('"' . $item['Name'] . '" has been retired. Clients cannot choose it anymore.');
        } else {
            Equipment::setActive($item['EquipmentID'], 1);
            setFlash('"' . $item['Name'] . '" is offered again.');
        }
        redirect('admin-equipment');
    }

    // the global prices 
    public static function prices(array $form = [], ?string $error = null): void {
        requireRole('Admin');

        view('admin/prices', [
            'title' => 'Prices - Eventra',
            'rates' => Booking::currentRates(),
            'form' => $form,
            'error' => $error,
        ]);
    }

    public static function savePrices(): void {
        requireRole('Admin');
        $form = [
            'staff_rate' => trim($_POST['staff_rate'] ?? ''),
            'food_rate' => trim($_POST['food_rate'] ?? ''),
        ];
        $staffRate = self::cents($form['staff_rate'], 1000);
        $foodRate = self::cents($form['food_rate'], 1000);

        if ($staffRate === null) {
            $error = 'The hourly staff rate must be an amount in euro between 0.01 and 1000 (for example 5 or 5.50)';
        } elseif ($foodRate === null) {
            $error = 'The food rate must be an amount in euro between 0.01 and 1000 (for example 20 or 25.50)';
        } else {
            Booking::saveRates($staffRate, $foodRate);
            setFlash('The prices have been saved. New bookings use them from now on.');
            redirect('admin-prices');
        }

        self::prices($form, $error);
    }

    // all accounts, optionally only one role 
    public static function users(): void {
        requireRole('Admin');
        $role = $_GET['role'] ?? '';

        if (!isset(self::ROLE_FILTERS[$role])) {
            $role = '';
        }

        view('admin/users', [
            'title' => 'Users - Eventra',
            'mainClass' => '',
            'users' => User::all($role),
            'role' => $role,
        ]);
    }

    // the form for a new venue manager or server account 
    public static function user(array $form = [], ?string $error = null): void {
        requireRole('Admin');

        view('admin/user', [
            'title' => 'New staff account - Eventra',
            'venues' => Venue::all(),
            'form' => $form,
            'error' => $error,
        ]);
    }

    public static function saveUser(): void {
        requireRole('Admin');
        $form = [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'role' => ($_POST['role'] ?? '') === 'Server' ? 'Server' : 'Manager',
            'venue_id' => (int) ($_POST['venue_id'] ?? 0),
        ];
        $error = self::accountError($form, $_POST['password'] ?? '', $_POST['password_confirm'] ?? '');

        if ($error === null) {
            // the account and the venue change are saved together or not at all
            $db = db();
            $db->exec('BEGIN IMMEDIATE');

            try {
                $userId = User::create($form['first_name'], $form['last_name'], $form['email'], $_POST['password'], $form['role']);

                if ($form['role'] === 'Manager' && $form['venue_id'] !== 0) {
                    Venue::setManager($form['venue_id'], $userId);
                }
                $db->exec('COMMIT');
                setFlash('The account has been created. Give ' . $form['first_name'] . ' the email address and the password so they can log in.');
                redirect('admin-users&role=' . $form['role']);
            } catch (PDOException $e) {
                $db->exec('ROLLBACK');
                $error = 'The account could not be created. Please try again.';
            }
        }

        self::user($form, $error);
    }

    // deactivate an account (the person can't log in anymore) or activate it again 
    public static function userStatus(): void {
        requireRole('Admin');
        $person = User::findById((int) ($_POST['user_id'] ?? 0));
        $back = 'admin-users&role=' . urlencode($_POST['role_filter'] ?? '');

        if ($person === null) {
            self::notFound('User not found');
            return;
        }

        $name = $person['FirstName'] . ' ' . $person['LastName'];

        if (($_POST['action'] ?? '') === 'activate') {
            User::setActive($person['UserID'], 1);
            setFlash($name . '\'s account is active again.');
            redirect($back);
        }

        $error = self::deactivationError($person);

        if ($error === null) {
            try {
                User::setActive($person['UserID'], 0);
                setFlash($name . '\'s account has been deactivated. They cannot log in anymore.');
                redirect($back);
            } catch (PDOException $e) {
                $error = 'The account could not be deactivated because something changed in the meantime. Please try again.';
            }
        }

        setFlash($error, 'danger');
        redirect($back);
    }

    // upcoming confirmed bookings that only the administrator can cancel 
    public static function bookings(): void {
        requireRole('Admin');

        view('admin/bookings', [
            'title' => 'Confirmed bookings - Eventra',
            'mainClass' => '',
            'bookings' => Booking::confirmedUpcoming(),
        ]);
    }

    // the emergency cancellation (the booking becomes cancelled and if it was paid its payment becomes refunded)
    public static function cancelBooking(): void {
        requireRole('Admin');
        $bookingId = (int) ($_POST['booking_id'] ?? 0);

        // both changes are saved together or not at all
        $db = db();
        $db->exec('BEGIN IMMEDIATE');

        try {
            if (!Booking::changeStatus($bookingId, 'Confirmed', 'Cancelled')) {
                $db->exec('ROLLBACK');
                setFlash('This booking is no longer Confirmed, so it was not cancelled.', 'danger');
                redirect('admin-bookings');
            }
            $refunded = Booking::refund($bookingId);
            $db->exec('COMMIT');
        } catch (PDOException $e) {
            $db->exec('ROLLBACK');
            setFlash('The booking could not be cancelled. Please try again.', 'danger');
            redirect('admin-bookings');
        }

        if ($refunded) {
            setFlash('The booking has been cancelled and its payment is marked as Refunded. Return the money to the client outside of Eventra.');
        } else {
            setFlash('The booking has been cancelled. It was not paid yet so there is nothing to refund.');
        }
        redirect('admin-bookings');
    }

    // company reports for a chosen period
    public static function reports(): void {
        requireRole('Admin');
        $period = $_GET['period'] ?? 'year';

        if (!isset(self::PERIODS[$period])) {
            $period = 'year';
        }
        [$from, $to] = self::periodDates($period);

        view('admin/reports', [
            'title' => 'Reports - Eventra',
            'mainClass' => '',
            'period' => $period,
            'summary' => Report::summary($from, $to),
            'statuses' => Report::bookingsByStatus($from, $to),
            'venues' => Report::venues($from, $to),
            'overdue' => Report::overduePayments(),
        ]);
    }

    // checks the venue form and returns the first problem or null if everything is fine
    private static function venueError(array $form, ?array $venue): ?string {
        $manager = User::managers()[$form['manager_id']] ?? null;
        $venueId = $venue === null ? 0 : (int) $venue['VenueID'];
        $currentManager = $venue === null ? 0 : (int) $venue['ManagerID'];

        if ($form['name'] === '' || mb_strlen($form['name']) > 100) {
            return 'Please write a name of at most 100 characters.';
        }
        if (Venue::nameTaken($form['name'], $venueId)) {
            return 'Another venue already has this name.';
        }
        if ($form['description'] === '' || mb_strlen($form['description']) > 2000) {
            return 'Please write a description of at most 2000 characters.';
        }
        if (self::cents($form['hourly_rate'], 10000) === null) {
            return 'The hourly rate must be an amount in euro between 0.01 and 10000 (for example 90 or 95.50)';
        }
        if (!ctype_digit($form['max_capacity']) || (int) $form['max_capacity'] < 1 || (int) $form['max_capacity'] > self::MAX_CAPACITY) {
            return 'The capacity must be a whole number of guests between 1 and ' . self::MAX_CAPACITY . '.';
        }
        if ($manager === null) {
            return 'Please choose a venue manager.';
        }
        // a deactivated venue manager can only stay on an inactive venue they already had
        if (!$manager['IsActive'] && ($form['is_active'] === 1 || $form['manager_id'] !== $currentManager)) {
            return 'This venue manager\'s account is deactivated. Please choose another venue manager.';
        }
        if ($venue === null) {
            return null;
        }

        $largestBooking = Venue::largestOpenGuestCount($venueId);
        $openBookings = Venue::openBookingCount($venueId);

        if ((int) $form['max_capacity'] < $largestBooking) {
            return 'The capacity cannot be lower than ' . $largestBooking . ', the number of guests of an open booking.';
        }
        if ($form['is_active'] === 1 && !$venue['IsActive'] && count(Venue::photoList($venueId)) === 0) {
            return 'A venue needs at least one photo before it can be made active. Its venue manager adds the photos.';
        }
        if ($form['is_active'] === 0 && $venue['IsActive'] && $openBookings > 0) {
            return 'This venue still has ' . plural($openBookings, 'open booking') . '. They must be completed or cancelled before the venue can be made inactive.';
        }
        return null;
    }

    // checks an equipment item's name and price and returns the first problem (or null)
    private static function equipmentError(string $name, string $price, int $equipmentId): ?string {
        if ($name === '' || mb_strlen($name) > 100) {
            return 'Please write an item name of at most 100 characters.';
        }
        if (Equipment::nameTaken($name, $equipmentId)) {
            return 'The catalog already has an item with this name.';
        }
        if (self::cents($price, 10000) === null) {
            return 'The price must be an amount in euro between 0.01 and 10000 (for example 25 or 10.50)';
        }
        return null;
    }

    // checks the new account form with the same rules as the public registration
    private static function accountError(array $form, string $password, string $passwordConfirm): ?string {
        if ($form['first_name'] === '' || $form['last_name'] === '' || $form['email'] === '' || $password === '') {
            return 'Please fill in all fields.';
        }
        if (mb_strlen($form['first_name']) > 50 || mb_strlen($form['last_name']) > 50) {
            return 'First and last name can be at most 50 characters long.';
        }
        if (strlen($form['email']) > 255 || !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
            return 'Please enter a valid email address.';
        }
        if (strlen($password) < 8) {
            return 'The password must be at least 8 characters long.';
        }
        if ($password !== $passwordConfirm) {
            return 'The passwords do not match.';
        }
        if (User::findByEmail($form['email']) !== null) {
            return 'An account with this email already exists.';
        }
        if ($form['venue_id'] !== 0 && $form['role'] === 'Server') {
            return 'A server does not run a venue. Please choose "No venue for now" or make the account a venue manager.';
        }
        if ($form['venue_id'] !== 0 && Venue::find($form['venue_id']) === null) {
            return 'Please choose a venue from the list.';
        }
        return null;
    }

    // an account can't be deactivated while people still depend on it
    private static function deactivationError(array $person): ?string {
        $name = $person['FirstName'] . ' ' . $person['LastName'];

        if ($person['Role'] === 'Admin') {
            return 'Administrator accounts cannot be deactivated.';
        }
        if ($person['Role'] === 'Manager') {
            $venues = Venue::activeCountForManager($person['UserID']);

            if ($venues > 0) {
                return $name . ' still runs ' . plural($venues, 'active venue') . '. Give them to another venue manager first.';
            }
        }
        if ($person['Role'] === 'Server') {
            $events = StaffAssignment::upcomingCount($person['UserID']);

            if ($events > 0) {
                return $name . ' still has ' . plural($events, 'upcoming event') . '. The account can be deactivated once none are left.';
            }
        }
        return null;
    }

    // the first and the last event date of a reports period as 'YYYY-MM-DD', 'YYYY-MM-DD'
    private static function periodDates(string $period): array {
        $today = date('Y-m-d');

        return match ($period) {
            'month' => [date('Y-m-d', strtotime('-30 days')), $today],
            'half-year' => [date('Y-m-d', strtotime('-6 months')), $today],
            'year' => [date('Y-m-d', strtotime('-12 months')), $today],
            default => ['0000-01-01', '9999-12-31'],
        };
    }

    // turns an amount in euro from a form (like 92.50 or something) into cents 9250 or gives null if it's not an amount between 0.01 and $maxEuro
    private static function cents(string $amount, int $maxEuro): ?int {
        if (!preg_match('/^\d{1,5}(\.\d{1,2})?$/', $amount) || (float) $amount <= 0 || (float) $amount > $maxEuro) {
            return null;
        }
        return (int) round((float) $amount * 100);
    }

    private static function notFound(string $heading): void {
        http_response_code(404);
        view('error', [
            'heading' => $heading,
            'message' => 'It does not exist or is no longer available.',
        ]);
    }
}