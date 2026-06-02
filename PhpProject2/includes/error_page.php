<?php
function renderErrorPage($statusCode, $title, $message) {
    http_response_code((int) $statusCode);
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $basePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $basePath = preg_replace('#/(admin|creator|actions|errors)$#', '', $basePath);
    $homeUrl = rtrim($basePath, '/') . '/index.php';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo (int) $statusCode; ?> | RecipeShare</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <main class="container min-vh-100 d-flex align-items-center justify-content-center">
            <section class="text-center">
                <p class="display-1 fw-bold text-success mb-0"><?php echo (int) $statusCode; ?></p>
                <h1 class="h3 mb-3"><?php echo $safeTitle; ?></h1>
                <p class="text-muted mb-4"><?php echo $safeMessage; ?></p>
                <a class="btn btn-success" href="<?php echo htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8'); ?>">Back to Home</a>
            </section>
        </main>
    </body>
    </html>
    <?php
}
