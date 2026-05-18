<?php
/**
 * Update job application work destination — Superadmin only (job applicants dashboard).
 */
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/role.php';
require_once __DIR__ . '/../helpers/job_application_destination.php';

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
    jsonResponse('Unauthorized', false, 401);
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
    jsonResponse('Only Superadmin can edit destination', false, 403);
}

$applicationId = (int) ($_POST['application_id'] ?? $_POST['id'] ?? 0);
$workCountryId = (int) ($_POST['work_country_id'] ?? 0);
$destinationsCsv = trim((string) ($_POST['destinations'] ?? ''));

if ($applicationId <= 0) {
    jsonResponse('Invalid application id', false, 400);
}

if ($workCountryId <= 0 && $destinationsCsv === '') {
    jsonResponse('Select a country or enter destination(s)', false, 400);
}

try {
    $result = xander_job_update_application_destination($conn, $applicationId, $workCountryId, $destinationsCsv);
    jsonResponse(array_merge(['message' => 'Destination updated'], $result), true);
} catch (InvalidArgumentException $e) {
    jsonResponse($e->getMessage(), false, 404);
} catch (Throwable $e) {
    error_log('[job-application-destination] ' . $e->getMessage());
    jsonResponse('Could not update destination', false, 500);
}
