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

    if ($title === '' || $description === '' || $category === '') {
        $error = 'Title, description, and category are required.';
    } elseif (!in_array($category, $allowedCategories, true)) {
        $error = 'Invalid category selected.';
    } elseif (!in_array($status, $allowedStatuses, true)) {
        $error = 'Invalid status.';
    } else {
        // Step up one directory level cleanly to get the correct absolute path
        $uploadDir = dirname(__DIR__) . '/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowedImages = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($extension, $allowedImages, true)) {
                $imagePath = time() . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', basename($_FILES['image']['name']));
                move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imagePath);
            }
        }

        $stmt = $mysqli->prepare('INSERT INTO dbProj_Recipes (UserID, Title, Description, Ingredients, Instructions, ImagePath, Category, Status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('isssssss', $userId, $title, $description, $ingredients, $instructions, $imagePath, $category, $status);

        if ($stmt->execute()) {
            $message = 'Recipe saved successfully.';
        } else {
            $error = 'Unable to save recipe.';
        }
    }
}
?>

    <form method="POST" enctype="multipart/form-data" class="mt-2">
        <div class="row g-4">
            <!-- Left Column: Interactive view components -->
            <div class="col-lg-8">
                <?php if (isset($message)): ?>
                    <div class="alert alert-success shadow-sm"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger shadow-sm"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <!-- Recipe Title Field -->
                <div class="mb-4">
                    <label class="form-label small fw-bold text-success-emphasis text-uppercase tracking-wider">Recipe Title</label>
                    <input type="text" name="title" class="form-control form-control-lg fw-bold" placeholder="e.g., Burnt Basque Cheesecake" required>
                </div>

                <!-- Styled Cover Image upload container with preview capability -->
                <div class="recipe-image-container mb-4 position-relative border-2 border-dashed bg-light p-4 rounded text-center d-flex align-items-center justify-content-center" style="min-height: 220px; border: 2px dashed #ced4da;">
                    <div class="py-2 w-100">
                        <!-- Preview container hidden on init -->
                        <div id="image-preview-container" class="d-none mb-3 mx-auto" style="max-width: 140px; height: 90px; overflow: hidden; border-radius: 8px; border: 1px solid #dee2e6;">
                            <img id="image-preview" src="" alt="Cover preview" class="w-100 h-100" style="object-fit: cover;">
                        </div>

                        <div id="upload-placeholder-icon">
                            <svg class="text-muted mb-2" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                                <circle cx="8.5" cy="8.5" r="1.5" />
                                <polyline points="21 15 16 10 5 21" />
                            </svg>
                        </div>
                        <p id="upload-text-label" class="mb-1 text-secondary small fw-bold">Upload cover image</p>
                        <p class="text-muted extra-small-date mb-3">Accepts jpg, png, or webp formats</p>
                        <input type="file" name="image" id="recipe-image-input" class="form-control form-control-sm w-auto mx-auto" accept="image/*">
                    </div>
                </div>

                <!-- Structured content blocks matching recipe_view.php design -->
                <div class="recipe-instructions-card p-4 mb-4">
                    <section class="mb-4">
                        <h4 class="section-title mb-3">Description</h4>
                        <textarea name="description" class="form-control" rows="3" placeholder="Describe the recipe history, taste, or serving ideas..." required></textarea>
                    </section>

                    <section class="mb-4">
                        <h4 class="section-title mb-3">Ingredients (Optional)</h4>
                        <textarea name="ingredients" class="form-control" rows="5" placeholder="Specify list elements, e.g.&#10;500g cream cheese&#10;1 cup sugar..."></textarea>
                    </section>

                    <section class="mb-0">
                        <h4 class="section-title mb-3">Instructions (Optional)</h4>
                        <textarea name="instructions" class="form-control" rows="6" placeholder="Describe step-by-step preparation guidelines..."></textarea>
                    </section>
                </div>
            </div>

            <!-- Right Column: Settings and actions panel -->
            <div class="col-lg-4">
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="card-title mb-0 fw-bold text-success-emphasis">Recipe Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Category</label>
                            <select name="category" class="form-select" required>
                                <option value="" disabled selected>Select Category</option>
                                <option value="Breakfast">Breakfast</option>
                                <option value="Lunch">Lunch</option>
                                <option value="Dinner">Dinner</option>
                                <option value="Dessert">Dessert</option>
                                <option value="Vegan">Vegan</option>
                                <option value="Snacks">Snacks</option>
                                <option value="Drinks">Drinks</option>
                            </select>
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-bold text-muted">Status</label>
                            <select name="status" class="form-select">
                                <option value="Draft">Draft</option>
                                <option value="Published">Published</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="card-title mb-0 fw-bold text-success-emphasis">Publish Actions</h5>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted mb-3">Review your text block sections above before saving. You can edit this layout at any time.</p>
                        <button type="submit" class="btn btn-success w-100 py-2 fw-bold">Save Recipe</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

<?php require_once '../includes/Footer.php'; ?>