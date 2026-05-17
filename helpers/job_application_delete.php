<?php
declare(strict_types=1);

/**
 * Human-readable label for job_documents.document_type.
 */
function xander_job_document_display_label(string $type): string
{
    $type = trim($type);
    if ($type === '') {
        return 'Document';
    }

    return ucwords(str_replace(['_', '-'], ' ', $type));
}

function xander_job_application_absolute_doc_path(string $relativePath): ?string
{
    $relativePath = str_replace('\\', '/', trim($relativePath));
    if ($relativePath === '' || str_contains($relativePath, '..')) {
        return null;
    }
    $base = realpath(dirname(__DIR__) . '/uploads/job');
    $file = realpath(dirname(__DIR__) . '/' . ltrim($relativePath, '/'));
    if (!$base || !$file || !is_file($file) || !str_starts_with($file, $base)) {
        return null;
    }

    return $file;
}

/**
 * Delete a job application, its documents (DB + files), and clear portal job link.
 */
function xander_job_application_delete_by_id(mysqli $conn, int $id): bool
{
    if ($id <= 0) {
        return false;
    }

    $st = $conn->prepare('SELECT id, user_id, email FROM job_applications WHERE id = ? LIMIT 1');
    if (!$st) {
        return false;
    }
    $st->bind_param('i', $id);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();
    if (!$row) {
        return false;
    }

    $userId = trim((string) ($row['user_id'] ?? ''));
    $paths = [];
    if ($userId !== '') {
        $dq = $conn->prepare('SELECT file_path FROM job_documents WHERE user_id = ?');
        if ($dq) {
            $dq->bind_param('s', $userId);
            $dq->execute();
            $res = $dq->get_result();
            while ($doc = $res->fetch_assoc()) {
                $p = trim((string) ($doc['file_path'] ?? ''));
                if ($p !== '') {
                    $paths[] = $p;
                }
            }
            $dq->close();
        }
    }

    foreach ($paths as $rel) {
        $abs = xander_job_application_absolute_doc_path($rel);
        if ($abs) {
            @unlink($abs);
        }
    }

    if ($userId !== '') {
        $uploadDir = dirname(__DIR__) . '/uploads/job/' . $userId;
        if (is_dir($uploadDir)) {
            @rmdir($uploadDir);
        }
        $dd = $conn->prepare('DELETE FROM job_documents WHERE user_id = ?');
        if ($dd) {
            $dd->bind_param('s', $userId);
            $dd->execute();
            $dd->close();
        }
        $schemaFile = __DIR__ . '/student_portal_schema.php';
        if (is_readable($schemaFile)) {
            require_once $schemaFile;
            try {
                pcvc_student_portal_ensure_schema($conn);
            } catch (Throwable $e) {
                error_log('[job_application_delete] portal schema: ' . $e->getMessage());
            }
        }
        $pu = $conn->prepare('UPDATE student_portal_accounts SET job_user_id = NULL WHERE job_user_id = ?');
        if ($pu) {
            $pu->bind_param('s', $userId);
            $pu->execute();
            $pu->close();
        }
    }

    $del = $conn->prepare('DELETE FROM job_applications WHERE id = ? LIMIT 1');
    if (!$del) {
        return false;
    }
    $del->bind_param('i', $id);
    $del->execute();
    $ok = $del->affected_rows > 0;
    $del->close();

    return $ok;
}
