<?php
/**
 * Delete job application — Superadmin only
 */
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../helpers/db.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/role.php';
require_once __DIR__ . '/../helpers/job_application_delete.php';

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse('Method not allowed', false, 405);
}

$adminId = 0;
if (!empty($_SESSION['id'])) {
    $adminId = (int) $_SESSION['id'];
} elseif (!empty($_SESSION['admin_id'])) {
    $adminId = (int) $_SESSION['admin_id'];
}

if ($adminId <= 0) {
    jsonResponse('Unauthorized — please log in again as admin', false, 401);
}

$stmtRole = $conn->prepare('SELECT role FROM admins WHERE id = ? LIMIT 1');
if (!$stmtRole) {
    jsonResponse('Server error', false, 500);
}
$stmtRole->bind_param('i', $adminId);
$stmtRole->execute();
$roleRow = $stmtRole->get_result()->fetch_assoc();
$stmtRole->close();

if (!$roleRow) {
    jsonResponse('Unauthorized', false, 401);
}

$dbRole = (string) ($roleRow['role'] ?? '');
$sessionRole = (string) ($_SESSION['role'] ?? '');
if (!xander_is_superadmin_role($dbRole) && !xander_is_superadmin_role($sessionRole)) {
    jsonResponse('Only Superadmin can delete applications', false, 403);
}

$id = isset($_POST['application_id']) ? (int) $_POST['application_id'] : 0;
if ($id <= 0) {
    jsonResponse('Invalid application id', false, 400);
}

try {
    $deleted = xander_job_application_delete_by_id($conn, $id);
} catch (Throwable $e) {
    error_log('delete-job-application: ' . $e->getMessage());
    jsonResponse('Could not delete: ' . $e->getMessage(), false, 500);
}

if (!$deleted) {
    jsonResponse('Application not found or could not be deleted', false, 404);
}

jsonResponse(['deleted' => true, 'application_id' => $id]);
