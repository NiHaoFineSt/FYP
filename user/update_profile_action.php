<?php
session_start();
include('../config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $name = $_POST['full_name'];
    $phone = $_POST['phone'];

    $sql = "UPDATE users SET name = ?, phone = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $name, $phone, $uid);

    if ($stmt->execute()) {
        header("Location: Profilepage.php?status=success");
    } else {
        echo "Error updating record: " . $conn->error;
    }
}
?>