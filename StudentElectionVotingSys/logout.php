<?php
session_start();

session_destroy();

$redirect_path = 'index.php';

if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false || strpos($_SERVER['REQUEST_URI'], '/voter/') !== false) {
    $redirect_path = 'index.php';
}

header('Location: ' . $redirect_path);
exit();
?>
