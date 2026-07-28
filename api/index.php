<?php
// Capture requested path
$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

// Default home page routing (e.g. login.php or homepage.html)
if ($path === '/' || $path === '') {
    if (file_exists(__DIR__ . '/../login.php')) {
        require __DIR__ . '/../login.php';
    } else {
        require __DIR__ . '/../homepage.html';
    }
    exit;
}

// Locate requested file relative to root directory
$file = __DIR__ . '/..' . $path;

if (file_exists($file) && is_file($file)) {
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    if ($ext === 'php') {
        require $file;
    } else {
        // Serve static asset (CSS, JS, Images)
        return false;
    }
} else {
    http_response_code(404);
    echo "404 - Page Not Found";
}
?>