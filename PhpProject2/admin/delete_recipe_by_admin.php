<?php
session_start();
require_once '../includes/db_connect.php';

// Verify authentication and administrator role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../login.php');
    exit;
}

$recipeId = (int) ($_GET['id'] ?? 0);

if ($recipeId > 0) {
    // Retrieve associated assets to delete them from server storage
    $fileStmt = $mysqli->prepare('SELECT ImagePath, VideoPath FROM dbProj_Recipes WHERE RecipeID = ?');
    $fileStmt->bind_param('i', $recipeId);
    $fileStmt->execute();
    $recipeFiles = $fileStmt->get_result()->fetch_assoc();

    if ($recipeFiles) {
        $deleteStmt = $mysqli->prepare('DELETE FROM dbProj_Recipes WHERE RecipeID = ?');
        $deleteStmt->bind_param('i', $recipeId);

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

header('Location: index.php');
exit;