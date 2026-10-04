<?php
declare(strict_types=1);

session_start();
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/includes/service_contract_lib.php';

$token = trim((string) ($_SESSION['service_contract_token'] ?? ''));
if (!xander_sc_token_is_valid($token) || !xander_sc_mark_upfront_paid($conn, $token)) {
    http_response_code(400);
    echo 'Payment was not confirmed for this contract. Finish Pay Here, then use Return to your contract.';
    exit;
}

header('Location: ' . xander_sc_public_url($token));
exit;
