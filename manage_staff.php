<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle the Approval Action
if (isset($_POST['approve_staff'])) {
    $staff_id = $_POST['staff_id'];
    $conn->query("UPDATE users SET is_approved = 1 WHERE user_id = $staff_id");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Management | RecycleHub</title>
    <link rel="stylesheet" href="admin.css">
    <style>
        .table-wrapper { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-bottom: 40px; }
        .styled-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .styled-table th { text-align: left; padding: 12px; background: #f8faf9; color: #666; border-bottom: 2px solid #eee; text-transform: uppercase; font-size: 13px; }
        .styled-table td { padding: 15px; border-bottom: 1px solid #eee; vertical-align: middle; }
        .btn-approve { background: #2d4a27; color: white; border: none; padding: 10px 18px; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .btn-approve:hover { background: #3d6135; }
        .doc-link { color: #007bff; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="logo">Recycle<span>Hub</span></div>
        <nav class="side-nav">
            <a href="admin.php">Overview</a>
            <a href="manage_staff.php" class="active">Manage Staff</a>
            <a href="manage_center.php">Manage Centers</a>
            <a href="approve_factory_staff.php">Manage Factory</a>
            <a href="logout.php" class="logout">Logout</a>
        </nav>
    </aside>

    <main class="main-content">
        <h2 style="color: #2d4a27;">Pending Staff Approvals (Document Review)</h2>
        <div class="table-wrapper">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Documents</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $pending = $conn->query("SELECT * FROM users WHERE LOWER(role)='recycling staff' AND is_approved = 0");
                    if ($pending->num_rows > 0):
                        while($row = $pending->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                <td><a href="uploads/<?php echo $row['document_path'] ?? '#'; ?>" target="_blank" class="doc-link">Review Turn-in Document</a></td>
                                <td>
                                    <form method="POST">
                                        <input type="hidden" name="staff_id" value="<?php echo $row['user_id']; ?>">
                                        <button type="submit" name="approve_staff" class="btn-approve">Approve Registration</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile;
                    else: ?>
                        <tr><td colspan="3" style="text-align:center; padding:20px; color:#999;">No pending staff registrations found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <h2 style="color: #2d4a27;">Registered Staff Name List</h2>
        <div class="table-wrapper">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Staff Name</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $approved = $conn->query("SELECT * FROM users WHERE LOWER(role)='recycling staff' AND is_approved = 1");
                    while($row = $approved->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $row['user_id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>