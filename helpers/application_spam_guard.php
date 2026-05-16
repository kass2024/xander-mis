<?php
/**
 * Detect and block bot/spam student_applications (heuristics + OpenAI).
 */
declare(strict_types=1);

require_once __DIR__ . '/openai_env.php';

/**
 * @return list<string>
 */
function pcvc_spam_blocked_email_domains(): array
{
    $raw = function_exists('xander_env_get')
        ? (string) xander_env_get('SPAM_GUARD_BLOCKED_EMAIL_DOMAINS', '')
        : '';

    $defaults = ['korper.nl', 'tempmail.com', 'guerrillamail.com', 'mailinator.com'];
    $extra = array_filter(array_map('strtolower', array_map('trim', explode(',', $raw))));
    $all = array_merge($defaults, $extra);

    return array_values(array_unique($all));
}

/**
 * @param array<string, mixed> $post
 * @return array<string, string>
 */
function pcvc_spam_fields_from_post(array $post): array
{
    return [
        'first_name'  => trim((string) ($post['first_name'] ?? '')),
        'last_name'   => trim((string) ($post['last_name'] ?? '')),
        'email'       => strtolower(trim((string) ($post['email'] ?? ''))),
        'area_code'   => trim((string) ($post['area_code'] ?? '')),
        'phone_number'=> trim((string) ($post['phone_number'] ?? '')),
        'gender'      => trim((string) ($post['gender'] ?? '')),
        'dob'         => trim((string) ($post['dob'] ?? '')),
        'nationality' => trim((string) ($post['nationality'] ?? '')),
        'city'        => trim((string) ($post['city'] ?? '')),
        'address_line1' => trim((string) ($post['address_line1'] ?? '')),
    ];
}

/**
 * @param array<string, string> $row DB row or normalized fields
 * @return array<string, string>
 */
function pcvc_spam_fields_from_row(array $row): array
{
    return [
        'first_name'  => trim((string) ($row['first_name'] ?? '')),
        'last_name'   => trim((string) ($row['last_name'] ?? '')),
        'email'       => strtolower(trim((string) ($row['email'] ?? ''))),
        'area_code'   => trim((string) ($row['area_code'] ?? '')),
        'phone_number'=> trim((string) ($row['phone_number'] ?? '')),
        'gender'      => trim((string) ($row['gender'] ?? '')),
        'dob'         => trim((string) ($row['dob'] ?? '')),
        'nationality' => trim((string) ($row['nationality'] ?? '')),
        'city'        => trim((string) ($row['city'] ?? '')),
        'address_line1' => trim((string) ($row['address_line1'] ?? '')),
    ];
}

function pcvc_spam_guard_should_check(array $fields): bool
{
    foreach (['first_name', 'last_name', 'email', 'phone_number'] as $k) {
        if (($fields[$k] ?? '') !== '') {
            return true;
        }
    }

    return false;
}

function pcvc_spam_name_token_looks_random(string $token): bool
{
    $token = trim($token);
    if ($token === '') {
        return false;
    }

    $len = mb_strlen($token, 'UTF-8');
    if ($len < 10) {
        return false;
    }

    if (preg_match('/\s/u', $token)) {
        return false;
    }

    if (preg_match('/[0-9@#$%^&*()_+=\[\]{}|\\\\;:"<>?\/~]/', $token)) {
        return true;
    }

    if (preg_match('/[bcdfghjklmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ]{7,}/', $token)) {
        return true;
    }

    $letters = preg_replace('/[^\p{L}]/u', '', $token);
    if ($letters === '') {
        return false;
    }

    $letterLen = mb_strlen($letters, 'UTF-8');
    if ($letterLen >= 12) {
        $upper = preg_match_all('/\p{Lu}/u', $letters);
        if ($upper >= 3 && $upper / max(1, $letterLen) >= 0.2) {
            return true;
        }

        $vowels = preg_match_all('/[aeiouyAEIOUY]/u', $letters);
        if ($vowels / max(1, $letterLen) < 0.12) {
            return true;
        }

        $chars = function_exists('mb_str_split')
            ? mb_str_split(mb_strtolower($letters, 'UTF-8'))
            : preg_split('//u', mb_strtolower($letters, 'UTF-8'), -1, PREG_SPLIT_NO_EMPTY);
        if (is_array($chars) && count($chars) >= 12 && count(array_unique($chars)) / count($chars) > 0.72) {
            return true;
        }
    }

    return false;
}

/**
 * Fast local checks — returns spam verdict when confident.
 *
 * @param array<string, string> $fields
 * @return array{is_spam: bool, reason: string, method: string}|null
 */
