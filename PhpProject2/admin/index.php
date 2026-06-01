<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../login.php');
    exit;
}

require_once '../includes/Header.php';

$popularStmt = $mysqli->prepare('SELECT Title, Views FROM dbProj_Recipes ORDER BY Views DESC LIMIT 5');
$popularStmt->execute();
$popularRecipes = $popularStmt->get_result();

$topRankedStmt = $mysqli->prepare(
    'SELECT r.Title, COALESCE(AVG(rt.RatingValue), 0) AS AvgRating, COUNT(rt.RatingID) AS RatingCount
     FROM dbProj_Recipes r
     LEFT JOIN dbProj_Ratings rt ON r.RecipeID = rt.RecipeID
     GROUP BY r.RecipeID, r.Title
     ORDER BY AvgRating DESC, r.Views DESC
     LIMIT 5'
);
$topRankedStmt->execute();
$topRankedRecipes = $topRankedStmt->get_result();

$latestStmt = $mysqli->prepare("SELECT Title, CreatedAt FROM dbProj_Recipes WHERE Status = 'Published' ORDER BY CreatedAt DESC LIMIT 5");
$latestStmt->execute();
$latestRecipes = $latestStmt->get_result();

$usersStmt = $mysqli->prepare('SELECT UserID, Username, Email, Role, CreatedAt FROM dbProj_Users ORDER BY CreatedAt DESC');
$usersStmt->execute();
$users = $usersStmt->get_result();

$creatorReport = null;
$selectedCreatorName = trim($_GET['creator_name'] ?? '');
$creatorId = (int) ($_GET['creator_id'] ?? 0);

if ($creatorId <= 0 && $selectedCreatorName !== '') {
    $creatorLookupStmt = $mysqli->prepare("SELECT UserID FROM dbProj_Users WHERE Username = ? AND Role = 'Creator' LIMIT 1");
    $creatorLookupStmt->bind_param('s', $selectedCreatorName);
    $creatorLookupStmt->execute();
    $creatorLookup = $creatorLookupStmt->get_result()->fetch_assoc();
    $creatorId = $creatorLookup ? (int) $creatorLookup['UserID'] : 0;
}

if ($creatorId > 0) {
    $creatorNameStmt = $mysqli->prepare("SELECT Username FROM dbProj_Users WHERE UserID = ? AND Role = 'Creator'");
    $creatorNameStmt->bind_param('i', $creatorId);
    $creatorNameStmt->execute();
    $creatorRow = $creatorNameStmt->get_result()->fetch_assoc();

    if ($creatorRow) {
        $selectedCreatorName = $creatorRow['Username'];
        $creatorRole = 'Creator';
        $stmt = $mysqli->prepare(
            'SELECT r.Title, r.Category, r.Status, r.Views, r.CreatedAt
             FROM dbProj_Recipes r
             JOIN dbProj_Users u ON r.UserID = u.UserID
             WHERE r.UserID = ? AND u.Role = ?
             ORDER BY r.CreatedAt DESC'
        );
        $stmt->bind_param('is', $creatorId, $creatorRole);
        $stmt->execute();
        $creatorReport = $stmt->get_result();
    }
}
?>

<h2 class="h4 mb-4">Admin Dashboard</h2>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Recipe Reports</div>
            <div class="card-body">
                <ul class="nav nav-tabs" id="adminReportTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="popular-tab" data-bs-toggle="tab" data-bs-target="#popular-report" type="button" role="tab">Most Popular</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="ranked-tab" data-bs-toggle="tab" data-bs-target="#ranked-report" type="button" role="tab">Top Ranked</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="latest-tab" data-bs-toggle="tab" data-bs-target="#latest-report" type="button" role="tab">Latest Published</button>
                    </li>
                </ul>

                <div class="tab-content pt-3">
                    <div class="tab-pane fade show active" id="popular-report" role="tabpanel" aria-labelledby="popular-tab">
                        <ul class="list-group list-group-flush">
                            <?php while ($row = $popularRecipes->fetch_assoc()): ?>
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span><?php echo htmlspecialchars($row['Title']); ?></span>
                                    <span class="badge bg-success"><?php echo (int) $row['Views']; ?> views</span>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>

                    <div class="tab-pane fade" id="ranked-report" role="tabpanel" aria-labelledby="ranked-tab">
                        <ul class="list-group list-group-flush">
                            <?php while ($row = $topRankedRecipes->fetch_assoc()): ?>
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span><?php echo htmlspecialchars($row['Title']); ?></span>
                                    <span class="badge bg-warning text-dark">
                                        <?php echo (int) $row['RatingCount'] > 0 ? number_format((float) $row['AvgRating'], 1) . ' stars' : 'No ratings'; ?>
                                    </span>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>

                    <div class="tab-pane fade" id="latest-report" role="tabpanel" aria-labelledby="latest-tab">
                        <ul class="list-group list-group-flush">
                            <?php while ($row = $latestRecipes->fetch_assoc()): ?>
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span><?php echo htmlspecialchars($row['Title']); ?></span>
                                    <span class="text-muted"><?php echo date('M d, Y', strtotime($row['CreatedAt'])); ?></span>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Content By Creator</div>
            <div class="card-body">
                <form method="GET" class="mb-3 creator-search-form" autocomplete="off">
                    <label class="form-label" for="creator-search-input">Content By Creator Name</label>
                    <div class="position-relative">
                        <div class="input-group">
                            <input type="text"
                                   name="creator_name"
                                   id="creator-search-input"
                                   class="form-control"
                                   value="<?php echo htmlspecialchars($selectedCreatorName); ?>"
                                   data-search-url="../actions/search_creators.php"
                                   data-recipes-url="../actions/fetch_creator_recipes.php">
                            <input type="hidden" name="creator_id" id="creator-id-input" value="<?php echo $creatorId > 0 ? (int) $creatorId : ''; ?>">
                            <button class="btn btn-success" type="submit">View</button>
                        </div>
                        <div id="creator-search-results" class="creator-search-results d-none"></div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Recipe</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Views</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody id="creator-recipes-body">
                            <?php if ($creatorReport): ?>
                                <?php while ($row = $creatorReport->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['Title']); ?></td>
                                        <td><?php echo htmlspecialchars($row['Category']); ?></td>
                                        <td><?php echo htmlspecialchars($row['Status']); ?></td>
                                        <td><?php echo (int) $row['Views']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($row['CreatedAt'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php elseif ($selectedCreatorName !== ''): ?>
                                <tr><td colspan="5" class="text-muted fst-italic">No creator content found.</td></tr>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-muted fst-italic">Select a creator to view recipes.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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

<?php require_once '../includes/Footer.php'; ?>
