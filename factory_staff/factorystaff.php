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
    
    <!-- Link to local folder CSS as well as global fallback styles -->
    <link rel="stylesheet" href="factorystaff.css">
    
    <style>
        .actions form {
            display: inline-block;
        }
        .status-badge.high-load {
            background-color: #ed8936;
            color: white;
        }
        .status-badge.full-load {
            background-color: #e53e3e;
            color: white;
        }
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