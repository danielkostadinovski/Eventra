<?php

class HomeController
{
    public static function index(): void
    {
        $user = currentUser();

        if ($user !== null) {
            redirect(dashboardPage($user));
        }

        view('home', ['title' => 'Eventra – Book an event venue', 'mainClass' => 'd-flex']);
    }
}