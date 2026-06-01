<?php
$avgRating = isset($row['AvgRating']) && $row['AvgRating'] !== null ? number_format((float) $row['AvgRating'], 1) : 'No ratings';
$imagePath = !empty($row['ImagePath']) ? $row['ImagePath'] : 'default.jpg';
$createdAt = !empty($row['CreatedAt']) ? date('M d, Y', strtotime($row['CreatedAt'])) : '';
?>
<div class="col-md-4 mb-4">
    <div class="card h-100 recipe-card">
        <img src="uploads/<?php echo htmlspecialchars($imagePath); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($row['Title']); ?>">
        <div class="card-body d-flex flex-column">
            <h5 class="card-title"><?php echo htmlspecialchars($row['Title']); ?></h5>
            <p class="text-muted mb-1">By <?php echo htmlspecialchars($row['Username']); ?> | <?php echo (int) $row['Views']; ?> views</p>
            <p class="text-warning mb-1">Rating: <?php echo htmlspecialchars($avgRating); ?></p>
            <?php if ($createdAt !== ''): ?>
                <p class="text-muted small mb-2">Published <?php echo htmlspecialchars($createdAt); ?></p>
            <?php endif; ?>
            <p class="card-text recipe-description"><?php echo htmlspecialchars($row['Description'] ?? ''); ?></p>
            <div class="text-center mt-auto">
                <a href="recipe_view.php?id=<?php echo (int) $row['RecipeID']; ?>" class="btn btn-primary btn-sm view-recipe-btn">View Recipe</a>
            </div>
        </div>
    </div>
</div>
