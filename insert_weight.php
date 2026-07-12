<?php
error_reporting(0);
$conn = new mysqli("localhost", "root", "", "recycling_system");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["berat"])) {
    $berat = floatval($_POST["berat"]);
    $kod_rawak = strval(rand(1000, 9999));

    $sql = "INSERT INTO timbangan (berat_g, kod_pin) VALUES ($berat, '$kod_rawak')";
    if ($conn->query($sql) === TRUE) {
        // KITA HANTAR NOMBOR PIN SAHAJA, TIADA PERKATAAN 'SUCCESS'
        echo $kod_rawak; 
    } else {
        echo "E102"; // Ralat SQL
    }
} else {
    echo "E101"; // Ralat Tiada Post Data
}
$conn->close();
?>