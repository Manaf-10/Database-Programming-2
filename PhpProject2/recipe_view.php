<?php
require_once 'includes/Header.php';

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = (int) $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = $_POST['new_status'] ?? '';
    $allowedStatuses = ['Draft', 'Published'];

    if (in_array($newStatus, $allowedStatuses, true)) {
        $ownerStmt = $mysqli->prepare('SELECT UserID FROM dbProj_Recipes WHERE RecipeID = ?');
        $ownerStmt->bind_param('i', $id);
        $ownerStmt->execute();
        $owner = $ownerStmt->get_result()->fetch_assoc();
        $canUpdate = $owner && isLoggedIn() && ((int) $_SESSION['user_id'] === (int) $owner['UserID'] || checkRole('Admin'));

        if ($canUpdate) {
            $updateStmt = $mysqli->prepare('UPDATE dbProj_Recipes SET Status = ? WHERE RecipeID = ?');
            $updateStmt->bind_param('si', $newStatus, $id);
            if ($updateStmt->execute()) {
                $statusMessage = 'Status updated to ' . $newStatus . '.';
            }
        }
    }
}

// Increment views
$stmt = $mysqli->prepare('UPDATE dbProj_Recipes SET Views = Views + 1 WHERE RecipeID = ?');
$stmt->bind_param('i', $id);
$stmt->execute();

