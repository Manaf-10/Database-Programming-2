<?php
session_start();
require_once '../includes/db_connect.php';

// Verify authentication and role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Creator') {
    header('Location: ../login.php');
    exit;
}

$recipeId = (int) ($_GET['id'] ?? 0);
$userId = (int) $_SESSION['user_id'];

if ($recipeId > 0) {
    // Fetch paths of associated files to delete them from the server filesystem
    $fileStmt = $mysqli->prepare('SELECT ImagePath, VideoPath FROM dbProj_Recipes WHERE RecipeID = ? AND UserID = ?');
    $fileStmt->bind_param('ii', $recipeId, $userId);
    $fileStmt->execute();
    $recipeFiles = $fileStmt->get_result()->fetch_assoc();

    if ($recipeFiles) {
        // Delete the database record
        $deleteStmt = $mysqli->prepare('DELETE FROM dbProj_Recipes WHERE RecipeID = ? AND UserID = ?');
        $deleteStmt->bind_param('ii', $recipeId, $userId);

        if ($deleteStmt->execute()) {
            // Delete actual files from uploads folder if they exist
            if (!empty($recipeFiles['ImagePath']) && $recipeFiles['ImagePath'] !== 'default.jpg') {
                $imageFullPath = '../uploads/' . $recipeFiles['ImagePath'];
                if (file_exists($imageFullPath)) {
                    @unlink($imageFullPath);
                }
            }
            if (!empty($recipeFiles['VideoPath'])) {
                $videoFullPath = '../uploads/' . $recipeFiles['VideoPath'];
                if (file_exists($videoFullPath)) {
                    @unlink($videoFullPath);
                }
            }
        }
    }
}

// Redirect back to the recipe list page
header('Location: my_recipes.php');
exit;