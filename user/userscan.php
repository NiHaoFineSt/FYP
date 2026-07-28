<?php
session_start();
include('../config.php'); 

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// SEPADAN DATABASE SEBENAR: Menggunakan kolum 'points'
$user_query = $conn->prepare("SELECT points FROM users WHERE user_id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_result = $user_query->get_result()->fetch_assoc();
$current_points = isset($user_result['points']) ? $user_result['points'] : 0;

$mesej = "";
$status_mesej = "";

// ==========================================
// PROSES PENGESAHAN KOD PIN DARI DATABASE
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_pin'])) {
    $pin_input = trim($_POST['pin_code']);

    if (!empty($pin_input)) {
        // 1. Cari sama ada kod PIN wujud dan belum dituntut
        $stmt = $conn->prepare("SELECT id, berat_g FROM timbangan WHERE kod_pin = ? AND status = 'Belum Dituntut'");
        $stmt->bind_param("s", $pin_input);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $timbangan_id = $row['id'];
            
            // PEMBERSIHAN DATA BERAT (Diambil dari ESP32 dalam unit GRAM)
            $raw_berat = preg_replace('/[^0-9.]/', '', $row['berat_g']);
            $berat_gram = floatval($raw_berat); 

            // Formula ganjaran (1 gram berat = 1 mata ganjaran)
            $points_earned = floor($berat_gram); 

            // 2. Kemas kini status PIN kepada 'Dah Dituntut'
            $update_stmt = $conn->prepare("UPDATE timbangan SET status = 'Dah Dituntut' WHERE id = ?");
            $update_stmt->bind_param("i", $timbangan_id);
            
            if ($update_stmt->execute()) {
                // 3. Kemas kini kolum 'points' dan 'total_points' pengguna
                $points_stmt = $conn->prepare("UPDATE users SET points = points + ?, total_points = total_points + ? WHERE user_id = ?");
                $points_stmt->bind_param("iii", $points_earned, $points_earned, $user_id);
                $points_stmt->execute();

                // 4. MASUKKAN REKOD KE TRANSACTIONS
                // Tukar Gram dari ESP32 ke KG untuk paparan My Recycling (cth: 500g -> 0.50 kg)
                $weight_kg = floatval(number_format($berat_gram / 1000, 2, '.', '')); 
                
                // Setkan jenis bahan secara khusus kepada Plastic
                $material_type = "Plastic"; 
                $hub_location = "Smart Bin Kiosk";  
                $status_trx = "Claimed";            

                $trx_stmt = $conn->prepare("INSERT INTO transactions (user_id, material_type, weight, points, hub_location, status, date) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $trx_stmt->bind_param("isdiss", $user_id, $material_type, $weight_kg, $points_earned, $hub_location, $status_trx);
                $trx_stmt->execute();

                // Mesej kejayaan
                $mesej = "🎉 Point has been claimed successfully! (+$points_earned Points | $weight_kg kg Plastic)";
                $status_mesej = "success";

                // Refresh nilai mata ganjaran pada skrin
                $user_query->execute();
                $user_result = $user_query->get_result()->fetch_assoc();
                $current_points = $user_result['points'];
            } else {
                $mesej = "⚠️ System error while processing your claim.";
                $status_mesej = "error";
            }
        } else {
            $mesej = "❌ Invalid PIN code, expired, or already claimed!";
            $status_mesej = "error";
        }
    } else {
        $mesej = "✍️ Please enter a 4-digit PIN code.";
        $status_mesej = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim Code | RecycleHub</title>
    <link rel="stylesheet" href="../style.css">
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
                <p style="color: white; margin-bottom: 5px;">ID: #<?php echo $user_id; ?></p>
                <p style="color: #a2ffa2; font-weight: bold; font-size: 0.9rem; margin-top: 5px;">Points: <?php echo $current_points; ?> pts</p>
            </div>
            <nav class="side-nav">
                <a href="citizen_dashboard.php">Overview</a>
                <a href="myrecycing.php">My Recycling</a>
                <a href="dropoff.php">Drop-off Points</a>
                <a href="customerqr.php">My QR Code</a>
                <a href="userscan.php" class="active">Claim Code</a>
                <a href="reward.php">Rewards</a>
                <a href="Profilepage.php">Profile</a>
                
                <div class="nav-divider"></div>
                <a href="../logout.php" class="logout">Logout</a>
            </nav>
        </aside>

        <main class="dashboard-content">
            <h2>Claim Recycling Code</h2>
            
            <section class="activity-section" style="max-width: 500px;">
                
                <?php if (!empty($mesej)): ?>
                    <div style="padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; background: <?php echo ($status_mesej == 'success') ? '#e8f5e9' : '#ffebee'; ?>; color: <?php echo ($status_mesej == 'success') ? '#2d5a27' : '#c62828'; ?>;">
                        <?php echo $mesej; ?>
                    </div>
                <?php endif; ?>

                <div>
                    <h3>Kemasukan Kod Ganjaran</h3>
                    <p>Masukkan 4-digit PIN yang terpapar di skrin OLED mesin penimbang.</p>
                    
                    <form method="POST" action="">
                        <input type="text" name="pin_code" maxlength="4" placeholder="0000" required autocomplete="off" style="font-size: 2rem; letter-spacing: 8px; text-align: center; padding: 10px; width: 100%; max-width: 250px; margin: 20px 0; display: block;">
                        
                        <button type="submit" name="submit_pin" class="btn-primary">Tuntut Mata Ganjaran</button>
                    </form>
                </div>

            </section>
        </main>
    </div>

    <script>
        window.onload = function() {
            new QRCode(document.getElementById("sidebar-qrcode"), {
                text: "<?php echo $user_id; ?>",
                width: 85,
                height: 85,
                colorDark : "#2d5a27",
                colorLight : "#ffffff"
            });
        };
    </script>
</body>
</html>