function pcvc_spam_heuristic_verdict(array $fields): ?array
{
    $email = $fields['email'] ?? '';
    if ($email !== '') {
        $domain = strtolower((string) substr(strrchr($email, '@') ?: '', 1));
        foreach (pcvc_spam_blocked_email_domains() as $blocked) {
            if ($blocked !== '' && ($domain === $blocked || str_ends_with($domain, '.' . $blocked))) {
                return [
                    'is_spam' => true,
                    'reason'  => 'Email domain is not allowed for applications.',
                    'method'  => 'heuristic_domain',
                ];
            }
        }
    }

    foreach (['first_name', 'last_name'] as $key) {
        $name = $fields[$key] ?? '';
        if ($name !== '' && pcvc_spam_name_token_looks_random($name)) {
            return [
                'is_spam' => true,
                'reason'  => 'Name does not look like a real person.',
                'method'  => 'heuristic_name',
            ];
        }
    }

    $full = trim(($fields['first_name'] ?? '') . ' ' . ($fields['last_name'] ?? ''));
    if ($full !== '' && pcvc_spam_name_token_looks_random(str_replace(' ', '', $full))) {
        return [
            'is_spam' => true,
            'reason'  => 'Applicant name appears to be randomly generated.',
            'method'  => 'heuristic_name',
        ];
    }

    return null;
}

/**
 * @param array<string, string> $fields
 * @return array{is_spam: bool, reason: string, method: string, confidence: int}
 */
function pcvc_spam_ai_verdict(array $fields): array
{
    $fallback = [
        'is_spam'    => false,
        'reason'     => '',
        'method'     => 'ai_skipped',
        'confidence' => 0,
    ];

    if (!function_exists('xander_env_get') || (string) xander_env_get('SPAM_GUARD_AI_ENABLED', '1') === '0') {
        return $fallback;
    }

    $apiKey = xander_openai_api_key();
    if ($apiKey === '') {
        return $fallback;
    }

    $payload = json_encode([
        'first_name' => $fields['first_name'] ?? '',
        'last_name'  => $fields['last_name'] ?? '',
        'email'      => $fields['email'] ?? '',
        'phone'      => trim(($fields['area_code'] ?? '') . ' ' . ($fields['phone_number'] ?? '')),
        'gender'     => $fields['gender'] ?? '',
        'dob'        => $fields['dob'] ?? '',
        'nationality'=> $fields['nationality'] ?? '',
        'city'       => $fields['city'] ?? '',
        'address'    => $fields['address_line1'] ?? '',
    ], JSON_UNESCAPED_UNICODE);

    $system = <<<'SYS'
You classify scholarship application form data for Xander Global Scholars.
Reply with JSON only: {"is_spam":boolean,"confidence":0-100,"reason":"short string"}
Mark is_spam true for: bot submissions, randomly generated names (long alphanumeric strings),
disposable/fake emails, obvious test junk, incomplete profiles that are clearly automated spam.
Mark is_spam false for genuine human applicants even if some fields are empty (draft).
SYS;

    $user = "Application fields:\n" . $payload;

    $body = [
        'model'           => 'gpt-4o-mini',
        'temperature'     => 0,
        'max_tokens'      => 120,
        'response_format' => ['type' => 'json_object'],
        'messages'        => [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ],
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($body),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_TIMEOUT => 20,
    ]);

    $response = curl_exec($ch);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr || !$response) {
        error_log('[spam_guard] OpenAI request failed: ' . ($curlErr ?: 'empty response'));

        return $fallback;
    }

    $data = json_decode($response, true);
    $content = $data['choices'][0]['message']['content'] ?? '';
    $parsed = json_decode((string) $content, true);
    if (!is_array($parsed)) {
        error_log('[spam_guard] Invalid AI JSON: ' . substr((string) $content, 0, 200));

        return $fallback;
    }

    $isSpam = !empty($parsed['is_spam']);
    $confidence = (int) ($parsed['confidence'] ?? 0);
    $reason = trim((string) ($parsed['reason'] ?? ''));

    return [
        'is_spam'    => $isSpam && $confidence >= 55,
        'reason'     => $reason !== '' ? $reason : ($isSpam ? 'Flagged as spam by AI.' : ''),
        'method'     => 'ai',
        'confidence' => $confidence,
    ];
}

/**
 * @param array<string, string> $fields
 * @return array{is_spam: bool, reason: string, method: string, confidence: int}
 */
function pcvc_spam_evaluate(array $fields, bool $useAi = true): array
{
    if (!pcvc_spam_guard_should_check($fields)) {
        return ['is_spam' => false, 'reason' => '', 'method' => 'none', 'confidence' => 0];
    }

    $heuristic = pcvc_spam_heuristic_verdict($fields);
    if ($heuristic !== null && $heuristic['is_spam']) {
        return [
            'is_spam'    => true,
            'reason'     => $heuristic['reason'],
            'method'     => $heuristic['method'],
            'confidence' => 100,
        ];
    }

    if ($useAi) {
        $ai = pcvc_spam_ai_verdict($fields);
        if ($ai['is_spam']) {
            return $ai;
        }

        return ['is_spam' => false, 'reason' => '', 'method' => 'ok', 'confidence' => (int) ($ai['confidence'] ?? 0)];
    }

    return ['is_spam' => false, 'reason' => '', 'method' => 'heuristic_only', 'confidence' => 0];
}

