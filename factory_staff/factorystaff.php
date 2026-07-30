<?php
session_start();
include __DIR__ . '/../config.php';

// SECURITY CHECK (Ensure user is factory staff)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'factory') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$max_capacity = 1000.0; // Maximum factory storage capacity in kg

// HANDLE DECISION (Accept / Reject Transfer Requests)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    $req_id = intval($_POST['request_id']);
    $action = $_POST['action_type']; // 'Approved' or 'Rejected'
    $req_weight = floatval($_POST['request_weight']);

    if ($action === 'Approved') {
        // Calculate current total approved weight to prevent capacity overflow
        $cap_query = "SELECT SUM(weight) as total_weight FROM factory_requests WHERE status IN ('Approved', 'Completed')";
        $cap_res = $conn->query($cap_query);
        $cap_row = $cap_res->fetch_assoc();
        $current_weight = floatval($cap_row['total_weight'] ?? 0);

        if (($current_weight + $req_weight) > $max_capacity) {
            echo "<script>alert('🚨 Factory Storage Limit Exceeded! Cannot accept more material.'); window.location.href='factorystaff.php';</script>";
            exit();
        }
    }

    // Update status in factory_requests table
    $update_stmt = $conn->prepare("UPDATE factory_requests SET status = ? WHERE id = ?");
    $update_stmt->bind_param("si", $action, $req_id);
    
    if ($update_stmt->execute()) {
        echo "<script>alert('Request #$req_id successfully $action!'); window.location.href='factorystaff.php';</script>";
    } else {
        echo "<script>alert('Failed to update request status.'); window.location.href='factorystaff.php';</script>";
    }
    $update_stmt->close();
    exit();
}

// 1. CALCULATE CURRENT STORAGE LOAD
$current_load_query = "SELECT SUM(weight) as total_weight FROM factory_requests WHERE status IN ('Approved', 'Completed')";
$load_res = $conn->query($current_load_query);
$load_row = $load_res->fetch_assoc();
$current_capacity = floatval($load_row['total_weight'] ?? 0.0);

$fill_percent = min(100, round(($current_capacity / $max_capacity) * 100, 1));
$status_badge = "LIVE LOAD";

if ($fill_percent >= 85 && $fill_percent < 100) {
    $status_badge = "HIGH LOAD";
} elseif ($fill_percent >= 100) {
    $status_badge = "FACTORY FULL";
}

// 2. FETCH PENDING REQUESTS JOINED WITH RECYCLING STAFF NAME
$pending_query = "SELECT fr.id, fr.material, fr.weight, fr.created_at, u.name as staff_name 
                 FROM factory_requests fr 
                 LEFT JOIN users u ON fr.user_id = u.user_id 
                 WHERE fr.status = 'Pending' 
                 ORDER BY fr.created_at ASC";
$pending_result = $conn->query($pending_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factory Command Center | RecycleHub</title>
    
    <!-- External Stylesheets -->
    <link rel="stylesheet" href="/style.css">
    <link rel="stylesheet" href="factorystaff.css">
    
    <!-- Embedded Backup Layout & Cards Styling -->
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
        .dash-header { margin-bottom: 25px; }
        .dash-header h2 { color: #1e293b; font-size: 24px; font-weight: 700; }
        .dash-header p { color: #64748b; margin-top: 4px; }
        
        .capacity-section { margin-bottom: 30px; }
        .capacity-card { background: white; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .cap-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .live-label { background: #fed7d7; color: #c53030; font-size: 0.75rem; font-weight: bold; padding: 4px 10px; border-radius: 12px; }
        .cap-info { font-size: 1.5rem; font-weight: bold; color: #2d5a27; margin-bottom: 10px; }
        .cap-bar { background: #edf2f7; height: 12px; border-radius: 10px; overflow: hidden; width: 100%; }
        .cap-fill { background: #68b04d; height: 100%; border-radius: 10px; transition: width 0.4s ease; }
        
        .req-list { background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .req-list h3 { font-size: 18px; color: #1e293b; margin-bottom: 15px; }
        .req-item { background: white; padding: 18px 20px; border-radius: 10px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #e2e8f0; }
        .staff-tag { font-size: 0.75rem; color: #68b04d; text-transform: uppercase; font-weight: bold; }
        .btn-accept { background: #2d5a27; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .btn-accept:hover { background: #1b3818; }
        .btn-reject { background: #fff; color: #e53e3e; border: 1px solid #e53e3e; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .btn-reject:hover { background: #fff5f5; }
    </style>
</head>
<body>

    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="logo">Recycle<span>Hub</span></div>
            <nav class="side-nav">
                <a href="factorystaff.php" class="active">Incoming Requests</a>
                <a href="factory_stock.php">Request History</a>
                <div class="nav-divider"></div>
                <a href="../logout.php" class="logout">Logout</a>
            </nav>
        </aside>

        <main class="dashboard-content">
            <header class="dash-header">
                <div>
                    <h2>Incoming Material Requests</h2>
                    <p>Approve transfers from Recycling Staff to Factory Storage.</p>
                </div>
            </header>

            <section class="capacity-section">
                <div class="capacity-card">
                    <div class="cap-header">
                        <h3>Factory Storage Load</h3>
                        <span class="live-label"><?= $status_badge ?></span>
                    </div>
                    <div class="cap-info">
                        <?= number_format($current_capacity, 1) ?> / <?= number_format($max_capacity, 1) ?> kg
                    </div>
                    <div class="cap-bar">
                        <div class="cap-fill" style="width: <?= $fill_percent ?>%; <?= ($fill_percent >= 85) ? 'background-color: #e53e3e;' : '' ?>"></div>
                    </div>
                </div>
            </section>

            <section class="req-list">
                <h3>Pending Requests</h3>
                <div id="req-container">
                    <?php if ($pending_result && $pending_result->num_rows > 0): ?>
                        <?php while ($req = $pending_result->fetch_assoc()): ?>
                            <div class="req-item" id="req-<?= $req['id'] ?>">
                                <div class="info">
                                    <span class="staff-tag">REQ #<?= $req['id'] ?> | Staff: <?= htmlspecialchars($req['staff_name'] ?? 'Recycling Staff') ?></span>
                                    <h4 style="margin: 5px 0; font-size: 1.1rem; color: #1e293b;"><?= htmlspecialchars($req['material']) ?></h4>
                                    <p style="color: #64748b; font-size: 0.95rem;">Weight: <strong><?= number_format($req['weight'], 1) ?> kg</strong></p>
                                </div>
                                <div class="actions">
                                    <!-- Accept Form -->
                                    <form method="POST" action="factorystaff.php" onsubmit="return confirm('Accept this transfer request?');" style="display:inline-block;">
                                        <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                        <input type="hidden" name="request_weight" value="<?= $req['weight'] ?>">
                                        <input type="hidden" name="action_type" value="Approved">
                                        <button type="submit" class="btn-accept">Accept</button>
                                    </form>

                                    <!-- Reject Form -->
                                    <form method="POST" action="factorystaff.php" onsubmit="return confirm('Reject this transfer request?');" style="display:inline-block;">
                                        <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                        <input type="hidden" name="request_weight" value="<?= $req['weight'] ?>">
                                        <input type="hidden" name="action_type" value="Rejected">
                                        <button type="submit" class="btn-reject">Reject</button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="req-item" style="justify-content: center; color: #888;">
                            <p>No pending transfer requests from recycling staff.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

</body>
</html>