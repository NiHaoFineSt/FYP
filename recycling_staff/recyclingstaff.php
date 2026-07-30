<?php
session_start();
require_once __DIR__ . '/../config.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'recycling staff') {
    header("Location: ../login.php");
    exit();
}

$staff_id = $_SESSION['user_id'];

// Stats Queries based on inventory transactions
$total_items_res = $conn->query("SELECT COUNT(DISTINCT material_type) as total_items FROM inventory_transactions");
$total_items = $total_items_res ? ($total_items_res->fetch_assoc()['total_items'] ?? 0) : 0;

$stock_weight_res = $conn->query("SELECT SUM(weight) as total_weight FROM inventory_transactions");
$total_stock_weight = $stock_weight_res ? ($stock_weight_res->fetch_assoc()['total_weight'] ?? 0) : 0;
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
                    <h4>Material Types</h4>
                    <p style="font-size:20px; color:#2d5a27; font-weight:bold;"><?php echo $total_items; ?></p>
                </div>
                <div class="stat-card" style="background:white; padding:20px; border-radius:10px;">
                    <h4>Total Stock Weight</h4>
                    <p style="font-size:20px; color:green; font-weight:bold;"><?php echo number_format($total_stock_weight, 2); ?> kg</p>
                </div>
            </section>

            <section class="activity-section" style="background:white; padding:20px; border-radius:10px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 style="margin: 0;">Recent Inventory Logs</h3>
                    <a href="inventorylog.php" style="color: #2d5a27; text-decoration: none; font-weight: bold;">View Full Log →</a>
                </div>
                
                <table style="width:100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align:left; border-bottom: 2px solid #eee;">
                            <th style="padding: 10px 5px;">Transaction ID</th>
                            <th style="padding: 10px 5px;">Material Type</th>
                            <th style="padding: 10px 5px;">Net Weight</th>
                            <th style="padding: 10px 5px;">Date Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch latest logs matching the log layout
                        $log_query = "SELECT * FROM inventory_transactions ORDER BY transaction_id DESC LIMIT 5";
                        $log_res = $conn->query($log_query);

                        if ($log_res && $log_res->num_rows > 0):
                            while($row = $log_res->fetch_assoc()):
                        ?>
                        <tr style="border-bottom: 1px solid #f4f4f4;">
                            <td style="padding: 12px 5px;">#TX-<?php echo $row['transaction_id'] ?? $row['id']; ?></td>
                            <td style="padding: 12px 5px; font-weight: bold;"><?php echo htmlspecialchars($row['material_type']); ?></td>
                            <td style="padding: 12px 5px; color: green; font-weight: bold;">+ <?php echo number_format($row['weight'], 2); ?> kg</td>
                            <td style="padding: 12px 5px; color: #666;"><?php echo date("d M Y", strtotime($row['created_at'] ?? $row['date_added'] ?? $row['date'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr><td colspan="4" style="padding: 15px; text-align: center; color: #777;">No inventory transaction records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</body>
</html>