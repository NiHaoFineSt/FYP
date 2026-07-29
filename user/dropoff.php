<?php
session_start();
include __DIR__ . '/../config.php';

// SECURITY CHECK
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch distinct states for the dropdown filter
$states_query = "SELECT DISTINCT state FROM recycling_centers WHERE state IS NOT NULL AND state != '' ORDER BY state ASC";
$states_result = $conn->query($states_query);

// Fetch distinct locations/cities for the dropdown filter
$locations_query = "SELECT DISTINCT location FROM recycling_centers WHERE location IS NOT NULL AND location != '' ORDER BY location ASC";
$locations_result = $conn->query($locations_query);

// Fetch all recycling centers from database
$hubs_query = "SELECT * FROM recycling_centers ORDER BY center_name ASC";
$hubs_result = $conn->query($hubs_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drop-off Points | RecycleHub</title>
    <link rel="stylesheet" href="../style.css"> 
    <link rel="stylesheet" href="dropoff.css">
    <link rel="stylesheet" href="citizen dashboard.css">
    <!-- QRCode library loaded in head so it's available when needed -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        .filter-controls {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
        .area-select {
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 0.9rem;
            outline: none;
            background-color: #fff;
            min-width: 160px;
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
                <p style="color: white;">ID: #<?php echo htmlspecialchars($user_id); ?></p>
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
                    <p>Locate the nearest Community Recycling Centers (CRC) and facilities.</p>
                </div>
                <button class="btn-primary" onclick="getLocation()" style="padding: 10px 20px; cursor: pointer;">Use Current Location</button>
            </header>

            <section class="activity-section">
                <div class="section-header">
                    <h3>Interactive Hub Finder</h3>
                    <div class="filter-controls">
                        <!-- State Dropdown Filter -->
                        <select id="state-filter" class="area-select" onchange="filterHubs()">
                            <option value="all">All States</option>
                            <?php 
                            if ($states_result && $states_result->num_rows > 0) {
                                while($state_row = $states_result->fetch_assoc()) {
                                    echo '<option value="' . htmlspecialchars($state_row['state']) . '">' . htmlspecialchars($state_row['state']) . '</option>';
                                }
                            }
                            ?>
                        </select>

                        <!-- Location / City Dropdown Filter -->
                        <select id="location-filter" class="area-select" onchange="filterHubs()">
                            <option value="all">All Locations / Cities</option>
                            <?php 
                            if ($locations_result && $locations_result->num_rows > 0) {
                                while($loc_row = $locations_result->fetch_assoc()) {
                                    echo '<option value="' . htmlspecialchars($loc_row['location']) . '">' . htmlspecialchars($loc_row['location']) . '</option>';
                                }
                            }
                            ?>
                        </select>
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
                                <th>Center Name</th>
                                <th>Location / State</th>
                                <th>Full Address</th>
                                <th>Operating Hours</th>
                                <th>Phone Number</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="hub-list-body">
                            <?php if ($hubs_result && $hubs_result->num_rows > 0): ?>
                                <?php while ($hub = $hubs_result->fetch_assoc()): ?>
                                    <tr class="hub-row" 
                                        data-state="<?= htmlspecialchars($hub['state'] ?? '') ?>" 
                                        data-location="<?= htmlspecialchars($hub['location'] ?? '') ?>">
                                        <td><strong><?= htmlspecialchars($hub['center_name']) ?></strong></td>
                                        <td>
                                            <?= htmlspecialchars($hub['location']) ?><?= (!empty($hub['state']) ? ', ' . htmlspecialchars($hub['state']) : '') ?>
                                        </td>
                                        <td><?= htmlspecialchars($hub['full_address'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($hub['service_hours'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($hub['phone_number'] ?? 'N/A') ?></td>
                                        <td>
                                            <?php 
                                                // Uses google_maps_link column, fallback to search query if empty
                                                $map_url = !empty($hub['google_maps_link']) 
                                                    ? $hub['google_maps_link'] 
                                                    : "https://www.google.com/maps/search/?api=1&query=" . urlencode($hub['center_name'] . " " . $hub['full_address']);
                                            ?>
                                            <a href="<?= htmlspecialchars($map_url) ?>" 
                                               target="_blank" class="btn-outline" style="text-decoration: none; padding: 6px 12px; display: inline-block;">Directions</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: #888;">No recycling centers registered yet.</td>
                                </tr>
                            <?php endif; ?>
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

        // Live Filtering Logic
        function filterHubs() {
            var selectedState = document.getElementById('state-filter').value.toLowerCase();
            var selectedLocation = document.getElementById('location-filter').value.toLowerCase();
            var rows = document.querySelectorAll('.hub-row');
            var visibleCount = 0;

            rows.forEach(function(row) {
                var rowState = row.getAttribute('data-state').toLowerCase();
                var rowLocation = row.getAttribute('data-location').toLowerCase();

                var stateMatches = (selectedState === 'all' || rowState === selectedState);
                var locationMatches = (selectedLocation === 'all' || rowLocation === selectedLocation);

                if (stateMatches && locationMatches) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            var nearestInfo = document.getElementById('nearest-info');
            if (visibleCount === 0) {
                nearestInfo.innerText = "No locations found matching the selected filter criteria.";
            } else {
                nearestInfo.innerText = "Displaying " + visibleCount + " center(s).";
            }
        }

        // Generate Sidebar QR Code
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