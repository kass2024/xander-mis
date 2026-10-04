<?php
// daily-salary-api.php
require_once "../db.php";
header("Content-Type: application/json");

// ------------------------------
// 1. Validate Required Input
// ------------------------------
$admin_id = intval($_POST['admin_id'] ?? 0);
$date     = trim($_POST['date'] ?? '');

if ($admin_id <= 0 || $date === '') {
    echo json_encode([
        "status" => "error",
        "message" => "Missing or invalid admin_id or date"
    ]);
    exit;
}

// ------------------------------
// 2. Fetch Attendance Row
// ------------------------------
$stmt = $conn->prepare("
    SELECT 
        a.salary_per_minute,
        att.check_in_time,
        att.check_out_time,
        att.total_work_minutes
    FROM attendance att
    JOIN admins a ON a.id = att.admin_id
    WHERE att.admin_id = ? AND att.date = ?
    LIMIT 1
");
$stmt->bind_param("is", $admin_id, $date);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "No attendance found for selected date"
    ]);
    exit;
}

$row = $result->fetch_assoc();

// ------------------------------
// 3. Calculate salary from the current configured rate
// ------------------------------
$worked_minutes = intval($row['total_work_minutes'] ?? 0);
$payable_minutes = min($worked_minutes, 480);
$salary = (int)round(
    $payable_minutes * (float)($row['salary_per_minute'] ?? 0)
);

// ------------------------------
// 4. Successful Output
// ------------------------------
echo json_encode([
    "status"          => "success",
    "message"         => "Daily salary report loaded",
    "check_in_time"   => $row['check_in_time'],
    "check_out_time"  => $row['check_out_time'],
    "worked_minutes"  => $worked_minutes,
    "paid_minutes"    => $payable_minutes,
    "salary"          => $salary
]);

?>
