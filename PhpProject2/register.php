<?php 
include('includes/header.php'); 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    // Task 1.1: Modern Hashing
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); 
    $role = 3; // Default 'Visitor' role

    // Task 2: Secure Prepared Statement
    $stmt = $conn->prepare("INSERT INTO dbProj_users (Username, Email, Password, RoleID) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $username, $email, $password, $role);
    
    if ($stmt->execute()) {
        echo "<script>alert('Account created! Please login.'); window.location='login.php';</script>";
    } else {
        // Handling duplicates
        $error = "Registration failed. Username or Email might already be taken.";
    }
}
?>

<div class="auth-container">
    <form action="register.php" method="POST" class="styled-form" onsubmit="return validateRegister()">
        <h2>Join RecipeShare</h2>
        <p class="subtitle">Create an account to start sharing</p>

        <?php if(isset($error)) echo "<p style='color:red; font-size:0.8rem;'>$error</p>"; ?>
        
        <div class="input-wrapper">
            <input type="text" id="reg_user" name="username" placeholder="Username" required>
        </div>

        <div class="input-wrapper">
            <input type="email" id="reg_email" name="email" placeholder="Email Address" required>
        </div>
        
        <div class="input-wrapper">
            <input type="password" id="reg_pass" name="password" placeholder="Password" required>
        </div>

        <div class="input-wrapper">
            <input type="password" id="reg_confirm" placeholder="Confirm Password" required>
        </div>
        
        <button type="submit" class="btn-gradient">Sign Up</button>
        
        <div class="form-footer">
            <p>Already a member? <a href="login.php">Login here</a></p>
        </div>
    </form>
</div>

<script>
// Task 1.1: JavaScript Validation
function validateRegister() {
    const user = document.getElementById('reg_user').value;
    const pass = document.getElementById('reg_pass').value;
    const confirm = document.getElementById('reg_confirm').value;
    
    if (user.length < 4) {
        alert("Username must be at least 4 characters.");
        return false;
    }
    if (pass.length < 6) {
        alert("Password must be at least 6 characters.");
        return false;
    }
    if (pass !== confirm) {
        alert("Passwords do not match!");
        return false;
    }
    return true;
}
</script>

<?php include('includes/footer.php'); ?>