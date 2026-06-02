<?php
require_once '../includes/db_connect.php';
require_once '../includes/Header.php';

if (!checkRole('Creator')) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $ingredients = trim($_POST['ingredients'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $status = $_POST['status'] ?? 'Draft';

    $allowedCategories = ['Breakfast', 'Lunch', 'Dinner', 'Dessert', 'Vegan', 'Snacks', 'Drinks'];
    $allowedStatuses = ['Draft', 'Published'];
    $userId = (int) $_SESSION['user_id'];
    $imagePath = 'default.jpg';
    $videoPath = null;

    // Ingredients and instructions are now optional
    if ($title === '' || $description === '' || $category === '') {
        $error = 'Title, description, and category are required.';
    } elseif (!in_array($category, $allowedCategories, true)) {
        $error = 'Invalid category selected.';
    } elseif (!in_array($status, $allowedStatuses, true)) {
        $error = 'Invalid status.';
    } else {
        if (!is_dir('../uploads')) {
            mkdir('../uploads', 0775, true);
        }

        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowedImages = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($extension, $allowedImages, true)) {
                $imagePath = time() . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', basename($_FILES['image']['name']));
                move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/' . $imagePath);
            }
        }

        if (!empty($_FILES['video']['name']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
            $extension = strtolower(pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION));
            $allowedVideos = ['mp4', 'webm', 'mov'];
            if (in_array($extension, $allowedVideos, true)) {
                $videoPath = time() . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', basename($_FILES['video']['name']));
                move_uploaded_file($_FILES['video']['tmp_name'], '../uploads/' . $videoPath);
            }
        }

        $stmt = $mysqli->prepare('INSERT INTO dbProj_Recipes (UserID, Title, Description, Ingredients, Instructions, ImagePath, VideoPath, Category, Status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('issssssss', $userId, $title, $description, $ingredients, $instructions, $imagePath, $videoPath, $category, $status);

        if ($stmt->execute()) {
            $message = 'Recipe saved successfully.';
        } else {
            $error = 'Unable to save recipe.';
        }
    }
}
?>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title mb-0 fw-bold text-success-emphasis">Add Recipe</h5>
                </div>
                <div class="card-body p-4">
                    <?php if (isset($message)): ?>
                        <div class="alert alert-success shadow-sm"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger shadow-sm"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Title</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Description</label>
                            <textarea name="description" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Ingredients (Optional)</label>
                            <textarea name="ingredients" class="form-control" rows="4"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Instructions (Optional)</label>
                            <textarea name="instructions" class="form-control" rows="5"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-secondary">Category</label>
                                <select name="category" class="form-select" required>
                                    <option value="" disabled selected>Select a Category</option>
                                    <option value="Breakfast">Breakfast</option>
                                    <option value="Lunch">Lunch</option>
                                    <option value="Dinner">Dinner</option>
                                    <option value="Dessert">Dessert</option>
                                    <option value="Vegan">Vegan</option>
                                    <option value="Snacks">Snacks</option>
                                    <option value="Drinks">Drinks</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-secondary">Status</label>
                                <select name="status" class="form-select">
                                    <option value="Draft">Draft</option>
                                    <option value="Published">Published</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Video</label>
                            <input type="file" name="video" class="form-control" accept="video/*">
                        </div>
                        <button type="submit" class="btn btn-success px-4 fw-bold mt-2">Save Recipe</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php require_once '../includes/Footer.php'; ?><?php
require_once '../includes/db_connect.php';
require_once '../includes/Header.php';

if (!checkRole('Creator')) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $ingredients = trim($_POST['ingredients'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $status = $_POST['status'] ?? 'Draft';

    $allowedCategories = ['Breakfast', 'Lunch', 'Dinner', 'Dessert', 'Vegan', 'Snacks', 'Drinks'];
    $allowedStatuses = ['Draft', 'Published'];
    $userId = (int) $_SESSION['user_id'];
    $imagePath = 'default.jpg';
    $videoPath = null;

    // Only title, description, and category are required
    if ($title === '' || $description === '' || $category === '') {
        $error = 'Title, description, and category are required.';
    } elseif (!in_array($category, $allowedCategories, true)) {
        $error = 'Invalid category selected.';
    } elseif (!in_array($status, $allowedStatuses, true)) {
        $error = 'Invalid status.';
    } else {
        if (!is_dir('../uploads')) {
            mkdir('../uploads', 0775, true);
        }

        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowedImages = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($extension, $allowedImages, true)) {
                $imagePath = time() . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', basename($_FILES['image']['name']));
                move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/' . $imagePath);
            }
        }

        if (!empty($_FILES['video']['name']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
            $extension = strtolower(pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION));
            $allowedVideos = ['mp4', 'webm', 'mov'];
            if (in_array($extension, $allowedVideos, true)) {
                $videoPath = time() . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', basename($_FILES['video']['name']));
                move_uploaded_file($_FILES['video']['tmp_name'], '../uploads/' . $videoPath);
            }
        }

        $stmt = $mysqli->prepare('INSERT INTO dbProj_Recipes (UserID, Title, Description, Ingredients, Instructions, ImagePath, VideoPath, Category, Status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('issssssss', $userId, $title, $description, $ingredients, $instructions, $imagePath, $videoPath, $category, $status);

        if ($stmt->execute()) {
            $message = 'Recipe saved successfully.';
        } else {
            $error = 'Unable to save recipe.';
        }
    }
}
?>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title mb-0 fw-bold text-success-emphasis">Add Recipe</h5>
                </div>
                <div class="card-body p-4">
                    <?php if (isset($message)): ?>
                        <div class="alert alert-success shadow-sm"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger shadow-sm"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Title</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Description</label>
                            <textarea name="description" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Ingredients (Optional)</label>
                            <textarea name="ingredients" class="form-control" rows="4"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Instructions (Optional)</label>
                            <textarea name="instructions" class="form-control" rows="5"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-secondary">Category</label>
                                <select name="category" class="form-select" required>
                                    <option value="" disabled selected>Select a Category</option>
                                    <option value="Breakfast">Breakfast</option>
                                    <option value="Lunch">Lunch</option>
                                    <option value="Dinner">Dinner</option>
                                    <option value="Dessert">Dessert</option>
                                    <option value="Vegan">Vegan</option>
                                    <option value="Snacks">Snacks</option>
                                    <option value="Drinks">Drinks</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-secondary">Status</label>
                                <select name="status" class="form-select">
                                    <option value="Draft">Draft</option>
                                    <option value="Published">Published</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Video</label>
                            <input type="file" name="video" class="form-control" accept="video/*">
                        </div>
                        <button type="submit" class="btn btn-success px-4 fw-bold mt-2">Save Recipe</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php require_once '../includes/Footer.php'; ?>