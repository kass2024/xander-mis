<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers/prescreening_apply.php';

$token = trim((string) ($_GET['h'] ?? ''));
$key = trim((string) ($_GET['key'] ?? ''));
$handoff = xander_prescreening_load_handoff_by_token($token);

if (
    !is_array($handoff)
    || $key === ''
    || !isset($handoff['paths'][$key])
) {
    http_response_code(403);
    exit('Forbidden');
}

$abs = xander_prescreening_absolute_doc_path((string) $handoff['paths'][$key]);
if (!$abs) {
    http_response_code(404);
    exit('Not found');
}

$mime = 'application/octet-stream';
$ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
$map = [
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'webp' => 'image/webp',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];
if (isset($map[$ext])) {
    $mime = $map[$ext];
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($abs));
header('Content-Disposition: inline; filename="' . basename($abs) . '"');
readfile($abs);
exit;
