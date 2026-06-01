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
    $allowedStatuses = ['Draft', 'Published'];
    $userId = (int) $_SESSION['user_id'];
    $imagePath = 'default.jpg';
    $videoPath = null;

    if ($title === '' || $description === '' || $ingredients === '' || $instructions === '') {
        $error = 'Title, description, ingredients, and instructions are required.';
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
        <div class="card">
            <div class="card-header">Add Recipe</div>
            <div class="card-body">
                <?php if (isset($message)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ingredients</label>
                        <textarea name="ingredients" class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Instructions</label>
                        <textarea name="instructions" class="form-control" rows="5" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category</label>
                            <input type="text" name="category" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="Draft">Draft</option>
                                <option value="Published">Published</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Image</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Video</label>
                        <input type="file" name="video" class="form-control" accept="video/*">
                    </div>
                    <button type="submit" class="btn btn-success">Save Recipe</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/Footer.php'; ?>
