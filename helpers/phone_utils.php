<?php
declare(strict_types=1);

/**
 * Digits only, no leading zeros (national number for storage).
 */
function pcvc_normalize_national_phone(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    return ltrim($digits, '0');
}

/**
 * @return string|null Error message, or null if valid.
 */
function pcvc_validate_national_phone(string $digits, string $areaCode = ''): ?string
{
    $areaCode = preg_replace('/\D+/', '', $areaCode) ?? '';

    if ($digits === '') {
        return 'Phone number is required.';
    }

    if (!preg_match('/^\d+$/', $digits)) {
        return 'Phone number must contain digits only.';
    }

    $len = strlen($digits);

    if ($areaCode === '250') {
        if ($len !== 9) {
            return 'Rwanda mobile numbers are 9 digits without the country code (e.g. 780123456).';
        }
        if ($digits[0] !== '7') {
            return 'Rwanda mobile numbers start with 7.';
        }
        return null;
    }

    if ($areaCode === '1' && $len !== 10) {
        return 'US/Canada numbers are 10 digits without the country code.';
    }

    if ($len < 7 || $len > 15) {
        return 'Enter 7–15 digits (national number only, without country code).';
    }

    return null;
}
