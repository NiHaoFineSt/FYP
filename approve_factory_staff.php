<?php
session_start();
include __DIR__ . '/../config.php';

// SECURITY CHECK (Ensure logged-in user is an admin)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// HANDLE APPROVAL / REJECTION ACTIONS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['action'])) {
    $target_user_id = intval($_POST['user_id']);
    $action = $_POST['action']; // 'approved' or 'rejected'

    if (in_array($action, ['approved', 'rejected'])) {
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ? AND role = 'factory'");
        $stmt->bind_param("si", $action, $target_user_id);
        
        if ($stmt->execute()) {
            $_SESSION['msg'] = "Factory staff request has been " . ucfirst($action) . " successfully!";
        } else {
            $_SESSION['err'] = "Failed to update user status.";
        }
        $stmt->close();
    }
    header("Location: approve_factory_staff.php");
    exit();
}

// FETCH ALL PENDING FACTORY STAFF
$pending_query = "SELECT user_id, name, email, phone_number, created_at 
                 FROM users 
                 WHERE role = 'factory' AND status = 'pending' 
                 ORDER BY created_at ASC";
$pending_result = $conn->query($pending_query);

// FETCH APPROVED FACTORY STAFF FOR REFERENCE
$approved_query = "SELECT user_id, name, email, phone_number, created_at 
                  FROM users 
                  WHERE role = 'factory' AND status = 'approved' 
                  ORDER BY name ASC";
$approved_result = $conn->query($approved_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Factory Staff | RecycleHub Admin</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="admin_dashboard.css">
    <style>
        .table-card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .admin-table th, .admin-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .admin-table th {
            background-color: #f8f9fa;
            color: #333;
        }
        .action-btns {
            display: flex;
            gap: 8px;
        }
        .btn-approve {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 6px 14px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-reject {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 6px 14px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        .alert-success {
            padding: 12px;
            background-color: #d4edda;
            color: #155724;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert-error {
            padding: 12px;
            background-color: #f8d7da;
            color: #721c24;
            border-radius: 6px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="logo">Recycle<span>Hub Admin</span></div>
            <nav class="side-nav">
                <a href="admin.php">Overview</a>
                <a href="manage_user.php">Manage Citizens</a>
                <a href="manage_staff.php">Manage Staff</a>
                <a href="manage_center.php">Manage Centers</a>
                <a href="approve_factory_staff.php" class="active">Factory Staff Approvals</a>
                <div class="nav-divider"></div>
                <a href="../logout.php" class="logout">Logout</a>
            </nav>
        </aside>

        <main class="dashboard-content">
            <header class="dash-header">
                <div>
                    <h2>Factory Staff Approvals</h2>
                    <p>Manage and authorize accounts for factory personnel.</p>
                </div>
            </header>

            <?php if (isset($_SESSION['msg'])): ?>
                <div class="alert-success"><?= $_SESSION['msg']; unset($_SESSION['msg']); ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['err'])): ?>
                <div class="alert-error"><?= $_SESSION['err']; unset($_SESSION['err']); ?></div>
            <?php endif; ?>

            <!-- PENDING APPROVALS SECTION -->
            <section class="table-card">
                <h3>Pending Approvals</h3>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Requested On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($pending_result && $pending_result->num_rows > 0): ?>
                            <?php while ($row = $pending_result->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?= htmlspecialchars($row['user_id']) ?></td>
                                    <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= htmlspecialchars($row['phone_number'] ?? 'N/A') ?></td>
                                    <td><?= date('Y-m-d H:i', strtotime($row['created_at'])) ?></td>
                                    <td>
                                        <div class="action-btns">
                                            <!-- Approve Form -->
                                            <form method="POST" onsubmit="return confirm('Approve this factory staff member?');">
                                                <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                                                <input type="hidden" name="action" value="approved">
                                                <button type="submit" class="btn-approve">Approve</button>
                                            </form>

                                            <!-- Reject Form -->
                                            <form method="POST" onsubmit="return confirm('Reject this factory staff request?');">
                                                <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                                                <input type="hidden" name="action" value="rejected">
                                                <button type="submit" class="btn-reject">Reject</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #888; padding: 20px;">No pending factory staff requests.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

            <!-- ACTIVE / APPROVED FACTORY STAFF -->
            <section class="table-card">
                <h3>Active Factory Staff</h3>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($approved_result && $approved_result->num_rows > 0): ?>
                            <?php while ($row = $approved_result->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?= htmlspecialchars($row['user_id']) ?></td>
                                    <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= htmlspecialchars($row['phone_number'] ?? 'N/A') ?></td>
                                    <td><span style="color: #28a745; font-weight: bold;">● Active</span></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #888; padding: 20px;">No active factory staff registered.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

</body>
</html>