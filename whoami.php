<?php
include 'config.php';
$res = $conn->query("SELECT VERSION() AS ver, DATABASE() AS db");
$row = $res->fetch_assoc();
echo "<b>Version:</b> " . $row['ver'] . "<br>";
echo "<b>Database:</b> " . $row['db'] . "<br>";
echo "<b>Port:</b> " . $conn->host_info;
?>