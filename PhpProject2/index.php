<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/Header.php';

$searchTerm = trim($_GET['search'] ?? '');
$creator = trim($_GET['creator'] ?? '');
$category = trim($_GET['category'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$sort = $_GET['sort'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$showLatest = $page === 1;

$categories = [];
$categoryStmt = $mysqli->prepare("SELECT DISTINCT Category FROM dbProj_Recipes WHERE Category IS NOT NULL AND Category <> '' ORDER BY Category");
$categoryStmt->execute();
$categoryResult = $categoryStmt->get_result();
while ($cat = $categoryResult->fetch_assoc()) {
    $categories[] = $cat['Category'];
}

$latestStmt = $mysqli->prepare(
    "SELECT r.RecipeID, r.Title, r.ImagePath, r.Description, r.Views, r.CreatedAt, u.Username,
            AVG(rt.RatingValue) AS AvgRating
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
    <h1>Discover Delicious Recipes</h1>
    <form id="recipe-search-form" action="index.php" method="GET" class="row g-2 mt-3">
        <input type="hidden" name="sort" id="sort-input" value="<?php echo htmlspecialchars($sort); ?>">
        <div class="col-md-3">
            <input type="text" name="search" class="form-control" placeholder="Title or ingredient" value="<?php echo htmlspecialchars($searchTerm); ?>">
        </div>
        <div class="col-md-2">
            <input type="text" name="creator" class="form-control" placeholder="Creator" value="<?php echo htmlspecialchars($creator); ?>">
        </div>
        <div class="col-md-3">
            <select name="category" class="form-select">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <input type="date" name="date_from" class="form-control" aria-label="Published Since" value="<?php echo htmlspecialchars($dateFrom); ?>">
        </div>
        <div class="col-md-2 d-grid">
            <button class="btn btn-success" type="submit">Search</button>
        </div>
    </form>
</section>

<section id="latest-recipes-section" class="mb-4 <?php echo $showLatest ? '' : 'd-none'; ?>">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h4 mb-0">Latest Recipes</h2>
    </div>
    <div class="row">
        <?php if ($latestRecipes->num_rows > 0): ?>
            <?php while ($row = $latestRecipes->fetch_assoc()): ?>
                <?php include __DIR__ . '/includes/recipe_card.php'; ?>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12"><div class="alert alert-info">No latest recipes found.</div></div>
        <?php endif; ?>
    </div>
</section>

<section class="mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="h4 mb-0">All Recipes</h2>
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