/**
 * @param array<string, mixed> $post
 * @return array{is_spam: bool, reason: string, method: string}
 */
function pcvc_spam_check_post(array $post): array
{
    $verdict = pcvc_spam_evaluate(pcvc_spam_fields_from_post($post));

    return [
        'is_spam' => (bool) $verdict['is_spam'],
        'reason'  => (string) $verdict['reason'],
        'method'  => (string) $verdict['method'],
    ];
}

/**
 * Delete one spam application row (child tables optional; best-effort).
 */
function pcvc_spam_delete_application(mysqli $conn, int $id): bool
{
    if ($id <= 0) {
        return false;
    }

    $childTables = ['application_study_choices'];
    foreach ($childTables as $table) {
        $t = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        if ($t !== '') {
            @$conn->query("DELETE FROM `{$t}` WHERE application_id = " . $id);
        }
    }

    $st = $conn->prepare('DELETE FROM student_applications WHERE id = ? LIMIT 1');
    if (!$st) {
        error_log('[spam_guard] delete prepare failed: ' . $conn->error);

        return false;
    }
    $st->bind_param('i', $id);
    $st->execute();
    $ok = $st->affected_rows > 0;
    $st->close();

    return $ok;
}

/**
 * Scan DB for likely spam rows and delete them.
 *
 * @return array{scanned: int, deleted: int, ids: list<int>, dry_run: bool}
 */
function pcvc_spam_purge_database(mysqli $conn, int $limit = 200, bool $dryRun = false, bool $useAi = false): array
{
    $limit = max(1, min(500, $limit));
    $ids = [];
    $deleted = 0;

    $sql = "
        SELECT id, first_name, last_name, email, area_code, phone_number,
               gender, dob, nationality, city, address_line1, submitted, app_start, created_at
        FROM student_applications
        WHERE TRIM(COALESCE(email, '')) <> ''
           OR TRIM(COALESCE(first_name, '')) <> ''
           OR TRIM(COALESCE(last_name, '')) <> ''
        ORDER BY id DESC
        LIMIT ?
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ['scanned' => 0, 'deleted' => 0, 'ids' => [], 'dry_run' => $dryRun];
    }
    $stmt->bind_param('i', $limit);
    $stmt->execute();

    $candidates = [];
    if (method_exists($stmt, 'get_result')) {
        $res = $stmt->get_result();
        if ($res) {
            while ($dbRow = $res->fetch_assoc()) {
                $candidates[] = $dbRow;
            }
            $res->free();
        }
    } else {
        $bind = [
            'id', 'first_name', 'last_name', 'email', 'area_code', 'phone_number',
            'gender', 'dob', 'nationality', 'city', 'address_line1', 'submitted', 'app_start', 'created_at',
        ];
        $refs = [];
        $row = [];
        foreach ($bind as $col) {
            $row[$col] = null;
            $refs[] = &$row[$col];
        }
        call_user_func_array([$stmt, 'bind_result'], $refs);
        while ($stmt->fetch()) {
            $candidates[] = $row;
        }
    }
    $stmt->close();

    $scanned = count($candidates);
    foreach ($candidates as $row) {
        $fields = pcvc_spam_fields_from_row($row);
        $verdict = pcvc_spam_evaluate($fields, $useAi);

        $profileEmpty = ($fields['gender'] === '' && $fields['dob'] === '' && $fields['nationality'] === ''
            && $fields['city'] === '' && $fields['address_line1'] === '');
        $hasContact = ($fields['email'] !== '' || $fields['phone_number'] !== '');
        $looksLikeDraftBot = $profileEmpty && $hasContact && (int) ($row['submitted'] ?? 0) === 0;

        if (!$verdict['is_spam'] && $looksLikeDraftBot) {
            $nameSpam = pcvc_spam_name_token_looks_random($fields['first_name'])
                || pcvc_spam_name_token_looks_random($fields['last_name']);
            if ($nameSpam) {
                $verdict = [
                    'is_spam' => true,
                    'reason'  => 'Incomplete application with random-looking name.',
                    'method'  => 'heuristic_profile',
                ];
            }
        }

        if (empty($verdict['is_spam'])) {
            continue;
        }

        $id = (int) $row['id'];
        $ids[] = $id;

        if (!$dryRun) {
            if (pcvc_spam_delete_application($conn, $id)) {
                $deleted++;
            }
        }
    }

    return [
        'scanned' => $scanned,
        'deleted' => $dryRun ? 0 : $deleted,
        'ids'     => $ids,
        'dry_run' => $dryRun,
    ];
}
