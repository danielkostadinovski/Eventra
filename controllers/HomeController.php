<?php

class HomeController
{
    public static function index(): void
    {
        view('home', ['title' => 'Eventra – Book an event venue']);
    }
}