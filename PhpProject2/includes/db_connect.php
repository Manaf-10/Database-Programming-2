<?php
// Local phpMyAdmin/XAMPP defaults. Update these if your deployed server uses
// different database credentials.
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'dbProj_recipe_db');

$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($mysqli->connect_error) {
    die('ERROR: Could not connect. ' . $mysqli->connect_error);
}

$mysqli->set_charset('utf8mb4');

// Compatibility alias for older files that used $conn.
$conn = $mysqli;
?>
