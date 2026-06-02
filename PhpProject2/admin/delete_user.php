<?php
session_start();
require_once '../includes/db_connect.php';

// Verify authentication and administrator role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../login.php');
    exit;
}

$targetUserId = (int) ($_GET['id'] ?? 0);
$currentAdminId = (int) ($_SESSION['user_id'] ?? 0);

// Prevent an administrator from deleting themselves
if ($targetUserId > 0 && $targetUserId !== $currentAdminId) {
    // Delete the user record. Foreign keys are expected to cascade delete, or cascade manually as needed.
    $deleteStmt = $mysqli->prepare('DELETE FROM dbProj_Users WHERE UserID = ?');
    $deleteStmt->bind_param('i', $targetUserId);
    $deleteStmt->execute();
}

header('Location: index.php');
exit;