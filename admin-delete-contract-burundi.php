<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/contract_admin_helpers.php';

$isAjax = (
    (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_POST['ajax']) && $_POST['ajax'] === '1')
);

function delete_contract_burundi_respond(bool $ok, string $message, int $code = 200): void
{
    global $isAjax;

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($ok ? 200 : ($code >= 400 ? $code : 400));
        echo json_encode(['success' => $ok, 'message' => $message]);
        exit;
    }

    header('Location: admin-contracts-burundi.php?' . ($ok ? 'deleted=1' : 'error=' . urlencode($message)));
    exit;
}

if (!isset($_SESSION['admin_id'])) {
    delete_contract_burundi_respond(false, 'Unauthorized', 403);
}

if (
    empty($_POST['csrf_token']) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token'])
) {
    delete_contract_burundi_respond(false, 'Invalid CSRF token — refresh the page and try again.', 403);
}

if (!isset($_POST['contract_id']) || !ctype_digit((string) $_POST['contract_id'])) {
    delete_contract_burundi_respond(false, 'Invalid contract ID', 400);
}

$contractId = (int) $_POST['contract_id'];
$deleted = xander_admin_delete_contract($conn, 'student_contracts_burundi', 'student_signatures_burundi', $contractId);

if (!$deleted) {
    delete_contract_burundi_respond(false, 'Could not delete contract.', 500);
}

delete_contract_burundi_respond(true, 'Contract deleted successfully.');
