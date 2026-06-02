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

$stmt = $mysqli->prepare(
    'INSERT INTO dbProj_Ratings (RecipeID, UserID, RatingValue)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE RatingValue = VALUES(RatingValue)'
);
$stmt->bind_param('iii', $recipeId, $userId, $rating);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Rating saved.', 'rating' => $rating]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>
