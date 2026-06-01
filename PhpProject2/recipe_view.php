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

$stmt = $mysqli->prepare('UPDATE dbProj_Recipes SET Views = Views + 1 WHERE RecipeID = ?');
$stmt->bind_param('i', $id);
$stmt->execute();

$stmt = $mysqli->prepare('SELECT r.*, u.Username FROM dbProj_Recipes r JOIN dbProj_Users u ON r.UserID = u.UserID WHERE r.RecipeID = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$recipe = $stmt->get_result()->fetch_assoc();

if (!$recipe) {
    echo "<div class='alert alert-warning'>Recipe not found.</div>";
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

    return '<p>' . nl2br(htmlspecialchars($content)) . '</p>';
}
?>

<div class="row">
    <div class="col-md-8">
        <?php if (isset($statusMessage)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($statusMessage); ?></div>
        <?php endif; ?>
        <h2><?php echo htmlspecialchars($recipe['Title']); ?></h2>
        <p class="text-muted">
            By <?php echo htmlspecialchars($recipe['Username']); ?> |
            <?php echo (int) $recipe['Views']; ?> views |
            Category: <span class="badge bg-success"><?php echo htmlspecialchars($recipe['Category']); ?></span> |
            Status: <strong><?php echo htmlspecialchars($recipe['Status']); ?></strong>
        </p>

        <?php if ($canEdit): ?>
            <form method="POST" class="mb-3 p-3 border rounded bg-light">
                <label class="form-label mb-0"><strong>Change Status:</strong></label>
                <select name="new_status" class="form-select d-inline-block w-auto mx-2">
                    <option value="Draft" <?php echo $recipe['Status'] === 'Draft' ? 'selected' : ''; ?>>Draft</option>
                    <option value="Published" <?php echo $recipe['Status'] === 'Published' ? 'selected' : ''; ?>>Published</option>
                </select>
                <button type="submit" name="update_status" class="btn btn-sm btn-warning">Update</button>
            </form>
        <?php endif; ?>
        <div class="recipe-image-container mb-4">
            <?php if ($hasImage): ?>
                <img src="uploads/<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($recipe['Title']); ?>">
            <?php else: ?>
                <span class="text-muted">No image provided</span>
            <?php endif; ?>
        </div>

        <?php if (!empty($recipe['VideoPath'])): ?>
            <video controls class="w-100 rounded mb-4">
                <source src="uploads/<?php echo htmlspecialchars($recipe['VideoPath']); ?>">
            </video>
        <?php endif; ?>

        <section class="mb-4">
            <h4 class="mb-3">Description</h4>
            <?php echo renderRecipeContent($recipe['Description'] ?? ''); ?>
        </section>

        <section class="mb-4">
            <h4 class="mb-3">Ingredients</h4>
            <?php echo renderRecipeContent($recipe['Ingredients'] ?? ''); ?>
        </section>

        <section class="mb-4">
            <h4 class="mb-3">Instructions</h4>
            <?php echo renderRecipeContent($recipe['Instructions'] ?? ''); ?>
        </section>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Rate this Recipe</div>
            <div class="card-body text-center">
                <?php if (isLoggedIn()): ?>
                    <div class="star-rating" id="rating-container" data-user-rating="<?php echo (int) $currentUserRating; ?>">
                        <span data-value="1" class="star">&#9733;</span>
                        <span data-value="2" class="star">&#9733;</span>
                        <span data-value="3" class="star">&#9733;</span>
                        <span data-value="4" class="star">&#9733;</span>
                        <span data-value="5" class="star">&#9733;</span>
                    </div>
                    <input type="hidden" id="recipe_id" value="<?php echo (int) $recipe['RecipeID']; ?>">
                    <p id="rating-msg" class="mt-2"></p>
                <?php else: ?>
                    <p><a href="login.php">Login</a> to rate.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">Comments</div>
            <div class="card-body">
                <?php
                $commentStmt = $mysqli->prepare('SELECT c.CommentText, c.CreatedAt, u.Username FROM dbProj_Comments c JOIN dbProj_Users u ON c.UserID = u.UserID WHERE c.RecipeID = ? ORDER BY c.CreatedAt DESC');
                $commentStmt->bind_param('i', $id);
                $commentStmt->execute();
                $comments = $commentStmt->get_result();

                while ($comment = $comments->fetch_assoc()):
                ?>
                    <div class="border-bottom mb-2 pb-2">
                        <strong><?php echo htmlspecialchars($comment['Username']); ?></strong>
                        <small class="text-muted"><?php echo htmlspecialchars($comment['CreatedAt']); ?></small>
                        <p class="mb-0"><?php echo htmlspecialchars($comment['CommentText']); ?></p>
                    </div>
                <?php endwhile; ?>

                <?php if (isLoggedIn()): ?>
                    <form action="actions/post_comment.php" method="POST" class="mt-3">
                        <input type="hidden" name="recipe_id" value="<?php echo $id; ?>">
                        <div class="mb-3">
                            <textarea name="comment" class="form-control" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-success btn-sm">Post Comment</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/Footer.php'; ?>
