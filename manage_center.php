<?php
session_start();
include 'config.php';

// 1. SECURITY: Only Admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// 2. ACTION: ADD NEW CENTER
if (isset($_POST['add_center'])) {
    $name = trim($_POST['center_name']);
    $loc = trim($_POST['location']);
    $contact = trim($_POST['contact_number']);
    $state = trim($_POST['state']);
    
    // Prepared statement handling state column to prevent database errors
    $stmt = $conn->prepare("INSERT INTO recycling_centers (center_name, location, contact_number, state) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $loc, $contact, $state);
    
    if ($stmt->execute()) {
        $stmt->close();
        header("Location: manage_center.php?status=success");
        exit();
    } else {
        $error_msg = "Database Error: " . $conn->error;
        $stmt->close();
    }
}

// 3. ACTION: DELETE CENTER
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $del_stmt = $conn->prepare("DELETE FROM recycling_centers WHERE center_id = ?");
    $del_stmt->bind_param("i", $id);
    $del_stmt->execute();
    $del_stmt->close();
    
    header("Location: manage_center.php?status=deleted");
    exit();
}

// 4. FETCH CENTERS
$result = $conn->query("SELECT * FROM recycling_centers ORDER BY center_id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Centers | RecycleHub</title>
    <link rel="stylesheet" href="admin.css">
    <style>
        /* Sidebar Styling */
        .sidebar { width: 260px; height: 100vh; background: #fff; position: fixed; left: 0; top: 0; border-right: 1px solid #eee; padding: 30px 0; display: flex; flex-direction: column; }
        .logo { font-size: 24px; font-weight: bold; padding: 0 30px 40px; color: #333; }
        .logo span { color: #2d4a27; }
        .side-nav a { padding: 15px 30px; text-decoration: none; color: #666; font-weight: 500; display: block; transition: 0.3s; border-left: 4px solid transparent; }
        .side-nav a:hover, .side-nav a.active { background: #f8faf9; color: #2d4a27; border-left: 4px solid #2d4a27; }
        
        /* Main Layout */
        .main-content { margin-left: 260px; padding: 40px; background: #fcfdfd; min-height: 100vh; }
        .admin-box { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; }
        
        /* Form & Table Layout */
        .input-grid { display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; }
        .input-group { flex: 1; min-width: 200px; }
        .input-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; }
        .input-group input, .input-group select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; outline: none; background-color: #fff; }
        .btn-submit { background: #2d4a27; color: white; border: none; padding: 13px 25px; border-radius: 8px; cursor: pointer; font-weight: bold; }
        .btn-submit:hover { background: #1f351b; }
        
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { text-align: left; padding: 15px; background: #f8faf9; color: #666; border-bottom: 2px solid #eee; text-transform: uppercase; font-size: 12px; }
        .data-table td { padding: 15px; border-bottom: 1px solid #eee; font-size: 14px; }
        .btn-delete { color: #d9534f; text-decoration: none; font-weight: bold; }
        .btn-delete:hover { text-decoration: underline; }
        
        .alert-error { background: #f8d7da; color: #721c24; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="logo">Recycle<span>Hub</span></div>
        <nav class="side-nav">
            <a href="admin.php">Overview</a>
            <a href="manage_staff.php">Manage Staff</a>
            <a href="manage_center.php" class="active">Manage Centers</a>
            <a href="approve_factory_staff.php">Manage Factory</a>
            <a href="logout.php" style="margin-top:auto; color:#d9534f;">Logout</a>
        </nav>
    </aside>

    <main class="main-content">
        <h2 style="margin-bottom: 20px;">Recycling Center Management</h2>
        
        <?php if (!empty($error_msg)): ?>
            <div class="alert-error"><?= htmlspecialchars($error_msg) ?></div>
        <?php endif; ?>

        <div class="admin-box">
            <h3 style="margin-bottom:20px;">Add New Center</h3>
            <form method="POST" action="manage_center.php">
                <div class="input-grid">
                    <div class="input-group">
                        <label>Center Name</label>
                        <input type="text" name="center_name" required placeholder="e.g. EcoRecycle Subang">
                    </div>
                    <div class="input-group">
                        <label>State</label>
                        <select name="state" required>
                            <option value="" disabled selected>Select State</option>
                            <option value="Selangor">Selangor</option>
                            <option value="Kuala Lumpur">Kuala Lumpur</option>
                            <option value="Johor">Johor</option>
                            <option value="Penang">Penang</option>
                            <option value="Perak">Perak</option>
                            <option value="Pahang">Pahang</option>
                            <option value="Kedah">Kedah</option>
                            <option value="Melaka">Melaka</option>
                            <option value="Negeri Sembilan">Negeri Sembilan</option>
                            <option value="Kelantan">Kelantan</option>
                            <option value="Terengganu">Terengganu</option>
                            <option value="Perlis">Perlis</option>
                            <option value="Sabah">Sabah</option>
                            <option value="Sarawak">Sarawak</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Location (Address)</label>
                        <input type="text" name="location" required placeholder="Full street address">
                    </div>
                    <div class="input-group">
                        <label>Contact Number</label>
                        <input type="text" name="contact_number" placeholder="e.g. 03-55667788">
                    </div>
                    <button type="submit" name="add_center" class="btn-submit">Add Center</button>
                </div>
            </form>
        </div>

        <div class="admin-box">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Center Name</th>
                        <th>State</th>
                        <th>Location</th>
                        <th>Contact</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?= $row['center_id']; ?></strong></td>
                                <td><?= htmlspecialchars($row['center_name']); ?></td>
                                <td><?= htmlspecialchars($row['state'] ?? 'N/A'); ?></td>
                                <td>
                                    <?= htmlspecialchars($row['location']); ?><br>
                                    <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($row['location'] . ' ' . ($row['state'] ?? '')); ?>" 
                                       target="_blank" style="color:#2d4a27; text-decoration:none; font-weight:bold; font-size:12px;">
                                        📍 View on Map
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($row['contact_number'] ?: 'N/A'); ?></td>
                                <td>
                                    <a href="manage_center.php?delete_id=<?= $row['center_id']; ?>" 
                                       class="btn-delete" 
                                       onclick="return confirm('Remove this center?')">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; padding:30px; color:#999;">No centers listed.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>