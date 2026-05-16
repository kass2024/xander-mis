<?php
declare(strict_types=1);

/**
 * Ensure `student_applications` has all columns used by the app/portal.
 * Safe to run multiple times (idempotent).
 */

function pcvc_student_applications_ensure_schema(mysqli $conn): void
{
    // Fetch existing columns
    $existing = [];
    $sql = "SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'student_applications'";

    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $existing[strtolower((string)$row['COLUMN_NAME'])] = true;
        }
        $res->free();
    }

    // If table doesn't exist, do nothing here (main app creates it elsewhere).
    if (empty($existing)) {
        return;
    }

    // Columns required for parity (mostly nullable to avoid breaking existing data)
    $cols = [
        // Personal
        'middle_name' => "VARCHAR(100) NULL",

        // Emergency contact (portal edit)
        'emergency_first_name' => "VARCHAR(100) NULL",
        'emergency_last_name' => "VARCHAR(100) NULL",
        'emergency_email' => "VARCHAR(190) NULL",
        'emergency_area_code' => "VARCHAR(16) NULL",
        'emergency_phone_number' => "VARCHAR(32) NULL",
        'emergency_relationship' => "VARCHAR(100) NULL",
        'emergency_same_address' => "VARCHAR(8) NULL",

        // Docs used by student portal/materials
        'valid_passport' => "LONGTEXT NULL",
        'degree_transcripts' => "LONGTEXT NULL",
        'high_school_degree' => "LONGTEXT NULL",
        'cv_resume' => "LONGTEXT NULL",
        'recommendation_letters' => "LONGTEXT NULL",
        'personal_statement' => "LONGTEXT NULL",
        'english_certificate' => "LONGTEXT NULL",
        'birth_certificate' => "LONGTEXT NULL",
        'payment_proof' => "LONGTEXT NULL",

        // Workflow flags shown in portal (if missing)
        'incomplete_app' => "TINYINT(1) NOT NULL DEFAULT 0",
        'submitted' => "TINYINT(1) NOT NULL DEFAULT 0",
        'app_paid' => "TINYINT(1) NOT NULL DEFAULT 0",
        'admit' => "TINYINT(1) NOT NULL DEFAULT 0",
        'i20_sent' => "TINYINT(1) NOT NULL DEFAULT 0",
        'sevis_paid' => "TINYINT(1) NOT NULL DEFAULT 0",
        'visa_scheduled' => "TINYINT(1) NOT NULL DEFAULT 0",
        'visa_approved' => "TINYINT(1) NOT NULL DEFAULT 0",
        'enrolled' => "TINYINT(1) NOT NULL DEFAULT 0",
        'addn_doc' => "TINYINT(1) NOT NULL DEFAULT 0",
        'deny' => "TINYINT(1) NOT NULL DEFAULT 0",
        'app_start' => "TINYINT(1) NOT NULL DEFAULT 0",
    ];

    foreach ($cols as $name => $ddl) {
        if (isset($existing[strtolower($name)])) continue;
        $conn->query("ALTER TABLE student_applications ADD COLUMN `$name` $ddl");
    }
}

