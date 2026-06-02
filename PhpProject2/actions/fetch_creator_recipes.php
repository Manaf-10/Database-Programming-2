<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    echo '<tr><td colspan="5" class="text-danger">Access denied.</td></tr>';
    exit;
}

$creatorId = (int) ($_GET['creator_id'] ?? 0);

if ($creatorId <= 0) {
    echo '<tr><td colspan="5" class="text-muted fst-italic">Select a creator to view recipes.</td></tr>';
    exit;
}

$role = 'Creator';
$stmt = $mysqli->prepare(
    'SELECT r.Title, r.Category, r.Status, r.Views, r.CreatedAt, r.Category
     FROM dbProj_Recipes r
     JOIN dbProj_Users u ON r.UserID = u.UserID
     WHERE r.UserID = ? AND u.Role = ?
     ORDER BY r.CreatedAt DESC'
);
$stmt->bind_param('is', $creatorId, $role);
$stmt->execute();
$recipes = $stmt->get_result();

if ($recipes->num_rows === 0) {
    echo '<tr><td colspan="5" class="text-muted fst-italic">No creator content found.</td></tr>';
    exit;
}

while ($row = $recipes->fetch_assoc()) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($row['Title']) . '</td>';
    echo '<td>' . htmlspecialchars($row['Category']) . '</td>';
    echo '<td>' . htmlspecialchars($row['Status']) . '</td>';
    echo '<td>' . (int) $row['Views'] . '</td>';
    echo '<td>' . date('M d, Y', strtotime($row['CreatedAt'])) . '</td>';
    echo '</tr>';
}
