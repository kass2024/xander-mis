<?php
/**
 * Forward Meta WhatsApp webhooks from cPanel → xanderbot VPS (FAQ bot + prescreening forward).
 * Set in cPanel .env: XANDERBOT_WEBHOOK_URL=https://xanderbot.site/api/webhook/meta
 */
declare(strict_types=1);

function xander_bot_webhook_forward_url(): string
{
    if (!function_exists('xander_env_get')) {
        return '';
    }
    xander_load_env_file();

    return trim((string) xander_env_get('XANDERBOT_WEBHOOK_URL'));
}

/**
 * Proxy GET (verify) or POST (events) to xanderbot; returns true if request was forwarded.
 */
function xander_bot_webhook_forward_request(): bool
{
    $target = xander_bot_webhook_forward_url();
    if ($target === '' || !function_exists('curl_init')) {
        return false;
    }

    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $headers = ['Content-Type: application/json'];

    $sig = (string) ($_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '');
    if ($sig !== '') {
        $headers[] = 'X-Hub-Signature-256: ' . $sig;
    }

  $url = $target;
    if ($method === 'GET') {
        $qs = $_SERVER['QUERY_STRING'] ?? '';
        if ($qs !== '') {
            $url .= (str_contains($target, '?') ? '&' : '?') . $qs;
        }
        $body = null;
    } else {
        $body = file_get_contents('php://input') ?: '';
    }

    $ch = curl_init($url);
    if ($ch === false) {
        error_log('[webhook_forward] curl_init failed');

        return false;
    }

    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CUSTOMREQUEST => $method,
    ];
    if ($body !== null) {
        $opts[CURLOPT_POSTFIELDS] = $body;
    }
    curl_setopt_array($ch, $opts);

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        error_log('[webhook_forward] curl error: ' . $err);

        return false;
    }

    http_response_code($httpCode > 0 ? $httpCode : 200);
    if ($method === 'GET') {
        header('Content-Type: text/plain; charset=utf-8');
    } else {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo $response;

    return true;
}
