<?php include('includes/header.php'); ?>

<!-- Hero Section with Search -->
<section class="hero">
    <div class="hero-content">
        <h1>Discover Delicious Recipes</h1>
        <form action="index.php" method="GET" class="search-bar">
            <input type="text" name="search" placeholder="Search by title, date, or creator...">
            <button type="submit">Search</button>
        </form>
    </div>
</section>

<!-- Recipe Grid Section -->
<main class="container">
    <h2 class="section-title">Latest Recipes</h2>
    <div class="recipe-grid">
        <?php
        // Basic query for newest first
        $sql = "SELECT * FROM dbProj_recipes ORDER BY CreatedAt DESC";

        // If user searches, update the query (Task 1.3)
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = $conn->real_escape_string($_GET['search']);
            $sql = "SELECT * FROM dbProj_recipes 
                    WHERE Title LIKE '%$search%' 
                    OR ShortDescription LIKE '%$search%' 
                    ORDER BY CreatedAt DESC";
        }

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo '<div class="recipe-card">';
                echo '<img src="uploads/' . $row['MainImage'] . '" alt="Recipe Image">';
                echo '<div class="card-body">';
                echo '<h3>' . htmlspecialchars($row['Title']) . '</h3>';
                echo '<p>' . htmlspecialchars($row['ShortDescription']) . '</p>';
                echo '<a href="recipe_details.php?id=' . $row['RecipeID'] . '" class="view-btn">View More</a>';
                echo '</div></div>';
            }
        } else {
            echo "<p>No recipes found matching your search.</p>";
        }
        ?>
    </div>
</main>

<?php include('includes/footer.php'); ?>