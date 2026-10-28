<?php

class AuthController
{
    public static function showLogin(): void
    {
        self::redirectIfLoggedIn();
        view('auth/login', ['title' => 'Log in – Eventra', 'email' => '', 'error' => null]);
    }

    public static function login(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $user = User::findByEmail($email);

        if ($user === null || !password_verify($password, $user['PasswordHash'])) {
            view('auth/login', ['title' => 'Log in – Eventra', 'email' => $email, 'error' => 'Incorrect email or password.']);
            return;
        }

        if (!$user['IsActive']) {
            view('auth/login', [
                'title' => 'Log in – Eventra',
                'email' => $email,
                'error' => 'This account has been deactivated. Please contact the administrator.',
            ]);
            return;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['UserID'];
        redirect(dashboardPage($user));
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
        setFlash('You have been logged out.');
        redirect('login');
    }

    private static function redirectIfLoggedIn(): void
    {
        $user = currentUser();

        if ($user !== null) {
            redirect(dashboardPage($user));
        }
    }
}