<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (empty($_SESSION['id']) && empty($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized.']);
    exit;
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/dashboard_live_stats.php';

$stats = xander_dashboard_stats($conn);
echo json_encode([
    'ok' => true,
    'total' => $stats['total'],
    'submitted' => $stats['flags']['submitted'] ?? 0,
    'admit' => $stats['flags']['admit'] ?? 0,
    'visa_approved' => $stats['flags']['visa_approved'] ?? 0,
    'flags' => $stats['flags'],
], JSON_UNESCAPED_UNICODE);
