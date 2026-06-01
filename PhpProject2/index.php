<?php require_once 'includes/header.php'; ?>

<section class="hero-panel mb-4">
    <h1>Discover Delicious Recipes</h1>
    <form action="index.php" method="GET" class="row g-2 mt-3">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Title or ingredient" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
        </div>
        <div class="col-md-3">
            <input type="text" name="creator" class="form-control" placeholder="Creator" value="<?php echo htmlspecialchars($_GET['creator'] ?? ''); ?>">
        </div>
        <div class="col-md-2">
            <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($_GET['date_from'] ?? ''); ?>">
        </div>
        <div class="col-md-2">
            <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($_GET['date_to'] ?? ''); ?>">
        </div>
        <div class="col-md-1 d-grid">
            <button class="btn btn-success" type="submit">Search</button>
        </div>
    </form>
</section>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0">Latest Recipes</h2>
    <a href="index.php?sort=popular" class="btn btn-outline-success btn-sm">Most Popular</a>
</div>

<div class="row">
<?php
$conditions = ["r.Status = 'Published'"];
$params = [];
$types = '';

$searchTerm = trim($_GET['search'] ?? '');
$creator = trim($_GET['creator'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');
$sort = $_GET['sort'] ?? '';

if ($searchTerm !== '') {
    $conditions[] = '(r.Title LIKE ? OR r.Ingredients LIKE ? OR r.Description LIKE ?)';
    $like = '%' . $searchTerm . '%';
    array_push($params, $like, $like, $like);
    $types .= 'sss';
}

if ($creator !== '') {
    $conditions[] = 'u.Username LIKE ?';
    $params[] = '%' . $creator . '%';
    $types .= 's';
}

if ($dateFrom !== '') {
    $conditions[] = 'DATE(r.CreatedAt) >= ?';
    $params[] = $dateFrom;
    $types .= 's';
}

if ($dateTo !== '') {
    $conditions[] = 'DATE(r.CreatedAt) <= ?';
    $params[] = $dateTo;
    $types .= 's';
}

$orderBy = $sort === 'popular' ? 'r.Views DESC, AvgRating DESC, r.CreatedAt DESC' : 'r.CreatedAt DESC';
$sql = "SELECT r.RecipeID, r.Title, r.ImagePath, r.Description, r.Views, u.Username, AVG(rt.RatingValue) AS AvgRating
        FROM dbProj_Recipes r
        JOIN dbProj_User u ON r.UserID = u.UserID
        LEFT JOIN dbProj_Ratings rt ON r.RecipeID = rt.RecipeID
        WHERE " . implode(' AND ', $conditions) . "
        GROUP BY r.RecipeID, r.Title, r.ImagePath, r.Description, r.Views, u.Username, r.CreatedAt
        ORDER BY $orderBy";

$stmt = $mysqli->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $avgRating = $row['AvgRating'] ? round($row['AvgRating'], 1) : 'No ratings';
        $imagePath = $row['ImagePath'] ?: 'default.jpg';
        ?>
        <div class="col-md-4 mb-4">
            <div class="card h-100 recipe-card">
                <img src="uploads/<?php echo htmlspecialchars($imagePath); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($row['Title']); ?>">
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($row['Title']); ?></h5>
                    <p class="text-muted mb-1">By <?php echo htmlspecialchars($row['Username']); ?> | <?php echo (int) $row['Views']; ?> views</p>
                    <p class="card-text"><?php echo htmlspecialchars(substr($row['Description'] ?? '', 0, 100)); ?>...</p>
                    <p class="text-warning">Rating: <?php echo htmlspecialchars($avgRating); ?></p>
                    <a href="recipe_view.php?id=<?php echo (int) $row['RecipeID']; ?>" class="btn btn-primary btn-sm">View Recipe</a>
                </div>
            </div>
        </div>
        <?php
    }
} else {
    echo "<div class='col-12'><div class='alert alert-info'>No recipes found.</div></div>";
}
?>
</div>

<?php require_once 'includes/footer.php'; ?>
