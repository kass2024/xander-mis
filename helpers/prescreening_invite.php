<?php
declare(strict_types=1);

require_once __DIR__ . '/prescreening_schema.php';
require_once __DIR__ . '/mail_smtp.php';
require_once __DIR__ . '/phone_whatsapp_normalize.php';

function xander_prescreening_ensure_invite_columns(mysqli $conn): void
{
    xander_prescreening_ensure_submissions_columns($conn);
    xander_ensure_prescreening_invites_table($conn);
}

function xander_prescreening_base_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    if ($base === '/' || $base === '\\') {
        $base = '';
    }

    return $scheme . '://' . $host . $base;
}

function xander_prescreening_invite_url(string $token): string
{
    return rtrim(xander_prescreening_base_url(), '/') . '/prescreening-student.php?t=' . rawurlencode($token);
}

/**
 * @return array{user_id:string,token:string,url:string,id:int}
 */
function xander_prescreening_create_invite(
    mysqli $conn,
    string $studentName,
    string $studentEmail,
    string $whatsapp,
    string $channel
): array {
    xander_prescreening_ensure_invite_columns($conn);

    $userId = 'user-' . time() . '-' . random_int(1000, 9999);
    $token = bin2hex(random_bytes(24));
    $channel = in_array($channel, ['email', 'whatsapp', 'both'], true) ? $channel : 'whatsapp';

    $stmt = $conn->prepare(
        'INSERT INTO prescreening_invites (
            user_id, source, student_name, student_email, whatsapp_number,
            invite_token, invite_channel, created_at
        ) VALUES (?, \'invite\', ?, ?, ?, ?, ?, NOW())'
    );
    if (!$stmt) {
        throw new RuntimeException('Could not create invite: ' . $conn->error);
    }
    $stmt->bind_param('ssssss', $userId, $studentName, $studentEmail, $whatsapp, $token, $channel);
    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        throw new RuntimeException('Could not save invite: ' . $err);
    }
    $id = (int) $stmt->insert_id;
    $stmt->close();

    return [
        'id' => $id,
        'user_id' => $userId,
        'token' => $token,
        'url' => xander_prescreening_invite_url($token),
    ];
}

/**
 * Admin form draft (session user_id) — stored in invites until final save.
 */
function xander_prescreening_ensure_admin_draft(mysqli $conn, string $userId): void
{
    xander_prescreening_ensure_invite_columns($conn);
    if (!preg_match('/^user-[0-9]+-[0-9]+$/', $userId)) {
        return;
    }

    $check = $conn->prepare('SELECT id FROM prescreening_invites WHERE user_id = ? LIMIT 1');
    if (!$check) {
        return;
    }
    $check->bind_param('s', $userId);
    $check->execute();
    $exists = (bool) $check->get_result()->fetch_row();
    $check->close();
    if ($exists) {
        return;
    }

    $token = 'admin-' . substr(hash('sha256', $userId), 0, 40);
    $stmt = $conn->prepare(
        'INSERT INTO prescreening_invites (
            user_id, source, invite_token, invite_channel, created_at
        ) VALUES (?, \'admin\', ?, \'\', NOW())'
    );
    if ($stmt) {
        $stmt->bind_param('ss', $userId, $token);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * @return array<string,mixed>|null
 */
function xander_prescreening_load_draft_by_user_id(mysqli $conn, string $userId): ?array
{
    xander_prescreening_ensure_invite_columns($conn);
    if ($userId === '') {
        return null;
    }
    $stmt = $conn->prepare('SELECT * FROM prescreening_invites WHERE user_id = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('s', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

/**
 * @return array<string,mixed>|null
 */
function xander_prescreening_load_invite_by_token(mysqli $conn, string $token): ?array
{
    xander_prescreening_ensure_invite_columns($conn);
    $token = trim($token);
    if ($token === '' || strlen($token) < 16) {
        return null;
    }
    $stmt = $conn->prepare(
        'SELECT * FROM prescreening_invites WHERE invite_token = ? LIMIT 1'
    );
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

/**
 * @return array<string,mixed>|null
 */
function xander_prescreening_submission_by_invite_token(mysqli $conn, string $token): ?array
{
    $token = trim($token);
    if ($token === '') {
        return null;
    }
    xander_prescreening_ensure_submissions_columns($conn);
    $stmt = $conn->prepare(
        'SELECT * FROM prescreening_submissions
         WHERE invite_token = ? AND submitted_at IS NOT NULL
         ORDER BY id DESC LIMIT 1'
    );
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

function xander_prescreening_user_has_submission(mysqli $conn, string $userId): bool
{
    if ($userId === '') {
        return false;
    }
    $stmt = $conn->prepare(
        'SELECT id FROM prescreening_submissions WHERE user_id = ? AND submitted_at IS NOT NULL LIMIT 1'
    );
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('s', $userId);
    $stmt->execute();
    $ok = (bool) $stmt->get_result()->fetch_row();
    $stmt->close();

    return $ok;
}

function xander_prescreening_delete_invite(mysqli $conn, string $userId): void
{
    if ($userId === '') {
        return;
    }
    $stmt = $conn->prepare('DELETE FROM prescreening_invites WHERE user_id = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('s', $userId);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * @return array<string,mixed>|null
 */
function xander_prescreening_find_pending_by_whatsapp(mysqli $conn, string $waPhone): ?array
{
    $digits = preg_replace('/\D+/', '', $waPhone) ?? '';
    if ($digits === '') {
        return null;
    }
    xander_prescreening_ensure_invite_columns($conn);
    $res = $conn->query(
        "SELECT * FROM prescreening_invites
         WHERE whatsapp_number != ''
         ORDER BY id DESC LIMIT 50"
    );
    if (!$res) {
        return null;
    }
    while ($row = $res->fetch_assoc()) {
        $stored = preg_replace('/\D+/', '', (string) ($row['whatsapp_number'] ?? '')) ?? '';
        if ($stored !== '' && $stored === $digits) {
            $res->free();

            return $row;
        }
    }
    $res->free();

    return null;
}

/**
 * @return array{ok:bool,error:string}
 */
function xander_prescreening_send_invite_email(string $toEmail, string $studentName, string $link): array
{
    $toEmail = trim($toEmail);
    if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Invalid email address.'];
    }

    $name = $studentName !== '' ? $studentName : 'Student';
    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');

    $html = '<p>Hello ' . $safeName . ',</p>'
        . '<p>Please complete your <strong>pre-screening</strong> with Xander Global Scholars using your personal link below:</p>'
        . '<p style="margin:20px 0"><a href="' . $safeLink . '" style="background:#1f4fd8;color:#fff;padding:12px 20px;border-radius:8px;text-decoration:none;font-weight:600">Start pre-screening</a></p>'
        . '<p>Or copy this link:<br><a href="' . $safeLink . '">' . $safeLink . '</a></p>'
        . '<p>This link is unique to you. If you have questions, reply to this email.</p>'
        . '<p>— Xander Global Scholars</p>';

    try {
        $mail = xander_create_phpmailer_applicant_sender();
        $mail->addAddress($toEmail, $name);
        $mail->Subject = 'Your Xander Global Scholars pre-screening link';
        $mail->isHTML(true);
        $mail->Body = $html;
        $mail->AltBody = "Hello {$name},\n\nComplete your pre-screening here:\n{$link}\n\n— Xander Global Scholars";
        $mail->send();

        return ['ok' => true, 'error' => ''];
    } catch (Throwable $e) {
        error_log('[prescreening_invite_email] ' . $e->getMessage());

        return ['ok' => false, 'error' => 'Could not send email.'];
    }
}
