<?php

class DashboardController
{
    public static function client(): void
    {
        $user = requireRole('Client');
        $upcoming = [];
        $past = [];

        // upcoming: active bookings from today on 
        // past: everything that happened already or it was cancelled
        foreach (Booking::allForClient($user['UserID']) as $booking) {
            if ($booking['EventDate'] >= date('Y-m-d') && $booking['Status'] !== 'Cancelled') {
                $upcoming[] = $booking;
            } else {
                $past[] = $booking;
            }
        }

        view('bookings/index', [
            'title' => 'My bookings - Eventra',
            'mainClass' => '',
            'user' => $user,
            'upcoming' => $upcoming,
            'past' => array_reverse($past),
        ]);
    }

    public static function manager(): void
    {
        $user = requireRole('Manager');
        $today = date('Y-m-d');
        $sections = ['pending' => [], 'approved' => [], 'toComplete' => [], 'overdue' => [], 'upcoming' => [], 'past' => []];

        // each booking goes into the section where the manager has to do something with it
        foreach (Booking::allForManager($user['UserID']) as $booking) {
            $overdue = $booking['PaymentStatus'] === 'Unpaid' && $booking['Status'] !== 'Cancelled'
                && $booking['EventDate'] < date('Y-m-d', strtotime('-14 days'));

            if ($booking['Status'] === 'Pending') {
                $sections['pending'][] = $booking;
            } elseif ($booking['Status'] === 'Approved') {
                $sections['approved'][] = $booking;
            } elseif ($booking['Status'] === 'Confirmed' && $booking['EventDate'] <= $today) {
                $sections['toComplete'][] = $booking;
            } elseif ($overdue) {
                $sections['overdue'][] = $booking;
            } elseif ($booking['Status'] === 'Confirmed') {
                $sections['upcoming'][] = $booking;
            } else {
                $sections['past'][] = $booking;
            }
        }

        view('manager/dashboard', [
            'title' => 'Bookings - Eventra',
            'mainClass' => '',
            'user' => $user,
            'venues' => Venue::forManager($user['UserID']),
            'sections' => $sections,
            // the 20 most recent past bookings, newest first
            'past' => array_slice(array_reverse($sections['past']), 0, 20),
        ]);
    }
}