
<?php

class ManagerController {
    // the status changes a venue nanager can make [status before, status after, message]
    const STATUS_ACTIONS = [
        'approve' => ['Pending', 'Approved', 'The booking has been approved.'],
        'reject' => ['Pending', 'Cancelled', 'The booking has been rejected.'],
        'confirm' => ['Approved', 'Confirmed', 'The booking has been confirmed.'],
        'complete' => ['Confirmed', 'Completed', 'The booking has been marked as completed.'],
    ];

    // attachments - max 2 MB - PHP's default limit and only these file types
    const MAX_ATTACHMENT_SIZE = 2 * 1024 * 1024;
    const ATTACHMENT_TYPES = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];

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
            setFlash('Only a ending assignment can be withdrawn.', 'danger');
        }
        redirect('manage-booking&id=' . $booking['BookingID']);
    }

    // returns the booking if it belongs to one of the manager's venues otherwise shows "Booking not found"
    private static function findBooking(array $user, int $bookingId): ?array {
        $booking = Booking::findForManager($bookingId, $user['UserID']);

        if ($booking === null) {
            self::notFound('Booking not found');
        }
        return $booking;
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
