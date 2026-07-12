<?php
$servername = "localhost";
$username = "root"; 
$password = "";     
$dbname = "recycling_system"; // Nama database awak

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Contoh URL dari telefon: claim_points.php?id=1&user_id=USR99
if (isset($_GET['id']) && isset($_GET['user_id'])) {
    $transaksi_id = intval($_GET['id']);
    $user_id = $conn->real_escape_string($_GET['user_id']);

    // 1. Ambil data dari table TIMBANGAN dan dapatkan beza masa (dalam saat) dengan masa SEKARANG
    $sql = "SELECT berat_g, status, TIMESTAMPDIFF(SECOND, tarikh_masa, NOW()) AS beza_saat 
            FROM timbangan WHERE id = $transaksi_id";
    
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // 2. SEMAKAN 1: Adakah QR Code sudah melebihi 30 saat?
        if ($row['beza_saat'] > 30) {
            echo "<h1 style='color:red;'>Ralat: QR Code Telah Tamat Tempoh (Expired)!</h1>";
            echo "<p>Masa tamat selepas 30 saat. Sila lakukan timbangan semula untuk mendapatkan QR Code baru.</p>";
        } 
        // 3. SEMAKAN 2: Adakah mata bagi berat ini sudah pernah ditebus?
        elseif ($row['status'] == 'Ditebus') {
            echo "<h1 style='color:orange;'>Ralat: Mata bagi QR Code ini telah pun ditebus sebelum ini!</h1>";
        } 
        // 4. JIKA SEMUA OK: Luluskan mata!
        else {
            $berat = $row['berat_g'];
            $points_diberi = round($berat * 1.0); // Formula: 1g = 1 Point

            // Mulakan proses kemas kini (Transaction)
            $conn->begin_transaction();

            try {
                // Update mata user di jadual users (Pastikan table 'users' & column 'total_points' wujud di web awak)
                $sql_user = "UPDATE users SET total_points = total_points + $points_diberi WHERE user_id = '$user_id'";
                $conn->query($sql_user);

                // Tukar status di table timbangan kepada 'Ditebus' supaya tidak boleh di-scan lagi
                $sql_transaksi = "UPDATE timbangan SET status = 'Ditebus' WHERE id = $transaksi_id";
                $conn->query($sql_transaksi);

                $conn->commit();
                echo "<div style='text-align:center; font-family:sans-serif; margin-top:50px;'>";
                echo "<h1 style='color:green;'>Tahniah!</h1>";
                echo "<p>Anda berjaya mengitar semula botol seberat <b>" . $berat . "g</b>.</p>";
                echo "<h2 style='color:#2e7d32;'>+$points_diberi Mata</h2>";
                echo "<p>Mata telah berjaya dimasukkan ke akaun anda!</p>";
                echo "</div>";
                
            } catch (Exception $e) {
                $conn->rollback();
                echo "Ralat sistem semasa proses penebusan.";
            }
        }
    } else {
        echo "Transaksi tidak wujud dalam database.";
    }
} else {
    echo "Maklumat imbasan tidak lengkap. (ID atau User ID hilang)";
}
$conn->close();
?>