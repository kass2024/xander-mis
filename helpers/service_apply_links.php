<?php
declare(strict_types=1);

require_once __DIR__ . '/urls.php';

/**
 * Build a public apply URL for a service card (with tracking id when required).
 *
 * @param bool $freshId When true, always mint a new applicant id (Apply Now). When false, reuse session id for the same service (optional).
 */
function xander_service_apply_url(string $serviceId, string $formPath, ?string $lang = null, bool $freshId = false): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $hash = '';
    $base = $formPath;
    if (str_contains($formPath, '#')) {
        [$base, $frag] = explode('#', $formPath, 2);
        $hash = '#' . $frag;
    }

    $params = [];
    if ($lang !== null && $lang !== '') {
        $params['lang'] = $lang;
    }

    if (xander_service_apply_needs_id($serviceId)) {
        $_SESSION['xander_service_apply_ids'] ??= [];
        if ($freshId || empty($_SESSION['xander_service_apply_ids'][$serviceId])) {
            $_SESSION['xander_service_apply_ids'][$serviceId] = xander_service_new_application_id($serviceId);
        }
        $params['id'] = (string) $_SESSION['xander_service_apply_ids'][$serviceId];
    }

    $url = $base;
    if ($params !== []) {
        $url .= '?' . http_build_query($params);
    }

    return $url . $hash;
}

/** Absolute URL for clipboard / sharing. */
function xander_service_public_apply_url(string $serviceId, string $formPath, ?string $lang = null, bool $freshId = true): string
{
    $relative = xander_service_apply_url($serviceId, $formPath, $lang, $freshId);
    if (str_starts_with($relative, 'http://') || str_starts_with($relative, 'https://')) {
        return $relative;
    }

    return pcvc_public_url('/' . ltrim($relative, '/'));
}

function xander_service_apply_needs_id(string $serviceId): bool
{
    return in_array($serviceId, ['scholarships', 'jobs', 'airticket', 'visa', 'credit', 'admissions', 'i20'], true);
}

function xander_service_new_application_id(string $serviceId): string
{
    $prefix = match ($serviceId) {
        'credit' => 'credit',
        'airticket' => 'ticket',
        'scholarships' => 'loan',
        default => 'user',
    };

    return $prefix . '-' . time() . '-' . random_int(1000, 9999);
}

/**
 * URL copied from “Copy link” — shareable apply link (or services deep link for scholarships).
 */
function xander_service_copy_url(string $serviceId, string $formPath, ?string $lang = null): string
{
    return xander_service_public_apply_url($serviceId, $formPath, $lang, true);
}
