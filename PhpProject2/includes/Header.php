<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db_connect.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function checkRole($required_role) {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === $required_role;
}

$currentDir = basename(dirname($_SERVER['SCRIPT_NAME']));
$pathPrefix = in_array($currentDir, ['admin', 'creator'], true) ? '../' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RecipeShare | IT8415</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo $pathPrefix; ?>assets/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $pathPrefix; ?>index.php">RecipeShare</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="<?php echo $pathPrefix; ?>index.php">Home</a></li>
                    <?php if (isLoggedIn()): ?>
                        <?php if (checkRole('Creator')): ?>
                            <li class="nav-item"><a class="nav-link" href="<?php echo $pathPrefix; ?>creator/add_recipe.php">Add Recipe</a></li>
                            <li class="nav-item"><a class="nav-link" href="<?php echo $pathPrefix; ?>creator/my_recipes.php">My Recipes</a></li>
                        <?php endif; ?>
                        <?php if (checkRole('Admin')): ?>
                            <li class="nav-item"><a class="nav-link" href="<?php echo $pathPrefix; ?>admin/index.php">Admin Panel</a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $pathPrefix; ?>logout.php">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $pathPrefix; ?>login.php">Login</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $pathPrefix; ?>register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <main class="container mt-4 flex-grow-1">
