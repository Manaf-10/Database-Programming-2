<?php
require_once '../includes/db_connect.php';
require_once '../includes/Header.php';

if (!checkRole('Creator')) {
    header('Location: ../login.php');
    exit;
}

$recipeId = (int) ($_GET['id'] ?? 0);
$userId = (int) $_SESSION['user_id'];

// Load existing recipe data and verify ownership
$fetchStmt = $mysqli->prepare('SELECT * FROM dbProj_Recipes WHERE RecipeID = ? AND UserID = ?');
$fetchStmt->bind_param('ii', $recipeId, $userId);
$fetchStmt->execute();
$recipe = $fetchStmt->get_result()->fetch_assoc();

if (!$recipe) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>Recipe not found or access denied.</div></div>";
    require_once '../includes/Footer.php';
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

    $imagePath = $recipe['ImagePath'] ?? 'default.jpg';
    $videoPath = $recipe['VideoPath'] ?? null;

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

        // Image upload replacement
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowedImages = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($extension, $allowedImages, true)) {
                // Delete previous custom image if present
                if ($recipe['ImagePath'] !== 'default.jpg' && file_exists('../uploads/' . $recipe['ImagePath'])) {
                    @unlink('../uploads/' . $recipe['ImagePath']);
                }
                $imagePath = time() . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', basename($_FILES['image']['name']));
                move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/' . $imagePath);
            }
        }

        // Video upload replacement
        if (!empty($_FILES['video']['name']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
            $extension = strtolower(pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION));
            $allowedVideos = ['mp4', 'webm', 'mov'];
            if (in_array($extension, $allowedVideos, true)) {
                // Delete previous video if present
                if (!empty($recipe['VideoPath']) && file_exists('../uploads/' . $recipe['VideoPath'])) {
                    @unlink('../uploads/' . $recipe['VideoPath']);
                }
                $videoPath = time() . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', basename($_FILES['video']['name']));
                move_uploaded_file($_FILES['video']['tmp_name'], '../uploads/' . $videoPath);
            }
        }

        $updateStmt = $mysqli->prepare('
            UPDATE dbProj_Recipes 
            SET Title = ?, Description = ?, Ingredients = ?, Instructions = ?, ImagePath = ?, VideoPath = ?, Category = ?, Status = ? 
            WHERE RecipeID = ? AND UserID = ?
        ');
        $updateStmt->bind_param('ssssssssii', $title, $description, $ingredients, $instructions, $imagePath, $videoPath, $category, $status, $recipeId, $userId);

        if ($updateStmt->execute()) {
            $message = 'Recipe updated successfully.';

            // Sync local array values to display updated info in fields
            $recipe['Title'] = $title;
            $recipe['Description'] = $description;
            $recipe['Ingredients'] = $ingredients;
            $recipe['Instructions'] = $instructions;
            $recipe['Category'] = $category;
            $recipe['Status'] = $status;
            $recipe['ImagePath'] = $imagePath;
            $recipe['VideoPath'] = $videoPath;
        } else {
            $error = 'Unable to save recipe changes.';
        }
    }
}
?>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="mb-3">
                <a href="my_recipes.php" class="text-success text-decoration-none small fw-bold">&larr; Back to Dashboard</a>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title mb-0 fw-bold text-success-emphasis">Edit Recipe</h5>
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
                            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($recipe['Title'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Description</label>
                            <textarea name="description" class="form-control" rows="3" required><?php echo htmlspecialchars($recipe['Description'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Ingredients (Optional)</label>
                            <textarea name="ingredients" class="form-control" rows="4"><?php echo htmlspecialchars($recipe['Ingredients'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Instructions (Optional)</label>
                            <textarea name="instructions" class="form-control" rows="5"><?php echo htmlspecialchars($recipe['Instructions'] ?? ''); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-secondary">Category</label>
                                <select name="category" class="form-select" required>
                                    <option value="" disabled>Select a Category</option>
                                    <?php
                                    $categories = ['Breakfast', 'Lunch', 'Dinner', 'Dessert', 'Vegan', 'Snacks', 'Drinks'];
                                    foreach ($categories as $cat) {
                                        $selected = (($recipe['Category'] ?? '') === $cat) ? 'selected' : '';
                                        echo '<option value="' . $cat . '" ' . $selected . '>' . $cat . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-secondary">Status</label>
                                <select name="status" class="form-select">
                                    <option value="Draft" <?php echo ($recipe['Status'] ?? 'Draft') === 'Draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="Published" <?php echo ($recipe['Status'] ?? 'Draft') === 'Published' ? 'selected' : ''; ?>>Published</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Image (leave empty to keep current image)</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                            <?php if (($recipe['ImagePath'] ?? 'default.jpg') !== 'default.jpg'): ?>
                                <div class="mt-2 text-muted small">
                                    Current Image: <code><?php echo htmlspecialchars($recipe['ImagePath'] ?? ''); ?></code>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Video (leave empty to keep current video)</label>
                            <input type="file" name="video" class="form-control" accept="video/*">
                            <?php if (!empty($recipe['VideoPath'])): ?>
                                <div class="mt-2 text-muted small">
                                    Current Video: <code><?php echo htmlspecialchars($recipe['VideoPath'] ?? ''); ?></code>
                                </div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn btn-success px-4 fw-bold mt-2">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php require_once '../includes/Footer.php'; ?>