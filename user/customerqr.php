<?php
session_start();
include('../config.php'); 

// SECURITY CHECK
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. FETCH USER DATA (Name & Points)
$query = "SELECT name, points FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();

// 2. CALCULATE TOTAL WEIGHT SAVED (KG) FROM TRANSACTIONS
$sumQuery = "SELECT SUM(weight) AS total_kg FROM transactions WHERE user_id = ?";
$sumStmt = $conn->prepare($sumQuery);
$sumStmt->bind_param("i", $user_id);
$sumStmt->execute();
$sumResult = $sumStmt->get_result()->fetch_assoc();

$total_kg = isset($sumResult['total_kg']) && $sumResult['total_kg'] !== null ? $sumResult['total_kg'] : 0;
$total_points = isset($userData['points']) ? $userData['points'] : 0;

// 3. DYNAMIC GLOBAL RANKING
$rankQuery = "SELECT COUNT(*) + 1 AS user_rank FROM users WHERE points > ?";
$rankStmt = $conn->prepare($rankQuery);
$rankStmt->bind_param("i", $total_points);
$rankStmt->execute();
$rankResult = $rankStmt->get_result()->fetch_assoc();

$global_rank = $rankResult['user_rank'];
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
    <link rel="stylesheet" href="citizen dashboard.css"> 
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <style>
        /* RESET BASE LAYOUT */
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            background-color: #f4f6f8 !important;
            width: 100%;
            height: 100%;
            overflow-x: hidden;
        }

        /* FLEX CONTAINER WITH ZERO GAPS */
        .dashboard-wrapper {
            display: flex !important;
            width: 100% !important;
            min-height: 100vh !important;
            margin: 0 !important;
            padding: 0 !important;
            gap: 0 !important; /* Forces zero space between sidebar & content */
        }

        /* SIDEBAR STYLING & SCROLL */
        .sidebar {
            width: 260px !important;
            min-width: 260px !important;
            max-width: 260px !important;
            height: 100vh !important;
            max-height: 100vh !important;
            overflow-y: auto !important;
            position: sticky !important;
            top: 0 !important;
            box-sizing: border-box !important;
            margin: 0 !important;
            flex-shrink: 0 !important;
        }

        /* MAIN CONTENT AREA - STRETCH TO FILL ALL REMAINING SPACE */
        .dashboard-content {
            flex: 1 !important;
            width: calc(100% - 260px) !important;
            margin: 0 !important;
            padding: 24px 32px !important; /* Adjust internal padding neatly */
            box-sizing: border-box !important;
            background-color: #f4f6f8 !important;
            min-height: 100vh !important;
        }

        /* FIX INTERNAL SECTIONS OVERFLOW / MARGIN OFFSETS */
        .dash-header, .stats-grid, .activity-section {
            width: 100% !important;
            max-width: 1200px !important; /* Keeps cards neat on ultra-wide screens */
            margin-left: 0 !important;    /* Prevents central cards from drifting right */
            box-sizing: border-box !important;
        }

        .qr-display-container {
            max-width: 700px;
            margin: 0 auto; /* Centers the QR display card inside the content column */
        }

        /* Smooth scrollbar for webkit browsers */
        .sidebar::-webkit-scrollbar {
            width: 5px;
        }
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
        }
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.4);
        }
    </style>
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
            
            <!-- SIDEBAR NAVIGATION WITH LOGOUT -->
            <nav class="side-nav">
                <a href="citizen_dashboard.php">Overview</a>
                <a href="myrecycing.php">My Recycling</a>
                <a href="dropoff.php">Drop-off Points</a>
                <a href="customerqr.php" class="active">My QR Code</a>
                <a href="userscan.php">Claim Code</a>
                <a href="reward.php">Rewards</a>
                <a href="Profilepage.php">Profile</a>
                
                <div class="nav-divider"></div>
                <!-- LOGOUT BUTTON IN NAVIGATION -->
                <a href="../logout.php" class="logout" onclick="return confirm('Are you sure you want to log out?');">Logout</a>
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
                    <p><?php echo number_format($total_points); ?> <span>pts</span></p>
                </div>
                <div class="stat-card">
                    <h4>Weight Saved</h4>
                    <p><?php echo number_format($total_kg, 2); ?> <span>kg</span></p>
                </div>
                <div class="stat-card">
                    <h4>Global Rank</h4>
                    <p>#<?php echo $global_rank; ?> <span>rank</span></p>
                </div>
            </section>

            <section class="activity-section" style="text-align: center; padding: 20px 0;">
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
        var userID = "<?php echo $user_id; ?>";

        new QRCode(document.getElementById("qrcode"), {
            text: userID,
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