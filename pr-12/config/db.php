<?php
// ========================================================
// Practical 8 & Practical 9: Central Database Configuration
// Database Name: studenthub
// The Backbenchers University - Student Portal
// ========================================================

$host = "localhost";
$dbname = "studenthub";
$username = "root";
$password = "";

// 1. MySQLi Connection (Used for Practical 9 Prepared Statements)
$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    $db_mysqli_error = $conn->connect_error;
} else {
    $conn->set_charset("utf8mb4");
}

// 2. PDO Connection (Used for Practical 8 Database Operations)
$pdo = null;
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $db_connection_error = $e->getMessage();
}
?>
