<?php
session_start();
include __DIR__ . '/../config.php';

// SECURITY CHECK (Ensure user is factory staff)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'factory') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$max_capacity = 1000.0; // Maximum factory storage in kg

// HANDLE DECISION (Accept/Reject Post Actions)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    $req_id = intval($_POST['request_id']);
    $action = $_POST['action_type']; // 'Approved' or 'Rejected'
    $req_weight = floatval($_POST['request_weight']);

    if ($action === 'Approved') {
        // Calculate current total approved weight
        $cap_query = "SELECT SUM(weight) as total_weight FROM factory_requests WHERE status = 'Approved' OR status = 'Completed'";
        $cap_res = $conn->query($cap_query);
        $cap_row = $cap_res->fetch_assoc();
        $current_weight = floatval($cap_row['total_weight'] ?? 0);

        if (($current_weight + $req_weight) > $max_capacity) {
            echo "<script>alert('🚨 Factory Full! Cannot accept more material.'); window.location.href='factorystaff.php';</script>";
            exit();
        }
    }

    // Update database status
    $update_stmt = $conn->prepare("UPDATE factory_requests SET status = ? WHERE id = ?");
    $update_stmt->bind_param("si", $action, $req_id);
    
    if ($update_stmt->execute()) {
        echo "<script>alert('Request #$req_id marked as $action!'); window.location.href='factorystaff.php';</script>";
    } else {
        echo "<script>alert('Failed to update request status.'); window.location.href='factorystaff.php';</script>";
    }
    $update_stmt->close();
    exit();
}

// 1. CALCULATE CURRENT STORAGE LOAD
$current_load_query = "SELECT SUM(weight) as total_weight FROM factory_requests WHERE status = 'Approved' OR status = 'Completed'";
$load_res = $conn->query($current_load_query);
$load_row = $load_res->fetch_assoc();
$current_capacity = floatval($load_row['total_weight'] ?? 0.0);

$fill_percent = min(100, round(($current_capacity / $max_capacity) * 100, 1));
$status_badge = "NORMAL";
$badge_class = "status-badge";

if ($fill_percent >= 85 && $fill_percent < 100) {
    $status_badge = "HIGH LOAD";
    $badge_class .= " high-load";
} elseif ($fill_percent >= 100) {
    $status_badge = "FULL";
    $badge_class .= " full-load";
}

// 2. FETCH PENDING REQUESTS JOINED WITH STAFF NAME
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
    
    <!-- Link external stylesheets -->
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="factorystaff.css">
    
    <!-- Self-Contained Fallback Styling (Fixes layout if external CSS fails) -->
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f4f7f6; color: #333; }
        
        /* Sidebar Layout */
        .dashboard-wrapper { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background-color: #1e293b; color: #fff; padding: 20px; flex-shrink: 0; }
        .sidebar .logo { font-size: 24px; font-weight: bold; margin-bottom: 30px; color: #fff; }
        .sidebar .logo span { color: #4ade80; }
        .side-nav { display: flex; flex-direction: column; gap: 10px; }
        .side-nav a { color: #94a3b8; text-decoration: none; padding: 12px 15px; border-radius: 8px; font-weight: 500; display: block; }
        .side-nav a:hover, .side-nav a.active { background-color: #334155; color: #fff; }
        .side-nav a.logout { color: #f87171; margin-top: 20px; }
        .nav-divider { height: 1px; background-color: #334155; margin: 10px 0; }
        
        /* Main Dashboard Content */
        .dashboard-content { flex: 1; padding: 30px; background-color: #f8fafc; }
        .dash-header { margin-bottom: 25px; }
        .dash-header h2 { color: #0f172a; font-size: 24px; font-weight: 700; }
        .dash-header p { color: #64748b; margin-top: 4px; }
        
        /* Capacity Card */
        .capacity-card { background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .cap-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .cap-header h3 { font-size: 18px; color: #1e293b; }
        .status-badge { background-color: #22c55e; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .status-badge.high-load { background-color: #ed8936; }
        .status-badge.full-load { background-color: #e53e3e; }
        .cap-numbers { font-size: 22px; font-weight: bold; color: #0f172a; margin-bottom: 12px; }
        .cap-bar-bg { width: 100%; height: 12px; background-color: #e2e8f0; border-radius: 6px; overflow: hidden; }
        .cap-fill { height: 100%; background-color: #22c55e; transition: width 0.4s ease; }
        
        /* Requests Section */
        .req-list { background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .req-list h3 { font-size: 18px; color: #1e293b; margin-bottom: 15px; }
        .req-item { display: flex; justify-content: space-between; align-items: center; padding: 16px; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 12px; background-color: #fff; }
        .req-item .info .label { font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: bold; }
        .req-item .info h4 { font-size: 16px; color: #0f172a; margin: 4px 0; }
        .req-item .info p { font-size: 14px; color: #475569; }
        .actions { display: flex; gap: 10px; }
        .actions form { display: inline-block; }
        .btn-acc { background-color: #16a34a; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; }
        .btn-acc:hover { background-color: #15803d; }
        .btn-rej { background-color: #dc2626; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; }
        .btn-rej:hover { background-color: #b91c1c; }
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

            <section class="capacity-card">
                <div class="cap-header">
                    <h3>Factory Storage Load</h3>
                    <span id="status-tag" class="<?= $badge_class ?>"><?= $status_badge ?></span>
                </div>
                <div class="cap-main">
                    <div class="cap-numbers">
                        <span id="current-cap"><?= number_format($current_capacity, 1) ?></span> / <span id="max-cap"><?= number_format($max_capacity, 1) ?></span> kg
                    </div>
                    <div class="cap-bar-bg">
                        <div id="cap-fill" class="cap-fill" style="width: <?= $fill_percent ?>%; <?= ($fill_percent >= 85) ? 'background-color: #e53e3e;' : '' ?>"></div>
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
                                    <span class="label">REQ #<?= $req['id'] ?> | Staff: <?= htmlspecialchars($req['staff_name'] ?? 'Staff') ?></span>
                                    <h4><?= htmlspecialchars($req['material']) ?></h4>
                                    <p>Weight: <strong><?= number_format($req['weight'], 1) ?> kg</strong></p>
                                </div>
                                <div class="actions">
                                    <!-- Accept Form -->
                                    <form method="POST" onsubmit="return confirm('Accept this transfer request?');">
                                        <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                        <input type="hidden" name="request_weight" value="<?= $req['weight'] ?>">
                                        <input type="hidden" name="action_type" value="Approved">
                                        <button type="submit" class="btn-acc">Accept</button>
                                    </form>

                                    <!-- Reject Form -->
                                    <form method="POST" onsubmit="return confirm('Reject this transfer request?');">
                                        <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                        <input type="hidden" name="request_weight" value="<?= $req['weight'] ?>">
                                        <input type="hidden" name="action_type" value="Rejected">
                                        <button type="submit" class="btn-rej">Reject</button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="req-item" style="justify-content: center; color: #777;">
                            <p>No pending transfer requests from recycling staff.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

</body>
</html>