<?php
declare(strict_types=1);

require_once __DIR__ . '/prescreening_notify.php';

/** @return array{first:string,last:string} */
function xander_prescreening_split_name(string $full): array
{
    $full = trim($full);
    if ($full === '') {
        return ['first' => '', 'last' => ''];
    }
    $parts = preg_split('/\s+/u', $full, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    if (count($parts) <= 1) {
        return ['first' => $full, 'last' => ''];
    }
    $first = (string) array_shift($parts);

    return ['first' => $first, 'last' => implode(' ', $parts)];
}

/**
 * @return array<string,mixed>|null
 */
function xander_prescreening_load_by_id(mysqli $conn, int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    $stmt = $conn->prepare('SELECT * FROM prescreening_submissions WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

/**
 * @return list<array{key:string,label:string,path:string,filename:string}>
 */
function xander_prescreening_collect_documents(array $row): array
{
    $labels = xander_prescreening_document_labels();
    $out = [];
    foreach ($labels as $key => $label) {
        $path = trim((string) ($row[$key] ?? ''));
        if ($path === '') {
            continue;
        }
        $out[] = [
            'key' => $key,
            'label' => $label,
            'path' => $path,
            'filename' => basename($path),
        ];
    }

    return $out;
}

function xander_prescreening_resolve_application_user_id(mysqli $conn, array $row): string
{
    $email = strtolower(trim((string) ($row['student_email'] ?? '')));
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $conn->prepare(
            'SELECT user_id FROM student_applications
             WHERE LOWER(TRIM(email)) = ?
             ORDER BY id DESC LIMIT 1'
        );
        if ($stmt) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!empty($existing['user_id'])) {
                return (string) $existing['user_id'];
            }
        }
    }

    $uid = trim((string) ($row['user_id'] ?? ''));
    if ($uid !== '' && preg_match('/^user-[0-9]+-[0-9]+$/', $uid)) {
        return $uid;
    }

    return 'user-' . time() . '-' . random_int(1000, 9999);
}

/**
 * @return array{token:string,user_id:string,prefill:array<string,string>,hints:array<string,string>,docs:array<int,array<string,string>>}
 */
function xander_prescreening_build_apply_handoff(mysqli $conn, array $row): array
{
    $name = xander_prescreening_split_name((string) ($row['student_name'] ?? ''));
    $phone = trim((string) ($row['whatsapp_number'] ?? ''));
    $userId = xander_prescreening_resolve_application_user_id($conn, $row);
    $token = bin2hex(random_bytes(16));
    $docs = [];

    foreach (xander_prescreening_collect_documents($row) as $doc) {
        $docs[] = [
            'key' => $doc['key'],
            'label' => $doc['label'],
            'filename' => $doc['filename'],
            'url' => 'prescreening_apply_doc.php?h=' . rawurlencode($token) . '&key=' . rawurlencode($doc['key']),
        ];
    }

    return [
        'token' => $token,
        'prescreen_id' => (int) ($row['id'] ?? 0),
        'user_id' => $userId,
        'prefill' => array_filter([
            'email' => trim((string) ($row['student_email'] ?? '')),
            'first_name' => $name['first'],
            'last_name' => $name['last'],
            'phone_number' => $phone,
        ], static fn ($v) => trim((string) $v) !== ''),
        'hints' => array_filter([
            'country_interest' => trim((string) ($row['country_interest'] ?? '')),
            'course_program' => trim((string) ($row['course_program'] ?? '')),
            'education_level' => trim((string) ($row['education_level'] ?? '')),
        ], static fn ($v) => trim((string) $v) !== ''),
        'docs' => $docs,
        'paths' => array_column(xander_prescreening_collect_documents($row), 'path', 'key'),
    ];
}

function xander_prescreening_absolute_doc_path(string $relativePath): ?string
{
    $relativePath = str_replace('\\', '/', trim($relativePath));
    if ($relativePath === '' || str_contains($relativePath, '..')) {
        return null;
    }
    $base = realpath(dirname(__DIR__) . '/uploads/prescreening');
    $file = realpath(dirname(__DIR__) . '/' . ltrim($relativePath, '/'));
    if (!$base || !$file || !is_file($file) || !str_starts_with($file, $base)) {
        return null;
    }

    return $file;
}

function xander_prescreening_delete_submission(mysqli $conn, int $id): bool
{
    $row = xander_prescreening_load_by_id($conn, $id);
    if (!$row) {
        return false;
    }

    foreach (xander_prescreening_collect_documents($row) as $doc) {
        $abs = xander_prescreening_absolute_doc_path($doc['path']);
        if ($abs) {
            @unlink($abs);
        }
    }

    $stmt = $conn->prepare('DELETE FROM prescreening_submissions WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();

    return $ok;
}
