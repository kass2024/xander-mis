<?php
declare(strict_types=1);

/**
 * Latest signed contract per student email (avoids duplicate list rows from retests).
 */
function xander_admin_signed_contracts_sql(string $contractsTable, string $signaturesTable): string
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
    INNER JOIN (
        SELECT sig3.student_email, MAX(c3.id) AS max_contract_id
        FROM `{$contractsTable}` c3
        INNER JOIN `{$signaturesTable}` sig3 ON sig3.contract_id = c3.id
        WHERE c3.status = 'signed'
          AND sig3.student_email IS NOT NULL
          AND sig3.student_email != ''
        GROUP BY sig3.student_email
    ) latest ON latest.max_contract_id = c.id
    WHERE c.status = 'signed'
    ORDER BY c.signed_at DESC, c.id DESC
    ";
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
        $filePath = realpath(__DIR__ . '/../' . ltrim($pdfPath, '/\\'));
        $baseDir = realpath(__DIR__ . '/../uploads/contracts');
        if ($filePath && $baseDir && str_starts_with($filePath, $baseDir) && is_file($filePath)) {
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
