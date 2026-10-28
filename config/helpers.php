<?php

function e(?string $text): string
{
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

function view(string $name, array $data = []): void
{
    extract($data);
    require __DIR__ . '/../views/layout/header.php';
    require __DIR__ . '/../views/' . $name . '.php';
    require __DIR__ . '/../views/layout/footer.php';
}

function redirect(string $page): void
{
    header('Location: index.php?page=' . $page);
    exit;
}

function setFlash(string $message): void
{
    $_SESSION['flash'] = $message;
}

function takeFlash(): ?string
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $message;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

function currentUser(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    $user = User::findById($_SESSION['user_id']);

    if ($user === null || !$user['IsActive']) {
        unset($_SESSION['user_id']);
        return null;
    }

    return $user;
}

function dashboardPage(array $user): string
{
    return strtolower($user['Role']);
}

function roleName(string $role): string
{
    return [
        'Client' => 'Client',
        'Manager' => 'Venue Manager',
        'Server' => 'Server',
        'Admin' => 'Administrator',
    ][$role];
}

function requireRole(string $role): array
{
    $user = currentUser();

    if ($user === null) {
        setFlash('Please log in to continue.');
        redirect('login');
    }

    if ($user['Role'] !== $role) {
        http_response_code(403);
        view('error', [
            'heading' => 'Access denied',
            'message' => 'This page is only available to the ' . roleName($role) . ' role.',
        ]);
        exit;
    }

    return $user;
}