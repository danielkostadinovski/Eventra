<?php

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
]);

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/helpers.php';
require __DIR__ . '/../models/User.php';
require __DIR__ . '/../controllers/HomeController.php';
require __DIR__ . '/../controllers/AuthController.php';
require __DIR__ . '/../controllers/DashboardController.php';

$routes = [
    'GET home' => [HomeController::class, 'index'],
    'GET login' => [AuthController::class, 'showLogin'],
    'POST login' => [AuthController::class, 'login'],
    'POST logout' => [AuthController::class, 'logout'],
    'GET client' => [DashboardController::class, 'client'],
    'GET manager' => [DashboardController::class, 'manager'],
    'GET server' => [DashboardController::class, 'server'],
    'GET admin' => [DashboardController::class, 'admin'],
];

$method = $_SERVER['REQUEST_METHOD'];
$page = $_GET['page'] ?? 'home';

if ($method === 'POST' && !hash_equals(csrfToken(), $_POST['csrf_token'] ?? '')) {
    http_response_code(400);
    view('error', [
        'heading' => 'Session expired',
        'message' => 'Please go back, reload the page and try again.',
    ]);
    exit;
}

if (!isset($routes["$method $page"])) {
    http_response_code(404);
    view('error', [
        'heading' => 'Page not found',
        'message' => 'The page you are looking for does not exist.',
    ]);
    exit;
}

[$controller, $action] = $routes["$method $page"];
$controller::$action();