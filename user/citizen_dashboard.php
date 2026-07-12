<?php
session_start();
// Use '../' because this file is inside the 'user' folder
include '../config.php'; 

// 1. SECURITY CHECK: Ensure user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. FETCH LIVE USER DATA (Totals from 'users' table)
$query = "SELECT name, total_kg, total_points FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();

// 3. GENERATE QR CODE URL (For the sidebar)
$qrCodeUrl = "https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl=" . $user_id . "&choe=UTF-8";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citizen Dashboard | RecycleHub</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="citizen dashboard.css">
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
                    <h2>Hello, <?php echo htmlspecialchars($userData['name']); ?>!</h2>
                    <p>Track your impact and find nearby hubs.</p>
                </div>
                <div class="user-badge">Eco-Warrior</div>
            </header>

            <section class="stats-grid">
                <div class="stat-card">
                    <h4>Total Recycled</h4>
                    <p><?php echo number_format($userData['total_kg'], 1); ?> <span>kg</span></p>
                </div>
                <div class="stat-card">
                    <h4>Hub Points</h4>
                    <p><?php echo number_format($userData['total_points']); ?> <span>pts</span></p>
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
                                    echo "<td>" . $row['weight'] . " kg</td>";
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
        // Generate QR code in the sidebar
        new QRCode(document.getElementById("sidebar-qrcode"), {
            text: "<?php echo $user_id; ?>",
            width: 150,
            height: 150,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
</body>
</html>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>