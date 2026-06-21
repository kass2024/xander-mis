<?php
declare(strict_types=1);

require_once __DIR__ . '/env_load.php';
require_once __DIR__ . '/student_status_notify.php';

/**
 * Candidate access tokens from .env (WHATSAPP_ACCESS_TOKEN first, then META_SYSTEM_USER_TOKEN).
 *
 * @return array<int, string>
 */
function xander_whatsapp_token_candidates(): array
{
    xander_load_env_file();
    $out = [];
    foreach (['WHATSAPP_ACCESS_TOKEN', 'META_SYSTEM_USER_TOKEN'] as $key) {
        $v = trim(xander_env_get($key));
        if ($v !== '' && !in_array($v, $out, true)) {
            $out[] = $v;
        }
    }

    return $out;
}

function xander_whatsapp_graph_version(): string
{
    xander_load_env_file();
    $v = trim(xander_env_get('META_GRAPH_VERSION'));

    return $v !== '' ? $v : 'v19.0';
}

/**
 * @return array{http:int,body:string,json:?array}
 */
function xander_whatsapp_graph_get(string $graphPath, string $token): array
{
    if (!function_exists('curl_init')) {
        return ['http' => 0, 'body' => '', 'json' => null];
    }
    $path = ltrim($graphPath, '/');
    $url = 'https://graph.facebook.com/' . $path;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPGET => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
    ]);
    $body = (string) curl_exec($ch);
    $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $decoded = json_decode($body, true);

    return ['http' => $http, 'body' => $body, 'json' => is_array($decoded) ? $decoded : null];
}

/**
 * Verify token can access the configured WhatsApp phone number ID.
 *
 * @return array{ok:bool,error:string,display_phone:string,verified_name:string,http:int}
 */
function xander_whatsapp_preflight_phone(string $token, string $phoneId): array
{
    $ver = xander_whatsapp_graph_version();
    $res = xander_whatsapp_graph_get(
        rawurlencode($ver) . '/' . rawurlencode($phoneId) . '?fields=display_phone_number,verified_name,quality_rating',
        $token
    );
    if ($res['http'] >= 200 && $res['http'] < 300 && is_array($res['json'])) {
        return [
            'ok' => true,
            'error' => '',
            'display_phone' => (string) ($res['json']['display_phone_number'] ?? ''),
            'verified_name' => (string) ($res['json']['verified_name'] ?? ''),
            'http' => $res['http'],
        ];
    }
    $err = xander_whatsapp_extract_error($res['json']);

    return [
        'ok' => false,
        'error' => $err ? xander_whatsapp_user_hint($err) : 'Could not verify WhatsApp phone number (HTTP ' . $res['http'] . ').',
        'display_phone' => '',
        'verified_name' => '',
        'http' => $res['http'],
    ];
}

/**
 * Discover phone_number_id from WhatsApp Business Account when env ID is wrong.
 *
 * @return array{id:string,display_phone:string,verified_name:string}|null
 */
function xander_whatsapp_discover_phone_from_waba(string $token, string $businessId): ?array
{
    $businessId = trim($businessId);
    if ($businessId === '') {
        return null;
    }
    $ver = xander_whatsapp_graph_version();
    $res = xander_whatsapp_graph_get(
        rawurlencode($ver) . '/' . rawurlencode($businessId) . '/phone_numbers?fields=id,display_phone_number,verified_name',
        $token
    );
    if ($res['http'] < 200 || $res['http'] >= 300 || !is_array($res['json'])) {
        return null;
    }
    $data = $res['json']['data'] ?? [];
    if (!is_array($data) || $data === []) {
        return null;
    }
    $first = $data[0];
    if (!is_array($first) || empty($first['id'])) {
        return null;
    }

    return [
        'id' => (string) $first['id'],
        'display_phone' => (string) ($first['display_phone_number'] ?? ''),
        'verified_name' => (string) ($first['verified_name'] ?? ''),
    ];
}

/**
 * Resolve working WhatsApp Cloud API credentials from .env.
 *
 * @return array{token:string,phone_id:string,version:string,url:string,display_phone:string,verified_name:string,preflight_ok:bool,preflight_error:string}|null
 */
