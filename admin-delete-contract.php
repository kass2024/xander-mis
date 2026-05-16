<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/contract_admin_helpers.php';

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    exit('Unauthorized');
}

if (
    empty($_POST['csrf_token']) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    http_response_code(403);
    exit('Invalid CSRF token');
}

if (!isset($_POST['contract_id']) || !ctype_digit((string) $_POST['contract_id'])) {
    http_response_code(400);
    exit('Invalid request');
}

$contractId = (int) $_POST['contract_id'];
$deleted = xander_admin_delete_contract($conn, 'student_contracts', 'student_signatures', $contractId);

header('Location: admin-contracts.php?' . ($deleted ? 'deleted=1' : 'error=delete_failed'));
exit;
