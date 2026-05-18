<?php
declare(strict_types=1);

require_once __DIR__ . '/env_load.php';
require_once __DIR__ . '/urls.php';

function xander_payment_bootstrap(): void
{
    xander_load_env_file();
}

function xander_stripe_secret_key(): string
{
    xander_payment_bootstrap();

    return xander_env_get('STRIPE_SECRET_KEY');
}

function xander_stripe_public_key(): string
{
    xander_payment_bootstrap();

    return xander_env_get('STRIPE_PUBLIC_KEY');
}

function xander_momo_itecpay_api_key(): string
{
    xander_payment_bootstrap();

    return xander_env_get('MOMO_ITECPAY_API_KEY');
}

function xander_momo_callback_secret(): string
{
    xander_payment_bootstrap();

    return xander_env_get('MOMO_CALLBACK_SECRET');
}

function xander_momo_pay_api_url(): string
{
    xander_payment_bootstrap();
    $url = xander_env_get('MOMO_ITECPAY_PAY_URL');

    return $url !== '' ? $url : 'https://pay.itecpay.rw/api2/pay';
}

function xander_momo_verify_api_url(): string
{
    xander_payment_bootstrap();
    $url = xander_env_get('MOMO_ITECPAY_VERIFY_URL');

    return $url !== '' ? $url : 'https://pay.itecpay.rw/api2/verify';
}

function xander_payment_public_url(string $path): string
{
    xander_payment_bootstrap();
    $path = '/' . ltrim($path, '/');

    return pcvc_public_url($path);
}

function xander_payment_require_stripe_keys(): array
{
    $secret = xander_stripe_secret_key();
    $public = xander_stripe_public_key();
    if ($secret === '' || $public === '') {
        http_response_code(503);
        die('Payment is not configured. Set STRIPE_SECRET_KEY and STRIPE_PUBLIC_KEY in .env.');
    }

    return ['secret' => $secret, 'public' => $public];
}

function xander_payment_require_momo_key(): string
{
    $key = xander_momo_itecpay_api_key();
    if ($key === '') {
        http_response_code(503);
        die('Mobile Money is not configured. Set MOMO_ITECPAY_API_KEY in .env.');
    }

    return $key;
}
