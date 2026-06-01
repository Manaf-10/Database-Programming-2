<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$recipeId = (int) ($_POST['recipe_id'] ?? 0);
$comment = trim($_POST['comment'] ?? '');
$userId = (int) $_SESSION['user_id'];

if ($recipeId > 0 && $comment !== '') {
    $stmt = $mysqli->prepare('INSERT INTO dbProj_Comments (RecipeID, UserID, CommentText) VALUES (?, ?, ?)');
    $stmt->bind_param('iis', $recipeId, $userId, $comment);
    $stmt->execute();
}

header('Location: ../recipe_view.php?id=' . $recipeId);
exit;
?>
