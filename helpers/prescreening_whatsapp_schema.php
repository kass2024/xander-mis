<?php
declare(strict_types=1);

require_once __DIR__ . '/prescreening_schema.php';

/** Submissions + WhatsApp tables (webhooks, WA flow). */
function xander_ensure_prescreening_whatsapp_tables(mysqli $conn): void
{
    xander_ensure_prescreening_schema($conn);
}

/** WhatsApp-only tables (called from xander_ensure_prescreening_schema). */
function xander_ensure_prescreening_whatsapp_tables_only(mysqli $conn): void
{
    $sqlSession = "CREATE TABLE IF NOT EXISTS whatsapp_prescreening_sessions (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        wa_phone VARCHAR(20) NOT NULL,
        current_step VARCHAR(64) NOT NULL DEFAULT 'idle',
        answers_json MEDIUMTEXT NULL,
        doc_index INT UNSIGNED NOT NULL DEFAULT 0,
        last_wamid VARCHAR(128) NULL DEFAULT NULL,
        last_delivery_status VARCHAR(32) NULL DEFAULT NULL,
        last_delivery_error_code INT NULL DEFAULT NULL,
        last_delivery_error_message VARCHAR(512) NULL DEFAULT NULL,
        last_delivery_at DATETIME NULL DEFAULT NULL,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_wa_phone (wa_phone)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if (!@$conn->query($sqlSession)) {
        error_log('[prescreening_whatsapp_schema] sessions table: ' . $conn->error);
    }

    $sqlDedup = "CREATE TABLE IF NOT EXISTS whatsapp_inbound_dedup (
        message_id VARCHAR(128) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (message_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if (!@$conn->query($sqlDedup)) {
        error_log('[prescreening_whatsapp_schema] dedup table: ' . $conn->error);
    }

    xander_prescreening_ensure_delivery_columns($conn);
}

/** Track Meta wamid + delivery webhook status per invite session. */
function xander_prescreening_ensure_delivery_columns(mysqli $conn): void
{
    if (!xander_prescreening_table_exists($conn, 'whatsapp_prescreening_sessions')) {
        return;
    }

    $columns = [
        'last_wamid' => ['VARCHAR(128) NULL DEFAULT NULL', 'doc_index'],
        'last_delivery_status' => ['VARCHAR(32) NULL DEFAULT NULL', 'last_wamid'],
        'last_delivery_error_code' => ['INT NULL DEFAULT NULL', 'last_delivery_status'],
        'last_delivery_error_message' => ['VARCHAR(512) NULL DEFAULT NULL', 'last_delivery_error_code'],
        'last_delivery_at' => ['DATETIME NULL DEFAULT NULL', 'last_delivery_error_message'],
    ];
    foreach ($columns as $name => [$def, $after]) {
        xander_prescreening_add_column_if_missing($conn, 'whatsapp_prescreening_sessions', $name, $def, $after);
    }
}
