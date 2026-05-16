<?php
/**
 * =========================================================
 * MOBILE API - ADMIN DASHBOARD DATA FOR FLUTTER APP
 * =========================================================
 * Returns JSON responses with dashboard statistics
 * =========================================================
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../db.php';
require_once '../database.php';

$response = ['success' => false, 'error' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Get total applications
        $totalAppsQuery = "SELECT COUNT(*) as total FROM student_applications";
        $result = $conn->query($totalAppsQuery);
        $totalApplications = $result ? $result->fetch_assoc()['total'] : 0;

        // Get applications by status using flag columns
        $statusCounts = [
            'incomplete' => 0,
            'submitted' => 0,
            'admit' => 0,
            'i20_sent' => 0,
            'sevis_paid' => 0,
            'visa_scheduled' => 0,
            'visa_approved' => 0,
            'enrolled' => 0,
            'addn_doc' => 0,
            'deny' => 0
        ];
        
        // Count each flag
        $flagQuery = "SELECT 
            SUM(CASE WHEN incomplete_app = 1 THEN 1 ELSE 0 END) as incomplete,
            SUM(CASE WHEN submitted = 1 THEN 1 ELSE 0 END) as submitted,
            SUM(CASE WHEN admit = 1 THEN 1 ELSE 0 END) as admit,
            SUM(CASE WHEN i20_sent = 1 THEN 1 ELSE 0 END) as i20_sent,
            SUM(CASE WHEN sevis_paid = 1 THEN 1 ELSE 0 END) as sevis_paid,
            SUM(CASE WHEN visa_scheduled = 1 THEN 1 ELSE 0 END) as visa_scheduled,
            SUM(CASE WHEN visa_approved = 1 THEN 1 ELSE 0 END) as visa_approved,
            SUM(CASE WHEN enrolled = 1 THEN 1 ELSE 0 END) as enrolled,
            SUM(CASE WHEN addn_doc = 1 THEN 1 ELSE 0 END) as addn_doc,
            SUM(CASE WHEN deny = 1 THEN 1 ELSE 0 END) as deny
            FROM student_applications";
        $result = $conn->query($flagQuery);
        if ($result) {
            $row = $result->fetch_assoc();
            $statusCounts = [
                'incomplete' => (int)$row['incomplete'],
                'submitted' => (int)$row['submitted'],
                'admit' => (int)$row['admit'],
                'i20_sent' => (int)$row['i20_sent'],
                'sevis_paid' => (int)$row['sevis_paid'],
                'visa_scheduled' => (int)$row['visa_scheduled'],
                'visa_approved' => (int)$row['visa_approved'],
                'enrolled' => (int)$row['enrolled'],
                'addn_doc' => (int)$row['addn_doc'],
                'deny' => (int)$row['deny']
            ];
        }

        // Get recent applications (last 5)
        $recentQuery = "SELECT id, first_name, last_name, email, created_at 
                      FROM student_applications 
                      WHERE email IS NOT NULL AND email != ''
                      ORDER BY created_at DESC 
                      LIMIT 5";
        $result = $conn->query($recentQuery);
        $recentApplications = [];
        while ($row = $result ? $result->fetch_assoc() : null) {
            $recentApplications[] = [
                'id' => (int)$row['id'],
                'name' => trim($row['first_name'] . ' ' . $row['last_name']),
                'email' => $row['email'],
                'status' => 'submitted',
                'date' => $row['created_at']
            ];
        }

        // Get payment statistics (using a placeholder for now)
        $paymentStats = [
            'total_payments' => 0,
            'total_amount' => 0,
            'avg_amount' => 0
        ];

        // Get students count
        $studentsQuery = "SELECT COUNT(*) as total FROM student_applications WHERE email IS NOT NULL AND email != ''";
        $result = $conn->query($studentsQuery);
        $totalStudents = $result ? $result->fetch_assoc()['total'] : 0;

        $response['success'] = true;
        $response['total_applications'] = (int)$totalApplications;
        $response['total_students'] = (int)$totalStudents;
        $response['status_counts'] = $statusCounts;
        $response['recent_applications'] = $recentApplications;
        $response['payment_stats'] = [
            'total_payments' => (int)$paymentStats['total_payments'],
            'total_amount' => (float)$paymentStats['total_amount'],
            'avg_amount' => (float)$paymentStats['avg_amount']
        ];
        $response['message'] = 'Dashboard data loaded successfully';

    } catch (Exception $e) {
        $response['error'] = 'Database error: ' . $e->getMessage();
    }
}

echo json_encode($response);
?>
