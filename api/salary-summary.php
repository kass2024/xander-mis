<?php
session_start();
require_once '../db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Not logged in"
    ]);
    exit;
}

$id = intval($_SESSION['id']);
$month = date('Y-m');

$q = $conn->query("
    SELECT 
        COALESCE(SUM(
            ROUND(
                LEAST(COALESCE(att.total_work_minutes, 0), 480)
                * COALESCE(a.salary_per_minute, 0),
                0
            )
        ), 0) AS total_salary,
        COALESCE(SUM(att.total_work_minutes), 0) AS total_minutes
    FROM attendance att
    JOIN admins a ON a.id = att.admin_id
    WHERE att.admin_id=$id
    AND att.date LIKE '$month%'
");

$rows = $q->fetch_assoc();

echo json_encode([
    "status" => "success",
    "month" => $month,
    "total_salary" => intval($rows['total_salary'] ?? 0),
    "total_work_minutes" => intval($rows['total_minutes'] ?? 0)
]);
?>
