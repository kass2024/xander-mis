<?php
session_start();
require_once '../db.php';
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    echo json_encode([
        "status" => "error", 
        "message" => "Unauthorized"
    ]);
    exit;
}

try {
    // ===============================
    // TOTAL APPLICATIONS COUNT
    // (Only rows with valid email)
    // ===============================
    $totalApplications = 0;

    $totalQuery = "
        SELECT COUNT(*) AS total
        FROM student_applications
        WHERE email IS NOT NULL
        AND email != ''
    ";

    $totalRes = mysqli_query($conn, $totalQuery);

    if ($totalRes) {
        $row = mysqli_fetch_assoc($totalRes);
        $totalApplications = (int)$row['total'];
    }

    // ===============================
    // COUNT ALL STATUS FLAGS
    // ===============================
    $flagCounts = [];

    $flagQuery = "
        SELECT
            COUNT(CASE WHEN incomplete_app = 1 THEN 1 END) AS incomplete_app,
            COUNT(CASE WHEN submitted = 1 THEN 1 END) AS submitted,
            COUNT(CASE WHEN admit = 1 THEN 1 END) AS admit,
            COUNT(CASE WHEN i20_sent = 1 THEN 1 END) AS i20_sent,
            COUNT(CASE WHEN sevis_paid = 1 THEN 1 END) AS sevis_paid,
            COUNT(CASE WHEN visa_scheduled = 1 THEN 1 END) AS visa_scheduled,
            COUNT(CASE WHEN visa_approved = 1 THEN 1 END) AS visa_approved,
            COUNT(CASE WHEN enrolled = 1 THEN 1 END) AS enrolled,
            COUNT(CASE WHEN addn_doc = 1 THEN 1 END) AS addn_doc,
            COUNT(CASE WHEN deny = 1 THEN 1 END) AS deny,
            COUNT(CASE WHEN app_start = 1 THEN 1 END) AS app_start
        FROM student_applications
    ";

    $flagRes = mysqli_query($conn, $flagQuery);

    if ($flagRes) {
        $flagCounts = mysqli_fetch_assoc($flagRes);
    }

    // Calculate derived stats
    $newApplications = (int)($flagCounts['submitted'] ?? 0);
    $pendingReviews = (int)($flagCounts['incomplete_app'] ?? 0);

    echo json_encode([
        "status" => "success",
        "total_applications" => $totalApplications,
        "flag_counts" => array_map('intval', $flagCounts),
        "new_applications" => $newApplications,
        "pending_reviews" => $pendingReviews
    ]);

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Server error: " . $e->getMessage()
    ]);
}
?>
