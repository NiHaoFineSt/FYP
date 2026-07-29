<?php
// Keep error reporting on while debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'config.php';

// Check if the form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    
    $document_data = null; // Default null for normal users
    $status = 'approved';  // Normal users approved automatically

    // JIKA pendaftar adalah staff atau kilang, wajibkan muat naik dokumen
    if ($role === 'recycling staff' || $role === 'factory') {
        $status = 'pending'; // Account needs admin approval

        if (isset($_FILES['document']) && $_FILES['document']['error'] == 0) {
            $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
            $file_name = $_FILES['document']['name'];
            $file_tmp  = $_FILES['document']['tmp_name'];
            
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            // Sahkan format fail
            if (in_array($file_ext, $allowed_extensions)) {
                
                // Read binary file and encode as Base64 to bypass serverless read-only storage
                $file_binary = file_get_contents($file_tmp);
                $mime_type = $_FILES['document']['type'];
                if (empty($mime_type)) {
                    $mime_type = 'application/octet-stream';
                }

                // Store full Data URI so it can be viewed or downloaded easily
                $document_data = 'data:' . $mime_type . ';base64,' . base64_encode($file_binary);

            } else {
                echo "<script>alert('Format fail tidak dibenarkan! Sila muat naik PDF, Word, atau Gambar sahaja.'); window.history.back();</script>";
                exit();
            }
        } else {
            echo "<script>alert('Ralat! Pendaftaran akaun ini memerlukan muat naik dokumen pengesahan.'); window.history.back();</script>";
            exit();
        }
    }

    // Prepared statement inserting Base64 data into document column
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, document, status) VALUES (?, ?, ?, ?, ?, ?)");
    
    if ($stmt === false) {
        die("Preparation failed: " . $conn->error);
    }

    $stmt->bind_param("ssssss", $name, $email, $password, $role, $document_data, $status);

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

    <script>
    function toggleDocumentUpload() {
        var roleSelect = document.getElementById("role-select").value;
        var uploadSection = document.getElementById("document-upload-section");
        var docInput = document.getElementById("document-input");

        if (roleSelect === "recycling staff" || roleSelect === "factory") {
            uploadSection.style.display = "block";
            docInput.required = true;
        } else {
            uploadSection.style.display = "none";
            docInput.required = false;
            docInput.value = "";
        }
    }
    </script>

</body>
</html>