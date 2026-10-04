<?php
session_start();
require_once '../db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit;
}

$id = intval($_SESSION['id']);

$q = $conn->query("
    SELECT
        att.date,
        att.check_in_time,
        att.check_out_time,
        att.total_work_minutes,
        ROUND(
            LEAST(COALESCE(att.total_work_minutes, 0), 480)
            * COALESCE(a.salary_per_minute, 0),
            0
        ) AS daily_salary_rwf
    FROM attendance att
    JOIN admins a ON a.id = att.admin_id
    WHERE att.admin_id=$id
    ORDER BY att.date DESC
    LIMIT 60
");

$history = [];
while ($row = $q->fetch_assoc()) {
    $history[] = $row;
}

echo json_encode([
    "status" => "success",
    "history" => $history
]);
?>
