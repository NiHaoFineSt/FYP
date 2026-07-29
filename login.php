<?php
// 1. Start buffering to prevent "Header already sent" warnings
ob_start(); 
session_start();
include 'config.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // 2. Prepared Statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // 3. Verify the hashed password
        if (password_verify($password, $user['password'])) {

            // --- APPROVAL CHECKS FOR STAFF & FACTORY ROLES ---
            // Handles both 'status' ('pending'/'approved') and 'is_approved' (0/1) schema variations
            $is_pending_status = (isset($user['status']) && $user['status'] === 'pending');
            $is_unapproved_flag = (isset($user['is_approved']) && $user['is_approved'] == 0);

            if ($user['role'] === 'recycling staff' && ($is_pending_status || $is_unapproved_flag)) {
                $error = "Your recycling staff account is pending admin approval. Please wait for document review.";
            } elseif ($user['role'] === 'factory' && ($is_pending_status || $is_unapproved_flag)) {
                $error = "Your factory staff account is pending admin approval. Please wait for an administrator to activate your account.";
            } else {
                // Login Success! Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'];

                ob_end_clean(); 

                // 4. Redirect based on role
                if ($user['role'] === 'admin') {
                    header("Location: admin.php");
                } elseif ($user['role'] === 'factory') {
                    header("Location: factory/factorystaff.php");
                } elseif ($user['role'] === 'recycling staff') {
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
    $stmt->close();
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

            <?php if(!empty($error)): ?>
                <div style="color: #d9534f; background: #f2dede; padding: 12px; border-radius: 8px; text-align: center; font-size: 0.9rem; margin-bottom: 20px; border: 1px solid #ebccd1;">
                    ⚠️ <?php echo htmlspecialchars($error); ?>
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