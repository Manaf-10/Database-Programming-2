<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../login.php');
    exit;
}

// Handle User Inline Form Updates securely with prepared statements
$userSuccessMessage = null;
$userErrorMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $editUserId = (int) ($_POST['edit_user_id'] ?? 0);
    $newEmail = trim($_POST['email'] ?? '');
    $newRole = $_POST['role'] ?? '';
    $allowedRoles = ['Viewer', 'Creator', 'Admin'];

    if ($editUserId > 0 && filter_var($newEmail, FILTER_VALIDATE_EMAIL) && in_array($newRole, $allowedRoles, true)) {
        $updateUserStmt = $mysqli->prepare('UPDATE dbProj_Users SET Email = ?, Role = ? WHERE UserID = ?');
        $updateUserStmt->bind_param('ssi', $newEmail, $newRole, $editUserId);
        if ($updateUserStmt->execute()) {
            $userSuccessMessage = "User updated successfully.";
        } else {
            $userErrorMessage = "Failed to update user.";
        }
    } else {
        $userErrorMessage = "Invalid input data. Please check the email format.";
    }
}

require_once '../includes/Header.php';

// --- SUMMARY METRICS STATS QUERIES ---
// 1. Total Users
$totUsersStmt = $mysqli->prepare("SELECT COUNT(*) AS total FROM dbProj_Users");
$totUsersStmt->execute();
$totUsers = $totUsersStmt->get_result()->fetch_assoc()['total'] ?? 0;

// 2. Total Recipes
$totRecipesStmt = $mysqli->prepare("SELECT COUNT(*) AS total FROM dbProj_Recipes");
$totRecipesStmt->execute();
$totRecipes = $totRecipesStmt->get_result()->fetch_assoc()['total'] ?? 0;

// 3. Published Recipes
$pubRecipesStmt = $mysqli->prepare("SELECT COUNT(*) AS total FROM dbProj_Recipes WHERE Status = 'Published'");
$pubRecipesStmt->execute();
$pubRecipes = $pubRecipesStmt->get_result()->fetch_assoc()['total'] ?? 0;

// 4. Total Recipe Views
$totViewsStmt = $mysqli->prepare("SELECT SUM(Views) AS total FROM dbProj_Recipes");
$totViewsStmt->execute();
$totViews = $totViewsStmt->get_result()->fetch_assoc()['total'] ?? 0;


// --- REPORT TABLES QUERIES ---
// Popular Recipes
$popularStmt = $mysqli->prepare('SELECT Title, Views FROM dbProj_Recipes ORDER BY Views DESC LIMIT 5');
$popularStmt->execute();
$popularRecipes = $popularStmt->get_result();

