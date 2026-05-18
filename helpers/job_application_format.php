<?php
declare(strict_types=1);

/**
 * Treat placeholder / junk address parts as empty for display.
 */
function xander_job_address_part_display(?string $value): string
{
    $v = trim((string) $value);
    if ($v === '' || $v === '0' || $v === '—' || strcasecmp($v, 'null') === 0 || strcasecmp($v, 'n/a') === 0) {
        return '';
    }

    return $v;
}

/**
 * Primary location line: province/state + district (no leading "0,").
 */
function xander_job_format_location_primary(?string $province, ?string $district): string
{
    $parts = [];
    foreach ([$province, $district] as $p) {
        $clean = xander_job_address_part_display($p);
        if ($clean !== '') {
            $parts[] = $clean;
        }
    }

    return $parts !== [] ? implode(', ', $parts) : '—';
}

/**
 * Detail line: sector / cell / village — only non-empty segments.
 */
function xander_job_format_location_detail(?string $sector, ?string $cellWard, ?string $village): string
{
    $parts = [];
    foreach ([$sector, $cellWard, $village] as $p) {
        $clean = xander_job_address_part_display($p);
        if ($clean !== '') {
            $parts[] = $clean;
        }
    }

    return $parts !== [] ? implode(' / ', $parts) : '';
}

/**
 * Normalize stored address fields on save (lenient / AI autofill).
 */
function xander_job_normalize_address_field(?string $value, string $fallback = ''): string
{
    $clean = xander_job_address_part_display($value);

    return $clean !== '' ? $clean : $fallback;
}
