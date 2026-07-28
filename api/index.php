<?php
// Set working directory to project root
chdir(__DIR__ . '/..');

$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

if ($path === '/' || $path === '') {
    if (file_exists(__DIR__ . '/../login.php')) {
        require __DIR__ . '/../login.php';
    } else {
        require __DIR__ . '/../homepage.html';
    }
    exit;
}

$file = __DIR__ . '/..' . $path;

if (file_exists($file) && is_file($file)) {
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    if ($ext === 'php') {
        require $file;
    } else {
        return false;
    }
} else {
    http_response_code(404);
    echo "404 - Page Not Found";
}
?>