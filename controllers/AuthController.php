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

    public static function showRegister(): void
    {
        self::redirectIfLoggedIn();
        view('auth/register', [
            'title' => 'Create an account – Eventra',
            'firstName' => '',
            'lastName' => '',
            'email' => '',
            'error' => null,
        ]);
    }

    public static function register(): void
    {
        self::redirectIfLoggedIn();

        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        $error = null;

        if ($firstName === '' || $lastName === '' || $email === '' || $password === '') {
            $error = 'Please fill in all fields.';
        } elseif (mb_strlen($firstName) > 50 || mb_strlen($lastName) > 50) {
            $error = 'First and last name can be at most 50 characters long.';
        } elseif (strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 8) {
            $error = 'The password must be at least 8 characters long.';
        } elseif ($password !== $passwordConfirm) {
            $error = 'The passwords do not match.';
        } elseif (User::findByEmail($email) !== null) {
            $error = 'An account with this email already exists.';
        }

        if ($error === null) {
            try {
                User::create($firstName, $lastName, $email, $password);
            } catch (PDOException $e) {
                $error = 'Your account could not be created. Try again please.';
            }
        }

        if ($error !== null) {
            view('auth/register', [
                'title' => 'Create an account – Eventra',
                'firstName' => $firstName,
                'lastName' => $lastName,
                'email' => $email,
                'error' => $error,
            ]);
            return;
        }

        setFlash('Your account has been created. You can now log in.');
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