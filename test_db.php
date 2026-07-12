<?php
include 'config.php';

if ($conn->connect_error) {
    echo "❌ Connection Failed: " . $conn->connect_error;
} else {
    echo "✅ Connection Successful!";
    
    // Let's try a manual insert to see if it works
    $test_sql = "INSERT INTO users (name, email, password, role) VALUES ('Test', 'test@test.com', '123', 'admin')";
    
    if ($conn->query($test_sql)) {
        echo "<br>🚀 Manual Insert worked! Check Workbench now.";
    } else {
        echo "<br>❌ Insert failed: " . $conn->error;
    }
}
?>