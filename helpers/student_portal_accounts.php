<?php
declare(strict_types=1);

require_once __DIR__ . '/student_portal_schema.php';

/**
 * Default password requested for auto-created accounts.
 */
const PCVC_STUDENT_DEFAULT_PASSWORD = 'Xander@2026';

function pcvc_student_email_norm(string $email): string
{
    return strtolower(trim($email));
}

/**
 * Ensure a portal account exists for an email (may or may not have a student_applications record yet).
 * - If student_applications exists for the email, link to latest application id.
 * - Otherwise, student_application_id remains NULL.
 */
function pcvc_student_portal_ensure_account_for_email(mysqli $conn, string $email, string $defaultPassword = PCVC_STUDENT_DEFAULT_PASSWORD): void
{
    pcvc_student_portal_ensure_schema($conn);

    $email = pcvc_student_email_norm($email);
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return;
    }

    $applicationId = null;
    $jobUserId = null;
    $st = $conn->prepare("
        SELECT id
        FROM student_applications
        WHERE LOWER(TRIM(email)) = ?
        ORDER BY id DESC
        LIMIT 1
    ");
    if ($st) {
        $st->bind_param('s', $email);
        $st->execute();
        $r = $st->get_result()->fetch_assoc();
        $st->close();
        if ($r) {
            $applicationId = (int) $r['id'];
        }
    }

    $stJob = $conn->prepare("
        SELECT user_id
        FROM job_applications
        WHERE LOWER(TRIM(email)) = ?
        ORDER BY id DESC
        LIMIT 1
    ");
    if ($stJob) {
        $stJob->bind_param('s', $email);
        $stJob->execute();
        $jr = $stJob->get_result()->fetch_assoc();
        $stJob->close();
        if ($jr && trim((string) ($jr['user_id'] ?? '')) !== '') {
            $jobUserId = trim((string) $jr['user_id']);
        }
    }

    $stmt2 = $conn->prepare("SELECT id FROM student_portal_accounts WHERE email = ? LIMIT 1");
    $existingId = 0;
    if ($stmt2) {
        $stmt2->bind_param('s', $email);
        $stmt2->execute();
        $acc = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();
        if ($acc) $existingId = (int)$acc['id'];
    }

    $hash = password_hash($defaultPassword, PASSWORD_DEFAULT);
    if ($existingId > 0) {
        $stU = $conn->prepare(
            "UPDATE student_portal_accounts
             SET student_application_id = ?, job_user_id = ?, password_hash = ?, status = 'active'
             WHERE id = ?"
        );
        if ($stU) {
            $appVal = $applicationId;
            $jobVal = $jobUserId;
            $aid = $existingId;
            $stU->bind_param('issi', $appVal, $jobVal, $hash, $aid);
            $stU->execute();
            $stU->close();
        }

        return;
    }

    $stmtI = $conn->prepare(
        'INSERT INTO student_portal_accounts (student_application_id, job_user_id, email, password_hash)
         VALUES (?, ?, ?, ?)'
    );
    if ($stmtI) {
        $appVal = $applicationId;
        $jobVal = $jobUserId;
        $stmtI->bind_param('isss', $appVal, $jobVal, $email, $hash);
        $stmtI->execute();
        $stmtI->close();
    }
}

/**
 * Link portal account to a job application immediately after submit.
 */
function pcvc_student_portal_ensure_account_for_job(
    mysqli $conn,
    string $jobUserId,
    string $email,
    string $defaultPassword = PCVC_STUDENT_DEFAULT_PASSWORD
): void {
    pcvc_student_portal_ensure_schema($conn);

    $jobUserId = trim($jobUserId);
    $email = pcvc_student_email_norm($email);
    if ($jobUserId === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return;
    }

    $applicationId = null;
    $st = $conn->prepare('SELECT id FROM student_applications WHERE LOWER(TRIM(email)) = ? ORDER BY id DESC LIMIT 1');
    if ($st) {
        $st->bind_param('s', $email);
        $st->execute();
        $r = $st->get_result()->fetch_assoc();
        $st->close();
        if ($r) {
            $applicationId = (int) $r['id'];
        }
    }

    $hash = password_hash($defaultPassword, PASSWORD_DEFAULT);

    $stmt2 = $conn->prepare('SELECT id FROM student_portal_accounts WHERE email = ? LIMIT 1');
    $existingId = 0;
    if ($stmt2) {
        $stmt2->bind_param('s', $email);
        $stmt2->execute();
        $acc = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();
        if ($acc) {
            $existingId = (int) $acc['id'];
        }
    }

    if ($existingId > 0) {
        $stU = $conn->prepare(
            'UPDATE student_portal_accounts
             SET student_application_id = ?, job_user_id = ?, password_hash = ?, status = \'active\'
             WHERE id = ?'
        );
        if ($stU) {
            $appVal = $applicationId;
            $stU->bind_param('issi', $appVal, $jobUserId, $hash, $existingId);
            $stU->execute();
            $stU->close();
        }

        return;
    }

    $stmtI = $conn->prepare(
        'INSERT INTO student_portal_accounts (student_application_id, job_user_id, email, password_hash)
         VALUES (?, ?, ?, ?)'
    );
    if ($stmtI) {
        $appVal = $applicationId;
        $stmtI->bind_param('isss', $appVal, $jobUserId, $email, $hash);
        $stmtI->execute();
        $stmtI->close();
    }
}

