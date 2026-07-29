<?php
session_start();
require_once __DIR__ . '/../config.php';

// Authentication Check - Recycling Staff Only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'recycling staff') {
    header("Location: ../login.php");
    exit();
}

$current_staff_id = $_SESSION['user_id'];

// Get current staff name
$stmt = $conn->prepare("SELECT name FROM users WHERE user_id = ?");
$stmt->bind_param("i", $current_staff_id);
$stmt->execute();
$current_user = $stmt->get_result()->fetch_assoc();

// Fetch factory capacity entries
// Adjust table/column names if your database table is named differently
$factory_query = "SELECT f.factory_id, f.factory_name, f.current_capacity, f.max_capacity, f.last_updated, u.name AS updated_by 
                  FROM factory_capacity f 
                  LEFT JOIN users u ON f.updated_by_id = u.user_id 
                  ORDER BY f.factory_name ASC";

$factory_result = $conn->query($factory_query);
$factories = $factory_result ? $factory_result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factory Capacity | RecycleHub</title>
    <link rel="stylesheet" href="recyclingstaff.css">
    <link rel="stylesheet" href="factory.css">
</head>
<body>

    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="logo">Recycle<span>Hub</span></div>
            <nav class="side-nav">
                <a href="recyclingstaff.php">Overview</a>
                <a href="inventorylog.php">Inventory Log</a>
                <a href="staffscan.php">Scan QR</a>
                <a href="factory.php" class="active">Factory</a>
                <a href="staffchat.php">Staff Chat</a>
                <a href="staffprofile.php">Profile</a>
                <div class="nav-divider"></div>
                <a href="../logout.php" class="logout">Logout</a>
            </nav>
        </aside>

        <main class="dashboard-content">
            <header class="dash-header">
                <div>
                    <h2>Factory Capacities</h2>
                    <p>View current recycling factory limits updated by factory personnel.</p>
                </div>
                <div class="user-badge">
                    Logged in as: <?= htmlspecialchars($current_user['name'] ?? 'Staff') ?>
                </div>
            </header>

            <div class="factory-container">
                <?php if (empty($factories)): ?>
                    <div class="empty-state">
                        <p>No factory capacity records found in the database.</p>
                    </div>
                <?php else: ?>
                    <div class="factory-grid">
                        <?php foreach ($factories as $factory): ?>
                            <?php 
                                $current = floatval($factory['current_capacity']);
                                $max = floatval($factory['max_capacity']);
                                $percentage = ($max > 0) ? min(100, round(($current / $max) * 100)) : 0;
                                
                                // Dynamic progress bar colors based on capacity level
                                $status_class = 'status-normal';
                                if ($percentage >= 90) {
                                    $status_class = 'status-danger';
                                } elseif ($percentage >= 70) {
                                    $status_class = 'status-warning';
                                }
                            ?>
                            <div class="factory-card">
                                <div class="card-header">
                                    <h3><?= htmlspecialchars($factory['factory_name']) ?></h3>
                                    <span class="capacity-badge <?= $status_class ?>"><?= $percentage ?>% Full</span>
                                </div>

                                <div class="capacity-details">
                                    <p><strong>Current Load:</strong> <?= number_format($current, 2) ?> kg</p>
                                    <p><strong>Max Capacity:</strong> <?= number_format($max, 2) ?> kg</p>
                                </div>

                                <div class="progress-bar-bg">
                                    <div class="progress-bar-fill <?= $status_class ?>" style="width: <?= $percentage ?>%;"></div>
                                </div>

                                <div class="card-footer">
                                    <span>Updated by: <?= htmlspecialchars($factory['updated_by'] ?? 'Factory Staff') ?></span>
                                    <span><?= !empty($factory['last_updated']) ? date('d M Y, h:i A', strtotime($factory['last_updated'])) : 'N/A' ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

</body>
</html>