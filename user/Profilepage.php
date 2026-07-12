<?php
session_start();
include '../config.php'; 

// 1. SECURITY: Ensure user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. FETCH DATA: Get the latest info from the database
$query = "SELECT name, email, phone FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();

// 3. AVATAR LOGIC: Create initials (e.g., "John Doe" -> "JD")
$words = explode(" ", $userData['name']);
$initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | RecycleHub</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="citizen dashboard.css"> 
    <link rel="stylesheet" href="profilepage.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>
<body>

    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="logo">Recycle<span>Hub</span></div>
            
            <div class="user-profile-side" style="padding: 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1);">
                <div style="background: white; padding: 8px; border-radius: 8px; width: 100px; height: 100px; margin: 0 auto 10px auto; display: flex; align-items: center; justify-content: center;">
                    <div id="sidebar-qrcode"></div>
                </div>
                <p style="color: white; font-size: 0.85rem; margin: 0;">Member ID: #<?php echo $user_id; ?></p>
            </div>

            <nav class="side-nav">
                <a href="citizen_dashboard.php">Overview</a>
                <a href="myrecycing.php">My Recycling</a>
                <a href="dropoff.php">Drop-off Points</a>
                <a href="customerqr.php">My QR Code</a>
                <a href="userscan.php">Scan QR</a>
                <a href="reward.php">Rewards</a>
                <a href="Profilepage.php" class="active">Profile Settings</a>
                <div class="nav-divider"></div>
                <a href="../logout.php" class="logout">Logout</a>
            </nav>
        </aside>

        <main class="dashboard-content">
            <header class="dash-header">
                <div>
                    <h2>My Profile</h2>
                    <p>Update your details so hubs can contact you.</p>
                </div>
                <button type="submit" form="profileForm" class="btn-primary">Save Changes</button>
            </header>

            <?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
                <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                    ✅ Profile updated successfully!
                </div>
            <?php endif; ?>

            <section class="activity-section">
                <div class="section-header">
                    <h3>Personal Information</h3>
                </div>
                
                <form class="profile-form" id="profileForm" action="update_profile_action.php" method="POST">
                    <div class="profile-avatar-row" style="display: flex; align-items: center; gap: 20px; margin-bottom: 30px;">
                        <div class="avatar-circle" style="width: 70px; height: 70px; background: #2d5a27; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: bold;">
                            <?php echo $initials; ?>
                        </div>
                        <div class="avatar-text">
                            <strong style="font-size: 1.2rem; display: block;"><?php echo htmlspecialchars($userData['name']); ?></strong>
                            <p style="margin: 0; color: #666;">Member since 2026</p>
                        </div>
                    </div>

                    <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="input-group">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600;">Full Name</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($userData['name']); ?>" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px;">
                        </div>
                        <div class="input-group">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600;">Email Address</label>
                            <input type="email" value="<?php echo htmlspecialchars($userData['email']); ?>" disabled style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; background: #f5f5f5; cursor: not-allowed;">
                        </div>
                        <div class="input-group">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600;">Phone Number</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>" placeholder="+60..." style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px;">
                        </div>
                        <div class="input-group">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600;">Language</label>
                            <select name="lang" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; background: white;">
                                <option>English</option>
                                <option>Bahasa Melayu</option>
                                <option>Mandarin</option>
                            </select>
                        </div>
                    </div>
                </form>
            </section>
        </main>
    </div>

    <script>
        window.onload = function() {
            var userId = "<?php echo $user_id; ?>";
            var qrBox = document.getElementById("sidebar-qrcode");
            if (qrBox) {
                new QRCode(qrBox, {
                    text: userId,
                    width: 85,
                    height: 85,
                    colorDark : "#2d5a27",
                    colorLight : "#ffffff"
                });
            }
        };
    </script>
</body>
</html>