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
        <h2 class="h4 mb-0 fw-bold text-success-emphasis">My Recipes</h2>
        <a href="add_recipe.php" class="btn btn-success btn-sm fw-bold px-3">Add Recipe</a>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th class="ps-3">Title</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Views</th>
                    <th>Created</th>
                    <th class="text-end pe-3">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($recipes->num_rows > 0): ?>
                    <?php while ($recipe = $recipes->fetch_assoc()): ?>
                        <?php
                        $statusClass = $recipe['Status'] === 'Published' ? 'bg-success' : 'bg-warning text-dark';
                        ?>
                        <tr>
                            <td class="ps-3 fw-semibold text-success-emphasis"><?php echo htmlspecialchars($recipe['Title']); ?></td>
                            <td>
                                <span class="badge badge-category">
                                    <?php echo htmlspecialchars($recipe['Category'] ?? 'Uncategorized'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($recipe['Status']); ?>
                                </span>
                            </td>
                            <td><?php echo (int) $recipe['Views']; ?></td>
                            <td class="text-muted small"><?php echo htmlspecialchars($recipe['CreatedAt']); ?></td>
                            <td class="text-end pe-3">
                                <div class="action-btn-group">
                                    <a href="../recipe_view.php?id=<?php echo (int) $recipe['RecipeID']; ?>"
                                       class="btn btn-sm btn-outline-primary">View</a>
                                    <a href="edit_recipe.php?id=<?php echo (int) $recipe['RecipeID']; ?>"
                                       class="btn btn-sm btn-outline-warning">Edit</a>
                                    <a href="delete_recipe.php?id=<?php echo (int) $recipe['RecipeID']; ?>"
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Are you sure you want to delete this recipe?');">Delete</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted fst-italic">
                            You have not created any recipes yet.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php require_once '../includes/Footer.php'; ?>