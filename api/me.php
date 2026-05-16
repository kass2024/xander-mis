<?php
session_start();
require_once '../db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit;
}

$id = intval($_SESSION['id']);

$stmt = $conn->prepare("
    SELECT id, username, full_name, role, password_hash, office_id,
           email, phone_number, salary_per_minute, allowed_break_minutes,
           work_days_per_week, profile_photo
    FROM admins
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "User not found"]);
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

// Remove sensitive data
unset($user['password_hash']);

// Fetch office data if available
$office = null;
if (!empty($user['office_id']) && $user['office_id'] > 0) {
    $officeStmt = $conn->prepare("
        SELECT id, office_name, country, latitude, longitude, radius_meters
        FROM offices
        WHERE id = ?
        LIMIT 1
    ");
    $officeStmt->bind_param("i", $user['office_id']);
    $officeStmt->execute();
    $office = $officeStmt->get_result()->fetch_assoc();
    $officeStmt->close();
}

// Convert numeric fields to proper types
$user['id'] = (int)$user['id'];
$user['salary_per_minute'] = (float)$user['salary_per_minute'];
$user['allowed_break_minutes'] = (int)$user['allowed_break_minutes'];
$user['work_days_per_week'] = (int)$user['work_days_per_week'];
$user['office_id'] = (int)$user['office_id'];

echo json_encode([
    "status" => "success",
    "user" => $user,
    "office" => $office
]);
?>
