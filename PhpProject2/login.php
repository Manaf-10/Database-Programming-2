<?php 
include('includes/header.php'); 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    // 1. Prepare the statement to find the user (Task 2: Secure DB usage)
    $stmt = $conn->prepare("SELECT UserID, Password, RoleID FROM dbProj_users WHERE Username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // 2. Verify the encrypted password (Task 1.1)
        if (password_verify($pass, $row['Password'])) {
            // 3. Set Session variables for Role-based access
            $_SESSION['user_id'] = $row['UserID'];
            $_SESSION['role_id'] = $row['RoleID'];
            
            // Redirect to home page upon success
            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }
}
?>

<div class="auth-container">
    <form action="login.php" method="POST" class="styled-form">
        <h2>Welcome Back</h2>
        <p class="subtitle">Log in to manage your recipes</p>
        
        <?php if(isset($error)) echo "<p style='color:red; font-size:0.8rem;'>$error</p>"; ?>
        
        <div class="input-wrapper">
            <input type="text" name="username" placeholder="Username" required>
        </div>
        
        <div class="input-wrapper">
            <input type="password" name="password" placeholder="Password" required>
        </div>
        
        <button type="submit" class="btn-gradient">Enter Dashboard</button>
        
        <div class="form-footer">
            <p>New here? <a href="register.php">Create an account</a></p>
        </div>
    </form>
</div>

<?php include('includes/footer.php'); ?>