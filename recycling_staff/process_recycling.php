<?php
include '../config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = intval($_POST['user_id']);
    $weight = floatval($_POST['weight']);
    $material = mysqli_real_escape_string($conn, $_POST['material_type']);
    
    // Logic: 10 points per 1kg
    $points = floor($weight * 10);

    // 1. Update the 'users' table so the Dashboard big numbers change
    $updateUsers = "UPDATE users SET total_kg = total_kg + ?, total_points = total_points + ? WHERE user_id = ?";
    $stmt1 = $conn->prepare($updateUsers);
    $stmt1->bind_param("dii", $weight, $points, $user_id);
    
    // 2. Insert into 'transactions' table so the History list updates
    $insertTrans = "INSERT INTO transactions (user_id, material_type, weight, points, status, date) 
                    VALUES (?, ?, ?, ?, 'Verified', NOW())";
    $stmt2 = $conn->prepare($insertTrans);
    $stmt2->bind_param("isdi", $user_id, $material, $weight, $points);

    if ($stmt1->execute() && $stmt2->execute()) {
        echo "Success! Added " . $points . " points to User #" . $user_id;
    } else {
        echo "Error updating database.";
    }
}
?>