<?php
/**
 * Poll WhatsApp invite delivery status for a recipient (admin session required).
 * GET ?phone=14503675329  or  ?to=14503675329
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();

if (empty($_SESSION['id']) && empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/helpers/prescreening_access.php';
if (!isset($conn) || !($conn instanceof mysqli) || !xander_prescreening_has_menu_access($conn, 'prescreening.php')) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

require_once dirname(__DIR__) . '/helpers/prescreening_whatsapp_schema.php';
require_once dirname(__DIR__) . '/helpers/prescreening_whatsapp_flow.php';
require_once dirname(__DIR__) . '/helpers/whatsapp_api.php';

xander_ensure_prescreening_whatsapp_tables($conn);

$raw = trim((string) ($_GET['phone'] ?? $_GET['to'] ?? ''));
$normalized = xander_prescreening_normalize_wa_phone($raw);
if ($normalized === null) {
    http_response_code(422);
    echo json_encode(['error' => 'Invalid phone number']);
    exit;
}

$session = xander_prescreening_load_session($conn, $normalized);
$status = strtolower(trim((string) ($session['last_delivery_status'] ?? '')));
$display = xander_prescreening_invite_status_message($session);

$delivered = in_array($status, ['sent', 'delivered', 'read'], true);
$failed = ($status === 'failed');
$pending = ($status === '' || $status === 'api_accepted' || $status === 'accepted');

echo json_encode([
    'phone' => $normalized,
    'message_id' => (string) ($session['last_wamid'] ?? ''),
    'delivery_status' => $status !== '' ? $status : 'unknown',
    'delivered' => $delivered,
    'failed' => $failed,
    'pending' => $pending,
    'error_code' => (int) ($session['last_delivery_error_code'] ?? 0),
    'error_message' => (string) ($session['last_delivery_error_message'] ?? ''),
    'display_message' => $display,
    'updated_at' => (string) ($session['last_delivery_at'] ?? $session['updated_at'] ?? ''),
    'api_diagnostic' => xander_whatsapp_api_diagnostic(),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
