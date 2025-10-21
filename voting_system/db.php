<?php
// db.php
$host = "localhost";
$dbname = "voting_system";
$user = "root";
$pass = ""; // default for XAMPP

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Connected";
} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}
?>