<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/institution_portal.php';
require_once __DIR__ . '/../helpers/urls.php';

$universityId = (int) ($_SESSION['institution_university_id'] ?? 0);
$docId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && pcvc_csrf_validate_post() && $docId > 0) {
    xander_institution_delete_document($conn, $docId, $universityId);
}

header('Location: ' . pcvc_url('/institution/index.php?tab=' . urlencode((string) ($_GET['tab'] ?? 'scholarship'))));
exit;
