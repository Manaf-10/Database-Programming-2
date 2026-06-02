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

    <!-- Inline Modern Table Styling Overrides matching Admin Panel -->
    <style>
        .modern-table {
            border-collapse: separate !important;
            border-spacing: 0 10px !important; /* Visual spacing between rows */
            margin-top: -10px;
        }
        .modern-table thead th {
            border: none !important;
            font-size: 0.78rem !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            padding-bottom: 5px !important;
        }
        .modern-table tbody tr {
            background-color: #ffffff !important;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02) !important;
            border-radius: 10px !important;
            transition: all 0.15s ease-in-out;
        }
        .modern-table tbody tr:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(25, 135, 84, 0.06) !important;
            background-color: #fcfdfe !important;
        }
        .modern-table td {
            border: none !important;
            padding: 16px 14px !important;
            vertical-align: middle !important;
        }
        /* Border radius wrapping for independent card rows */
        .modern-table td:first-child, .modern-table th:first-child {
            border-top-left-radius: 10px !important;
            border-bottom-left-radius: 10px !important;
            padding-left: 20px !important;
        }
        .modern-table td:last-child, .modern-table th:last-child {
            border-top-right-radius: 10px !important;
            border-bottom-right-radius: 10px !important;
            padding-right: 20px !important;
        }

        /* Low-opacity modern status badge colors */
        .badge-soft-success {
            background-color: rgba(25, 135, 84, 0.1) !important;
            color: #198754 !important;
            font-weight: 600;
        }
        .badge-soft-warning {
            background-color: rgba(255, 193, 7, 0.12) !important;
            color: #b58404 !important;
            font-weight: 600;
        }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0 fw-bold text-success-emphasis">My Recipes</h2>
        <a href="add_recipe.php" class="btn btn-success btn-sm fw-bold px-3 py-2">Add Recipe</a>
    </div>

    <!-- Transparent wrapper card to allow card rows to float beautifully -->
    <div class="card border-0 bg-transparent overflow-hidden">
        <div class="table-responsive">
            <table class="table modern-table align-middle mb-0 w-100">
                <thead>
                <tr>
                    <th class="text-start text-muted">Title</th>
                    <th class="text-start text-muted">Category</th>
                    <th class="text-start text-muted">Status</th>
                    <th class="text-center text-muted">Views</th>
                    <th class="text-start text-muted">Created</th>
                    <th class="text-end text-muted">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($recipes->num_rows > 0): ?>
                    <?php while ($recipe = $recipes->fetch_assoc()): ?>
                        <?php
                        $statusClass = $recipe['Status'] === 'Published' ? 'badge-soft-success' : 'badge-soft-warning';
                        ?>
                        <tr>
                            <td class="text-start fw-bold text-success-emphasis small"><?php echo htmlspecialchars($recipe['Title']); ?></td>
                            <td class="text-start text-nowrap">
                                <span class="badge badge-category">
                                    <?php echo htmlspecialchars($recipe['Category'] ?? 'Uncategorized'); ?>
                                </span>
                            </td>
                            <td class="text-start text-nowrap">
                                <span class="badge <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($recipe['Status']); ?>
                                </span>
                            </td>
                            <td class="text-center text-nowrap small fw-bold text-secondary"><?php echo (int) $recipe['Views']; ?></td>
                            <td class="text-start text-nowrap text-muted small"><?php echo date('M d, Y', strtotime($recipe['CreatedAt'])); ?></td>
                            <td class="text-end text-nowrap">
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
                        <td colspan="6" class="text-center py-5 text-muted fst-italic bg-white rounded shadow-sm small">
                            You have not created any recipes yet.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php require_once '../includes/Footer.php'; ?>