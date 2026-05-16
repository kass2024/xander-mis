<?php
declare(strict_types=1);

/**
 * Ensures Burundi contract tables exist (mirrors special-contract schema).
 */
function xander_ensure_burundi_contract_tables(mysqli $conn): void
{
    $conn->query("
        CREATE TABLE IF NOT EXISTS student_contracts_burundi (
            id INT(11) NOT NULL AUTO_INCREMENT,
            student_id INT(11) DEFAULT NULL,
            contract_token CHAR(64) NOT NULL,
            status ENUM('draft','signed') NOT NULL DEFAULT 'draft',
            selected_package_code VARCHAR(20) DEFAULT NULL,
            selected_package_label VARCHAR(255) DEFAULT NULL,
            signed_at DATETIME DEFAULT NULL,
            sent_at DATETIME DEFAULT NULL,
            pdf_path VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY contract_token (contract_token),
            KEY student_id (student_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS student_signatures_burundi (
            id INT(11) NOT NULL AUTO_INCREMENT,
            contract_id INT(11) NOT NULL,
            student_name VARCHAR(255) NOT NULL,
            student_email VARCHAR(255) NOT NULL,
            signed_date DATE NOT NULL,
            signature_image LONGTEXT NOT NULL,
            client_residence VARCHAR(255) DEFAULT NULL,
            client_address TEXT DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_contract_signature (contract_id),
            KEY contract_id (contract_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    xander_ensure_burundi_signature_unique_contract($conn);
}

/** One signature row per contract (prevents duplicate admin list rows). */
function xander_ensure_burundi_signature_unique_contract(mysqli $conn): void
{
    $idx = @$conn->query("SHOW INDEX FROM student_signatures_burundi WHERE Key_name = 'unique_contract_signature'");
    if ($idx && $idx->num_rows === 0) {
        @$conn->query('ALTER TABLE student_signatures_burundi ADD UNIQUE KEY unique_contract_signature (contract_id)');
    }
    if ($idx) {
        $idx->free();
    }
}
