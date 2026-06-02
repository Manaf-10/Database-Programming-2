<?php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function checkRole($required_role) {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === $required_role;
}

if (!checkRole('Admin')) {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

$term = trim($_GET['term'] ?? '');

if ($term === '') {
    echo json_encode([]);
    exit;
}

$like = '%' . $term . '%';
$stmt = $mysqli->prepare("SELECT UserID, Username FROM dbProj_Users WHERE Role = 'Creator' AND Username LIKE ? ORDER BY Username LIMIT 8");
$stmt->bind_param('s', $like);
$stmt->execute();
$result = $stmt->get_result();

$creators = [];
while ($row = $result->fetch_assoc()) {
    $creators[] = [
        'id' => (int) $row['UserID'],
        'username' => $row['Username'],
    ];
}

echo json_encode($creators);
