<?php

class VenueController {
    public static function index(): void {
        requireRole('Client');
        view('venues/index', [
            'title' => 'Venues – Eventra',
            'venues' => Venue::allActive(),
        ]);
    }
}
