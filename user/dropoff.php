<?php
session_start();
include '../config.php'; 

// SECURITY CHECK
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drop-off Points | RecycleHub</title>
    <link rel="stylesheet" href="../style.css"> 
    <link rel="stylesheet" href="dropoff.css">
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
                <a href="dropoff.php" class="active">Drop-off Points</a>
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
                    <h2>Drop-off Points</h2>
                    <p>Locate the nearest Community Recycling Centers (CRC).</p>
                </div>
                <button class="btn-primary" onclick="getLocation()" style="padding: 10px 20px; cursor: pointer;">Use Current Location</button>
            </header>

            <section class="activity-section">
                <div class="section-header">
                    <h3>Interactive Hub Map</h3>
                    <div class="map-filters">
                        <button class="filter-btn active">All</button>
                        <button class="filter-btn">CRC</button>
                        <button class="filter-btn">MRCF</button>
                    </div>
                </div>
            </section>

            <section class="activity-section">
                <div class="section-header">
                    <h3>Nearby Locations</h3>
                    <p id="nearest-info" style="font-size: 0.85rem; color: #666;"></p>
                </div>
                
                <div class="table-responsive">
                    <table class="activity-table">
                        <thead>
                            <tr>
                                <th>Hub Name</th>
                                <th>Accepted Materials</th>
                                <th>Distance</th>
                                <th>Operating Hours</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="hub-list-body">
                            <tr>
                                <td><strong>Downtown CRC</strong></td>
                                <td>
                                    <span class="material-tag plastic">Plastic</span>
                                    <span class="material-tag paper">Paper</span>
                                </td>
                                <td>1.2 km</td>
                                <td>08:00 AM - 06:00 PM</td>
                                <td><button class="btn-outline">Directions</button></td>
                            </tr>
                            <tr>
                                <td><strong>Westside MRCF</strong></td>
                                <td>
                                    <span class="material-tag metal">Metal</span>
                                    <span class="material-tag glass">Glass</span>
                                </td>
                                <td>3.5 km</td>
                                <td>09:00 AM - 05:00 PM</td>
                                <td><button class="btn-outline">Directions</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <script>
        function getLocation() {
            if (navigator.geolocation) {
                document.getElementById('nearest-info').innerText = "Accessing location...";
                navigator.geolocation.getCurrentPosition(showPosition);
            } else {
                alert("Geolocation is not supported by this browser.");
            }
        }

        function showPosition(position) {
            document.getElementById('nearest-info').innerText = "Showing centers near your current coordinates (" + position.coords.latitude.toFixed(2) + ", " + position.coords.longitude.toFixed(2) + ").";
        }

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
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>