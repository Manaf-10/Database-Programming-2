<?php
require_once '../includes/db_connect.php';
require_once '../includes/Header.php';

// Allow access to both Creators and Admins
if (!isLoggedIn() || ($_SESSION['role'] !== 'Creator' && $_SESSION['role'] !== 'Admin')) {
    header('Location: ../login.php');
    exit;
}

$recipeId = (int) ($_GET['id'] ?? 0);
$userId = (int) $_SESSION['user_id'];
$isAdmin = ($_SESSION['role'] === 'Admin');

// Debugging logs array
$debugLogs = [];
$debugLogs[] = "Current Script Location (__DIR__): " . __DIR__;

// Load recipe data: Admins can load any recipe, Creators can only load their own
if ($isAdmin) {
    $fetchStmt = $mysqli->prepare('SELECT * FROM dbProj_Recipes WHERE RecipeID = ?');
    $fetchStmt->bind_param('i', $recipeId);
} else {
    $fetchStmt = $mysqli->prepare('SELECT * FROM dbProj_Recipes WHERE RecipeID = ? AND UserID = ?');
    $fetchStmt->bind_param('ii', $recipeId, $userId);
}

$fetchStmt->execute();
$recipe = $fetchStmt->get_result()->fetch_assoc();

if (!$recipe) {
    echo "<div class='container mt-4'><div class='alert alert-danger shadow-sm'>Recipe not found or access denied.</div></div>";
    require_once '../includes/Footer.php';
    exit;
}

