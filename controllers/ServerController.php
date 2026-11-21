
<?php

class ServerController {
    // the answers that a server can give (the button's value, the new status, and a message)
    const RESPONSES = [
        'accept' => ['Accepted', 'You have accepted the event.'],
        'decline' => ['Declined', 'You have declined the event. The venue manager can now assign someone else.'],
    ];

    // the server's page - the assignments to answer, the upcoming events, the past events, and the earnings
    public static function dashboard(): void {
        $user = requireRole('Server');
        $today = date('Y-m-d');
        $sections = ['pending' => [], 'upcoming' => [], 'worked' => [], 'other' => []];

        // each assignment goes into a section
        foreach (StaffAssignment::forServer($user['UserID']) as $assignment) {
            $confirmed = $assignment['BookingStatus'] === 'Confirmed';

            if ($assignment['Status'] === 'Pending' && $confirmed && $assignment['EventDate'] >= $today) {
                $sections['pending'][] = $assignment;
            } elseif ($assignment['Status'] === 'Accepted' && $confirmed && $assignment['EventDate'] >= $today) {
                $sections['upcoming'][] = $assignment;
            } elseif ($assignment['Status'] === 'Accepted' && $assignment['BookingStatus'] !== 'Cancelled') {
                $sections['worked'][] = $assignment;
            } else {
                // declined assignments and events that were cancelled
                $sections['other'][] = $assignment;
            }
        }

        view('server/dashboard', [
            'title' => 'My events - Eventra',
            'mainClass' => '',
            'user' => $user,
            'sections' => $sections,
            // the 20 most recent first (the total earnings still count every completed event tho)
            'worked' => array_slice(array_reverse($sections['worked']), 0, 20),
            'other' => array_slice(array_reverse($sections['other']), 0, 10),
            'earnings' => StaffAssignment::totalEarnings($user['UserID']),
        ]);
    }

    // accept or decline an assignment
    public static function respond(): void {
        $user = requireRole('Server');
        $action = $_POST['action'] ?? '';

        if (!isset(self::RESPONSES[$action])) {
            setFlash('Unknown action.', 'danger');
            redirect('server');
        }

        [$status, $message] = self::RESPONSES[$action];

        // the triggers refuse the change if the booking is no longer confirmed
        try {
            $changed = StaffAssignment::respond((int) ($_POST['assignment_id'] ?? 0), $user['UserID'], $status);
        } catch (PDOException $e) {
            $changed = false;
        }

        if ($changed) {
            setFlash($message);
        } else {
            setFlash('This assignment can no longer be answered.', 'danger');
        }
        redirect('server');
    }
}
