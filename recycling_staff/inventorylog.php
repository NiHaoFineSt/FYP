<?php
session_start();
require_once __DIR__ . '/../config.php';

// 1. SECURITY: Ensure only staff can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'recycling staff') {
    header("Location: ../login.php");
    exit();
}

$staff_id = $_SESSION['user_id']; // Ambil ID kakitangan yang sedang log masuk

// 2. FETCH TOTALS FOR STAT CARDS (DITAPIS UNTUK AKAUN STAF INI SAHAJA)
$totals_query = "SELECT material_type, SUM(weight) as total_weight 
                 FROM transactions 
                 WHERE status = 'Verified' AND (staff_id = '$staff_id' OR staff_id IS NULL OR staff_id = 0)
                 GROUP BY material_type";
$totals_res = $conn->query($totals_query);

$totals = ['Plastic' => 0, 'Metal' => 0, 'Glass' => 0, 'Paper' => 0];
if ($totals_res) {
    while($row = $totals_res->fetch_assoc()) {
        $type = ucfirst(strtolower($row['material_type'])); // Menyerasikan nama jenis bahan
        if (isset($totals[$type])) {
            $totals[$type] = $row['total_weight'];
        }
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
                    <p>Track verified recycling materials and warehouse stock (Logged as Staff #<?php echo $staff_id; ?>).</p>
                </div>
                <button class="btn-primary" onclick="window.print()" style="background: #2d5a27; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer;">Print Report</button>
            </header>

            <section class="stats-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
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
                <div class="stat-card" style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                    <h4>Total Paper</h4>
                    <p style="font-size: 1.8rem; font-weight: bold; color: #9c27b0;"><?php echo number_format($totals['Paper'], 1); ?> <span>kg</span></p>
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
                                <th style="padding: 12px;">Transaction ID</th>
                                <th style="padding: 12px;">Material Type</th>
                                <th style="padding: 12px;">Net Weight</th>
                                <th style="padding: 12px;">Date Added</th>
                                <th style="padding: 12px;">Handled By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // QUERY DIAGNOSTIK: Membaca data milik staff_id ini ATAU data lama yang kosong (NULL/0)
                            $query = "SELECT * FROM transactions 
                                      WHERE status = 'Verified' AND (staff_id = '$staff_id' OR staff_id IS NULL OR staff_id = 0) 
                                      ORDER BY date DESC";
                            $result = $conn->query($query);

                            if ($result && $result->num_rows > 0):
                                while($row = $result->fetch_assoc()):
                                    $tx_id = isset($row['transaction_id']) ? $row['transaction_id'] : (isset($row['id']) ? $row['id'] : 'N/A');
                                    $handled_by = ($row['staff_id'] == $staff_id) ? "You" : "Legacy/Unassigned (ID: ".$row['staff_id'].")";
                            ?>
                            <tr style="border-bottom: 1px solid #f9f9f9;">
                                <td style="padding: 12px;">#TX-<?php echo $tx_id; ?></td>
                                <td style="padding: 12px;"><span class="material-tag"><?php echo htmlspecialchars($row['material_type']); ?></span></td>
                                <td style="padding: 12px;"><strong>+ <?php echo $row['weight']; ?> kg</strong></td>
                                <td style="padding: 12px;"><?php echo date('d M Y', strtotime($row['date'])); ?></td>
                                <td style="padding: 12px;">
                                    <span style="background: <?php echo ($row['staff_id'] == $staff_id) ? '#e8f5e9; color: #2d5a27;' : '#fff3cd; color: #856404;'; ?> padding: 4px 8px; border-radius: 4px; font-size: 0.8rem;">
                                        <?php echo $handled_by; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php 
                                endwhile; 
                            else: 
                            ?>
                                <tr><td colspan="5" style="text-align: center; padding: 40px; color: #999;">No verified inventory logs found at all in 'transactions' table.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</body>
</html>