$imagePath = trim($recipe['ImagePath'] ?? '');
$hasImage = $imagePath !== '' && $imagePath !== 'default.jpg';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $debugLogs[] = "Form submitted via POST.";

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $ingredients = trim($_POST['ingredients'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $status = $_POST['status'] ?? 'Draft';

    $allowedCategories = ['Breakfast', 'Lunch', 'Dinner', 'Dessert', 'Vegan', 'Snacks', 'Drinks'];
    $allowedStatuses = ['Draft', 'Published'];

    $imagePath = $recipe['ImagePath'] ?? 'default.jpg';

    if ($title === '' || $description === '' || $category === '') {
        $error = 'Title, description, and category are required.';
        $debugLogs[] = "Validation failed: Title, description, or category was empty.";
    } elseif (!in_array($category, $allowedCategories, true)) {
        $error = 'Invalid category selected.';
        $debugLogs[] = "Validation failed: Category not in allowed list.";
    } elseif (!in_array($status, $allowedStatuses, true)) {
        $error = 'Invalid status.';
        $debugLogs[] = "Validation failed: Status not in allowed list.";
    } else {
        // Step up one directory level cleanly to get the correct absolute path
        $uploadDir = dirname(__DIR__) . '/uploads/';
        $debugLogs[] = "Target uploads directory path: " . $uploadDir;
        $debugLogs[] = "Does target directory exist? " . (is_dir($uploadDir) ? "YES" : "NO");
        $debugLogs[] = "Is target directory writable by PHP? " . (is_writable($uploadDir) ? "YES" : "NO");

        // Attempt to create the folder if it does not exist
        if (!is_dir($uploadDir)) {
            $debugLogs[] = "Directory does not exist. Attempting mkdir()...";
            $created = @mkdir($uploadDir, 0775, true);
            $debugLogs[] = "Result of mkdir(): " . ($created ? "SUCCESS" : "FAILED");
        }

        // Image upload replacement
        if (!empty($_FILES['image']['name'])) {
            $debugLogs[] = "Image file found in $_FILES payload: " . $_FILES['image']['name'];
            $debugLogs[] = "System Upload Error Code: " . $_FILES['image']['error'];
            $debugLogs[] = "Temporary PHP file path (tmp_name): " . $_FILES['image']['tmp_name'];
            $debugLogs[] = "Uploaded file size: " . $_FILES['image']['size'] . " bytes";

            if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowedImages = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if (in_array($extension, $allowedImages, true)) {
                    // Delete previous custom image if present
                    if ($recipe['ImagePath'] !== 'default.jpg' && file_exists($uploadDir . $recipe['ImagePath'])) {
                        $deletedOld = @unlink($uploadDir . $recipe['ImagePath']);
                        $debugLogs[] = "Attempted deletion of old cover: " . ($deletedOld ? "SUCCESS" : "FAILED");
                    }

                    $imagePath = time() . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', basename($_FILES['image']['name']));
                    $targetFilePath = $uploadDir . $imagePath;
                    $debugLogs[] = "Generated new image name: " . $imagePath;
                    $debugLogs[] = "Full destination path: " . $targetFilePath;

                    // Execute file move
                    $moved = move_uploaded_file($_FILES['image']['tmp_name'], $targetFilePath);
                    $debugLogs[] = "move_uploaded_file() execution result: " . ($moved ? "SUCCESS" : "FAILED");

                    if (!$moved) {
                        $debugLogs[] = "ERROR: move_uploaded_file failed. This is typically a directory permission restriction (chmod 755/777 on /uploads).";
                    }
                } else {
                    $debugLogs[] = "File extension rejected: ." . $extension;
                }
            }
        } else {
            $debugLogs[] = "No image file selected for upload.";
        }

        // Admins update globally, Creators update owned rows without video parameter column
        if ($isAdmin) {
            $updateStmt = $mysqli->prepare('
                UPDATE dbProj_Recipes 
                SET Title = ?, Description = ?, Ingredients = ?, Instructions = ?, ImagePath = ?, Category = ?, Status = ? 
                WHERE RecipeID = ?
            ');
            $updateStmt->bind_param('sssssssi', $title, $description, $ingredients, $instructions, $imagePath, $category, $status, $recipeId);
        } else {
            $updateStmt = $mysqli->prepare('
                UPDATE dbProj_Recipes 
                SET Title = ?, Description = ?, Ingredients = ?, Instructions = ?, ImagePath = ?, Category = ?, Status = ? 
                WHERE RecipeID = ? AND UserID = ?
            ');
            $updateStmt->bind_param('sssssssii', $title, $description, $ingredients, $instructions, $imagePath, $category, $status, $recipeId, $userId);
        }

        if ($updateStmt->execute()) {
            $message = 'Recipe updated successfully.';
            $debugLogs[] = "Database update completed successfully.";

            // Sync local array values to display updated info in fields
            $recipe['Title'] = $title;
            $recipe['Description'] = $description;
            $recipe['Ingredients'] = $ingredients;
            $recipe['Instructions'] = $instructions;
            $recipe['Category'] = $category;
            $recipe['Status'] = $status;
            $recipe['ImagePath'] = $imagePath;

            // Re-sync visual properties
            $imagePath = $imagePath;
            $hasImage = $imagePath !== '' && $imagePath !== 'default.jpg';
        } else {
            $error = 'Unable to save recipe changes.';
            $debugLogs[] = "Database update execution failed: " . htmlspecialchars($updateStmt->error);
        }
    }
}
?>

    <div class="mb-3">
        <?php if ($isAdmin): ?>
            <a href="../admin/index.php" class="text-success text-decoration-none small fw-bold">&larr; Back to Admin Dashboard</a>
        <?php else: ?>
            <a href="my_recipes.php" class="text-success text-decoration-none small fw-bold">&larr; Back to Dashboard</a>
        <?php endif; ?>
    </div>

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
                    <input type="text" name="title" class="form-control form-control-lg fw-bold" value="<?php echo htmlspecialchars($recipe['Title'] ?? ''); ?>" required>
                </div>

                <!-- Styled Cover Image upload container with preview thumbnail -->
                <div class="recipe-image-container mb-4 position-relative border-2 border-dashed bg-light p-4 rounded text-center d-flex align-items-center justify-content-center" style="min-height: 220px; border: 2px dashed #ced4da;">
                    <div class="py-2 w-100">
                        <!-- Preview container handles both DB images and instant client side file changes -->
                        <div id="image-preview-container" class="<?php echo ($hasImage && file_exists(dirname(__DIR__) . '/uploads/' . $imagePath)) ? '' : 'd-none'; ?> mb-3 mx-auto" style="max-width: 140px; height: 90px; overflow: hidden; border-radius: 8px; border: 1px solid #dee2e6;">
                            <img id="image-preview" src="<?php echo $hasImage ? '../uploads/' . htmlspecialchars($imagePath) : ''; ?>" alt="Cover preview" class="w-100 h-100" style="object-fit: cover;">
                        </div>

                        <div id="upload-placeholder-icon" class="<?php echo ($hasImage && file_exists(dirname(__DIR__) . '/uploads/' . $imagePath)) ? 'd-none' : ''; ?>">
                            <svg class="text-muted mb-2" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                                <circle cx="8.5" cy="8.5" r="1.5" />
                                <polyline points="21 15 16 10 5 21" />
                            </svg>
                        </div>

                        <p id="upload-text-label" class="mb-1 text-secondary small fw-bold">
                            <?php echo $hasImage ? 'Replace cover image' : 'Upload cover image'; ?>
                        </p>
                        <p class="text-muted extra-small-date mb-3">Accepts jpg, png, or webp formats</p>
                        <input type="file" name="image" id="recipe-image-input" class="form-control form-control-sm w-auto mx-auto" accept="image/*">
                    </div>
                </div>

                <!-- Structured content blocks matching recipe_view.php design -->
                <div class="recipe-instructions-card p-4 mb-4">
                    <section class="mb-4">
                        <h4 class="section-title mb-3">Description</h4>
                        <textarea name="description" class="form-control" rows="3" required><?php echo htmlspecialchars($recipe['Description'] ?? ''); ?></textarea>
                    </section>

                    <section class="mb-4">
                        <h4 class="section-title mb-3">Ingredients (Optional)</h4>
                        <textarea name="ingredients" class="form-control" rows="5"><?php echo htmlspecialchars($recipe['Ingredients'] ?? ''); ?></textarea>
                    </section>

                    <section class="mb-0">
                        <h4 class="section-title mb-3">Instructions (Optional)</h4>
                        <textarea name="instructions" class="form-control" rows="6"><?php echo htmlspecialchars($recipe['Instructions'] ?? ''); ?></textarea>
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
                                <option value="" disabled>Select Category</option>
                                <?php
                                $categories = ['Breakfast', 'Lunch', 'Dinner', 'Dessert', 'Vegan', 'Snacks', 'Drinks'];
                                foreach ($categories as $cat) {
                                    $selected = (($recipe['Category'] ?? '') === $cat) ? 'selected' : '';
                                    echo '<option value="' . $cat . '" ' . $selected . '>' . $cat . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-bold text-muted">Status</label>
                            <select name="status" class="form-select">
                                <option value="Draft" <?php echo ($recipe['Status'] ?? 'Draft') === 'Draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="Published" <?php echo ($recipe['Status'] ?? 'Draft') === 'Published' ? 'selected' : ''; ?>>Published</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="card-title mb-0 fw-bold text-success-emphasis">Publish Actions</h5>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted mb-3">Changes will update immediately. Double check your input blocks before saving your changes.</p>
                        <button type="submit" class="btn btn-success w-100 py-2 fw-bold">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Client-side script injection to print full PHP variable debug trace to browser console -->
<?php if (!empty($debugLogs)): ?>
    <script>
        console.group("PHP Image Upload Diagnostics");
        <?php foreach ($debugLogs as $log): ?>
        console.log(<?php echo json_encode($log); ?>);
        <?php endforeach; ?>
        console.groupEnd();
    </script>
<?php endif; ?>

<?php require_once '../includes/Footer.php'; ?>