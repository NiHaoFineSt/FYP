<?php
session_start();
include('../config.php'); 

// SECURITY CHECK
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$mesej = "";
$status_mesej = "";

// ==========================================
// PROSES PENEBUSAN REWARD (REDEEM ACTION)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['redeem_reward'])) {
    $reward_name = $_POST['reward_name'];
    $cost = intval($_POST['reward_cost']);

    // 1. Dapatkan mata ganjaran semasa pengguna
    $check_stmt = $conn->prepare("SELECT points FROM users WHERE user_id = ?");
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $user_data = $check_stmt->get_result()->fetch_assoc();
    $user_points = isset($user_data['points']) ? $user_data['points'] : 0;

    // 2. Semak jika mata ganjaran mencukupi
    if ($user_points >= $cost) {
        // Tolak mata ganjaran dari jadual users
        $update_stmt = $conn->prepare("UPDATE users SET points = points - ? WHERE user_id = ?");
        $update_stmt->bind_param("ii", $cost, $user_id);

        if ($update_stmt->execute()) {
            // Rekodkan transaksi penebusan ke dalam pangkalan data
            // Nota: Jika anda belum ada jadual rewards_claimed, buat jadual asas atau simpan ke transactions
            $log_stmt = $conn->prepare("INSERT INTO rewards_claimed (user_id, reward_name, points_spent, claimed_at) VALUES (?, ?, ?, NOW())");
            
            // Sekiranya anda menggunakan jadual 'transactions' sedia ada, tukar query di atas mengikut struktur anda.
            if ($log_stmt) {
                $log_stmt->bind_param("isi", $user_id, $reward_name, $cost);
                $log_stmt->execute();
            }

            $mesej = "🎉 Successfully redeemed " . htmlspecialchars($reward_name) . "!";
            $status_mesej = "success";
        } else {
            $mesej = "⚠️ Failed to process redemption. Please try again.";
            $status_mesej = "error";
        }
    } else {
        $mesej = "❌ You do not have enough points for this reward.";
        $status_mesej = "error";
    }
}

// ==========================================
// AMBIL DATA PENGGUNA & STATISTIK
// ==========================================
// 1. Baki mata ganjaran & Jumlah Terkumpul (Total Earned)
$query = "SELECT name, points, total_points FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();

$current_points = isset($userData['points']) ? $userData['points'] : 0;
$lifetime_points = isset($userData['total_points']) ? $userData['total_points'] : $current_points;

// 2. Kira jumlah ganjaran yang telah ditebus (Rewards Claimed)
$claim_count = 0;
$count_stmt = $conn->prepare("SELECT COUNT(*) AS total_claimed FROM rewards_claimed WHERE user_id = ?");
if ($count_stmt) {
    $count_stmt->bind_param("i", $user_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result()->fetch_assoc();
    $claim_count = $count_result['total_claimed'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redeem Rewards | RecycleHub</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="reward.css">
    <link rel="stylesheet" href="citizen dashboard.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
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
                <a href="myrecycing.php">My Recycling</a>
                <a href="dropoff.php">Drop-off Points</a>
                <a href="customerqr.php">My QR Code</a>
                <a href="userscan.php">Claim Code</a>
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

            <!-- NOTIFIKASI MESEJ -->
            <?php if (!empty($mesej)): ?>
                <div style="padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; background: <?php echo ($status_mesej == 'success') ? '#e8f5e9' : '#ffebee'; ?>; color: <?php echo ($status_mesej == 'success') ? '#2d5a27' : '#c62828'; ?>;">
                    <?php echo $mesej; ?>
                </div>
            <?php endif; ?>

            <section class="stats-grid">
                <div class="stat-card">
                    <h4>Your Balance</h4>
                    <p>🪙 <?php echo number_format($current_points); ?> <span>pts</span></p>
                </div>
                <div class="stat-card">
                    <h4>Lifetime Earned</h4>
                    <p><?php echo number_format($lifetime_points); ?> <span>pts</span></p>
                </div>
                <div class="stat-card">
                    <h4>Rewards Claimed</h4>
                    <p><?php echo $claim_count; ?></p> <!-- ANGKA DINAMIK -->
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
                
                <!-- REWARD 1 -->
                <?php 
                    $name1 = "$5 Grocery Voucher";
                    $cost1 = 500; 
                ?>
                <div class="activity-section reward-item <?php echo ($current_points < $cost1) ? 'locked' : ''; ?>">
                    <div class="reward-content">
                        <div class="reward-icon">🎫</div>
                        <div class="reward-details">
                            <h4><?php echo $name1; ?></h4>
                            <p>Get a discount on your next purchase at EcoMart.</p>
                            <span class="points-cost"><?php echo $cost1; ?> Points</span>
                        </div>
                    </div>
                    <form method="POST" action="">
                        <input type="hidden" name="reward_name" value="<?php echo $name1; ?>">
                        <input type="hidden" name="reward_cost" value="<?php echo $cost1; ?>">
                        <?php if ($current_points >= $cost1): ?>
                            <button type="submit" name="redeem_reward" class="btn-primary">Redeem Now</button>
                        <?php else: ?>
                            <button type="button" class="btn-primary" style="background: #ccc; cursor: not-allowed;" disabled>Not Enough Points</button>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- REWARD 2 -->
                <?php 
                    $name2 = "Reusable Bamboo Cup";
                    $cost2 = 800; 
                ?>
                <div class="activity-section reward-item <?php echo ($current_points < $cost2) ? 'locked' : ''; ?>">
                    <div class="reward-content">
                        <div class="reward-icon">🥤</div>
                        <div class="reward-details">
                            <h4><?php echo $name2; ?></h4>
                            <p>Pick up at any Downtown CRC location.</p>
                            <span class="points-cost"><?php echo $cost2; ?> Points</span>
                        </div>
                    </div>
                    <form method="POST" action="">
                        <input type="hidden" name="reward_name" value="<?php echo $name2; ?>">
                        <input type="hidden" name="reward_cost" value="<?php echo $cost2; ?>">
                        <?php if ($current_points >= $cost2): ?>
                            <button type="submit" name="redeem_reward" class="btn-primary">Redeem Now</button>
                        <?php else: ?>
                            <button type="button" class="btn-primary" style="background: #ccc; cursor: not-allowed;" disabled>Not Enough Points</button>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- REWARD 3 -->
                <?php 
                    $name3 = "Electric Scooter Rental";
                    $cost3 = 2500; 
                ?>
                <div class="activity-section reward-item <?php echo ($current_points < $cost3) ? 'locked' : ''; ?>">
                    <div class="reward-content">
                        <div class="reward-icon">🚲</div>
                        <div class="reward-details">
                            <h4><?php echo $name3; ?></h4>
                            <p>1 Full day of eco-friendly commuting.</p>
                            <span class="points-cost"><?php echo $cost3; ?> Points</span>
                        </div>
                    </div>
                    <form method="POST" action="">
                        <input type="hidden" name="reward_name" value="<?php echo $name3; ?>">
                        <input type="hidden" name="reward_cost" value="<?php echo $cost3; ?>">
                        <?php if ($current_points >= $cost3): ?>
                            <button type="submit" name="redeem_reward" class="btn-primary">Redeem Now</button>
                        <?php else: ?>
                            <button type="button" class="btn-primary" style="background: #ccc; cursor: not-allowed;" disabled>Not Enough Points</button>
                        <?php endif; ?>
                    </form>
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