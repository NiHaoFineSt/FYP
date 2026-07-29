<?php
session_start();
require_once __DIR__ . '/../config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'recycling staff') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id    = $_SESSION['user_id'];
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');

    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Combine First & Last Name into 'name' column
    $full_name = trim("$first_name $last_name");

    // Fetch current user details for validation
    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    // Check if new password is provided
    if (!empty($new_password)) {
        // Validate password confirmation
        if ($new_password !== $confirm_password) {
            echo "<script>alert('New passwords do not match.'); window.history.back();</script>";
            exit();
        }

        // Verify current password (supports hashed or plain text)
        $password_matches = password_verify($current_password, $user['password']) || ($current_password === $user['password']);
        
        if (!$password_matches) {
            echo "<script>alert('Current password is incorrect.'); window.history.back();</script>";
            exit();
        }

        // Hash new password and update with password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, password = ? WHERE user_id = ?");
        $update_stmt->bind_param("ssssi", $full_name, $email, $phone, $hashed_password, $user_id);
    } else {
        // Update profile info without changing password
        $update_stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE user_id = ?");
        $update_stmt->bind_param("sssi", $full_name, $email, $phone, $user_id);
    }

    if ($update_stmt->execute()) {
        echo "<script>alert('Profile updated successfully!'); window.location.href='staff_profile.php';</script>";
    } else {
        echo "<script>alert('Failed to update profile.'); window.history.back();</script>";
    }
}