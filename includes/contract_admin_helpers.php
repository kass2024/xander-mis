<?php
declare(strict_types=1);

/**
 * Latest signed contract per student email (avoids duplicate list rows from retests).
 * @deprecated Use xander_admin_all_signed_contracts_sql for admin list (delete must target exact row).
 */
function xander_admin_signed_contracts_sql(string $contractsTable, string $signaturesTable): string
{
    return xander_admin_all_signed_contracts_sql($contractsTable, $signaturesTable);
}

/**
 * All signed contracts (one row per contract) for admin management / delete.
 */
function xander_admin_all_signed_contracts_sql(string $contractsTable, string $signaturesTable): string
{
    $contractsTable = preg_replace('/[^a-z_]/', '', $contractsTable);
    $signaturesTable = preg_replace('/[^a-z_]/', '', $signaturesTable);

    return "
    SELECT
        c.id AS contract_id,
        c.contract_token,
        c.status,
        c.signed_at,
        c.sent_at,
        sig.student_name,
        sig.student_email AS email
    FROM `{$contractsTable}` c
    INNER JOIN (
        SELECT s1.*
        FROM `{$signaturesTable}` s1
        INNER JOIN (
            SELECT contract_id, MAX(id) AS max_id
            FROM `{$signaturesTable}`
            GROUP BY contract_id
        ) s2 ON s1.contract_id = s2.contract_id AND s1.id = s2.max_id
    ) sig ON sig.contract_id = c.id
    WHERE c.status = 'signed'
    ORDER BY c.signed_at DESC, c.id DESC
    ";
}

/** Resolve contract PDF path safely (relative or absolute). */
function xander_admin_resolve_contract_pdf_path(string $pdfPath): ?string
{
    $pdfPath = trim($pdfPath);
    if ($pdfPath === '') {
        return null;
    }

    $candidates = [$pdfPath];
    if (!preg_match('/^[a-zA-Z]:\\\\|^\\//', $pdfPath)) {
        $candidates[] = dirname(__DIR__) . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $pdfPath), DIRECTORY_SEPARATOR);
    }

    $baseDir = realpath(dirname(__DIR__) . '/uploads/contracts');
    foreach ($candidates as $candidate) {
        $filePath = realpath($candidate);
        if (!$filePath || !is_file($filePath) || !$baseDir) {
            continue;
        }
        if (str_starts_with($filePath, $baseDir)) {
            return $filePath;
        }
    }

    return null;
}

/**
 * Delete contract, signature row(s), and PDF file. Returns true if contract row was removed.
 */
function xander_admin_delete_contract(
    mysqli $conn,
    string $contractsTable,
    string $signaturesTable,
    int $contractId
): bool {
    $contractsTable = preg_replace('/[^a-z_]/', '', $contractsTable);
    $signaturesTable = preg_replace('/[^a-z_]/', '', $signaturesTable);
    if ($contractId <= 0) {
        return false;
    }

    $stmt = $conn->prepare("SELECT pdf_path FROM `{$contractsTable}` WHERE id = ? LIMIT 1");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('i', $contractId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return false;
    }

    $pdfPath = trim((string) ($row['pdf_path'] ?? ''));
    if ($pdfPath !== '') {
        $filePath = xander_admin_resolve_contract_pdf_path($pdfPath);
        if ($filePath) {
            @unlink($filePath);
        }
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("DELETE FROM `{$signaturesTable}` WHERE contract_id = ?");
        if (!$stmt) {
            throw new RuntimeException('Delete signatures prepare failed');
        }
        $stmt->bind_param('i', $contractId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM `{$contractsTable}` WHERE id = ? LIMIT 1");
        if (!$stmt) {
            throw new RuntimeException('Delete contract prepare failed');
        }
        $stmt->bind_param('i', $contractId);
        $stmt->execute();
        $deleted = $stmt->affected_rows > 0;
        $stmt->close();

        if (!$deleted) {
            $conn->rollback();
            return false;
        }

        $conn->commit();
        return true;
    } catch (Throwable $e) {
        $conn->rollback();
        error_log('[xander_admin_delete_contract] ' . $e->getMessage());
        return false;
    }
}

function xander_ensure_signature_unique_per_contract(mysqli $conn, string $table): void
{
    $table = preg_replace('/[^a-z_]/', '', $table);
    $idx = @$conn->query("SHOW INDEX FROM `{$table}` WHERE Key_name = 'unique_contract_signature'");
    if ($idx && $idx->num_rows === 0) {
        @$conn->query("ALTER TABLE `{$table}` ADD UNIQUE KEY unique_contract_signature (contract_id)");
    }
    if ($idx) {
        $idx->free();
    }
}
