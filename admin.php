<?php
session_start();
// Include config from the same directory to fix "Failed to open stream" error
include('config.php'); 

// SECURITY: Only allow logged-in 'admin'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// 1. FETCH TOTALS FOR CARDS
$total_citizens = $conn->query("SELECT COUNT(*) as count FROM users WHERE LOWER(role)='user'")->fetch_assoc()['count'] ?? 0;
$total_staff = $conn->query("SELECT COUNT(*) as count FROM users WHERE LOWER(role)='recycling staff'")->fetch_assoc()['count'] ?? 0;
// Ensure 'recycling_centers' table exists to avoid SQL exception
$total_centers = $conn->query("SELECT COUNT(*) as count FROM recycling_centers")->fetch_assoc()['count'] ?? 0;

// 2. FETCH RECENT ACTIVITY (Removed Staff Join)
// Using u.name to fix "Unknown column u.username" error
$recent_query = "SELECT t.*, u.name as citizen_name 
                 FROM transactions t 
                 JOIN users u ON t.user_id = u.user_id 
                 ORDER BY t.date DESC LIMIT 5";
$recent_result = $conn->query($recent_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | RecycleHub</title>
    <link rel="stylesheet" href="admin.css">
    <style>
        /* Styles to create the proper table format from your screenshot */
        .activity-container { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .activity-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .activity-table th { text-align: left; padding: 12px; border-bottom: 2px solid #eee; color: #666; font-size: 14px; text-transform: uppercase; }
        .activity-table td { padding: 12px; border-bottom: 1px solid #eee; font-size: 15px; color: #333; }
        .weight-text { font-weight: 500; }
        .citizen-name { font-weight: bold; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="logo">Recycle<span>Hub</span></div>
        <nav class="side-nav">
            <a href="admin.php" class="active">Overview</a>
            <a href="manage_user.php">Manage Citizens</a>
            <a href="manage_staff.php">Manage Staff</a>
            <a href="manage_center.php">Manage Centers</a>
            <a href="logout.php" class="logout">Logout</a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="dash-header">
            <h2>Admin Operations</h2>
            <div class="admin-badge">Admin ID: #<?php echo $_SESSION['user_id']; ?></div>
        </header>

        <section class="stats-grid">
            <div class="stat-card">
                <h3>Total Registered Citizens</h3>
                <a href="manage_users.php?role=user" style="text-decoration:none;"><p><?php echo $total_citizens; ?></p></a>
            </div>
            <div class="stat-card">
                <h3>Total Recycling Staff</h3>
                <a href="manage_users.php?role=recycling staff" style="text-decoration:none;"><p><?php echo $total_staff; ?></p></a>
            </div>
            <div class="stat-card">
                <h3>Total Recycling Centers</h3>
                <a href="manage_centers.php" style="text-decoration:none;"><p><?php echo $total_centers; ?></p></a>
            </div>
        </section>

        <div class="activity-container">
            <h3 style="color: #2d4a27;">Recent System Activity</h3>
            <table class="activity-table">
                <thead>
                    <tr>
                        <th>Citizen</th>
                        <th>Material</th>
                        <th>Weight</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_result && $recent_result->num_rows > 0): ?>
                        <?php while($row = $recent_result->fetch_assoc()): ?>
                            <tr>
                                <td class="citizen-name"><?php echo htmlspecialchars($row['citizen_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['material_type']); ?></td>
                                <td class="weight-text"><?php echo number_format($row['weight'], 2); ?> kg</td>
                                <td><?php echo date('d M Y', strtotime($row['date'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center; padding:20px;">No recent activity found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>