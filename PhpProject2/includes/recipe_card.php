<?php
$hasRatings = isset($row['RatingCount']) && (int) $row['RatingCount'] > 0;
$avgRating = $hasRatings ? number_format((float) $row['AvgRating'], 1) : 'No ratings';
$imagePath = !empty($row['ImagePath']) ? $row['ImagePath'] : 'default.jpg';
$createdAt = !empty($row['CreatedAt']) ? date('M d, Y', strtotime($row['CreatedAt'])) : '';

// Retrieve category (checking both common column variations)
$category = !empty($row['CategoryName']) ? $row['CategoryName'] : (!empty($row['Category']) ? $row['Category'] : '');
?>
<div class="col-md-4 mb-4">
    <div class="card h-100 recipe-card">
        <!-- Image Wrapper -->
        <div class="recipe-card-img-wrapper">
            <img src="uploads/<?php echo htmlspecialchars($imagePath); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($row['Title']); ?>" onerror="this.src='uploads/default.jpg';">
        </div>

        <!-- Content Body -->
        <div class="card-body d-flex flex-column p-4">
            <!-- 1. Title -->
            <h5 class="recipe-title mb-2"><?php echo htmlspecialchars($row['Title']); ?></h5>

            <!-- 2. Creator | Views -->
            <p class="recipe-meta mb-1">By <?php echo htmlspecialchars($row['Username'] ?? 'User'); ?> | <?php echo (int) ($row['Views'] ?? 0); ?> views</p>

            <!-- 3. Published Date -->
            <?php if ($createdAt !== ''): ?>
                <p class="recipe-date mb-1">Published <?php echo htmlspecialchars($createdAt); ?></p>
            <?php endif; ?>

            <!-- 4. Rate / Rating -->
            <p class="recipe-rating mb-1">Rating: <span class="rating-value"><?php echo htmlspecialchars($avgRating); ?></span></p>

            <!-- 5. Category -->
            <?php if ($category !== ''): ?>
                <p class="recipe-category mb-3">Category: <span class="badge-category"><?php echo htmlspecialchars($category); ?></span></p>
            <?php endif; ?>

            <!-- 6. Description (Clamped to 2 lines) -->
            <p class="card-text recipe-description"><?php echo htmlspecialchars($row['Description'] ?? ''); ?></p>

            <!-- Button Container pushed to the bottom -->
            <div class="mt-auto pt-3">
                <a href="recipe_view.php?id=<?php echo (int) $row['RecipeID']; ?>" class="btn btn-primary view-recipe-btn">View Recipe</a>
            </div>
        </div>
    </div>
</div>