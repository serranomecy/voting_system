<?php
session_start();

// Destroy session and redirect to login
session_destroy();

// Determine the correct path based on where logout.php is accessed from
$redirect_path = 'index.php';

// If accessed from admin or voter subdirectories, go up one level
if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false || strpos($_SERVER['REQUEST_URI'], '/voter/') !== false) {
    $redirect_path = 'index.php';
}

header('Location: ' . $redirect_path);
exit();
?>
