<?php
session_start();
require_once '../db.php';
header('Content-Type: application/json');

try {
    // Destroy session
    session_destroy();
    
    echo json_encode([
        "status" => "success",
        "message" => "Logged out successfully"
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Logout failed: " . $e->getMessage()
    ]);
}
?>
