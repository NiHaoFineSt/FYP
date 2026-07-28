<?php
// Retrieve database environment variables from Vercel
$host = getenv('DB_HOST') ?: $_ENV['DB_HOST'] ?? 'recyclehub-db-syafiqahfarhana718-e57b.b.aivencloud.com';
$user = getenv('DB_USER') ?: $_ENV['DB_USER'] ?? 'avnadmin';
$pass = getenv('DB_PASS') ?: $_ENV['DB_PASS'] ?? ''; 
$db   = getenv('DB_NAME') ?: $_ENV['DB_NAME'] ?? 'recycling_system';
$port = getenv('DB_PORT') ?: $_ENV['DB_PORT'] ?? 12827;

// Initialize mysqli
$conn = mysqli_init();

if (!$conn) {
    die("mysqli_init failed");
}

// Disable SSL certificate verification (required for Aiven)
$conn->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
$conn->ssl_set(NULL, NULL, NULL, NULL, NULL);

// Connect over TCP/IP with SSL enabled
if (!$conn->real_connect($host, $user, $pass, $db, (int)$port, NULL, MYSQLI_CLIENT_SSL)) {
    die("Database Connection Error: " . mysqli_connect_error());
}
?>