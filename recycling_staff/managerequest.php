<?php
session_start();
require_once __DIR__ . '/../config.php'; 

// 1. SECURITY: Ensure only staff can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'recycling staff') {
    header("Location: ../login.php");
    exit();
}

$staff_id = $_SESSION['user_id'];

// 2. FETCH STATS (Waiting Review)
$count_query = "SELECT COUNT(*) as total FROM recycling_requests WHERE status = 'pending'";
$count_res = $conn->query($count_query);
$waiting_count = $count_res->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Requests | RecycleHub Staff</title>
    <link rel="stylesheet" href="../style.css"> <link rel="stylesheet" href="managerequest.css">
</head>
<body>

    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="logo">Recycle<span>Hub</span></div>
            <nav class="side-nav">
                <a href="recyclingstaff.php">Overview</a>
                <a href="managerequest.php" class="active">Manage Requests</a>
                <a href="inventorylog.php">Inventory Log</a>
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
                    <h2>Manage Requests</h2>
                    <p>Review and verify citizen recycling submissions.</p>
                </div>
                <div class="user-badge" id="online-status" style="background: #2d5a27; color: white; padding: 5px 15px; border-radius: 20px;">
                    Staff ID: #<?php echo $staff_id; ?>
                </div>
            </header>

            <section class="stats-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
                <div class="stat-card" style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                    <h4>Waiting Review</h4>
                    <p style="font-size: 1.8rem; font-weight: bold; color: #d32f2f;"><?php echo $waiting_count; ?> <span>items</span></p>
                </div>
                <div class="stat-card" style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                    <h4>Today's Target</h4>
                    <p style="font-size: 1.8rem; font-weight: bold; color: #2d5a27;">85<span>%</span></p>
                </div>
                <div class="stat-card" style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                    <h4>Avg. Weight</h4>
                    <p style="font-size: 1.8rem; font-weight: bold; color: #1976d2;">4.2 <span>kg</span></p>
                </div>
            </section>

            <section class="activity-section" style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3>Incoming Queue</h3>
                </div>
                
                <div class="table-responsive">
                    <table class="activity-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 2px solid #eee;">
                                <th style="padding: 12px;">Request ID</th>
                                <th style="padding: 12px;">Citizen</th>
                                <th style="padding: 12px;">Material</th>
                                <th style="padding: 12px;">Weight</th>
                                <th style="padding: 12px;">Received</th>
                                <th style="padding: 12px;">Verification</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch all pending requests
                            $query = "SELECT r.*, u.name FROM recycling_requests r 
                                      JOIN users u ON r.user_id = u.user_id 
                                      WHERE r.status = 'pending' 
                                      ORDER BY r.log_time ASC";
                            $result = $conn->query($query);

                            if ($result && $result->num_rows > 0):
                                while($row = $result->fetch_assoc()):
                            ?>
                            <tr style="border-bottom: 1px solid #f9f9f9;">
                                <td style="padding: 12px;">#REQ-<?php echo $row['request_id']; ?></td>
                                <td style="padding: 12px;"><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                <td style="padding: 12px;"><span class="material-tag"><?php echo $row['material_type']; ?></span></td>
                                <td style="padding: 12px;"><?php echo $row['weight']; ?> kg</td>
                                <td style="padding: 12px;"><?php echo date('h:i A', strtotime($row['log_time'])); ?></td>
                                <td style="padding: 12px;">
                                    <a href="verify_action.php?id=<?php echo $row['request_id']; ?>&status=approve" class="btn-action" style="background: #e8f5e9; color: #2e7d32; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 0.85rem;">Verify</a>
                                    <a href="verify_action.php?id=<?php echo $row['request_id']; ?>&status=reject" class="btn-action" style="background: #ffebee; color: #c62828; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 0.85rem;">Decline</a>
                                </td>
                            </tr>
                            <?php 
                                endwhile; 
                            else: 
                            ?>
                                <tr><td colspan="6" style="text-align: center; padding: 40px; color: #999;">No pending requests in the queue.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

</body>
</html>