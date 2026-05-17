<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../helpers/db.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/institution_portal.php';

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    jsonResponse('Method not allowed', false, 405);
}

$q = trim((string) ($_GET['q'] ?? ''));
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    $row = xander_institution_load_university_by_id($conn, $id);
    if (!$row) {
        jsonResponse('Institution not found', false, 404);
    }
    $hasPortal = false;
    $st = $conn->prepare('SELECT 1 FROM institution_portal_accounts WHERE university_id = ? LIMIT 1');
    if ($st) {
        $st->bind_param('i', $id);
        $st->execute();
        $hasPortal = (bool) $st->get_result()->fetch_assoc();
        $st->close();
    }
    jsonResponse([
        'id' => (int) $row['id'],
        'name' => (string) $row['name'],
        'region_id' => (int) ($row['region_id'] ?? 0),
        'country_id' => (int) ($row['country_id'] ?? 0),
        'region_name' => (string) ($row['region_name'] ?? ''),
        'country_name' => (string) ($row['country_name'] ?? ''),
        'website' => (string) ($row['website'] ?? ''),
        'city' => (string) ($row['city'] ?? ''),
        'institution_phone' => (string) ($row['institution_phone'] ?? ''),
        'institution_kind' => (string) ($row['institution_kind'] ?? ''),
        'has_portal' => $hasPortal,
    ]);
}

$results = xander_institution_search_universities($conn, $q, 15);
jsonResponse(['items' => $results, 'query' => $q]);
