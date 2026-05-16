<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$q = $_GET['q'] ?? '';

if (strlen($q) < 2) {
    echo json_encode([]);
    exit();
}

$searchTerm = "%{$q}%";
$stmt = $conn->prepare("SELECT id, user_id, first_name, last_name, email, area_code, phone_number FROM student_applications WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone_number LIKE ? LIMIT 10");
$stmt->bind_param('ssss', $searchTerm, $searchTerm, $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode($students);
?>