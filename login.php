<?php
// 1. Start buffering to prevent "Header already sent" warnings
ob_start(); 
session_start();
include 'config.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 2. Sanitize input to prevent SQL injection
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // 3. Search for user by email
    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // 4. Verify the hashed password
        if (password_verify($password, $user['password'])) {

            // --- STAFF APPROVAL CHECK ---
            // If the role is staff and they are NOT approved, block access
            if ($user['role'] == 'recycling staff' && $user['is_approved'] == 0) {
                $error = "Your account is pending admin approval. Please wait for document review.";
            } else {
                // Login Success! Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'];

                ob_end_clean(); 

                // 5. Redirect based on role
                if ($user['role'] == 'admin') {
                    header("Location: admin.php");
                } elseif ($user['role'] == 'factory') {
                    header("Location: factory_dashboard.php");
                } elseif ($user['role'] == 'recycling staff') {
                    header("Location: recycling_staff/recyclingstaff.php");
                } else {
                    header("Location: user/citizen_dashboard.php");
                }
                exit();
            }
        } else {
            $error = "Incorrect password!";
        }
    } else {
        $error = "No account found with that email!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | RecycleHub</title>
    <link rel="stylesheet" href="relog.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <nav class="navbar">
        <div class="logo">Recycle<span>Hub</span></div>
        <ul class="nav-links">
            <li><a href="index.html">Home</a></li>
            <li><a href="register.php" class="btn-primary" style="text-decoration:none; color:white;">Register</a></li>
        </ul>
    </nav>

    <section class="auth-screen">
        <div class="auth-card">
            <div class="auth-header">
                <h2>Welcome Back</h2>
                <p>Log in to your RecycleHub account</p>
            </div>

            <?php if($error != ""): ?>
                <div style="color: #d9534f; background: #f2dede; padding: 12px; border-radius: 8px; text-align: center; font-size: 0.9rem; margin-bottom: 20px; border: 1px solid #ebccd1;">
                    ⚠️ <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="input-group">
                    <label style="display:block; margin-bottom: 8px; font-weight:600;">Email Address</label>
                    <input type="email" name="email" placeholder="name@company.com" required 
                           style="width:100%; padding:12px; border:1px solid #ddd; border-radius:8px;">
                </div>

                <div class="input-group" style="margin-top: 15px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <label style="font-weight:600;">Password</label>
                        <a href="#" style="font-size: 0.8rem; color: #68b04d; text-decoration: none;">Forgot?</a>
                    </div>
                    <input type="password" name="password" placeholder="••••••••" required 
                           style="width:100%; padding:12px; border:1px solid #ddd; border-radius:8px;">
                </div>

                <button type="submit" class="btn-primary" style="width:100%; margin-top: 25px; padding: 12px; border:none; border-radius:8px; cursor:pointer; font-weight:bold;">
                    Sign In
                </button>
            </form>

            <p class="auth-switch" style="text-align:center; margin-top: 20px; color: #666;">
                New to the hub? <a href="register.php" style="color: #2d5a27; text-decoration:none; font-weight:bold;">Create an account</a>
            </p>
        </div>
    </section>

</body>
</html>