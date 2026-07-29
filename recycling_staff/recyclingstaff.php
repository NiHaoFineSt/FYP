<?php
session_start();
require_once __DIR__ . '/../config.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'recycling staff') {
    header("Location: ../login.php");
    exit();
}

$staff_id = $_SESSION['user_id'];

// Stats Queries
$pending_res = $conn->query("SELECT COUNT(*) as total FROM recycling_requests WHERE status = 'pending'");
$pending_count = $pending_res->fetch_assoc()['total'] ?? 0;

$weight_res = $conn->query("SELECT SUM(weight) as today_kg FROM recycling_requests WHERE status = 'approved' AND DATE(log_time) = CURDATE()");
$today_weight = $weight_res->fetch_assoc()['today_kg'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Portal | RecycleHub</title>
    <link rel="stylesheet" href="../style.css"> 
    <link rel="stylesheet" href="recyclingstaff.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="logo">Recycle<span>Hub</span></div>
            <nav class="side-nav">
                <a href="recyclingstaff.php" class="active">Overview</a>
                <a href="inventorylog.php">Inventory Log</a>
                <a href="staffscan.php">Scan QR</a>
                <a href="factory.php">Factory</a>
                <a href="staffchat.php">Staff Chat</a>
                <a href="staff_profile.php">Profile</a>
                <div class="nav-divider"></div>
                <a href="../logout.php" class="logout">Logout</a>
            </nav>
        </aside>

        <main class="dashboard-content">
            <header class="dash-header">
                <h2>Staff Operations</h2>
                <div class="user-badge">Staff ID: #<?php echo $staff_id; ?></div>
            </header>

            <section class="stats-grid" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="stat-card" style="background:white; padding:20px; border-radius:10px;">
                    <h4>Pending</h4>
                    <p style="font-size:20px; color:red;"><?php echo $pending_count; ?></p>
                </div>
                <div class="stat-card" style="background:white; padding:20px; border-radius:10px;">
                    <h4>Today's Weight</h4>
                    <p style="font-size:20px; color:green;"><?php echo number_format($today_weight, 1); ?> kg</p>
                </div>
            </section>

            <section class="activity-section" style="background:white; padding:20px; border-radius:10px;">
                <h3>Incoming Submissions</h3>
                <table style="width:100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align:left; border-bottom: 1px solid #eee;">
                            <th>Citizen</th>
                            <th>Material</th>
                            <th>Weight</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $req_query = "SELECT r.*, u.name FROM recycling_requests r 
                                     JOIN users u ON r.user_id = u.user_id 
                                     WHERE r.status = 'pending' LIMIT 5";
                        $req_res = $conn->query($req_query);

                        if ($req_res && $req_res->num_rows > 0):
                            while($row = $req_res->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo $row['material_type']; ?></td>
                            <td><?php echo $row['weight']; ?> kg</td>
                            <td>
                                <a href="verify_action.php?id=<?php echo $row['request_id']; ?>&status=approve">Verify</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr><td colspan="4">No pending requests.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</body>
</html>