// Fetch recipe details including average ratings
$stmt = $mysqli->prepare('
    SELECT r.*, u.Username,
           COALESCE(AVG(rt.RatingValue), 0) AS AvgRating,
           COUNT(rt.RatingID) AS RatingCount
    FROM dbProj_Recipes r
    JOIN dbProj_Users u ON r.UserID = u.UserID
    LEFT JOIN dbProj_Ratings rt ON r.RecipeID = rt.RecipeID
    WHERE r.RecipeID = ?
    GROUP BY r.RecipeID, u.Username
');
$stmt->bind_param('i', $id);
$stmt->execute();
$recipe = $stmt->get_result()->fetch_assoc();

if (!$recipe) {
    echo "<div class='alert alert-warning my-4'>Recipe not found.</div>";
    require_once 'includes/Footer.php';
    exit;
}

$imagePath = trim($recipe['ImagePath'] ?? '');
$hasImage = $imagePath !== '' && $imagePath !== 'default.jpg';
$canEdit = isLoggedIn() && ((int) $_SESSION['user_id'] === (int) $recipe['UserID'] || checkRole('Admin'));
$currentUserRating = 0;

if (isLoggedIn()) {
    $currentUserId = (int) $_SESSION['user_id'];
    $ratingStmt = $mysqli->prepare('SELECT RatingValue FROM dbProj_Ratings WHERE RecipeID = ? AND UserID = ?');
    $ratingStmt->bind_param('ii', $id, $currentUserId);
    $ratingStmt->execute();
    $ratingRow = $ratingStmt->get_result()->fetch_assoc();
    $currentUserRating = $ratingRow ? (int) $ratingRow['RatingValue'] : 0;
}

function renderRecipeContent($content) {
    $content = trim((string) $content);

    if ($content === '') {
        return '<p class="text-muted fst-italic">No content posted here</p>';
    }

    return '<p class="recipe-text-body">' . nl2br(htmlspecialchars($content)) . '</p>';
}
?>

    <div class="row g-4 mt-2">
        <div class="col-lg-8">
            <?php if (isset($statusMessage)): ?>
                <div class="alert alert-success shadow-sm"><?php echo htmlspecialchars($statusMessage); ?></div>
            <?php endif; ?>

            <h2 class="display-6 fw-bold text-success-emphasis mb-2"><?php echo htmlspecialchars($recipe['Title']); ?></h2>

            <p class="recipe-detail-meta mb-4">
                By <strong><?php echo htmlspecialchars($recipe['Username']); ?></strong>
                <span class="meta-divider">&bull;</span>
                <span><?php echo (int) $recipe['Views']; ?> views</span>
                <span class="meta-divider">&bull;</span>
                <span class="badge badge-category"><?php echo htmlspecialchars($recipe['Category']); ?></span>
                <span class="meta-divider">&bull;</span>
                <span>Status: <strong class="text-secondary"><?php echo htmlspecialchars($recipe['Status']); ?></strong></span>
                <?php if ($recipe['RatingCount'] > 0): ?>
                    <span class="meta-divider">&bull;</span>
                    <span class="text-warning-emphasis">★ <?php echo number_format($recipe['AvgRating'], 1); ?> (<?php echo (int) $recipe['RatingCount']; ?>)</span>
                <?php endif; ?>
            </p>

            <?php if ($canEdit): ?>
                <div class="status-update-panel mb-4">
                    <form method="POST" class="d-flex align-items-center gap-2">
                        <label class="form-label mb-0 text-success-emphasis"><strong>Change Status:</strong></label>
                        <select name="new_status" class="form-select form-select-sm d-inline-block w-auto">
                            <option value="Draft" <?php echo $recipe['Status'] === 'Draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="Published" <?php echo $recipe['Status'] === 'Published' ? 'selected' : ''; ?>>Published</option>
                        </select>
                        <button type="submit" name="update_status" class="btn btn-sm btn-warning fw-semibold px-3">Update</button>
                    </form>
                </div>
            <?php endif; ?>

            <div class="recipe-image-container mb-4">
                <?php if ($hasImage): ?>
                    <img src="uploads/<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($recipe['Title']); ?>">
                <?php else: ?>
                    <div class="no-image-placeholder py-5 text-center text-muted">
                        <span>No image provided</span>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($recipe['VideoPath'])): ?>
                <div class="recipe-video-wrapper mb-4">
                    <video controls class="w-100 rounded">
                        <source src="uploads/<?php echo htmlspecialchars($recipe['VideoPath']); ?>">
                    </video>
                </div>
            <?php endif; ?>

            <div class="recipe-instructions-card p-4 mb-4">
                <section class="mb-4">
                    <h4 class="section-title mb-3">Description</h4>
                    <?php echo renderRecipeContent($recipe['Description'] ?? ''); ?>
                </section>

                <section class="mb-4">
                    <h4 class="section-title mb-3">Ingredients</h4>
                    <?php echo renderRecipeContent($recipe['Ingredients'] ?? ''); ?>
                </section>

                <section class="mb-0">
                    <h4 class="section-title mb-3">Instructions</h4>
                    <?php echo renderRecipeContent($recipe['Instructions'] ?? ''); ?>
                </section>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title mb-0 fw-bold text-success-emphasis">Rate this Recipe</h5>
                </div>
                <div class="card-body text-center py-4">
                    <?php if (isLoggedIn()): ?>
                        <div class="star-rating mb-2" id="rating-container" data-user-rating="<?php echo (int) $currentUserRating; ?>">
                            <span data-value="1" class="star">&#9733;</span>
                            <span data-value="2" class="star">&#9733;</span>
                            <span data-value="3" class="star">&#9733;</span>
                            <span data-value="4" class="star">&#9733;</span>
                            <span data-value="5" class="star">&#9733;</span>
                        </div>
                        <input type="hidden" id="recipe_id" value="<?php echo (int) $recipe['RecipeID']; ?>">
                        <p id="rating-msg" class="small mb-0"></p>
                    <?php else: ?>
                        <p class="mb-0 text-muted">Please <a href="login.php" class="text-success fw-bold text-decoration-none">login</a> to rate.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title mb-0 fw-bold text-success-emphasis">Comments</h5>
                </div>
                <div class="card-body">
                    <div class="comments-stream mb-3">
                        <?php
                        $commentStmt = $mysqli->prepare('SELECT c.CommentText, c.CreatedAt, u.Username FROM dbProj_Comments c JOIN dbProj_Users u ON c.UserID = u.UserID WHERE c.RecipeID = ? ORDER BY c.CreatedAt DESC');
                        $commentStmt->bind_param('i', $id);
                        $commentStmt->execute();
                        $comments = $commentStmt->get_result();

                        if ($comments->num_rows > 0):
                            while ($comment = $comments->fetch_assoc()):
                                ?>
                                <div class="comment-item border-bottom mb-3 pb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <strong class="text-success-emphasis small"><?php echo htmlspecialchars($comment['Username']); ?></strong>
                                        <span class="text-muted extra-small-date"><?php echo htmlspecialchars($comment['CreatedAt']); ?></span>
                                    </div>
                                    <p class="mb-0 text-secondary small text-break"><?php echo htmlspecialchars($comment['CommentText']); ?></p>
                                </div>
                            <?php
                            endwhile;
                        else:
                            ?>
                            <p class="text-muted fst-italic small text-center my-3">No comments posted yet.</p>
                        <?php endif; ?>
                    </div>

                    <?php if (isLoggedIn()): ?>
                        <form action="actions/post_comment.php" method="POST" class="mt-3">
                            <input type="hidden" name="recipe_id" value="<?php echo $id; ?>">
                            <div class="mb-2">
                                <textarea name="comment" class="form-control form-control-sm" rows="3" placeholder="Write a comment..." required></textarea>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-success btn-sm px-3 fw-bold">Post Comment</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<?php require_once 'includes/Footer.php'; ?>