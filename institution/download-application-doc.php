<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/helpers/institution_dashboard.php';
require_once dirname(__DIR__) . '/helpers/institution_portal.php';

xander_institution_portal_ensure_schema($conn);

$universityId = (int) ($_SESSION['institution_university_id'] ?? 0);
$docId = (int) ($_GET['id'] ?? 0);

if ($universityId <= 0 || $docId <= 0) {
    http_response_code(403);
    exit('Forbidden');
}

$st = $conn->prepare('
    SELECT stored_path, original_name, mime_type
    FROM institution_scholarship_application_documents
    WHERE id = ? AND university_id = ?
    LIMIT 1
');
if (!$st) {
    http_response_code(500);
    exit('Error');
}
$st->bind_param('ii', $docId, $universityId);
$st->execute();
$row = $st->get_result()->fetch_assoc();
$st->close();

if (!$row) {
    http_response_code(404);
    exit('Not found');
}

$rel = trim((string) ($row['stored_path'] ?? ''));
if ($rel === '' || str_contains($rel, '..')) {
    http_response_code(404);
    exit('Invalid path');
}

$abs = dirname(__DIR__) . '/' . ltrim($rel, '/');
if (!is_file($abs)) {
    http_response_code(404);
    exit('File missing');
}

$name = basename((string) ($row['original_name'] ?? 'document'));
$mime = (string) ($row['mime_type'] ?? 'application/octet-stream');

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $name) . '"');
header('Content-Length: ' . (string) filesize($abs));
readfile($abs);
exit;
