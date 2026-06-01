<?php
require_once '../includes/header.php';

if (!checkRole('Admin')) {
    header('Location: ../login.php');
    exit;
}

$popularStmt = $mysqli->prepare('SELECT Title, Views FROM dbProj_Recipes ORDER BY Views DESC LIMIT 5');
$popularStmt->execute();
$popularRecipes = $popularStmt->get_result();

$users = $mysqli->query('SELECT UserID, Username, Email, Role, CreatedAt FROM dbProj_User ORDER BY CreatedAt DESC');

$creatorReport = null;
if (!empty($_GET['creator_id'])) {
    $creatorId = (int) $_GET['creator_id'];
    $stmt = $mysqli->prepare('SELECT r.Title, r.Status, r.CreatedAt, u.Username FROM dbProj_Recipes r JOIN dbProj_User u ON r.UserID = u.UserID WHERE r.UserID = ? ORDER BY r.CreatedAt DESC');
    $stmt->bind_param('i', $creatorId);
    $stmt->execute();
    $creatorReport = $stmt->get_result();
}
?>

<h2 class="h4 mb-4">Admin Dashboard</h2>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Most Popular Recipes</div>
            <ul class="list-group list-group-flush">
                <?php while ($row = $popularRecipes->fetch_assoc()): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?php echo htmlspecialchars($row['Title']); ?></span>
                        <span><?php echo (int) $row['Views']; ?> views</span>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Content By Creator</div>
            <div class="card-body">
                <form method="GET" class="mb-3">
                    <label class="form-label">Creator User ID</label>
                    <div class="input-group">
                        <input type="number" name="creator_id" class="form-control" value="<?php echo htmlspecialchars($_GET['creator_id'] ?? ''); ?>">
                        <button class="btn btn-success">Generate</button>
                    </div>
                </form>
                <?php if ($creatorReport): ?>
                    <ul class="list-group">
                        <?php while ($row = $creatorReport->fetch_assoc()): ?>
                            <li class="list-group-item">
                                <?php echo htmlspecialchars($row['Title']); ?>
                                <span class="text-muted">(<?php echo htmlspecialchars($row['Status']); ?>)</span>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">Users</div>
    <div class="table-responsive">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo (int) $user['UserID']; ?></td>
                        <td><?php echo htmlspecialchars($user['Username']); ?></td>
                        <td><?php echo htmlspecialchars($user['Email']); ?></td>
                        <td><?php echo htmlspecialchars($user['Role']); ?></td>
                        <td><?php echo htmlspecialchars($user['CreatedAt']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
