<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

$host = 'localhost';
$user = 'u202303672';
$pass = 'w6n]l5@dH6CG0J!N';
$db   = 'db202303672';

// Create the connection object
$mysqli = new mysqli($host, $user, $pass, $db);

// Check if it failed
if ($mysqli->connect_error) {
    die("Connect Error: " . $mysqli->connect_error);
}

// Set charset
$mysqli->set_charset("utf8mb4");

// Optional: compatibility for other files
$conn = $mysqli;
?>