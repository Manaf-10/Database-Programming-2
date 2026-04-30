<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "dbProj_recipe_db"; // Ensure you use the mandatory prefix

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>