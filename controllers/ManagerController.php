<?php

class ManagerController {
    // the status changes a venue nanager can make [status before, status after, message]
    const STATUS_ACTIONS = [
        'approve' => ['Pending', 'Approved', 'The booking has been approved.'],
        'reject' => ['Pending', 'Cancelled', 'The booking has been rejected.'],
        'confirm' => ['Approved', 'Confirmed', 'The booking has been confirmed.'],
        'cancel' => ['Approved', 'Cancelled', 'The booking has been cancelled.'],
        'complete' => ['Confirmed', 'Completed', 'The booking has been marked as completed.'],
    ];

    // attachments - max 2 MB - PHP's default limit and only these file types
    const MAX_ATTACHMENT_SIZE = 2 * 1024 * 1024;
    const ATTACHMENT_TYPES = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];

    // venue photos - max 2 MB each (JPG or PNG) and max 10 per venue
    const MAX_PHOTO_SIZE = 2 * 1024 * 1024;
    const PHOTO_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    const MAX_PHOTOS = 10;

   // the booking page for the venue manager
    public static function booking(): void {
        $user = requireRole('Manager');
        $booking = self::findBooking($user, (int) ($_GET['id'] ?? 0));

        if ($booking === null) {
            return;
        }

        $equipment = Booking::equipment($booking['BookingID']);
        $assignments = StaffAssignment::forBooking($booking['BookingID']);
        $activeAssignments = count(array_filter($assignments, fn ($assignment) => $assignment['Status'] !== 'Declined'));

        view('manager/booking', [
            'title' => 'Booking ' . $booking['BookingID'] . ' - Eventra',
            'booking' => $booking,
            'equipment' => $equipment,
            'price' => Booking::calculatePrice($booking, $equipment),
            'assignments' => $assignments,
            'canAssign' => $booking['Status'] === 'Confirmed' && $activeAssignments < $booking['StaffCount'],
            'availableServers' => StaffAssignment::availableServers($booking['BookingID']),
        ]);
    }

    // approve, reject, confirm, or complete a booking
    public static function updateStatus(): void {
        $user = requireRole('Manager');
        $booking = self::findBooking($user, (int) ($_POST['booking_id'] ?? 0));

        if ($booking === null) {
            return;
        }

        $action = $_POST['action'] ?? '';

        if (!isset(self::STATUS_ACTIONS[$action])) {
            setFlash('Unknown action.', 'danger');
        } elseif ($action === 'complete' && $booking['EventDate'] > date('Y-m-d')) {
            setFlash('A booking can only be marked as completed on or after its event date.', 'danger');
        } else {
            [$from, $to, $message] = self::STATUS_ACTIONS[$action];

            try {
                if (Booking::changeStatus($booking['BookingID'], $from, $to)) {
                    setFlash($message);
                } else {
                    setFlash('The status of this booking has already changed.', 'danger');
                }
            } catch (PDOException $e) {
                setFlash('The status of this booking could not be changed.', 'danger');
            }
        }

        redirect('manage-booking&id=' . $booking['BookingID']);
    }

    // consultation notes and an optional attachment 
    public static function saveNotes(): void {
        $user = requireRole('Manager');
        $booking = self::findBooking($user, (int) ($_POST['booking_id'] ?? 0));

        if ($booking === null) {
            return;
        }

        $notes = trim($_POST['notes'] ?? '');
        $attachmentPath = $booking['AttachmentPath'];
        $file = $_FILES['attachment'] ?? null;

         // a new file was chosen
        if ($file !== null && $file['error'] !== UPLOAD_ERR_NO_FILE) {
            $type = $file['error'] === UPLOAD_ERR_OK ? mime_content_type($file['tmp_name']) : '';

            if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > self::MAX_ATTACHMENT_SIZE) {
                setFlash('The file could not be uploaded. Files can be at most 2 MB.', 'danger');
                redirect('manage-booking&id=' . $booking['BookingID']);
            }
            if (!isset(self::ATTACHMENT_TYPES[$type])) {
                setFlash('Only PDF, JPG, and PNG files can be attached.', 'danger');
                redirect('manage-booking&id=' . $booking['BookingID']);
            }

            // a random file name so that files can't be guessed or overwritten
            $attachmentPath = 'booking-' . $booking['BookingID'] . '-' . bin2hex(random_bytes(8)) . '.' . self::ATTACHMENT_TYPES[$type];
            move_uploaded_file($file['tmp_name'], self::uploadFolder() . $attachmentPath);

            // the previous attachment is replaced
            if ($booking['AttachmentPath'] !== null && is_file(self::uploadFolder() . $booking['AttachmentPath'])) {
                unlink(self::uploadFolder() . $booking['AttachmentPath']);
            }
        }

        Booking::saveNotes($booking['BookingID'], $notes === '' ? null : $notes, $attachmentPath);
        setFlash('The consultation notes have been saved.');
        redirect('manage-booking&id=' . $booking['BookingID']);
    }

    // attachments are outside the public folder, so they can only be opened through this check
    public static function attachment(): void {
        $user = requireRole('Manager');
        $booking = self::findBooking($user, (int) ($_GET['id'] ?? 0));

        if ($booking === null) {
            return;
        }

        $path = self::uploadFolder() . $booking['AttachmentPath'];

        if ($booking['AttachmentPath'] === null || !is_file($path)) {
            self::notFound('Attachment not found');
            return;
        }

        header('Content-Type: ' . mime_content_type($path));
        header('Content-Disposition: inline; filename="' . $booking['AttachmentPath'] . '"');
        readfile($path);
    }

    // record that the client has paid
    public static function markPaid(): void {
        $user = requireRole('Manager');
        $booking = self::findBooking($user, (int) ($_POST['booking_id'] ?? 0));

        if ($booking === null) {
            return;
        }

        if (Booking::pay($booking['BookingID'])) {
            setFlash('The payment has been recorded as paid.');
        } else {
            setFlash('This booking has no unpaid payment.', 'danger');
        }
        redirect('manage-booking&id=' . $booking['BookingID']);
    }

    // assign a server to work a confirmed booking
    public static function assignServer(): void {
        $user = requireRole('Manager');
        $booking = self::findBooking($user, (int) ($_POST['booking_id'] ?? 0));

        if ($booking === null) {
            return;
        }

        // the triggers check the limit, the overlaps, and the booking's status again
        try {
            StaffAssignment::assign($booking['BookingID'], (int) ($_POST['server_id'] ?? 0));
            setFlash('The server has been assigned and has to accept the assignment.');
        } catch (PDOException $e) {
            setFlash('This server could not be assigned because they may be working at that time or the booking already has enough servers.', 'danger');
        }
        redirect('manage-booking&id=' . $booking['BookingID']);
    }

    // withdraw a pending assignment (for example to choose another server)
    public static function withdrawServer(): void {
        $user = requireRole('Manager');
        $booking = self::findBooking($user, (int) ($_POST['booking_id'] ?? 0));

        if ($booking === null) {
            return;
        }

        try {
            $withdrawn = StaffAssignment::withdraw((int) ($_POST['assignment_id'] ?? 0), $booking['BookingID']);
        } catch (PDOException $e) {
            $withdrawn = false;
        }

        if ($withdrawn) {
            setFlash('The assignment has been withdrawn.');
        } else {
            setFlash('Only a pending assignment can be withdrawn.', 'danger');
        }
        redirect('manage-booking&id=' . $booking['BookingID']);
    }

    // the list of the manager's venues
    public static function venues(): void {
        $user = requireRole('Manager');

        view('manager/venues', [
            'title' => 'Your venues - Eventra',
            'venues' => Venue::forManager($user['UserID']),
        ]);
    }

    // the page for one venue - details, photos, and blackout dates
    // ($form and $errors are only given when a form is shown again after an error)
    public static function venue(array $form = [], array $errors = []): void {
        $user = requireRole('Manager');
        $venue = self::findVenue($user, (int) ($form['venue_id'] ?? $_GET['id'] ?? 0));

        if ($venue === null) {
            return;
        }

        view('manager/venue', [
            'title' => $venue['Name'] . ' - Eventra',
            'venue' => $venue,
            'photos' => Venue::photoList($venue['VenueID']),
            'blackouts' => Venue::upcomingBlackouts($venue['VenueID'], date('Y-m-d')),
            'form' => $form,
            'errors' => $errors,
        ]);
    }

    // save the description, the hourly rate, and the capacity
    public static function saveVenue(): void {
        $user = requireRole('Manager');
        $venue = self::findVenue($user, (int) ($_POST['venue_id'] ?? 0));

        if ($venue === null) {
            return;
        }

        $form = [
            'venue_id' => $venue['VenueID'],
            'description' => trim($_POST['description'] ?? ''),
            'hourly_rate' => trim($_POST['hourly_rate'] ?? ''),
            'max_capacity' => trim($_POST['max_capacity'] ?? ''),
        ];
        $largestBooking = Venue::largestOpenGuestCount($venue['VenueID']);
        $error = null;

        if ($form['description'] === '') {
            $error = 'Please write a description.';
        } elseif (mb_strlen($form['description']) > 2000) {
            $error = 'The description can be maximum 2000 characters long.';
        } elseif (!preg_match('/^\d{1,5}(\.\d{1,2})?$/', $form['hourly_rate']) || (float) $form['hourly_rate'] <= 0 || (float) $form['hourly_rate'] > 10000) {
            $error = 'The hourly rate must be an amount in euro between 0.01 and 10000 (for example 90 or 92.50).';
        } elseif (!ctype_digit($form['max_capacity']) || (int) $form['max_capacity'] < 1 || (int) $form['max_capacity'] > 500) {
            $error = 'The capacity must be a whole number of guests between 1 and 500.';
        } elseif ((int) $form['max_capacity'] < $largestBooking) {
            $error = 'The capacity cannot be lower than ' . $largestBooking . ', the number of guests of an open booking.';
        }

        if ($error === null) {
            // the triggers check the capacity again just in case a booking arrived in the meantime
            try {
                Venue::updateDetails($venue['VenueID'], $form['description'], (int) round((float) $form['hourly_rate'] * 100), (int) $form['max_capacity']);
                setFlash('The venue details have been saved.');
                redirect('manage-venue&id=' . $venue['VenueID']);
            } catch (PDOException $e) {
                $error = 'The details could not be saved. The capacity may be lower than the number of guests of an open booking.';
            }
        }

        self::venue($form, ['details' => $error]);
    }

    // add a photo -  they're public so they're saved in public/images/venues
    public static function addPhoto(): void {
        $user = requireRole('Manager');
        $venue = self::findVenue($user, (int) ($_POST['venue_id'] ?? 0));

        if ($venue === null) {
            return;
        }

        $back = 'manage-venue&id=' . $venue['VenueID'];
        $file = $_FILES['photo'] ?? null;

        if ($file === null || $file['error'] === UPLOAD_ERR_NO_FILE) {
            setFlash('Please choose a photo to add.', 'danger');
            redirect($back);
        }
        if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > self::MAX_PHOTO_SIZE) {
            setFlash('The photo could not be uploaded. Photos can be at most 2 MB.', 'danger');
            redirect($back);
        }

        $type = mime_content_type($file['tmp_name']);

        if (!isset(self::PHOTO_TYPES[$type])) {
            setFlash('Only JPG and PNG photos can be added.', 'danger');
            redirect($back);
        }
        if (count(Venue::photoList($venue['VenueID'])) >= self::MAX_PHOTOS) {
            setFlash('A venue can have at most ' . self::MAX_PHOTOS . ' photos. Remove one first.', 'danger');
            redirect($back);
        }

        // a random file name so that photos can't overwrite each other
        $fileName = 'venue-' . $venue['VenueID'] . '-' . bin2hex(random_bytes(8)) . '.' . self::PHOTO_TYPES[$type];
        move_uploaded_file($file['tmp_name'], __DIR__ . '/../public/images/venues/' . $fileName);
        Venue::addPhoto($venue['VenueID'], 'images/venues/' . $fileName);

        setFlash('The photo has been added.');
        redirect($back);
    }

    // remove a photo (a venue always keeps at least one photo tho because the first one is its cover photo)
    public static function deletePhoto(): void {
        $user = requireRole('Manager');
        $venue = self::findVenue($user, (int) ($_POST['venue_id'] ?? 0));

        if ($venue === null) {
            return;
        }

        $back = 'manage-venue&id=' . $venue['VenueID'];
        $photo = Venue::findPhoto((int) ($_POST['photo_id'] ?? 0), $venue['VenueID']);

        if ($photo === null) {
            setFlash('This photo does not exist anymore.', 'danger');
            redirect($back);
        }
        if (count(Venue::photoList($venue['VenueID'])) <= 1) {
            setFlash('A venue needs at least one photo. Add another photo before removing this one.', 'danger');
            redirect($back);
        }

        Venue::deletePhoto($photo['PhotoID']);

        // only files uploaded through Eventra are deleted from the folder
        // the original photos stay because the seed script uses them
        $path = __DIR__ . '/../public/' . $photo['FilePath'];

        if (str_starts_with(basename($photo['FilePath']), 'venue-') && is_file($path)) {
            unlink($path);
        }

        setFlash('The photo has been removed.');
        redirect($back);
    }

    // block a date for a reason that has nothing to do with bookings (like a holiday for example)
    public static function addBlackout(): void {
        $user = requireRole('Manager');
        $venue = self::findVenue($user, (int) ($_POST['venue_id'] ?? 0));

        if ($venue === null) {
            return;
        }

        $form = [
            'venue_id' => $venue['VenueID'],
            'blackout_date' => trim($_POST['blackout_date'] ?? ''),
            'reason' => trim($_POST['reason'] ?? ''),
        ];
        $date = $form['blackout_date'];
        $error = null;

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || date('Y-m-d', strtotime($date)) !== $date) {
            $error = 'Please choose a date.';
        } elseif ($date <= date('Y-m-d')) {
            $error = 'A blackout date must be after today.';
        } elseif ($form['reason'] === '') {
            $error = 'Please write a reason, for example a national holiday.';
        } elseif (mb_strlen($form['reason']) > 200) {
            $error = 'The reason can be at most 200 characters long.';
        } elseif (!Venue::isDateFree($venue['VenueID'], $date)) {
            $error = 'This date already has a booking or is already a blackout date.';
        }

        if ($error === null) {
            // the trigger checks again in case a client booked this date a moment ago
            try {
                Venue::addBlackout($venue['VenueID'], $date, $form['reason']);
                setFlash('The blackout date has been added.');
                redirect('manage-venue&id=' . $venue['VenueID']);
            } catch (PDOException $e) {
                $error = 'This date already has a booking or is already a blackout date.';
            }
        }

        self::venue($form, ['blackout' => $error]);
    }

    public static function deleteBlackout(): void {
        $user = requireRole('Manager');
        $venue = self::findVenue($user, (int) ($_POST['venue_id'] ?? 0));

        if ($venue === null) {
            return;
        }

        if (Venue::deleteBlackout((int) ($_POST['blackout_id'] ?? 0), $venue['VenueID'], date('Y-m-d'))) {
            setFlash('The blackout date has been removed.');
        } else {
            setFlash('This blackout date could not be removed.', 'danger');
        }
        redirect('manage-venue&id=' . $venue['VenueID']);
    }

    // returns the booking if it belongs to one of the manager's venues otherwise shows "Booking not found"
    private static function findBooking(array $user, int $bookingId): ?array {
        $booking = Booking::findForManager($bookingId, $user['UserID']);

        if ($booking === null) {
            self::notFound('Booking not found');
        }
        return $booking;
    }

    // returns the venue if this manager manages it otherwise shows "Venue not found"
    private static function findVenue(array $user, int $venueId): ?array {
        $venue = Venue::findForManager($venueId, $user['UserID']);

        if ($venue === null) {
            self::notFound('Venue not found');
        }
        return $venue;
    }

    private static function uploadFolder(): string {
        $folder = __DIR__ . '/../uploads/';

        if (!is_dir($folder)) {
            mkdir($folder);
        }
        return $folder;
    }

    private static function notFound(string $heading): void {
        http_response_code(404);
        view('error', [
            'heading' => $heading,
            'message' => 'It does not exist or is no longer available.',
        ]);
    }
}
