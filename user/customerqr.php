<?php
session_start();
include '../config.php'; 

// SECURITY CHECK
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// FETCH UPDATED USER DATA (For the Name and Stats)
$query = "SELECT name, total_kg, total_points FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My QR Code | RecycleHub</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="myrecycing.css"> 
    <link rel="stylesheet" href="customerqr.css"> 
    <link rel="stylesheet" href="citizen dashboard.css"> <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>
<body>

    <div class="dashboard-wrapper">
        <aside class="sidebar">
    <div class="logo">Recycle<span>Hub</span></div>
    <div class="user-profile-side" style="padding: 20px; text-align: center;">
                <div style="background: white; padding: 8px; border-radius: 8px; width: 100px; height: 100px; margin: 0 auto 10px auto;">
                    <div id="sidebar-qrcode"></div>
                </div>
                <p style="color: white;">ID: #<?php echo $user_id; ?></p>
            </div>
    <nav class="side-nav">
        <a href="citizen_dashboard.php">Overview</a>
        <a href="myrecycling.php">My Recycling</a>
        <a href="dropoff.php">Drop-off Points</a>
        <a href="customerqr.php" class="active">My QR Code</a>
        <a href="userscan.php">Scan QR</a>
        <a href="reward.php">Rewards</a>
        <a href="Profilepage.php">Profile</a>
        
        <div class="nav-divider"></div>
        <a href="../logout.php" class="logout">Logout</a>
    </nav>
</aside>
        <main class="dashboard-content">
            <header class="dash-header">
                <div>
                    <h2>Citizen Portal</h2>
                    <p>Welcome back, <strong><?php echo htmlspecialchars($userData['name']); ?></strong>. Ready to recycle?</p>
                </div>
                <div class="user-badge" style="background: #2d5a27;">Member ID: #<?php echo $user_id; ?></div>
            </header>

            <section class="stats-grid">
                <div class="stat-card">
                    <h4>Total Points</h4>
                    <p><?php echo number_format($userData['total_points']); ?> <span>pts</span></p>
                </div>
                <div class="stat-card">
                    <h4>Weight Saved</h4>
                    <p><?php echo number_format($userData['total_kg'], 1); ?> <span>kg</span></p>
                </div>
                <div class="stat-card">
                    <h4>Global Rank</h4>
                    <p>#<?php echo ($user_id + 10); ?> <span>top</span></p>
                </div>
            </section>

            <section class="activity-section" style="text-align: center; padding: 40px 0;">
                <div class="qr-display-container">
                    <div class="section-header" style="justify-content: center; margin-bottom: 20px;">
                        <h3>Your Identification QR</h3>
                    </div>
                    
                    <div id="qrcode-box" style="display: flex; justify-content: center; margin-bottom: 20px;">
                        <div id="qrcode" style="padding: 15px; background: white; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);"></div>
                    </div>

                    <div class="qr-meta">
                        <p class="user-id-text">Internal ID: <strong>#USR-<?php echo $user_id; ?></strong></p>
                        <p class="hint-text">Show this code to the staff during your next visit to instantly update your points.</p>
                    </div>

                    <button class="btn-primary" onclick="window.print()" style="margin-top: 20px; padding: 12px 30px; cursor: pointer;">
                        Print / Save QR Code
                    </button>
                </div>
            </section>
        </main>
    </div>

    <script>
        // Use PHP to echo the actual User ID into the JavaScript
        var userID = "<?php echo $user_id; ?>";

        new QRCode(document.getElementById("qrcode"), {
            text: userID, // This is what the staff scanner will read
            width: 220,
            height: 220,
            colorDark : "#2d5a27",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });

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