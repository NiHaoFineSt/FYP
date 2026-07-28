<?php
session_start();
// Use '../' because this file is inside the 'user' folder
include('../config.php'); 

// 1. SECURITY CHECK: Ensure user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. FETCH LIVE USER DATA (Name & Points)
$query = "SELECT name, points, total_points FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();

// 3. KIRA JUMLAH BERAT (TOTAL RECYCLED IN KG) TERUS DARI TRANSACTIONS
$sumQuery = "SELECT SUM(weight) AS total_recycled_kg FROM transactions WHERE user_id = ?";
$sumStmt = $conn->prepare($sumQuery);
$sumStmt->bind_param("i", $user_id);
$sumStmt->execute();
$sumResult = $sumStmt->get_result()->fetch_assoc();

// Jika belum ada sebarang transaksi, tetapkan 0
$total_recycled_kg = isset($sumResult['total_recycled_kg']) && $sumResult['total_recycled_kg'] !== null ? $sumResult['total_recycled_kg'] : 0;
$current_points = isset($userData['points']) ? $userData['points'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citizen Dashboard | RecycleHub</title>
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
                <p style="color: white;">ID: #<?php echo $user_id; ?></p>
            </div>
            <nav class="side-nav">
                <a href="citizen_dashboard.php" class="active">Overview</a>
                <a href="myrecycing.php">My Recycling</a>
                <a href="dropoff.php">Drop-off Points</a>
                <a href="customerqr.php">My QR Code</a>
                <a href="userscan.php">Claim Code</a>
                <a href="reward.php">Rewards</a>
                <a href="Profilepage.php">Profile</a>
                <div class="nav-divider"></div>
                <a href="../logout.php" class="logout">Logout</a>
            </nav>
        </aside>

        <main class="dashboard-content">
            <header class="dash-header">
                <div>
                    <h2>Hello, <?php echo htmlspecialchars($userData['name']); ?>!</h2>
                    <p>Track your impact and find nearby hubs.</p>
                </div>
                <div class="user-badge">Eco-Warrior</div>
            </header>

            <section class="stats-grid">
                <!-- STAT CARD 1: TOTAL RECYCLED (KG) -->
                <div class="stat-card">
                    <h4>Total Recycled</h4>
                    <p><?php echo number_format($total_recycled_kg, 2); ?> <span>kg</span></p>
                </div>
                
                <!-- STAT CARD 2: HUB POINTS (PTS) -->
                <div class="stat-card">
                    <h4>Hub Points</h4>
                    <p><?php echo number_format($current_points); ?> <span>pts</span></p>
                </div>
            </section>

            <section class="activity-section">
                <div class="section-header">
                    <h3>Recent Activity</h3>
                    <a href="myrecycing.php" class="view-all">View All</a>
                </div>
                
                <div class="table-responsive">
                    <table class="activity-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Material</th>
                                <th>Weight</th>
                                <th>Points</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch 3 most recent transactions
                            $hist = "SELECT * FROM transactions WHERE user_id = ? ORDER BY date DESC LIMIT 3";
                            $hStmt = $conn->prepare($hist);
                            $hStmt->bind_param("i", $user_id);
                            $hStmt->execute();
                            $hRes = $hStmt->get_result();

                            if ($hRes->num_rows > 0) {
                                while($row = $hRes->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . date('d M Y', strtotime($row['date'])) . "</td>";
                                    echo "<td><div class='material-tag plastic'>" . htmlspecialchars($row['material_type']) . "</div></td>";
                                    echo "<td>" . number_format($row['weight'], 2) . " kg</td>";
                                    echo "<td>+" . $row['points'] . "</td>";
                                    echo "<td><span class='status-verified'>" . $row['status'] . "</span></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' style='text-align:center; padding:20px;'>No recycling activity found. Go green today!</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
    
    <script>
        window.onload = function() {
            var userId = "<?php echo $user_id; ?>";
            var qrElement = document.getElementById("sidebar-qrcode");
            if (qrElement) {
                new QRCode(qrElement, {
                    text: userId,
                    width: 85,
                    height: 85,
                    colorDark : "#2d5a27",
                    colorLight : "#ffffff",
                    correctLevel : QRCode.CorrectLevel.H
                });
            }
        };
    </script>
</body>
</html>