<?php
declare(strict_types=1);

session_start();
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/includes/service_contract_lib.php';
require_once dirname(__DIR__) . '/helpers/csrf.php';
require_once dirname(__DIR__) . '/helpers/currencies.php';

header('Content-Type: application/json; charset=utf-8');

function xander_sc_api_out(array $payload, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

$adminId = isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : 0;
if (!xander_sc_staff_authorized($adminId)) {
    xander_sc_api_out(['ok' => false, 'error' => 'Unauthorized.'], 403);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $action = (string) ($_GET['action'] ?? 'list');

    if ($action === 'countries') {
        $service = (string) ($_GET['service'] ?? '');
        if (!xander_sc_is_service($service)) {
            xander_sc_api_out(['ok' => false, 'error' => 'Select a service.'], 422);
        }
        $countries = xander_sc_countries($conn, $service);
        xander_sc_api_out([
            'ok' => true,
            'countries' => $countries,
            'message' => $countries === [] ? 'No countries are currently available for this service.' : '',
        ]);
    }

    if ($action === 'offerings') {
        $service = (string) ($_GET['service'] ?? '');
        $countryRef = (string) ($_GET['country_ref'] ?? '');
        if (!xander_sc_is_service($service) || $countryRef === '') {
            xander_sc_api_out(['ok' => false, 'error' => 'Select a service and destination.'], 422);
        }
        $offerings = xander_sc_offerings($conn, $service, $countryRef);
        $empty = match ($service) {
            'study' => 'No active programs are currently available for this destination. Add the service under Services and prices and choose this country, or add it below for this contract only.',
            'work'  => 'No active jobs are currently available for this destination. Add the service under Services and prices and choose this country, or add it below for this contract only.',
            'visit' => 'No active visit packages are currently available for this destination. Add the service under Services and prices and choose this country, or add it below for this contract only.',
            default => 'Nothing is available for this destination.',
        };
        xander_sc_api_out([
            'ok' => true,
            'offerings' => $offerings,
            'message' => $offerings === [] ? $empty : '',
        ]);
    }

    if ($action === 'customers') {
        $q = (string) ($_GET['q'] ?? '');
        xander_sc_api_out(['ok' => true, 'customers' => xander_sc_search_customers($conn, $q)]);
    }

    if ($action === 'currencies') {
        xander_sc_api_out(['ok' => true, 'currencies' => xander_payment_currency_options()]);
    }

    if ($action === 'list') {
        $rows = xander_sc_list(
            $conn,
            (string) ($_GET['status'] ?? 'all'),
            (string) ($_GET['service'] ?? 'all'),
            (string) ($_GET['q'] ?? '')
        );
        foreach ($rows as &$row) {
            $row['url'] = xander_sc_public_url((string) $row['public_token']);
            unset($row['public_token']);
        }
        unset($row);
        xander_sc_api_out(['ok' => true, 'contracts' => $rows]);
    }

    xander_sc_api_out(['ok' => false, 'error' => 'Unknown action.'], 404);
}

if ($method !== 'POST') {
    xander_sc_api_out(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '', true);
if (!is_array($data)) {
    $data = $_POST;
}

$csrf = (string) ($data['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
$expected = (string) ($_SESSION['pcvc_csrf_token'] ?? '');
if ($csrf === '' || $expected === '' || !hash_equals($expected, $csrf)) {
    xander_sc_api_out(['ok' => false, 'error' => 'Invalid security token. Reload the page and try again.'], 403);
}

$action = (string) ($data['action'] ?? '');

if ($action === 'generate') {
    $staffName = trim((string) ($_SESSION['name'] ?? ''));
    $result = xander_sc_generate($conn, $adminId, $staffName, $data);
    xander_sc_api_out($result, !empty($result['ok']) ? 200 : 422);
}

if ($action === 'cancel') {
    $id = (int) ($data['id'] ?? 0);
    $ok = xander_sc_cancel($conn, $id, $adminId);
    if (!$ok) {
        xander_sc_api_out(['ok' => false, 'error' => 'This contract cannot be cancelled.'], 422);
    }
    xander_sc_api_out(['ok' => true]);
}

xander_sc_api_out(['ok' => false, 'error' => 'Unknown action.'], 404);