// Top Ranked Recipes
$topRankedStmt = $mysqli->prepare('
    SELECT r.Title, COALESCE(AVG(rt.RatingValue), 0) AS AvgRating, COUNT(rt.RatingID) AS RatingCount
    FROM dbProj_Recipes r
    LEFT JOIN dbProj_Ratings rt ON r.RecipeID = rt.RecipeID
    GROUP BY r.RecipeID, r.Title
    ORDER BY AvgRating DESC, r.Views DESC
    LIMIT 5
');
$topRankedStmt->execute();
$topRankedRecipes = $topRankedStmt->get_result();

// Latest Published Recipes
$latestStmt = $mysqli->prepare("SELECT Title, CreatedAt FROM dbProj_Recipes WHERE Status = 'Published' ORDER BY CreatedAt DESC LIMIT 5");
$latestStmt->execute();
$latestRecipes = $latestStmt->get_result();

// Users Table
$usersStmt = $mysqli->prepare('SELECT UserID, Username, Email, Role, CreatedAt FROM dbProj_Users ORDER BY CreatedAt DESC');
$usersStmt->execute();
$users = $usersStmt->get_result();

$creatorReport = null;
$selectedCreatorName = trim($_GET['creator_name'] ?? '');
$creatorId = (int) ($_GET['creator_id'] ?? 0);

// Look up any user by name regardless of their role status
if ($creatorId <= 0 && $selectedCreatorName !== '') {
    $creatorLookupStmt = $mysqli->prepare("SELECT UserID FROM dbProj_Users WHERE Username = ? LIMIT 1");
    $creatorLookupStmt->bind_param('s', $selectedCreatorName);
    $creatorLookupStmt->execute();
    $creatorLookup = $creatorLookupStmt->get_result()->fetch_assoc();
    $creatorId = $creatorLookup ? (int) $creatorLookup['UserID'] : 0;
}

if ($creatorId > 0) {
    $creatorNameStmt = $mysqli->prepare("SELECT Username FROM dbProj_Users WHERE UserID = ?");
    $creatorNameStmt->bind_param('i', $creatorId);
    $creatorNameStmt->execute();
    $creatorRow = $creatorNameStmt->get_result()->fetch_assoc();

    if ($creatorRow) {
        $selectedCreatorName = $creatorRow['Username'];
        $creatorRole = 'Creator';
        $stmt = $mysqli->prepare(
                'SELECT r.RecipeID, r.Title, r.Category, r.Status, r.Views, r.CreatedAt
             FROM dbProj_Recipes r
             WHERE r.UserID = ?
             ORDER BY r.CreatedAt DESC'
        );
        $stmt->bind_param('i', $creatorId);
        $stmt->execute();
        $creatorReport = $stmt->get_result();
    }
}
?>

    <!-- Inline Modern Table Styling Overrides -->
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
        }
        .modern-table td:last-child, .modern-table th:last-child {
            border-top-right-radius: 10px !important;
            border-bottom-right-radius: 10px !important;
        }

        /* Low-opacity modern badge colors */
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
        .badge-soft-danger {
            background-color: rgba(220, 53, 69, 0.1) !important;
            color: #dc3545 !important;
            font-weight: 600;
        }
        .badge-soft-secondary {
            background-color: rgba(108, 117, 125, 0.1) !important;
            color: #6c757d !important;
            font-weight: 600;
        }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 fw-bold text-success-emphasis">Admin Dashboard</h2>
        <span class="text-muted small">System Summary</span>
    </div>

    <!-- 1. DASHBOARD METRICS CARDS PANEL -->
    <div class="row g-4 mb-4">
        <!-- Metric Card: Total Users -->
        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-success bg-opacity-10 text-success rounded-3 p-3 me-3">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.109A11.386 11.386 0 0110.089 20M3 11.627a1.131 1.131 0 01.37-.852l7-7a1.13 1.13 0 011.602 0l7 7a1.13 1.13 0 01.37.852V18a2.25( 2.25h-9A2.25 2.25 0 013 18v-6.373z" />
                        </svg>
                    </div>
                    <div>
                        <h6 class="text-muted small mb-1 fw-bold uppercase">Total Users</h6>
                        <h4 class="mb-0 fw-extrabold"><?php echo number_format($totUsers); ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric Card: Total Recipes -->
        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-success bg-opacity-10 text-success rounded-3 p-3 me-3">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                        </svg>
                    </div>
                    <div>
                        <h6 class="text-muted small mb-1 fw-bold uppercase">Total Recipes</h6>
                        <h4 class="mb-0 fw-extrabold"><?php echo number_format($totRecipes); ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric Card: Published Recipes -->
        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-success bg-opacity-10 text-success rounded-3 p-3 me-3">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h6 class="text-muted small mb-1 fw-bold uppercase">Published</h6>
                        <h4 class="mb-0 fw-extrabold"><?php echo number_format($pubRecipes); ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric Card: Total Recipe Views -->
        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-success bg-opacity-10 text-success rounded-3 p-3 me-3">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div>
                        <h6 class="text-muted small mb-1 fw-bold uppercase">Total Views</h6>
                        <h4 class="mb-0 fw-extrabold"><?php echo number_format($totViews); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. SYSTEM REPORTS AND CREATOR CONTENT LAYOUT -->
    <div class="row g-4">
        <!-- Recipe Reports Card -->
        <div class="col-md-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title mb-0 fw-bold text-success-emphasis">Recipe Metrics</h5>
                </div>
                <div class="card-body p-4">
                    <ul class="nav nav-tabs admin-report-tabs border-bottom-0 gap-1" id="adminReportTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active small py-2 px-3 fw-bold" id="popular-tab" data-bs-toggle="tab" data-bs-target="#popular-report" type="button" role="tab">Most Popular</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link small py-2 px-3 fw-bold" id="ranked-tab" data-bs-toggle="tab" data-bs-target="#ranked-report" type="button" role="tab">Top Ranked</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link small py-2 px-3 fw-bold" id="latest-tab" data-bs-toggle="tab" data-bs-target="#latest-report" type="button" role="tab">Latest</button>
                        </li>
                    </ul>

                    <div class="tab-content pt-3">
                        <div class="tab-pane fade show active" id="popular-report" role="tabpanel" aria-labelledby="popular-tab">
                            <ul class="list-group list-group-flush">
                                <?php while ($row = $popularRecipes->fetch_assoc()): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0-last">
                                        <span class="small fw-semibold text-dark text-truncate" style="max-width: 70%;"><?php echo htmlspecialchars($row['Title']); ?></span>
                                        <span class="badge bg-success bg-opacity-10 text-success px-2 py-1 rounded-pill small"><?php echo (int) $row['Views']; ?> views</span>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        </div>

                        <div class="tab-pane fade" id="ranked-report" role="tabpanel" aria-labelledby="ranked-tab">
                            <ul class="list-group list-group-flush">
                                <?php while ($row = $topRankedRecipes->fetch_assoc()): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0-last">
                                        <span class="small fw-semibold text-dark text-truncate" style="max-width: 70%;"><?php echo htmlspecialchars($row['Title']); ?></span>
                                        <span class="badge bg-warning bg-opacity-10 text-warning-emphasis px-2 py-1 rounded-pill small">
                                            <?php echo (int) $row['RatingCount'] > 0 ? number_format((float) $row['AvgRating'], 1) . ' ★' : 'No ratings'; ?>
                                        </span>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        </div>

                        <div class="tab-pane fade" id="latest-report" role="tabpanel" aria-labelledby="latest-tab">
                            <ul class="list-group list-group-flush">
                                <?php while ($row = $latestRecipes->fetch_assoc()): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0-last">
                                        <span class="small fw-semibold text-dark text-truncate" style="max-width: 70%;"><?php echo htmlspecialchars($row['Title']); ?></span>
                                        <span class="text-muted small"><?php echo date('M d, Y', strtotime($row['CreatedAt'])); ?></span>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content By User Card -->
        <div class="col-md-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title mb-0 fw-bold text-success-emphasis">Content By User</h5>
                </div>
                <div class="card-body p-4">
                    <form method="GET" class="mb-4" autocomplete="off">
                        <label class="form-label text-secondary fw-semibold mb-2">Search Username</label>
                        <div class="position-relative">

                            <!-- Searchable Dropdown Selector -->
                            <div class="input-group" style="max-width: 500px;">
                                <div class="dropdown w-75">
                                    <button class="btn btn-white w-100 text-start d-flex justify-content-between align-items-center form-select rounded-end-0 border-end-0"
                                            type="button"
                                            id="adminUserSelectDropdown"
                                            data-bs-toggle="dropdown"
                                            data-bs-auto-close="outside"
                                            aria-expanded="false"
                                            style="height: 38px; border: 1px solid #ced4da;">
                                        <span id="selected-user-label" class="text-truncate">
                                            <?php echo $selectedCreatorName !== '' ? htmlspecialchars($selectedCreatorName) : 'Select a user...'; ?>
                                        </span>
                                    </button>
                                    <div class="dropdown-menu w-100 p-2 shadow-sm border-0" aria-labelledby="adminUserSelectDropdown" style="max-height: 280px; overflow-y: auto;">
                                        <input type="text" id="admin-user-search-filter" class="form-control form-control-sm mb-2" placeholder="Type to filter users...">
                                        <ul class="list-unstyled mb-0" id="admin-user-list">
                                            <li>
                                                <button type="button" class="dropdown-item admin-user-select-item text-muted small py-1" data-id="" data-username="">
                                                    <em>Clear Selection</em>
                                                </button>
                                            </li>
                                            <?php
                                            // Fetch all database users to build the searchable select
                                            $allUsersStmt = $mysqli->prepare("SELECT UserID, Username FROM dbProj_Users ORDER BY Username ASC");
                                            $allUsersStmt->execute();
                                            $allUsersResult = $allUsersStmt->get_result();
                                            while ($u = $allUsersResult->fetch_assoc()) {
                                                $selected = ($creatorId === (int)$u['UserID']) ? 'active' : '';
                                                echo '<li>';
                                                echo '<button type="button" class="dropdown-item admin-user-select-item py-1 ' . $selected . '" data-id="' . (int)$u['UserID'] . '" data-username="' . htmlspecialchars($u['Username']) . '">';
                                                echo htmlspecialchars($u['Username']);
                                                echo '</button>';
                                                echo '</li>';
                                            }
                                            ?>
                                        </ul>
                                    </div>
                                </div>

                                <!-- Standard form variables -->
                                <input type="hidden" name="creator_name" id="creator-search-input" value="<?php echo htmlspecialchars($selectedCreatorName); ?>">
                                <input type="hidden" name="creator_id" id="creator-id-input" value="<?php echo $creatorId > 0 ? (int) $creatorId : ''; ?>">

                                <button class="btn btn-success px-4 w-25 rounded-start-0 fw-bold" type="submit">View</button>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table modern-table align-middle mb-0 w-100">
                            <thead>
                            <tr>
                                <th class="text-start text-nowrap text-muted" style="width: 30%;">Recipe</th>
                                <th class="text-start text-nowrap text-muted" style="width: 15%;">Category</th>
                                <th class="text-start text-nowrap text-muted" style="width: 15%;">Status</th>
                                <th class="text-center text-nowrap text-muted" style="width: 10%;">Views</th>
                                <th class="text-start text-nowrap text-muted" style="width: 15%;">Created</th>
                                <th class="text-end text-nowrap text-muted" style="width: 15%;">Actions</th>
                            </tr>
                            </thead>
                            <tbody id="creator-recipes-body">
                            <?php if ($creatorReport && $creatorReport->num_rows > 0): ?>
                                <?php while ($row = $creatorReport->fetch_assoc()): ?>
                                    <?php $statusClass = $row['Status'] === 'Published' ? 'badge-soft-success' : 'badge-soft-warning'; ?>
                                    <tr>
                                        <td class="text-start fw-bold text-success-emphasis small"><?php echo htmlspecialchars($row['Title']); ?></td>
                                        <td class="text-start text-nowrap"><span class="badge badge-category"><?php echo htmlspecialchars($row['Category']); ?></span></td>
                                        <td class="text-start text-nowrap"><span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($row['Status']); ?></span></td>
                                        <td class="text-center text-nowrap small fw-bold text-secondary"><?php echo (int) $row['Views']; ?></td>
                                        <td class="text-start text-nowrap text-muted small"><?php echo date('M d, Y', strtotime($row['CreatedAt'])); ?></td>
                                        <td class="text-end text-nowrap">
                                            <div class="action-btn-group">
                                                <a href="../recipe_view.php?id=<?php echo (int) $row['RecipeID']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                                <a href="../creator/edit_recipe.php?id=<?php echo (int) $row['RecipeID']; ?>" class="btn btn-sm btn-outline-warning">Edit</a>
                                                <a href="delete_recipe_by_admin.php?id=<?php echo (int) $row['RecipeID']; ?>"
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Are you sure you want to permanently delete this recipe?');">Delete</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php elseif ($selectedCreatorName !== ''): ?>
                                <tr><td colspan="6" class="text-muted fst-italic text-center py-4 small">No recipes found for user "<?php echo htmlspecialchars($selectedCreatorName); ?>".</td></tr>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-muted fst-italic text-center py-4 small">Select a user to view recipes.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. USERS MANAGEMENT PANEL -->
    <div class="card mt-4 border-0 shadow-sm overflow-hidden">
        <div class="card-header bg-white border-bottom py-3">
            <h5 class="card-title mb-0 fw-bold text-success-emphasis">Users Management</h5>
        </div>

        <?php if (isset($userSuccessMessage)): ?>
            <div class="alert alert-success m-3 mb-0 shadow-sm"><?php echo htmlspecialchars($userSuccessMessage); ?></div>
        <?php endif; ?>
        <?php if (isset($userErrorMessage)): ?>
            <div class="alert alert-danger m-3 mb-0 shadow-sm"><?php echo htmlspecialchars($userErrorMessage); ?></div>
        <?php endif; ?>

        <div class="table-responsive px-3 pb-3">
            <table class="table modern-table align-middle mb-0 w-100">
                <thead>
                <tr>
                    <th class="ps-4 text-start text-nowrap text-muted" style="width: 8%;">ID</th>
                    <th class="text-start text-nowrap text-muted" style="width: 22%;">Username</th>
                    <th class="text-start text-nowrap text-muted" style="width: 25%;">Email</th>
                    <th class="text-start text-nowrap text-muted" style="width: 15%;">Role</th>
                    <th class="text-start text-nowrap text-muted" style="width: 15%;">Created</th>
                    <th class="pe-4 text-end text-nowrap text-muted" style="width: 15%;">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php while ($user = $users->fetch_assoc()): ?>
                    <?php if (isset($_GET['edit_user_id']) && (int)$_GET['edit_user_id'] === (int)$user['UserID']): ?>
                        <!-- Inline Edit Mode Row -->
                        <tr>
                            <td class="ps-4 text-start small text-secondary"><?php echo (int) $user['UserID']; ?></td>
                            <td class="text-start fw-bold text-success-emphasis small"><?php echo htmlspecialchars($user['Username']); ?></td>
                            <td class="text-start">
                                <form method="POST" id="edit-user-form-<?php echo (int)$user['UserID']; ?>" class="m-0">
                                    <input type="hidden" name="edit_user_id" value="<?php echo (int) $user['UserID']; ?>">
                                    <input type="email" name="email" class="form-control form-control-sm" value="<?php echo htmlspecialchars($user['Email']); ?>" required>
                                </form>
                            </td>
                            <td class="text-start">
                                <select name="role" form="edit-user-form-<?php echo (int)$user['UserID']; ?>" class="form-select form-select-sm w-auto">
                                    <option value="Viewer" <?php echo $user['Role'] === 'Viewer' ? 'selected' : ''; ?>>Viewer</option>
                                    <option value="Creator" <?php echo $user['Role'] === 'Creator' ? 'selected' : ''; ?>>Creator</option>
                                    <option value="Admin" <?php echo $user['Role'] === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </td>
                            <td class="text-start text-nowrap text-muted small"><?php echo htmlspecialchars($user['CreatedAt']); ?></td>
                            <td class="pe-4 text-end text-nowrap">
                                <div class="action-btn-group">
                                    <button type="submit" name="update_user" form="edit-user-form-<?php echo (int)$user['UserID']; ?>" class="btn btn-sm btn-success px-3">Save</button>
                                    <a href="index.php" class="btn btn-sm btn-outline-secondary">Cancel</a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <!-- View Mode Row -->
                        <tr>
                            <td class="ps-4 text-start small text-secondary"><?php echo (int) $user['UserID']; ?></td>
                            <td class="text-start fw-bold text-success-emphasis small"><?php echo htmlspecialchars($user['Username']); ?></td>
                            <td class="text-start text-truncate small text-secondary" style="max-width: 180px;"><?php echo htmlspecialchars($user['Email']); ?></td>
                            <td class="text-start text-nowrap">
                                <?php
                                $roleBadge = 'badge-soft-secondary';
                                if ($user['Role'] === 'Admin') $roleBadge = 'badge-soft-danger';
                                elseif ($user['Role'] === 'Creator') $roleBadge = 'badge-soft-success';
                                ?>
                                <span class="badge <?php echo $roleBadge; ?>"><?php echo htmlspecialchars($user['Role']); ?></span>
                            </td>
                            <td class="text-start text-nowrap text-muted small"><?php echo htmlspecialchars($user['CreatedAt']); ?></td>
                            <td class="pe-4 text-end text-nowrap">
                                <div class="action-btn-group">
                                    <a href="index.php?edit_user_id=<?php echo (int) $user['UserID']; ?>" class="btn btn-sm btn-outline-warning">Edit</a>
                                    <?php if ((int)$_SESSION['user_id'] !== (int)$user['UserID']): ?>
                                        <a href="delete_user.php?id=<?php echo (int) $user['UserID']; ?>"
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Are you sure you want to permanently delete this user?');">Delete</a>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-outline-secondary" disabled title="You cannot delete yourself">Delete</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Script triggers for the live client side search filtering on users list -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Live Search Filter for Dropdown items
            const filterInput = document.getElementById('admin-user-search-filter');
            if (filterInput) {
                filterInput.addEventListener('input', function() {
                    const term = this.value.toLowerCase().trim();
                    const items = document.querySelectorAll('#admin-user-list .admin-user-select-item');
                    items.forEach(function(item) {
                        const username = item.getAttribute('data-username').toLowerCase();
                        if (username === '' || username.includes(term)) {
                            item.closest('li').classList.remove('d-none');
                        } else {
                            item.closest('li').classList.add('d-none');
                        }
                    });
                });
            }

            // Selector event handler for user elements
            const selectItems = document.querySelectorAll('.admin-user-select-item');
            selectItems.forEach(function(item) {
                item.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const username = this.getAttribute('data-username');

                    document.getElementById('creator-id-input').value = id;
                    document.getElementById('creator-search-input').value = username;
                    document.getElementById('selected-user-label').innerText = username !== '' ? username : 'Select a user...';

                    // Update Active states
                    document.querySelectorAll('.admin-user-select-item').forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');

                    // Auto submit the form to refresh content lists for the selected user
                    this.closest('form').submit();
                });
            });
        });
    </script>

<?php require_once '../includes/Footer.php'; ?>