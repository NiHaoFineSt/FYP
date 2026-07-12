<?php include 'config.php'; ?>

<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<h2>Welcome!</h2>

<p>Your role: <?php echo $_SESSION['role']; ?></p>

<a href="logout.php">Logout</a>