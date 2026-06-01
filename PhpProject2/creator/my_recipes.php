<?php
require_once '../includes/Header.php';

if (!checkRole('Creator')) {
    header('Location: ../login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$stmt = $mysqli->prepare('SELECT RecipeID, Title, Category, Status, Views, CreatedAt FROM dbProj_Recipes WHERE UserID = ? ORDER BY CreatedAt DESC');
$stmt->bind_param('i', $userId);
$stmt->execute();
$recipes = $stmt->get_result();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0">My Recipes</h2>
    <a href="add_recipe.php" class="btn btn-success btn-sm">Add Recipe</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Views</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php while ($recipe = $recipes->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($recipe['Title']); ?></td>
                        <td><?php echo htmlspecialchars($recipe['Category'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($recipe['Status']); ?></td>
                        <td><?php echo (int) $recipe['Views']; ?></td>
                        <td><?php echo htmlspecialchars($recipe['CreatedAt']); ?></td>
                        <td><a href="../recipe_view.php?id=<?php echo (int) $recipe['RecipeID']; ?>" class="btn btn-outline-primary btn-sm">View</a></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/Footer.php'; ?>
