<?php
require_once 'includes/db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username !== '' && $password !== '') {
        $stmt = $mysqli->prepare('SELECT UserID, Username, Password, Role FROM dbProj_Users WHERE Username = ?');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['Password'])) {
                $_SESSION['user_id'] = $row['UserID'];
                $_SESSION['username'] = $row['Username'];
                $_SESSION['role'] = $row['Role'];

                if ($row['Role'] === 'Admin') {
                    header('Location: admin/index.php');
                } elseif ($row['Role'] === 'Creator') {
                    header('Location: creator/my_recipes.php');
                } else {
                    header('Location: index.php');
                }
                exit;
            }
        }

        $error = 'Invalid username or password.';
    } else {
        $error = 'Username and password are required.';
    }
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Login</div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Login</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
