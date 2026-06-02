<?php
$isAjaxRequest = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!isset($mysqli)) {
    require_once __DIR__ . '/../includes/db_connect.php';
}

$perPage = 9;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$conditions = ["r.Status = 'Published'"];
$params = [];
$types = '';

$searchTerm = trim($_GET['search'] ?? '');
$creator = trim($_GET['creator'] ?? '');
$category = trim($_GET['category'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
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

if ($category !== '') {
    $conditions[] = 'r.Category = ?';
    $params[] = $category;
    $types .= 's';
}

if ($dateFrom !== '') {
    $conditions[] = 'DATE(r.CreatedAt) >= ?';
    $params[] = $dateFrom;
    $types .= 's';
}

$whereSql = implode(' AND ', $conditions);
$orderBy = 'r.CreatedAt DESC';
if ($sort === 'popular') {
    $orderBy = 'r.Views DESC, r.CreatedAt DESC';
} elseif ($sort === 'top_rated') {
    $orderBy = 'AvgRating DESC, r.Views DESC, r.CreatedAt DESC';
}

$countSql = "SELECT COUNT(*) AS TotalRecipes
             FROM dbProj_Recipes r
             JOIN dbProj_Users u ON r.UserID = u.UserID
             WHERE $whereSql";
$countStmt = $mysqli->prepare($countSql);
if ($params) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRecipes = (int) ($countStmt->get_result()->fetch_assoc()['TotalRecipes'] ?? 0);
$totalPages = max(1, (int) ceil($totalRecipes / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

$sql = "SELECT r.RecipeID, r.Title, r.ImagePath, r.Description, r.Views, r.CreatedAt AS CreatedAt, u.Username,
               COALESCE(AVG(rt.RatingValue), 0) AS AvgRating,r.Category,
               COUNT(rt.RatingID) AS RatingCount
        FROM dbProj_Recipes r
        JOIN dbProj_Users u ON r.UserID = u.UserID
        LEFT JOIN dbProj_Ratings rt ON r.RecipeID = rt.RecipeID
        WHERE $whereSql
        GROUP BY r.RecipeID, r.Title, r.ImagePath, r.Description, r.Views, r.CreatedAt, u.Username
        ORDER BY $orderBy
        LIMIT ? OFFSET ?";

$recipeParams = $params;
$recipeTypes = $types . 'ii';
$recipeParams[] = $perPage;
$recipeParams[] = $offset;

$stmt = $mysqli->prepare($sql);
$stmt->bind_param($recipeTypes, ...$recipeParams);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="row">
    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <?php include __DIR__ . '/../includes/recipe_card.php'; ?>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-12"><div class="alert alert-info">No recipes found.</div></div>
    <?php endif; ?>
</div>

<?php if ($totalPages > 1): ?>
    <nav aria-label="Recipe pages" class="d-flex justify-content-center">
        <ul class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a class="page-link recipe-page-link" href="#" data-page="<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<?php
if ($isAjaxRequest) {
    exit;
}
?>
