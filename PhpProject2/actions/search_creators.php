<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

$term = trim($_GET['term'] ?? '');

if ($term === '') {
    echo json_encode([]);
    exit;
}

$search = "%" . $term . "%";

// Queries matches across all registered users regardless of their current role
$stmt = $mysqli->prepare('SELECT UserID AS id, Username AS username FROM dbProj_Users WHERE Username LIKE ? LIMIT 10');
$stmt->bind_param('s', $search);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = [
        'id' => (int) $row['id'],
        'username' => $row['username']
    ];
}

header('Content-Type: application/json');
echo json_encode($users);