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
$status_badge = "LIVE LOAD";

if ($fill_percent >= 85 && $fill_percent < 100) {
    $status_badge = "HIGH LOAD";
} elseif ($fill_percent >= 100) {
    $status_badge = "FACTORY FULL";
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
    
    <!-- Root-absolute path ensures style.css loads regardless of folder depth -->
    <link rel="stylesheet" href="/style.css">
    <link rel="stylesheet" href="factorystaff.css">
    
    <!-- Embedded Layout Backup: Ensures sidebar & layout stay intact if style.css is missing -->
    <style>
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
                                    <span class="staff-tag">REQ #<?= $req['id'] ?> | Staff: <?= htmlspecialchars($req['staff_name'] ?? 'Staff') ?></span>
                                    <h4 style="margin: 5px 0; font-size: 1.1rem;"><?= htmlspecialchars($req['material']) ?></h4>
                                    <p>Weight: <strong><?= number_format($req['weight'], 1) ?> kg</strong></p>
                                </div>
                                <div class="actions">
                                    <form method="POST" action="factorystaff.php" onsubmit="return confirm('Accept this transfer request?');" style="display:inline-block;">
                                        <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                        <input type="hidden" name="request_weight" value="<?= $req['weight'] ?>">
                                        <input type="hidden" name="action_type" value="Approved">
                                        <button type="submit" class="btn-accept">Accept</button>
                                    </form>

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