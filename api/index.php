<?php
header('Content-Type: application/json');

echo json_encode([
    "status" => "success",
    "message" => "Xander Admin API is working",
    "version" => "1.0.0",
    "endpoints" => [
        "auth-login.php" => "POST - Admin login",
        "me.php" => "GET - Get current user",
        "logout.php" => "POST - Logout user",
        "dashboard-stats.php" => "GET - Dashboard statistics",
        "applications.php" => "GET/POST - Manage applications"
    ],
    "documentation" => "https://github.com/xander-admin-api"
]);
?>
