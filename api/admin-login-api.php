<?php
/**
 * =========================================================
 * MOBILE API - ADMIN LOGIN FOR FLUTTER APP
 * =========================================================
 * Returns JSON responses instead of redirects
 * Compatible with Flutter mobile app
 * =========================================================
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../db.php';
require_once '../database.php';

// Debug: Log the request method
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);

$response = ['success' => false, 'error' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Check if data is sent as form data (for Flutter http package)
    if (!$input) {
        parse_str(file_get_contents('php://input'), $input);
    }
    
    // Debug: Log the input data
    error_log("Input data: " . print_r($input, true));
    
    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($username) || empty($password)) {
        $response['error'] = 'Username and password are required';
        echo json_encode($response);
        exit;
    }

    // Prepare statement to get admin user
    $stmt = $conn->prepare(
        "SELECT id, username, password_hash, full_name, role, status, email, phone_number
         FROM admins
         WHERE username = ?"
    );

    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();
        $stmt->close();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            // Check account status
            $status = $admin['status'] ?? 'pending';
            
            if ($status !== 'active') {
                if ($status === 'pending') {
                    $response['error'] = 'Your account is pending approval. Please contact administrator.';
                } elseif ($status === 'deactive') {
                    $response['error'] = 'Your account has been deactivated. Access denied.';
                } else {
                    $response['error'] = 'Invalid username or password.';
                }
            } else {
                // Login successful - return admin data
                $response['success'] = true;
                $response['admin_id'] = (int)$admin['id'];
                $response['username'] = $admin['username'];
                $response['full_name'] = $admin['full_name'];
                $response['role'] = $admin['role'];
                $response['status'] = $admin['status'];
                $response['email'] = $admin['email'];
                $response['phone_number'] = $admin['phone_number'];
                $response['message'] = 'Login successful';
            }
        } else {
            $response['error'] = 'Invalid username or password.';
        }
    } else {
        $response['error'] = 'Database error. Please try again.';
    }
}

echo json_encode($response);
?>
