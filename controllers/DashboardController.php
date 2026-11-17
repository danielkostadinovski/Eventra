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
            'user' => $user,
            'upcoming' => $upcoming,
            'past' => array_reverse($past),
        ]);
    }

    public static function manager(): void
    {
        $user = requireRole('Manager');
        view('dashboard', ['title' => 'Venue Manager - Eventra', 'user' => $user]);
    }

    public static function server(): void
    {
        $user = requireRole('Server');
        view('dashboard', ['title' => 'Server - Eventra', 'user' => $user]);
    }

    public static function admin(): void
    {
        $user = requireRole('Admin');
        view('dashboard', ['title' => 'Administrator - Eventra', 'user' => $user]);
    }
}