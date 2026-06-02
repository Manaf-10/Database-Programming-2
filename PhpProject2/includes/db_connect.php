<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$host = 'localhost';
$user = 'u202303672';
$pass = 'w6n]l5@dH6CG0J!N';
$db   = 'db202303672';

mysqli_report(MYSQLI_REPORT_OFF);

// Create the connection object
$mysqli = new mysqli($host, $user, $pass, $db);

// Check if it failed
if ($mysqli->connect_error) {
    error_log('Database connection failed: ' . $mysqli->connect_error);
    http_response_code(503);

    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $isJsonRequest = stripos($accept, 'application/json') !== false
        || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

    if ($isJsonRequest) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Service temporarily unavailable.']);
    } else {
        require __DIR__ . '/error_page.php';
        renderErrorPage(503, 'Service Unavailable', 'We cannot connect to the database right now. Please try again later.');
    }

    exit;
}

// Set charset
$mysqli->set_charset("utf8mb4");

// Optional: compatibility for other files
$conn = $mysqli;
?>
