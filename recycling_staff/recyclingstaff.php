<?php
session_start();
require_once __DIR__ . '/../config.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'recycling staff') {
    header("Location: ../login.php");
    exit();
}

$staff_id = $_SESSION['user_id'];

// Stats Queries for Inventory
$total_items_res = $conn->query("SELECT COUNT(*) as total_items FROM inventory");
$total_items = $total_items_res ? ($total_items_res->fetch_assoc()['total_items'] ?? 0) : 0;

$stock_weight_res = $conn->query("SELECT SUM(weight) as total_weight FROM inventory");
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
                    <h4>Total Inventory Categories</h4>
                    <p style="font-size:20px; color:#2d5a27; font-weight:bold;"><?php echo $total_items; ?></p>
                </div>
                <div class="stat-card" style="background:white; padding:20px; border-radius:10px;">
                    <h4>Total Stock Weight</h4>
                    <p style="font-size:20px; color:green; font-weight:bold;"><?php echo number_format($total_stock_weight, 2); ?> kg</p>
                </div>
            </section>

            <section class="activity-section" style="background:white; padding:20px; border-radius:10px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 style="margin: 0;">Inventory Summary Log</h3>
                    <a href="inventorylog.php" style="color: #2d5a27; text-decoration: none; font-weight: bold;">View Full Log →</a>
                </div>
                
                <table style="width:100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align:left; border-bottom: 2px solid #eee; padding-bottom: 8px;">
                            <th style="padding: 10px 5px;">Material Type</th>
                            <th style="padding: 10px 5px;">Total Weight (kg)</th>
                            <th style="padding: 10px 5px;">Last Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch current stock from inventory table
                        $inv_query = "SELECT * FROM inventory ORDER BY last_updated DESC LIMIT 5";
                        $inv_res = $conn->query($inv_query);

                        if ($inv_res && $inv_res->num_rows > 0):
                            while($row = $inv_res->fetch_assoc()):
                        ?>
                        <tr style="border-bottom: 1px solid #f4f4f4;">
                            <td style="padding: 12px 5px;"><?php echo htmlspecialchars($row['material_type'] ?? $row['material']); ?></td>
                            <td style="padding: 12px 5px;"><?php echo number_format($row['weight'] ?? $row['quantity'], 2); ?> kg</td>
                            <td style="padding: 12px 5px; color: #666;"><?php echo date("d M Y, h:i A", strtotime($row['last_updated'] ?? $row['updated_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr><td colspan="3" style="padding: 15px; text-align: center; color: #777;">No inventory records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</body>
</html>