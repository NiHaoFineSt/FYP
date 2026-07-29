<?php
session_start();
include 'config.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Fetching users with role 'user'
$result = $conn->query("SELECT * FROM users WHERE LOWER(role) = 'user'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Citizens | RecycleHub</title>
    <link rel="stylesheet" href="admin.css">
    <style>
        .table-container { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-top: 20px; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { text-align: left; padding: 12px; background: #f4f7f6; color: #666; border-bottom: 2px solid #eee; text-transform: uppercase; font-size: 13px; }
        .data-table td { padding: 12px; border-bottom: 1px solid #eee; font-size: 15px; color: #333; }
        .id-badge { color: #2d5a27; font-weight: bold; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="logo">Recycle<span>Hub</span></div>
        <nav class="side-nav">
            <a href="admin.php">Overview</a>
            <a href="manage_user.php" class="active">Manage Citizens</a>
            <a href="manage_staff.php">Manage Staff</a>
            <a href="manage_center.php">Manage Centers</a>
            <a href="approve_center.php">Manage Factory</a>
            <a href="logout.php" class="logout">Logout</a>
        </nav>
    </aside>

    <main class="main-content">
        <h2>List of Registered Citizens</h2>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="id-badge">#<?php echo $row['user_id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>