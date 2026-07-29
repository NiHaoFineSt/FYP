<?php
session_start();
require_once __DIR__ . '/../config.php';

// Ensure user is logged in as staff
// 1. Correct session check using 'user_id'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'recycling staff') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. Direct query without JOINs to prevent MySQL errors
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    die("Query Preparation Failed: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

// 3. Fallback variable assignments
$full_name       = htmlspecialchars($user_data['name'] ?? 'Staff Member');
$email           = htmlspecialchars($user_data['email'] ?? 'staff@recyclehub.com');
$phone           = htmlspecialchars($user_data['phone'] ?? '+60 12-345 6789');
$role            = htmlspecialchars($user_data['role'] ?? 'Recycling Staff');

// Static / Default fallback values for center info
$center_name     = 'Downtown CRC';
$center_location = 'Level 2, Central Mall';
$center_hours    = '08:00 AM - 08:00 PM';
$center_status   = 'Active';

// Generate avatar initials safely
$name_parts = explode(' ', trim($full_name));
$initials   = strtoupper(substr($name_parts[0] ?? 'S', 0, 1) . substr($name_parts[1] ?? '', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Profile | RecycleHub</title>
    <link rel="stylesheet" href="staffprofile.css">
</head>
<body>

    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="logo">Recycle<span>Hub</span></div>
            <nav class="side-nav">
                <a href="recyclingstaff.php">Overview</a>
                <a href="managerequest.php">Manage Requests</a>
                <a href="inventorylog.php">Inventory Log</a>
                <a href="staffscan.php">Scan QR</a>
                <a href="factory.php">Factory Recs</a>
                <a href="staffchat.php">Staff Chat</a>
                <a href="staff_profile.php" class="active">Profile</a>
                <div class="nav-divider"></div>
                <a href="logout.php" class="logout">Logout</a>
            </nav>
        </aside>

        <main class="dashboard-content">
            <header class="dash-header">
                <div>
                    <h2>Staff Profile</h2>
                    <p>Manage your professional credentials and view assigned recycling center information.</p>
                </div>
                <div class="user-badge" style="background: var(--primary-dark);">
                    Staff ID: #STF-<?= htmlspecialchars($staff_id) ?>
                </div>
            </header>

            <div class="profile-layout" style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
                
                <!-- Left Column: Avatar & Recycling Center Info -->
                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    
                    <!-- Staff Info Card -->
                    <section class="activity-section" style="text-align: center;">
                        <div class="avatar-large" style="width: 110px; height: 110px; background: var(--accent-mint); border-radius: 50%; margin: 0 auto 1.2rem; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: var(--primary-dark); border: 4px solid var(--primary-light); font-weight: bold;">
                            <?= $initials ?>
                        </div>
                        <h3><?= $first_name . ' ' . $last_name ?></h3>
                        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 0.8rem;"><?= $role ?></p>
                        <span class="material-tag" style="background: var(--primary-dark); color: white;">Full-Time Officer</span>
                        
                        <hr style="margin: 1.5rem 0; border: 0; border-top: 1px solid var(--border);">
                        
                        <div style="text-align: left;">
                            <h4 style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px;">Current Shift</h4>
                            <p style="font-weight: 600; margin-bottom: 1rem; font-size: 0.95rem;"><?= $shift ?></p>
                            
                            <h4 style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px;">Contact Work Email</h4>
                            <p style="font-weight: 600; font-size: 0.95rem;"><?= $email ?></p>
                        </div>
                    </section>

                    <!-- Assigned Recycling Center Details Card -->
                    <section class="activity-section">
                        <div class="section-header" style="margin-bottom: 1rem;">
                            <h3 style="font-size: 1.1rem; color: var(--primary-dark);">Assigned Center</h3>
                            <span class="status-verified"><?= $center_status ?></span>
                        </div>
                        
                        <div style="display: flex; flex-direction: column; gap: 0.8rem;">
                            <div>
                                <label style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: bold; display: block;">Center Name</label>
                                <p style="font-weight: 600; color: var(--text-main); font-size: 1rem;"><?= $center_name ?></p>
                            </div>

                            <div>
                                <label style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: bold; display: block;">Location / Address</label>
                                <p style="font-weight: 500; color: var(--text-main); font-size: 0.9rem;"><?= $center_location ?></p>
                            </div>

                            <div>
                                <label style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: bold; display: block;">Operating Hours</label>
                                <p style="font-weight: 500; color: var(--text-main); font-size: 0.9rem;"><?= $center_hours ?></p>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Right Column: Settings & Password Forms -->
                <section class="activity-section">
                    <div class="section-header">
                        <h3>Account Settings</h3>
                    </div>
                    
                    <form action="update_staff_profile.php" method="POST" class="profile-form">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div class="input-group">
                                <label>First Name</label>
                                <input type="text" name="first_name" value="<?= $first_name ?>" required>
                            </div>
                            <div class="input-group">
                                <label>Last Name</label>
                                <input type="text" name="last_name" value="<?= $last_name ?>" required>
                            </div>
                        </div>

                        <div class="input-group">
                            <label>Work Email Address</label>
                            <input type="email" name="email" value="<?= $email ?>" required>
                        </div>

                        <div class="input-group">
                            <label>Work Phone Number</label>
                            <input type="tel" name="phone" value="<?= $phone ?>" required>
                        </div>

                        <div class="section-header" style="margin-top: 2rem;">
                            <h3>Security & Credentials</h3>
                        </div>

                        <div class="input-group">
                            <label>Current Password</label>
                            <input type="password" name="current_password" placeholder="••••••••">
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div class="input-group">
                                <label>New Password</label>
                                <input type="password" name="new_password" placeholder="Leave blank to keep current">
                            </div>
                            <div class="input-group">
                                <label>Confirm New Password</label>
                                <input type="password" name="confirm_password" placeholder="Leave blank to keep current">
                            </div>
                        </div>

                        <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                            <button type="submit" class="btn-primary">Update Profile</button>
                            <button type="reset" class="btn-outline">Cancel Changes</button>
                        </div>
                    </form>
                </section>

            </div>
        </main>
    </div>

</body>
</html>