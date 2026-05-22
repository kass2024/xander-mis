-- Pre-screening schema for rwanda_xander (or your DB name)
-- Safe to re-run: use IF NOT EXISTS / check columns before ALTER on production.
-- PHP auto-migrate: helpers/prescreening_schema.php
-- CLI: php scripts/ensure-prescreening-schema.php

-- ---------------------------------------------------------------------------
-- 1) Main submissions table (new installs)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS prescreening_submissions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id VARCHAR(64) NOT NULL,
    source VARCHAR(16) NOT NULL DEFAULT 'admin',
    student_name VARCHAR(255) NOT NULL DEFAULT '',
    student_email VARCHAR(255) NOT NULL DEFAULT '',
    whatsapp_number VARCHAR(32) NOT NULL DEFAULT '',
    invite_token VARCHAR(64) NULL DEFAULT NULL,
    invite_channel VARCHAR(16) NOT NULL DEFAULT '',
    education_level VARCHAR(255) NOT NULL DEFAULT '',
    course_program VARCHAR(500) NOT NULL DEFAULT '',
    country_interest VARCHAR(255) NOT NULL DEFAULT '',
    open_other_countries TEXT NULL,
    budget_tuition VARCHAR(255) NOT NULL DEFAULT '',
    funds_application_visa VARCHAR(16) NOT NULL DEFAULT '',
    sponsor VARCHAR(64) NOT NULL DEFAULT '',
    afford_deposit VARCHAR(16) NOT NULL DEFAULT '',
    has_valid_passport VARCHAR(16) NOT NULL DEFAULT '',
    academic_docs_ready VARCHAR(64) NOT NULL DEFAULT '',
    english_level VARCHAR(64) NOT NULL DEFAULT '',
    english_test_taken VARCHAR(255) NOT NULL DEFAULT '',
    visa_denied VARCHAR(16) NOT NULL DEFAULT '',
    planned_intake VARCHAR(255) NOT NULL DEFAULT '',
    study_attendance_mode VARCHAR(32) NOT NULL DEFAULT '',
    ready_to_apply VARCHAR(16) NOT NULL DEFAULT '',
    doc_valid_passport VARCHAR(512) NOT NULL DEFAULT '',
    doc_degree_transcripts VARCHAR(512) NOT NULL DEFAULT '',
    doc_high_school VARCHAR(512) NOT NULL DEFAULT '',
    doc_cv_resume VARCHAR(512) NOT NULL DEFAULT '',
    doc_recommendation VARCHAR(512) NOT NULL DEFAULT '',
    doc_personal_statement VARCHAR(512) NOT NULL DEFAULT '',
    doc_english_certificate VARCHAR(512) NOT NULL DEFAULT '',
    doc_birth_certificate VARCHAR(512) NOT NULL DEFAULT '',
    doc_payment_proof VARCHAR(512) NOT NULL DEFAULT '',
    submitted_by_admin_id INT UNSIGNED NULL DEFAULT NULL,
    email_sent TINYINT(1) NOT NULL DEFAULT 0,
    whatsapp_sent TINYINT(1) NOT NULL DEFAULT 0,
    notify_errors TEXT NULL,
    submitted_at DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_prescreen_user (user_id),
    UNIQUE KEY uq_prescreen_invite_token (invite_token),
    KEY idx_prescreen_submitted (submitted_at),
    KEY idx_prescreen_email (student_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 2) ALTER TABLE — run only if upgrading an older prescreening_submissions
--    (skip any line that errors with "Duplicate column name")
-- ---------------------------------------------------------------------------
ALTER TABLE prescreening_submissions ADD COLUMN source VARCHAR(16) NOT NULL DEFAULT 'admin' AFTER user_id;
ALTER TABLE prescreening_submissions ADD COLUMN invite_token VARCHAR(64) NULL DEFAULT NULL AFTER whatsapp_number;
ALTER TABLE prescreening_submissions ADD COLUMN invite_channel VARCHAR(16) NOT NULL DEFAULT '' AFTER invite_token;

CREATE UNIQUE INDEX uq_prescreen_invite_token ON prescreening_submissions (invite_token);
CREATE INDEX idx_prescreen_submitted ON prescreening_submissions (submitted_at);
CREATE INDEX idx_prescreen_email ON prescreening_submissions (student_email);

-- ---------------------------------------------------------------------------
-- 3) WhatsApp flow tables
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS whatsapp_prescreening_sessions (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE whatsapp_prescreening_sessions ADD COLUMN last_wamid VARCHAR(128) NULL DEFAULT NULL AFTER doc_index;
ALTER TABLE whatsapp_prescreening_sessions ADD COLUMN last_delivery_status VARCHAR(32) NULL DEFAULT NULL AFTER last_wamid;
ALTER TABLE whatsapp_prescreening_sessions ADD COLUMN last_delivery_error_code INT NULL DEFAULT NULL AFTER last_delivery_status;
ALTER TABLE whatsapp_prescreening_sessions ADD COLUMN last_delivery_error_message VARCHAR(512) NULL DEFAULT NULL AFTER last_delivery_error_code;
ALTER TABLE whatsapp_prescreening_sessions ADD COLUMN last_delivery_at DATETIME NULL DEFAULT NULL AFTER last_delivery_error_message;

CREATE TABLE IF NOT EXISTS whatsapp_inbound_dedup (
    message_id VARCHAR(128) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (message_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
