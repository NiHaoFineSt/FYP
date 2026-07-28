<?php
session_start();
// Go up one level to find config.php from the 'user' folder
include('../config.php'); 

// 1. SECURITY CHECK: Only allow logged-in 'user' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. FETCH FULL RECYCLING HISTORY
$query = "SELECT * FROM transactions WHERE user_id = ? ORDER BY date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recycling History | RecycleHub</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="myrecycling.css">
    <link rel="stylesheet" href="citizen dashboard.css"> 
</head>
<body>

    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="logo">Recycle<span>Hub</span></div>
            
            <div class="user-profile-side" style="padding: 20px; text-align: center;">
                <div style="background: white; padding: 8px; border-radius: 8px; width: 100px; height: 100px; margin: 0 auto 10px auto; display: flex; align-items: center; justify-content: center;">
                    <div id="sidebar-qrcode"></div>
                </div>
                <p style="color: white;">ID: #<?php echo $user_id; ?></p>
            </div>
            <nav class="side-nav">
                <a href="citizen_dashboard.php">Overview</a>
                <a href="myrecycing.php" class="active">My Recycling</a>
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
                    <h2>My Recycling History</h2>
                    <p>A detailed log of all your past contributions to the environment.</p>
                </div>
                <div class="search-container">
                    <input type="text" id="searchInput" placeholder="Search materials..." class="filter-btn" style="text-align: left; width: 250px; background: white;">
                </div>
            </header>

            <section class="activity-section">
                <div class="section-header">
                    <h3>All Transactions</h3>
                    <div class="map-filters">
                        <button class="filter-btn active">All Time</button>
                        <button class="filter-btn" onclick="window.print()">Print Report</button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="activity-table">
                        <thead>
                            <tr>
                                <th>Ref ID</th>
                                <th>Date</th>
                                <th>Material</th>
                                <th>Weight</th>
                                <th>Points</th>
                                <th>Location</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="historyTable">
                            <?php 
                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    // FIX: Use null coalescing ?? '' to prevent Deprecated warnings
                                    $mType = $row['material_type'] ?? 'Unknown';
                                    $materialClass = strtolower($mType);
                                    $location = $row['hub_location'] ?? 'Not Specified';
                                    
                                    echo "<tr>";
                                    echo "<td>#RH-" . ($row['transaction_id'] ?? $row['id']) . "</td>";
                                    echo "<td>" . date('d M Y', strtotime($row['date'])) . "</td>";
                                    echo "<td><div class='material-tag $materialClass'>" . htmlspecialchars($mType) . "</div></td>";
                                    echo "<td>" . number_format($row['weight'], 2) . " kg</td>";
                                    echo "<td><strong style='color: #68b04d;'>+" . $row['points'] . "</strong></td>";
                                    echo "<td>" . htmlspecialchars($location) . "</td>";
                                    echo "<td><span class='status-verified'>" . htmlspecialchars($row['status'] ?? 'Verified') . "</span></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='7' style='text-align:center; padding: 20px;'>No records found. Visit a hub to start recycling!</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination-footer" style="margin-top: 2rem; display: flex; justify-content: center; gap: 0.5rem;">
                    <button class="filter-btn">Previous</button>
                    <button class="filter-btn active">1</button>
                    <button class="filter-btn">Next</button>
                </div>
            </section>
        </main>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
    document.getElementById('searchInput').addEventListener('keyup', function() {
        var input = this.value.toLowerCase();
        var rows = document.querySelectorAll('#historyTable tr');
        rows.forEach(row => {
            row.style.display = row.innerText.toLowerCase().includes(input) ? '' : 'none';
        });
    });

    window.onload = function() {
        var userId = "<?php echo $user_id; ?>";
        var qrElement = document.getElementById("sidebar-qrcode");
        if (qrElement) {
            new QRCode(qrElement, {
                text: userId,
                width: 90,
                height: 90,
                colorDark : "#2d5a27",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.H
            });
        }
    };
    </script>
</body>
</html>