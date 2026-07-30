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
$stmt->close();

// HANDLE SUBMIT REQUEST TO FACTORY
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_request'])) {
    $material = trim($_POST['material']);
    $weight = floatval($_POST['weight']);

    if (!empty($material) && $weight > 0) {
        // Insert into factory_requests table with status 'Pending'
        $insert_stmt = $conn->prepare("INSERT INTO factory_requests (user_id, material, weight, status, created_at) VALUES (?, ?, ?, 'Pending', NOW())");
        $insert_stmt->bind_param("isd", $current_staff_id, $material, $weight);
        
        if ($insert_stmt->execute()) {
            echo "<script>alert('Transfer request for {$weight} kg of {$material} sent successfully!'); window.location.href='factory.php';</script>";
        } else {
            echo "<script>alert('Failed to send transfer request.'); window.location.href='factory.php';</script>";
        }
        $insert_stmt->close();
        exit();
    } else {
        echo "<script>alert('Please select a material and enter a valid weight.');</script>";
    }
}

// Fetch factory capacity entries
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
    
    <!-- Link Stylesheets -->
    <link rel="stylesheet" href="/style.css">
    <link rel="stylesheet" href="recyclingstaff.css">
    <link rel="stylesheet" href="factory.css">

    <!-- Embedded Backup Styles for Request Form & Layout -->
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f8fafc; color: #333; }
        
        .dashboard-wrapper { display: flex; min-height: 100vh; }
        .sidebar { width: 240px; background-color: #1b3818; color: #fff; padding: 20px; flex-shrink: 0; }
        .sidebar .logo { font-size: 22px; font-weight: bold; margin-bottom: 30px; color: #fff; }
        .sidebar .logo span { color: #68b04d; }
        .side-nav { display: flex; flex-direction: column; gap: 8px; }
        .side-nav a { color: #d0e7cb; text-decoration: none; padding: 10px 14px; border-radius: 6px; font-weight: 500; display: block; }
        .side-nav a:hover, .side-nav a.active { background-color: #2d5a27; color: #fff; }
        .side-nav a.logout { color: #f87171; margin-top: 20px; }
        .nav-divider { height: 1px; background-color: rgba(255,255,255,0.1); margin: 10px 0; }
        
        .dashboard-content { flex: 1; padding: 30px; background-color: #f8fafc; }
        .dash-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .dash-header h2 { color: #1e293b; font-size: 24px; font-weight: 700; }
        .dash-header p { color: #64748b; margin-top: 4px; }
        .user-badge { background-color: #e2e8f0; padding: 8px 14px; border-radius: 8px; font-weight: 600; color: #334155; }

        /* Request Form Card Styling */
        .request-form-card {
            background: #ffffff;
            padding: 22px 25px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin-bottom: 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .request-form-card h3 { margin-bottom: 15px; color: #1b3818; font-size: 1.1rem; }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 0.85rem; font-weight: 600; color: #475569; }
        .form-group input, .form-group select {
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 0.95rem;
            outline: none;
        }
        .form-group input:focus, .form-group select:focus { border-color: #2d5a27; }
        .btn-send {
            background-color: #2d5a27;
            color: #ffffff;
            border: none;
            padding: 10px 24px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            height: 42px;
        }
        .btn-send:hover { background-color: #1b3818; }

        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
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
                    <h2>Factory Capacities & Material Transfer</h2>
                    <p>View current recycling factory limits and submit material transfer requests.</p>
                </div>
                <div class="user-badge">
                    Logged in as: <?= htmlspecialchars($current_user['name'] ?? 'Staff') ?>
                </div>
            </header>

            <!-- FORM: SEND MATERIAL REQUEST TO FACTORY -->
            <section class="request-form-card">
                <h3>Request Material Transfer to Factory</h3>
                <form method="POST" action="factory.php" onsubmit="return confirm('Submit this transfer request to factory staff?');">
                    <input type="hidden" name="send_request" value="1">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="material">Material Category</label>
                            <select name="material" id="material" required>
                                <option value="" disabled selected>Select Material</option>
                                <option value="Plastic">Plastic</option>
                                <option value="Paper">Paper</option>
                                <option value="Glass">Glass</option>
                                <option value="Metal">Metal</option>
                                <option value="E-Waste">E-Waste</option>
                                <option value="Cardboard">Cardboard</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="weight">Weight (kg)</label>
                            <input type="number" step="0.1" min="0.1" name="weight" id="weight" placeholder="e.g. 25.5" required>
                        </div>
                        <button type="submit" class="btn-send">Send Request</button>
                    </div>
                </form>
            </section>

            <!-- FACTORY CAPACITY GRID -->
            <div class="factory-container">
                <h3 style="margin-bottom: 15px; color: #1e293b;">Factory Capacity Overview</h3>
                <?php if (empty($factories)): ?>
                    <div class="empty-state" style="background:#fff; padding:20px; border-radius:8px; text-align:center; color:#777;">
                        <p>No factory capacity records found in the database.</p>
                    </div>
                <?php else: ?>
                    <div class="factory-grid">
                        <?php foreach ($factories as $factory): ?>
                            <?php 
                                $current = floatval($factory['current_capacity']);
                                $max = floatval($factory['max_capacity']);
                                $percentage = ($max > 0) ? min(100, round(($current / $max) * 100)) : 0;
                                
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