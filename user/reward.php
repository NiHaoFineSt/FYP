<?php
session_start();
include '../config.php'; 

// SECURITY CHECK
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// FETCH UPDATED USER DATA (Points balance)
$query = "SELECT name, total_points FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();

$current_points = $userData['total_points'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redeem Rewards | RecycleHub</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="reward.css">
    <link rel="stylesheet" href="citizen dashboard.css"> </head>
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
                <a href="myrecycing.php">My Recycling</a>
                <a href="dropoff.php">Drop-off Points</a>
                <a href="customerqr.php">My QR Code</a>
                <a href="userscan.php">Scan QR</a>
                <a href="reward.php" class="active">Rewards</a>
                <a href="Profilepage.php">Profile</a>
                <div class="nav-divider"></div>
                <a href="../logout.php" class="logout">Logout</a>
            </nav>
        </aside>

        <main class="dashboard-content">
            <header class="dash-header">
                <div>
                    <h2>Rewards Hub</h2>
                    <p>Turn your recycling efforts into real-world benefits.</p>
                </div>
                <div class="user-badge"><?php echo number_format($current_points); ?> Points Available</div>
            </header>

            <section class="stats-grid">
                <div class="stat-card">
                    <h4>Your Balance</h4>
                    <p>🪙 <?php echo number_format($current_points); ?> <span>pts</span></p>
                </div>
                <div class="stat-card">
                    <h4>Lifetime Earned</h4>
                    <p><?php echo number_format($current_points + 500); ?> <span>pts</span></p>
                </div>
                <div class="stat-card">
                    <h4>Rewards Claimed</h4>
                    <p>0</p>
                </div>
            </section>

            <div class="section-header" style="margin-bottom: 1.5rem;">
                <h3>Available Rewards</h3>
                <div class="map-filters">
                    <button class="filter-btn active">All</button>
                    <button class="filter-btn">Vouchers</button>
                    <button class="filter-btn">Merchandise</button>
                </div>
            </div>

            <section class="rewards-container">
                
                <?php $cost1 = 500; ?>
                <div class="activity-section reward-item <?php echo ($current_points < $cost1) ? 'locked' : ''; ?>">
                    <div class="reward-content">
                        <div class="reward-icon">🎫</div>
                        <div class="reward-details">
                            <h4>$5 Grocery Voucher</h4>
                            <p>Get a discount on your next purchase at EcoMart.</p>
                            <span class="points-cost"><?php echo $cost1; ?> Points</span>
                        </div>
                    </div>
                    <?php if ($current_points >= $cost1): ?>
                        <button class="btn-primary">Redeem Now</button>
                    <?php else: ?>
                        <button class="btn-primary" style="background: #ccc; cursor: not-allowed;" disabled>Not Enough Points</button>
                    <?php endif; ?>
                </div>

                <?php $cost2 = 800; ?>
                <div class="activity-section reward-item <?php echo ($current_points < $cost2) ? 'locked' : ''; ?>">
                    <div class="reward-content">
                        <div class="reward-icon">🥤</div>
                        <div class="reward-details">
                            <h4>Reusable Bamboo Cup</h4>
                            <p>Pick up at any Downtown CRC location.</p>
                            <span class="points-cost"><?php echo $cost2; ?> Points</span>
                        </div>
                    </div>
                    <?php if ($current_points >= $cost2): ?>
                        <button class="btn-primary">Redeem Now</button>
                    <?php else: ?>
                        <button class="btn-primary" style="background: #ccc; cursor: not-allowed;" disabled>Not Enough Points</button>
                    <?php endif; ?>
                </div>

                <?php $cost3 = 2500; ?>
                <div class="activity-section reward-item <?php echo ($current_points < $cost3) ? 'locked' : ''; ?>">
                    <div class="reward-content">
                        <div class="reward-icon">🚲</div>
                        <div class="reward-details">
                            <h4>Electric Scooter Rental</h4>
                            <p>1 Full day of eco-friendly commuting.</p>
                            <span class="points-cost"><?php echo $cost3; ?> Points</span>
                        </div>
                    </div>
                    <?php if ($current_points >= $cost3): ?>
                        <button class="btn-primary">Redeem Now</button>
                    <?php else: ?>
                        <button class="btn-primary" style="background: #ccc; cursor: not-allowed;" disabled>Not Enough Points</button>
                    <?php endif; ?>
                </div>

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
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>