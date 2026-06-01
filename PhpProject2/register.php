<?php
require_once 'includes/db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');
    $role = $_POST['role'] ?? 'Viewer';
    $allowedRoles = ['Viewer', 'Creator'];

    if ($username === '' || $email === '' || $password === '') {
        $error = 'All fields are required.';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!in_array($role, $allowedRoles, true)) {
        $error = 'Invalid role selected.';
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare('INSERT INTO dbProj_Users (Username, Email, Password, Role) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssss', $username, $email, $hashedPassword, $role);

        if ($stmt->execute()) {
            header('Location: login.php');
            exit;
        }

        $error = 'Username or email already exists.';
    }
}

require_once 'includes/Header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Create Account</div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form action="register.php" method="POST" onsubmit="return validateRegisterForm()">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" id="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Register as</label>
                        <select name="role" class="form-select">
                            <option value="Viewer">Viewer</option>
                            <option value="Creator">Creator</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Register</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function validateRegisterForm() {
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('confirm_password').value;

    if (username.length < 3) {
        alert('Username must be at least 3 characters.');
        return false;
    }

    if (password.length < 6) {
        alert('Password must be at least 6 characters.');
        return false;
    }

    if (password !== confirm) {
        alert('Passwords do not match.');
        return false;
    }

    return true;
}
</script>

<?php require_once 'includes/Footer.php'; ?>
