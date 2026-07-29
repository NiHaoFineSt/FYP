<?php
session_start();
require_once __DIR__ . '/../config.php';

// 1. FIXED SECURITY: Check for 'recycling staff' role, not 'citizen'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'recycling staff') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = ""; 

// 2. PHP LOGIC TO PROCESS THE DEPOSIT
if (isset($_POST['deposit_submit'])) {
    $cust_id = intval($_POST['customer_id_hidden']);
    $weight = floatval($_POST['weight']);
    $material = mysqli_real_escape_string($conn, $_POST['material_type']);
    
    // Points Calculation: 10 points per 1kg
    $points = floor($weight * 10);

    // Start a transaction to ensure both updates happen together
    $conn->begin_transaction();

    try {
        // Update the users table (for the big totals on the dashboard)
        $updateSql = "UPDATE users SET total_kg = total_kg + ?, total_points = total_points + ? WHERE user_id = ?";
        $stmtUp = $conn->prepare($updateSql);
        $stmtUp->bind_param("dii", $weight, $points, $cust_id);
        $stmtUp->execute();

        // Insert into transactions table (for the history list)
        $historySql = "INSERT INTO transactions (user_id, material_type, weight, points, status, date) 
                       VALUES (?, ?, ?, ?, 'Verified', NOW())";
        $stmtHist = $conn->prepare($historySql);
        $stmtHist->bind_param("isdi", $cust_id, $material, $weight, $points);
        $stmtHist->execute();

        $conn->commit();
        $message = "<div class='alert success'>✅ Success! Added $points points to User #$cust_id.</div>";
    } catch (Exception $e) {
        $conn->rollback();
        $message = "<div class='alert error'>❌ Error: Points could not be awarded.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Scan & Deposit | RecycleHub Staff</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="managerequest.css">
    <script src="https://unpkg.com/html5-qrcode"></script>
    <style>
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; text-align: center; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        #reader { width: 100%; border-radius: 12px; border: 2px solid #eee; overflow: hidden; background: #fafafa; }
        .locked { opacity: 0.4; pointer-events: none; transition: 0.3s; }
        .upload-box { margin-top: 10px; padding: 10px; border: 1px dashed #ccc; border-radius: 8px; text-align: center; background: #fff; }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="logo">Recycle<span>Hub</span></div>
            <nav class="side-nav">
                <a href="recyclingstaff.php">Overview</a>
                <a href="managerequest.php">Manage Requests</a>
                <a href="inventorylog.php">Inventory Log</a>
                <a href="staffscan.php" class="active">Scan QR</a>
                <a href="factory.php">Factory</a>
                <a href="staffchat.php">Staff Chat</a>
                <a href="staff_profile.php">Profile</a>
                <div class="nav-divider"></div>
                <a href="../logout.php" class="logout">Logout</a>
            </nav>
        </aside>

        <main class="dashboard-content">
            <header class="dash-header">
                <h2>Customer Deposit</h2>
                <p>Scan live, upload QR image, or enter ID manually.</p>
            </header>

            <?php echo $message; ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <section class="activity-section" style="background:white; padding:20px; border-radius:12px; box-shadow:0 4px 6px rgba(0,0,0,0.05);">
                    <h3>1. Identify Customer</h3>
                    
                    <div id="reader"></div>
                    
                    <div class="upload-box">
                        <label><strong>Or Upload QR Image:</strong></label><br>
                        <input type="file" id="qr-input-file" accept="image/*" style="margin-top:5px;">
                    </div>

                    <div style="margin-top: 15px;">
                        <label>Manual User ID Entry</label>
                        <input type="text" id="manual_id" placeholder="Enter ID..." style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
                    </div>
                    
                    <button type="button" onclick="verifyUser()" style="width:100%; margin-top:10px; padding:12px; background:#2d5a27; color:white; border:none; border-radius:8px; cursor:pointer;">
                        Verify Customer
                    </button>

                    <div id="customer-info" style="margin-top:15px; padding:15px; background:#e8f5e9; border-radius:8px; display:none; border: 1px solid #c3e6cb;">
                        <strong>User Found:</strong> <span id="disp-name"></span><br>
                        <strong>User ID:</strong> #<span id="disp-id"></span>
                    </div>
                </section>

                <section class="activity-section" style="background:white; padding:20px; border-radius:12px; box-shadow:0 4px 6px rgba(0,0,0,0.05);">
                    <h3>2. Key In Data</h3>
                    <form method="POST" id="points-form" class="locked">
                        <input type="hidden" name="customer_id_hidden" id="customer_id_hidden">
                        
                        <div style="margin-bottom:15px;">
                            <label>Material Type</label>
                            <select name="material_type" required style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd;">
                                <option value="Plastic">Plastic</option>
                                <option value="Metal">Metal</option>
                                <option value="Glass">Glass</option>
                                <option value="Paper">Paper</option>
                            </select>
                        </div>

                        <div style="margin-bottom:15px;">
                            <label>Weight (kg)</label>
                            <input type="number" name="weight" id="w-input" step="0.1" required style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd;">
                        </div>

                        <div style="background:#f8f9fa; padding:15px; border-radius:8px; margin-bottom:15px;">
                            Points Preview: <strong id="p-preview" style="color:#2d5a27; font-size:1.2rem;">0</strong>
                        </div>

                        <button type="submit" name="deposit_submit" style="width:100%; padding:12px; background:#2d5a27; color:white; border:none; border-radius:8px; font-weight:bold; cursor:pointer;">
                            Award Points
                        </button>
                    </form>
                </section>
            </div>
        </main>
    </div>

    <script>
        // --- 1. LIVE CAMERA SCANNER ---
        const html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: 250 });
        html5QrcodeScanner.render((decodedText) => {
            handleDiscovery(decodedText);
            html5QrcodeScanner.clear(); 
        });

        // --- 2. IMAGE UPLOAD SCANNER ---
        const fileInput = document.getElementById('qr-input-file');
        fileInput.addEventListener('change', e => {
            if (e.target.files.length == 0) return;
            const imageFile = e.target.files[0];
            const html5QrCode = new Html5Qrcode("reader");
            
            html5QrCode.scanFile(imageFile, true)
                .then(decodedText => {
                    handleDiscovery(decodedText);
                })
                .catch(err => {
                    alert("Could not find a valid QR code in this image.");
                });
        });

        function handleDiscovery(userId) {
            document.getElementById('manual_id').value = userId;
            verifyUser(userId);
        }

        // --- 3. VERIFICATION (Talks to get_user_status.php) ---
        function verifyUser(userId) {
            const id = userId || document.getElementById('manual_id').value;
            if(!id) return alert("Please scan, upload, or enter an ID");

            fetch(`get_user_status.php?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        document.getElementById('customer-info').style.display = 'block';
                        document.getElementById('disp-name').innerText = data.name;
                        document.getElementById('disp-id').innerText = id;
                        document.getElementById('customer_id_hidden').value = id;
                        document.getElementById('points-form').classList.remove('locked');
                    } else {
                        alert("User ID not found in database!");
                    }
                })
                .catch(err => alert("Server error. Check get_user_status.php"));
        }

        // --- 4. POINTS PREVIEW ---
        document.getElementById('w-input').addEventListener('input', (e) => {
            document.getElementById('p-preview').innerText = Math.floor(e.target.value * 10);
        });
    </script>
</body>
</html>