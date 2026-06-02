<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    echo '<tr><td colspan="6" class="text-danger text-center">Access denied.</td></tr>';
    exit;
}

$creatorId = (int) ($_GET['creator_id'] ?? 0);

if ($creatorId <= 0) {
    echo '<tr><td colspan="6" class="text-muted fst-italic text-center">Select a user to view recipes.</td></tr>';
    exit;
}

$role = 'Creator';
$stmt = $mysqli->prepare(
    'SELECT r.RecipeID, r.Title, r.Category, r.Status, r.Views, r.CreatedAt
     FROM dbProj_Recipes r
     WHERE r.UserID = ?
     ORDER BY r.CreatedAt DESC'
);
$stmt->bind_param('i', $creatorId);
$stmt->execute();
$recipes = $stmt->get_result();

if ($recipes->num_rows === 0) {
    echo '<tr><td colspan="6" class="text-muted fst-italic text-center">No recipe content found for this user.</td></tr>';
    exit;
}

while ($row = $recipes->fetch_assoc()) {
    $statusClass = $row['Status'] === 'Published' ? 'bg-success' : 'bg-warning text-dark';

    echo '<tr>';
    echo '<td class="fw-semibold text-success-emphasis">' . htmlspecialchars($row['Title']) . '</td>';
    echo '<td><span class="badge badge-category">' . htmlspecialchars($row['Category']) . '</span></td>';
    echo '<td><span class="badge ' . $statusClass . '">' . htmlspecialchars($row['Status']) . '</span></td>';
    echo '<td>' . (int) $row['Views'] . '</td>';
    echo '<td class="text-muted small">' . date('M d, Y', strtotime($row['CreatedAt'])) . '</td>';
    echo '<td class="text-end">';
    echo '  <div class="action-btn-group">';
    echo '    <a href="../recipe_view.php?id=' . (int)$row['RecipeID'] . '" class="btn btn-sm btn-outline-primary">View</a>';
    echo '    <a href="../creator/edit_recipe.php?id=' . (int)$row['RecipeID'] . '" class="btn btn-sm btn-outline-warning">Edit</a>';
    echo '    <a href="delete_recipe_by_admin.php?id=' . (int)$row['RecipeID'] . '" class="btn btn-sm btn-outline-danger" onclick="return confirm(\'Are you sure you want to permanently delete this recipe?\');">Delete</a>';
    echo '  </div>';
    echo '</td>';
    echo '</tr>';
}