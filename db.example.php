<?php
// Copy to db.php — never commit db.php
//
// cPanel production (xanderglobalscholars.com) — set in .env (not committed):
//   DB_HOST=localhost
//   DB_USER=xandhqav_user
//   DB_PASS=your_password
//   DB_NAME=xandhqav_db
//   CYPRUS_DB_NAME=xandhqav_db
// Local XAMPP:
//   $db_host = 'localhost';
//   $db_user = 'root';
//   $db_pass = '';
//   $db_name = 'rwanda_xander';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '+00:00'");
