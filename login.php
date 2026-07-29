<?php
session_start();
include __DIR__ . '/config.php';

$error = '';

// If already logged in, redirect to their respective dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/admin_dashboard.php");
    } elseif ($_SESSION['role'] === 'factory') {
        header("Location: factory/factory_dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($email) && !empty($password)) {
        // Prepare statement to fetch user data safely
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verify hashed password
            if (password_verify($password, $user['password'])) {

                // Safely evaluate approval status across 'status' and 'is_approved' columns
                $status_is_approved = (!isset($user['status']) || strtolower($user['status']) === 'approved');
                $flag_is_approved   = (!isset($user['is_approved']) || (int)$user['is_approved'] === 1);

                $is_fully_approved  = $status_is_approved && $flag_is_approved;

                // Check approval status for restricted roles
                if (!$is_fully_approved && in_array($user['role'], ['factory', 'recycling staff'])) {
                    if ($user['role'] === 'factory') {
                        $error = "Your factory staff account is pending admin approval. Please wait for an administrator to activate your account.";
                    } else {
                        $error = "Your recycling staff account is pending admin approval. Please wait for document review.";
                    }
                } else {
                    // Login successful: Set up session variables
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['role']    = $user['role'];
                    $_SESSION['name']    = $user['name'];

                    // Redirect based on user role
                    if ($user['role'] === 'admin') {
                        header("Location: admin/admin_dashboard.php");
                    } elseif ($user['role'] === 'factory') {
                        header("Location: factory/factory_dashboard.php");
                    } else {
                        header("Location: index.php");
                    }
                    exit();
                }
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
        $stmt->close();
    } else {
        $error = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | RecycleHub</title>
    <link rel="stylesheet" href="admin/admin_dashboard.css">
    <style>
        .login-body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f4f7f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
        }

        .login-card {
            background: #ffffff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 420px;
            box-sizing: border-box;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h2 {
            margin: 0;
            color: #2d5a27;
            font-size: 2rem;
            font-weight: 700;
        }

        .login-header p {
            margin-top: 8px;
            color: #666;
            font-size: 0.95rem;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.9rem;
            margin-bottom: 20px;
            line-height: 1.4;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #cccccc;
            border-radius: 8px;
            font-size: 0.95rem;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #2d5a27;
        }

        .btn-submit {
            width: 100%;
            background-color: #2d5a27;
            color: #ffffff;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-submit:hover {
            background-color: #1e3d1a;
        }
    </style>
</head>
<body class="login-body">

    <div class="login-card">
        <div class="login-header">
            <h2>Welcome Back</h2>
            <p>Log in to your RecycleHub account</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-error">
                ⚠️ <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="name@company.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-submit">Sign In</button>
        </form>
    </div>

</body>
</html>