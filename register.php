<?php
// Keep error reporting on while we are debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'config.php';

// Check if the form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    
    $document_name = null; // Default kosong jika tiada dokumen (untuk user biasa)
    $status = 'approved';  // User biasa terus diluluskan

    // JIKA pendaftar adalah staff atau kilang, wajibkan muat naik dokumen
    if ($role === 'recycling staff' || $role === 'factory') {
        $status = 'pending'; // Akaun perlu disemak oleh admin dahulu

        if (isset($_FILES['document']) && $_FILES['document']['error'] == 0) {
            $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
            $file_name = $_FILES['document']['name'];
            $file_size = $_FILES['document']['size'];
            $file_tmp  = $_FILES['document']['tmp_name'];
            
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            // Sahkan format fail
            if (in_array($file_ext, $allowed_extensions)) {
                // Beri nama unik kepada fail bagi mengelakkan pertindihan nama
                $document_name = time() . '_' . uniqid() . '.' . $file_ext;
                $upload_dir = 'uploads/documents/';

                // Cipta folder uploads jika belum wujud
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                // Pindahkan fail ke folder tujuan
                if (!move_uploaded_file($file_tmp, $upload_dir . $document_name)) {
                    die("Gagal memindahkan fail muat naik.");
                }
            } else {
                echo "<script>alert('Format fail tidak dibenarkan! Sila muat naik PDF, Word, atau Gambar sahaja.'); window.history.back();</script>";
                exit();
            }
        } else {
            echo "<script>alert('Ralat! Pendaftaran akaun ini memerlukan muat naik dokumen pengesahan.'); window.history.back();</script>";
            exit();
        }
    }

    // Ganti/Suaikan query jika table 'users' anda mempunyai kolon 'document' dan 'status'
    // Prepared statement untuk keselamatan
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, document, status) VALUES (?, ?, ?, ?, ?, ?)");
    
    if ($stmt === false) {
        die("Preparation failed: " . $conn->error);
    }

    $stmt->bind_param("ssssss", $name, $email, $password, $role, $document_name, $status);

    if ($stmt->execute()) {
        if ($status === 'pending') {
            echo "<script>alert('Pendaftaran Berjaya! Dokumen anda sedang disemak oleh pihak Admin sebelum anda boleh log masuk.'); window.location='login.php';</script>";
        } else {
            echo "<script>alert('SUCCESS! You are now in the database.'); window.location='login.php';</script>";
        }
    } else {
        echo "Execute failed: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | RecycleHub</title>
    <link rel="stylesheet" href="relog.css">
    <style>
        /* CSS Tambahan untuk menyembunyikan/memaparkan bahagian fail */
        #document-upload-section {
            display: none;
            margin-top: 15px;
            padding: 12px;
            background: #f4fbf4;
            border-left: 4px solid #2d5a27;
            border-radius: 4px;
        }
        .file-input {
            margin-top: 5px;
            display: block;
            width: 100%;
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="logo">Recycle<span>Hub</span></div>
        <ul class="nav-links">
            <li><a href="index.html">Home</a></li>
            <li><a href="login.php" class="btn-primary">Sign In</a></li>
        </ul>
    </nav>

    <section class="auth-screen">
        <div class="auth-card">
            <div class="auth-header">
                <h2>Join the Hub</h2>
                <p>Select your role to get started</p>
            </div>

            <!-- Ditambah enctype="multipart/form-data" untuk membolehkan muat naik fail -->
            <form method="POST" action="register.php" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="input-group">
                        <label>Full Name</label>
                        <input type="text" name="name" placeholder="John Doe" required>
                    </div>
                </div>

                <div class="input-group">
                    <label>Account Type</label>
                    <select class="custom-select" name="role" id="role-select" onchange="toggleDocumentUpload()" required>
                        <option value="" disabled selected>Who are you?</option>
                        <option value="user">Individual User</option>
                        <option value="factory">Factory Personnel</option>
                        <option value="recycling staff">Recycling Staff</option>
                    </select>
                </div>

                <!-- Bahagian Muat Naik Dokumen (Disembunyikan secara default) -->
                <div id="document-upload-section" class="input-group">
                    <label style="color: #2d5a27; font-weight: bold;">Upload Verification Document</label>
                    <span style="font-size: 0.8rem; color: #666; display:block;">Please upload your staff ID card or company authorization letter (PDF, Word, or Image).</span>
                    <input type="file" name="document" id="document-input" class="file-input">
                </div>

                <div class="input-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="name@company.com" required>
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn-primary btn-full">Create Account</button>
            </form>

            <p class="auth-switch">Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </section>

    <!-- JavaScript untuk fungsi dinamik paparan input file -->
    <script>
    function toggleDocumentUpload() {
        var roleSelect = document.getElementById("role-select").value;
        var uploadSection = document.getElementById("document-upload-section");
        var docInput = document.getElementById("document-input");

        if (roleSelect === "recycling staff" || roleSelect === "factory") {
            uploadSection.style.display = "block";
            docInput.required = true; // Wajib isi jika pilih staff/kilang
        } else {
            uploadSection.style.display = "none";
            docInput.required = false; // Pengguna biasa tidak perlu isi
            docInput.value = ""; // Reset fail jika pengguna tukar pilihan semula
        }
    }
    </script>

</body>
</html>