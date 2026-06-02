<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/Header.php';

$searchTerm = trim($_GET['search'] ?? '');
$selectedCreators = $_GET['creator'] ?? [];
if (!is_array($selectedCreators)) {
    $selectedCreators = ($selectedCreators !== '') ? [$selectedCreators] : [];
}
$category = trim($_GET['category'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');
$sort = $_GET['sort'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$latestOpen = $page === 1;

// 1. Fetch Categories for Dropdown
$categories = [];
$categoryStmt = $mysqli->prepare("SELECT DISTINCT Category FROM dbProj_Recipes WHERE Category IS NOT NULL AND Category <> '' ORDER BY Category");
$categoryStmt->execute();
$categoryResult = $categoryStmt->get_result();
while ($cat = $categoryResult->fetch_assoc()) {
    $categories[] = $cat['Category'];
}

// 2. Fetch Active Creators based on Published Work (independent of Role modifications)
$creatorsList = [];
$creatorsStmt = $mysqli->prepare("
    SELECT DISTINCT u.UserID, u.Username 
    FROM dbProj_Users u
    JOIN dbProj_Recipes r ON u.UserID = r.UserID
    ORDER BY u.Username ASC
");
$creatorsStmt->execute();
$creatorsResult = $creatorsStmt->get_result();
while ($user = $creatorsResult->fetch_assoc()) {
    $creatorsList[] = $user;
}

$latestStmt = $mysqli->prepare(
        "SELECT r.RecipeID, r.Title, r.ImagePath, r.Description, r.Views, r.CreatedAt AS CreatedAt, u.Username,
            COALESCE(AVG(rt.RatingValue), 0) AS AvgRating, r.Category,
            COUNT(rt.RatingID) AS RatingCount
     FROM dbProj_Recipes r
     JOIN dbProj_Users u ON r.UserID = u.UserID
     LEFT JOIN dbProj_Ratings rt ON r.RecipeID = rt.RecipeID
     WHERE r.Status = 'Published'
     GROUP BY r.RecipeID, r.Title, r.ImagePath, r.Description, r.Views, r.CreatedAt, u.Username
     ORDER BY r.CreatedAt DESC
     LIMIT 3"
);
$latestStmt->execute();
$latestRecipes = $latestStmt->get_result();
?>

    <section class="hero-panel mb-4">
        <h1 class="h3 mb-3 fw-bold text-success-emphasis">Discover Delicious Recipes</h1>
        <form id="recipe-search-form" action="index.php" method="GET" class="mt-2">
            <input type="hidden" name="sort" id="sort-input" value="<?php echo htmlspecialchars($sort); ?>">

            <div class="row g-3">
                <!-- Title or Ingredient -->
                <div class="col-lg-4 col-md-6">
                    <label class="form-label small fw-bold text-muted">Keywords</label>
                    <input type="text" name="search" class="form-control" placeholder="Title or ingredient" value="<?php echo htmlspecialchars($searchTerm); ?>">
                </div>

                <!-- Category -->
                <div class="col-lg-2 col-md-6">
                    <label class="form-label small fw-bold text-muted">Category</label>
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Creators Checkbox Dropdown -->
                <div class="col-lg-2 col-md-6">
                    <label class="form-label small fw-bold text-muted">Creators</label>
                    <div class="dropdown">
                        <button class="btn btn-white w-100 text-start d-flex justify-content-between align-items-center form-select"
                                type="button"
                                id="creatorDropdownMenu"
                                data-bs-toggle="dropdown"
                                data-bs-auto-close="outside"
                                aria-expanded="false">
                            <span id="creator-dropdown-label" class="text-truncate">All Creators</span>
                        </button>
                        <ul class="dropdown-menu creator-checkbox-menu w-100 p-2 shadow-sm" aria-labelledby="creatorDropdownMenu">
                            <?php if (empty($creatorsList)): ?>
                                <li class="text-muted p-2 small italic text-center">No creators found</li>
                            <?php else: ?>
                                <?php foreach ($creatorsList as $c): ?>
                                    <li class="creator-checkbox-item p-1">
                                        <div class="form-check">
                                            <input class="form-check-input creator-checkbox"
                                                   type="checkbox"
                                                   name="creator[]"
                                                   value="<?php echo htmlspecialchars($c['Username']); ?>"
                                                   id="creator_<?php echo $c['UserID']; ?>"
                                                    <?php echo in_array($c['Username'], $selectedCreators) ? 'checked' : ''; ?>>
                                            <label class="form-check-label w-100 small" for="creator_<?php echo $c['UserID']; ?>">
                                                <?php echo htmlspecialchars($c['Username']); ?>
                                            </label>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <!-- Date From -->
                <div class="col-lg-2 col-md-6">
                    <label class="form-label small fw-bold text-muted">Published From</label>
                    <input type="date" name="date_from" class="form-control" aria-label="Published From" value="<?php echo htmlspecialchars($dateFrom); ?>">
                </div>

                <!-- Date To -->
                <div class="col-lg-2 col-md-6">
                    <label class="form-label small fw-bold text-muted">Published To</label>
                    <input type="date" name="date_to" class="form-control" aria-label="Published To" value="<?php echo htmlspecialchars($dateTo); ?>">
                </div>
            </div>

            <!-- Action Button Row -->
            <div class="row mt-3">
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-success px-4" type="submit">Search Recipes</button>
                </div>
            </div>
        </form>
    </section>

    <section id="latest-recipes-section" class="mb-4">
        <div class="latest-toggle-wrapper">
            <button class="btn w-100 text-start d-flex justify-content-between align-items-center py-3 px-4 latest-toggle border-0"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#latestRecipesCollapse"
                    aria-expanded="<?php echo $latestOpen ? 'true' : 'false'; ?>"
                    aria-controls="latestRecipesCollapse">
                <span class="h5 mb-0 fw-bold text-success">Latest Recipes</span>
                <svg class="toggle-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </button>
        </div>
        <div class="collapse <?php echo $latestOpen ? 'show' : ''; ?>" id="latestRecipesCollapse">
            <div class="px-4 pb-4 pt-2">
                <div class="row g-3">
                    <?php if ($latestRecipes->num_rows > 0): ?>
                        <?php while ($row = $latestRecipes->fetch_assoc()): ?>
                            <?php include __DIR__ . '/includes/recipe_card.php'; ?>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12"><div class="alert alert-info mb-0">No latest recipes found.</div></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h4 mb-0 text-success-emphasis fw-bold">All Recipes</h2>
            <div class="btn-group btn-group-sm" role="group" aria-label="Recipe sorting">
                <button type="button" class="btn sort-btn <?php echo $sort === 'popular' ? 'btn-success' : 'btn-outline-success'; ?>" data-sort="popular">Most Popular</button>
                <button type="button" class="btn sort-btn <?php echo $sort === 'top_rated' ? 'btn-success' : 'btn-outline-success'; ?>" data-sort="top_rated">Top Rated</button>
            </div>
        </div>
        <div id="all-recipes-container" data-current-page="<?php echo (int) $page; ?>">
            <?php
            $_GET['page'] = $page;
            include __DIR__ . '/actions/fetch_recipes.php';
            ?>
        </div>
    </section>

<?php require_once 'includes/Footer.php'; ?>