function xander_whatsapp_resolve_api(): ?array
{
    static $cached = null;
    if ($cached !== null) {
        return $cached['api'] ?? null;
    }

    xander_load_env_file();
    $tokens = xander_whatsapp_token_candidates();
    $phoneId = trim(xander_env_get('WHATSAPP_PHONE_NUMBER_ID'));
    $businessId = trim(xander_env_get('WHATSAPP_BUSINESS_ID'));
    $version = xander_whatsapp_graph_version();

    if ($tokens === []) {
        $cached = ['api' => null];

        return null;
    }

    $lastError = 'WhatsApp API is not configured (WHATSAPP_ACCESS_TOKEN missing in .env).';

    foreach ($tokens as $token) {
        if ($phoneId !== '') {
            $check = xander_whatsapp_preflight_phone($token, $phoneId);
            if ($check['ok']) {
                $cached = ['api' => [
                    'token' => $token,
                    'phone_id' => $phoneId,
                    'version' => $version,
                    'url' => 'https://graph.facebook.com/' . rawurlencode($version) . '/' . rawurlencode($phoneId) . '/messages',
                    'display_phone' => $check['display_phone'],
                    'verified_name' => $check['verified_name'],
                    'preflight_ok' => true,
                    'preflight_error' => '',
                ]];

                return $cached['api'];
            }
            $lastError = $check['error'];
        }

        $discovered = xander_whatsapp_discover_phone_from_waba($token, $businessId);
        if ($discovered !== null) {
            $discoveredId = $discovered['id'];
            $check = xander_whatsapp_preflight_phone($token, $discoveredId);
            if ($check['ok']) {
                error_log('[whatsapp_api] Using discovered phone_number_id ' . $discoveredId
                    . ' (env WHATSAPP_PHONE_NUMBER_ID may be outdated)');
                $cached = ['api' => [
                    'token' => $token,
                    'phone_id' => $discoveredId,
                    'version' => $version,
                    'url' => 'https://graph.facebook.com/' . rawurlencode($version) . '/' . rawurlencode($discoveredId) . '/messages',
                    'display_phone' => $check['display_phone'] ?: $discovered['display_phone'],
                    'verified_name' => $check['verified_name'] ?: $discovered['verified_name'],
                    'preflight_ok' => true,
                    'preflight_error' => '',
                ]];

                return $cached['api'];
            }
            $lastError = $check['error'];
        }
    }

    if ($phoneId === '') {
        $lastError = 'WHATSAPP_PHONE_NUMBER_ID is missing in .env.';
    }

    $cached = ['api' => [
        'token' => $tokens[0],
        'phone_id' => $phoneId,
        'version' => $version,
        'url' => $phoneId !== ''
            ? 'https://graph.facebook.com/' . rawurlencode($version) . '/' . rawurlencode($phoneId) . '/messages'
            : '',
        'display_phone' => '',
        'verified_name' => '',
        'preflight_ok' => false,
        'preflight_error' => $lastError,
    ]];

    return $cached['api'];
}

/**
 * Non-secret diagnostic snapshot for admin/CLI.
 *
 * @return array<string,mixed>
 */
function xander_whatsapp_api_diagnostic(): array
{
    xander_load_env_file();
    $api = xander_whatsapp_resolve_api();
    $tokens = xander_whatsapp_token_candidates();
    $phoneFromFile = trim(xander_env_get_from_dotenv_file('WHATSAPP_PHONE_NUMBER_ID'));
    $phoneFromServer = '';
    foreach ([$_ENV['WHATSAPP_PHONE_NUMBER_ID'] ?? null, getenv('WHATSAPP_PHONE_NUMBER_ID'), $_SERVER['WHATSAPP_PHONE_NUMBER_ID'] ?? null] as $c) {
        if ($c !== false && $c !== null && trim((string) $c) !== '') {
            $phoneFromServer = trim((string) $c);
            break;
        }
    }

    return [
        'tokens_configured' => count($tokens),
        'phone_id_env' => trim(xander_env_get('WHATSAPP_PHONE_NUMBER_ID')),
        'phone_id_dotenv_file' => $phoneFromFile,
        'phone_id_server_stale' => ($phoneFromFile !== '' && $phoneFromServer !== '' && $phoneFromFile !== $phoneFromServer)
            ? $phoneFromServer
            : '',
        'business_id_env' => trim(xander_env_get('WHATSAPP_BUSINESS_ID')),
        'graph_version' => xander_whatsapp_graph_version(),
        'preflight_ok' => $api !== null && ($api['preflight_ok'] ?? false),
        'preflight_error' => $api['preflight_error'] ?? 'API not resolved',
        'resolved_phone_id' => $api['phone_id'] ?? '',
        'display_phone' => $api['display_phone'] ?? '',
        'verified_name' => $api['verified_name'] ?? '',
        'fix_hint' => 'Use Phone number ID from Meta → WhatsApp → API Setup (current: 1157403454116116 for +1 438-900-9784). '
            . 'Set WHATSAPP_PHONE_NUMBER_ID and WHATSAPP_ACCESS_TOKEN in project .env — not cPanel Environment Variables.',
    ];
}
