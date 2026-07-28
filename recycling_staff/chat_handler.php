<?php
session_start();
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

// Check user login session
if (!isset($_SESSION['user_id']) && !isset($_SESSION['staff_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$sender_id = $_SESSION['user_id'] ?? $_SESSION['staff_id'];
$action = $_REQUEST['action'] ?? '';

// ACTION 1: FETCH MESSAGES
if ($action === 'fetch') {
    $receiver_id = intval($_GET['receiver_id'] ?? 0);

    $query = "SELECT sender_id, receiver_id, message, DATE_FORMAT(created_at, '%h:%i %p') AS formatted_time 
              FROM staff_messages 
              WHERE (sender_id = ? AND receiver_id = ?) 
                 OR (sender_id = ? AND receiver_id = ?) 
              ORDER BY created_at ASC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiii", $sender_id, $receiver_id, $receiver_id, $sender_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = [
            'sender_id' => $row['sender_id'],
            'message' => htmlspecialchars($row['message']),
            'formatted_time' => $row['formatted_time']
        ];
    }

    echo json_encode(['status' => 'success', 'messages' => $messages]);
    exit();
}

// ACTION 2: SEND MESSAGE
if ($action === 'send') {
    $receiver_id = intval($_POST['receiver_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    if ($receiver_id > 0 && !empty($message)) {
        $stmt = $conn->prepare("INSERT INTO staff_messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $sender_id, $receiver_id, $message);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
            exit();
        }
    }
    
    echo json_encode(['status' => 'error', 'message' => 'Failed to send message']);
    exit();
}