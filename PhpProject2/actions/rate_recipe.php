<?php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to rate.']);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$recipeId = (int) ($_POST['recipe_id'] ?? 0);
$rating = (int) ($_POST['rating'] ?? 0);

if ($recipeId <= 0 || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid rating.']);
    exit;
}

$check = $mysqli->prepare('SELECT RatingID FROM dbProj_Ratings WHERE RecipeID = ? AND UserID = ?');
$check->bind_param('ii', $recipeId, $userId);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    $stmt = $mysqli->prepare('UPDATE dbProj_Ratings SET RatingValue = ? WHERE RecipeID = ? AND UserID = ?');
    $stmt->bind_param('iii', $rating, $recipeId, $userId);
} else {
    $stmt = $mysqli->prepare('INSERT INTO dbProj_Ratings (RecipeID, UserID, RatingValue) VALUES (?, ?, ?)');
    $stmt->bind_param('iii', $recipeId, $userId, $rating);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Thank you for rating.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>
