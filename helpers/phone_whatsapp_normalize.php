<?php
/**
 * Normalize phone values for storage and WhatsApp Cloud API (E.164 digits only, no +).
 * International-first: numbers must include a country code (+250…, +1…, +44…) unless
 * WHATSAPP_DEFAULT_COUNTRY_CODE is set for optional national-format fallback on one deployment.
 */
declare(strict_types=1);

function xander_phone_digits_only(string $s): string
{
    $d = preg_replace('/\D+/', '', $s);

    return $d ?? '';
}

/** User-facing hint when normalization fails. */
function xander_whatsapp_phone_validation_hint(): string
{
    return 'Use international format with country code, e.g. +250788123456 (Rwanda), +12704387305 (US), +447700900123 (UK), +2348012345678 (Nigeria).';
}

/**
 * @return string|null Digits-only E.164 without leading +
 */
function xander_whatsapp_validate_e164_digits(string $digits): ?string
{
    $len = strlen($digits);
    if ($len < 10 || $len > 15) {
        return null;
    }
    if ($digits[0] === '0') {
        return null;
    }

    return $digits;
}

/**
 * Digits-only E.164 for WhatsApp Cloud API `to` (no + prefix).
 *
 * @param string|null $defaultCountryDigits Optional from WHATSAPP_DEFAULT_COUNTRY_CODE (digits only, e.g. 250, 1, 234)
 * @return string|null
 */
function xander_format_phone_for_whatsapp_e164(string $raw, ?string $defaultCountryDigits): ?string
{
    $raw = trim($raw);
    if ($raw === '') {
        return null;
    }

    // Explicit international (+…)
    if (str_starts_with($raw, '+')) {
        $digits = xander_phone_digits_only(substr($raw, 1));

        return xander_whatsapp_validate_e164_digits($digits);
    }

    $digits = xander_phone_digits_only($raw);
    if ($digits === '') {
        return null;
    }

    // 00… international dialling prefix (UK, EU, many African countries)
    if (str_starts_with($digits, '00') && strlen($digits) > 10) {
        $intl = substr($digits, 2);
        $validated = xander_whatsapp_validate_e164_digits($intl);
        if ($validated !== null) {
            return $validated;
        }
    }

    $cc = $defaultCountryDigits !== null && $defaultCountryDigits !== ''
        ? preg_replace('/\D+/', '', $defaultCountryDigits)
        : '';
    $cc = $cc === '' ? null : $cc;

    $len = strlen($digits);

    // National numbers with leading 0 — only when a default country code is configured
    if ($len >= 10 && $len <= 12 && $digits[0] === '0' && $cc !== null) {
        $rest = substr($digits, 1);
        if (strlen($rest) >= 8 && strlen($rest) <= 14) {
            return xander_whatsapp_validate_e164_digits($cc . $rest);
        }

        return null;
    }

    // Full international without + (11–15 digits, no leading 0)
    if ($len >= 11 && $len <= 15 && $digits[0] !== '0') {
        return xander_whatsapp_validate_e164_digits($digits);
    }

    // 8–9 digit national (e.g. Rwanda 788…) — require +country or optional default CC
    if ($len >= 8 && $len <= 9 && $digits[0] !== '0') {
        if ($cc !== null) {
            return xander_whatsapp_validate_e164_digits($cc . $digits);
        }

        return null;
    }

    // 10-digit national without country code — optional default CC only (often US/CA = 1)
    if ($len === 10 && $digits[0] !== '0') {
        if ($cc === '1') {
            return xander_whatsapp_validate_e164_digits('1' . $digits);
        }
        if ($cc !== null && $cc !== '1') {
            return xander_whatsapp_validate_e164_digits($cc . $digits);
        }

        return null;
    }

    if ($len >= 10 && $len <= 15 && $digits[0] !== '0') {
        return xander_whatsapp_validate_e164_digits($digits);
    }

    return null;
}

/**
 * Job form stores dial code + national parts. Strip + from area; dedupe if national includes country code.
 *
 * @return array{0:string,1:string} [dial_digits, national_digits]
 */
function xander_normalize_job_phone_pair(string $areaRaw, string $numberRaw): array
{
    $cc = xander_phone_digits_only($areaRaw);
    $nat = xander_phone_digits_only($numberRaw);

    if ($nat !== '' && $cc !== '' && str_starts_with($nat, $cc)) {
        $nat = substr($nat, strlen($cc));
    }

    if ($nat !== '' && $nat[0] === '0') {
        $nat = ltrim($nat, '0');
    }

    return [$cc, $nat];
}

/**
 * Single-field mobile (visa): digits only, full international where possible.
 */
function xander_normalize_visa_mobile_storage(string $raw): string
{
    return xander_phone_digits_only($raw);
}
