<?php

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
]);

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/helpers.php';
require __DIR__ . '/../controllers/HomeController.php';

$routes = [
    'GET home' => [HomeController::class, 'index'],
];

$method = $_SERVER['REQUEST_METHOD'];
$page = $_GET['page'] ?? 'home';

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