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
<nav class="navbar navbar-expand-lg navbar-dark bg-success py-3">
    <div class="container">
        <a class="navbar-brand fs-4" href="<?php echo $pathPrefix; ?>index.php">RecipeShare</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item"><a class="nav-link me-2" href="<?php echo $pathPrefix; ?>index.php">Home</a></li>

                <?php if (isLoggedIn()): ?>
                    <?php if (checkRole('Creator')): ?>
                        <li class="nav-item"><a class="nav-link me-2" href="<?php echo $pathPrefix; ?>creator/add_recipe.php">Add Recipe</a></li>
                    <?php endif; ?>

                    <!-- User Dropdown Menu -->
                    <li class="nav-item dropdown ms-lg-2 mt-2 mt-lg-0">
                        <a class="nav-link dropdown-toggle user-badge-dropdown" href="#" id="userNavbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <svg class="me-1" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="vertical-align: -2px;">
                                <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0zM0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
                            </svg>
                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" aria-labelledby="userNavbarDropdown">
                            <?php if (checkRole('Admin')): ?>
                                <li><a class="dropdown-item fw-bold text-danger" href="<?php echo $pathPrefix; ?>admin/index.php">Admin Panel</a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <?php if (checkRole('Creator')): ?>
                                <li><a class="dropdown-item" href="<?php echo $pathPrefix; ?>creator/my_recipes.php">My Recipes</a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item text-muted" href="<?php echo $pathPrefix; ?>logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <!-- Logged Out Action Buttons -->
                    <li class="nav-item mt-2 mt-lg-0">
                        <a class="nav-link nav-btn-login px-3 mx-lg-1" href="<?php echo $pathPrefix; ?>login.php">Login</a>
                    </li>
                    <li class="nav-item mt-2 mt-lg-0">
                        <a class="nav-link nav-btn-register px-3 mx-lg-1 text-center" href="<?php echo $pathPrefix; ?>register.php">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
        </div>
    </nav>
    <main class="container mt-4 flex-grow-1">
