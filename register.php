<?php
// Keep error reporting on while we are debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'config.php';

// Check if the form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    // Prepared statement for security
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    
    if ($stmt === false) {
        die("Preparation failed: " . $conn->error);
    }

    $stmt->bind_param("ssss", $name, $email, $password, $role);

    if ($stmt->execute()) {
        echo "<script>alert('SUCCESS! You are now in the database.'); window.location='login.php';</script>";
    } else {
        echo "Execute failed: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | RecycleHub</title>
    <link rel="stylesheet" href="relog.css">
</head>
<body>

    <nav class="navbar">
        <div class="logo">Recycle<span>Hub</span></div>
        <ul class="nav-links">
            <li><a href="index.html">Home</a></li>
            <li><a href="login.php" class="btn-primary">Sign In</a></li>
        </ul>
    </nav>

    <section class="auth-screen">
        <div class="auth-card">
            <div class="auth-header">
                <h2>Join the Hub</h2>
                <p>Select your role to get started</p>
            </div>

            <form method="POST" action="register.php">
                <div class="form-row">
                    <div class="input-group">
                        <label>Full Name</label>
                        <input type="text" name="name" placeholder="John Doe" required>
                    </div>
                </div>

                <div class="input-group">
                    <label>Account Type</label>
                    <select class="custom-select" name="role" required>
                        <option value="" disabled selected>Who are you?</option>
                        <option value="user">Individual User</option>
                        <option value="factory">Factory Personnel</option>
                        <option value="recycling staff">Recycling Staff</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>

                <div class="input-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="name@company.com" required>
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn-primary btn-full">Create Account</button>
            </form>

            <p class="auth-switch">Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </section>

</body>
</html>