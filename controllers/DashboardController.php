<?php

class DashboardController
{
    public static function client(): void
    {
        $user = requireRole('Client');
        view('dashboard', ['title' => 'Client – Eventra', 'user' => $user]);
    }

    public static function manager(): void
    {
        $user = requireRole('Manager');
        view('dashboard', ['title' => 'Venue Manager – Eventra', 'user' => $user]);
    }

    public static function server(): void
    {
        $user = requireRole('Server');
        view('dashboard', ['title' => 'Server – Eventra', 'user' => $user]);
    }

    public static function admin(): void
    {
        $user = requireRole('Admin');
        view('dashboard', ['title' => 'Administrator – Eventra', 'user' => $user]);
    }
}