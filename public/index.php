<?php

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
]);

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/helpers.php';
require __DIR__ . '/../models/User.php';
require __DIR__ . '/../models/Venue.php';
require __DIR__ . '/../models/Equipment.php';
require __DIR__ . '/../models/Booking.php';        
require __DIR__ . '/../models/StaffAssignment.php';                      
require __DIR__ . '/../controllers/HomeController.php';
require __DIR__ . '/../controllers/AuthController.php';
require __DIR__ . '/../controllers/DashboardController.php';
require __DIR__ . '/../controllers/VenueController.php'; 
require __DIR__ . '/../controllers/BookingController.php';
require __DIR__ . '/../controllers/ManagerController.php';
              

$routes = [
    'GET home' => [HomeController::class, 'index'],
    'GET login' => [AuthController::class, 'showLogin'],
    'POST login' => [AuthController::class, 'login'],
    'GET register' => [AuthController::class, 'showRegister'],
    'POST register' => [AuthController::class, 'register'],
    'POST logout' => [AuthController::class, 'logout'],
    'GET client' => [DashboardController::class, 'client'],
    'GET manager' => [DashboardController::class, 'manager'],
    'GET server' => [DashboardController::class, 'server'],
    'GET admin' => [DashboardController::class, 'admin'],
    'GET venues' => [VenueController::class, 'index'],
    'GET venue' => [VenueController::class, 'show'],
    'POST booking-review' => [BookingController::class, 'review'],
    'POST booking-change' => [BookingController::class, 'change'],
    'POST booking-submit' => [BookingController::class, 'submit'],
    'GET booking' => [BookingController::class, 'show'],
    'POST booking-cancel' => [BookingController::class, 'cancel'],
    'POST booking-pay' => [BookingController::class, 'pay'],
    'POST review-add' => [BookingController::class, 'addReview'],
    'GET manage-booking' => [ManagerController::class, 'booking'],
    'GET manage-attachment' => [ManagerController::class, 'attachment'],
    'POST manage-status' => [ManagerController::class, 'updateStatus'],
    'POST manage-notes' => [ManagerController::class, 'saveNotes'],
    'POST manage-paid' => [ManagerController::class, 'markPaid'],
    'POST manage-assign' => [ManagerController::class, 'assignServer'],
    'POST manage-withdraw' => [ManagerController::class, 'withdrawServer'],
    'GET manage-venues' => [ManagerController::class, 'venues'],
    'GET manage-venue' => [ManagerController::class, 'venue'],
    'POST manage-venue-save' => [ManagerController::class, 'saveVenue'],
    'POST manage-photo-add' => [ManagerController::class, 'addPhoto'],
    'POST manage-photo-delete' => [ManagerController::class, 'deletePhoto'],
    'POST manage-blackout-add' => [ManagerController::class, 'addBlackout'],
    'POST manage-blackout-delete' => [ManagerController::class, 'deleteBlackout'],

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
// cancel the pending and approved bookings whose date arrived without getting confirmed
Booking::cancelExpired();
[$controller, $action] = $routes["$method $page"];
$controller::$action();