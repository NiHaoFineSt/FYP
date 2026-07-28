<?php
include('../config.php'); // Path to main folder
header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $id = intval(trim($_GET['id'])); // Cleans the QR data

    $stmt = $conn->prepare("SELECT name FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'name' => $user['name']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No ID provided']);
}
?>