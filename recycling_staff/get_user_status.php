<?php
// Use __DIR__ to calculate absolute path and prevent stream errors
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

try {
    if (isset($_GET['id']) && $_GET['id'] !== '') {
        $id = intval(trim($_GET['id']));

        if (!$conn) {
            throw new Exception('Database connection failed.');
        }

        $stmt = $conn->prepare("SELECT name FROM users WHERE user_id = ?");
        
        if ($stmt === false) {
            throw new Exception("Preparation failed: " . $conn->error);
        }

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            echo json_encode(['success' => true, 'name' => $user['name']]);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'No ID provided']);
    }
} catch (Exception $e) {
    // Returns a safe JSON error payload if anything fails
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>