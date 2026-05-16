<?php
// Copy to db.php — never commit db.php
$host = 'localhost';
$user = 'your_db_user';
$pass = 'your_db_password';
$dbname = 'your_database';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '+00:00'");
