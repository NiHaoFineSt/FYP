<?php
session_start();
include '../config.php'; 

// 1. SECURITY: Ensure only staff can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'recycling staff') {
    header("Location: ../login.php");
    exit();
}

// 2. FETCH TOTALS FOR STAT CARDS
$totals_query = "SELECT material_type, SUM(weight) as total_weight 
                 FROM recycling_requests 
                 WHERE status = 'approved' 
                 GROUP BY material_type";
$totals_res = $conn->query($totals_query);

$totals = ['Plastic' => 0, 'Metal' => 0, 'Glass' => 0];
while($row = $totals_res->fetch_assoc()) {
    $type = ucfirst(strtolower($row['material_type'])); // Normalize naming
    if (isset($totals[$type])) {
        $totals[$type] = $row['total_weight'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Log | RecycleHub Staff</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="managerequest.css"> 
</head>
<body>

    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="logo">Recycle<span>Hub</span></div>
            <nav class="side-nav">
                <a href="recyclingstaff.php">Overview</a>
                <a href="managerequest.php">Manage Requests</a>
                <a href="inventorylog.php" class="active">Inventory Log</a>
                <a href="staffscan.php">Scan QR</a>
                <a href="factory.php">Factory</a>
                <a href="staffchat.php">Staff Chat</a>
                <a href="staffprofile.php">Profile</a>
                <div class="nav-divider"></div>
                <a href="../logout.php" class="logout">Logout</a>
            </nav>
        </aside>

        <main class="dashboard-content">
            <header class="dash-header">
                <div>
                    <h2>Inventory Management</h2>
                    <p>Track verified recycling materials and warehouse stock.</p>
                </div>
                <button class="btn-primary" onclick="window.print()" style="background: #2d5a27; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer;">Print Report</button>
            </header>

            <section class="stats-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
                <div class="stat-card" style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                    <h4>Total Plastic</h4>
                    <p style="font-size: 1.8rem; font-weight: bold; color: #1976d2;"><?php echo number_format($totals['Plastic'], 1); ?> <span>kg</span></p>
                </div>
                <div class="stat-card" style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                    <h4>Total Metal</h4>
                    <p style="font-size: 1.8rem; font-weight: bold; color: #f57c00;"><?php echo number_format($totals['Metal'], 1); ?> <span>kg</span></p>
                </div>
                <div class="stat-card" style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                    <h4>Total Glass</h4>
                    <p style="font-size: 1.8rem; font-weight: bold; color: #388e3c;"><?php echo number_format($totals['Glass'], 1); ?> <span>kg</span></p>
                </div>
            </section>

            <section class="activity-section" style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                <div class="section-header">
                    <h3>Inventory Transactions</h3>
                </div>
                
                <div class="table-responsive">
                    <table class="activity-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 2px solid #eee;">
                                <th style="padding: 12px;">Log ID</th>
                                <th style="padding: 12px;">Material Type</th>
                                <th style="padding: 12px;">Net Weight</th>
                                <th style="padding: 12px;">Date Added</th>
                                <th style="padding: 12px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch all APPROVED requests (This represents our inventory)
                            $query = "SELECT * FROM recycling_requests WHERE status = 'approved' ORDER BY log_time DESC";
                            $result = $conn->query($query);

                            if ($result && $result->num_rows > 0):
                                while($row = $result->fetch_assoc()):
                            ?>
                            <tr style="border-bottom: 1px solid #f9f9f9;">
                                <td style="padding: 12px;">#INV-<?php echo $row['request_id']; ?></td>
                                <td style="padding: 12px;"><span class="material-tag"><?php echo htmlspecialchars($row['material_type']); ?></span></td>
                                <td style="padding: 12px;"><strong>+ <?php echo $row['weight']; ?> kg</strong></td>
                                <td style="padding: 12px;"><?php echo date('d M Y', strtotime($row['log_time'])); ?></td>
                                <td style="padding: 12px;"><span style="background: #e8f5e9; color: #2d5a27; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem;">In Stock</span></td>
                            </tr>
                            <?php 
                                endwhile; 
                            else: 
                            ?>
                                <tr><td colspan="5" style="text-align: center; padding: 40px; color: #999;">No verified inventory logs found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

</body>
</html>