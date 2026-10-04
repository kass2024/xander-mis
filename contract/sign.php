<?php
declare(strict_types=1);

$appRoot = dirname(__DIR__);
chdir($appRoot);

$token = trim((string) ($_GET['token'] ?? ''));
if ($token === '' && !empty($_SERVER['PATH_INFO'])) {
    $token = trim((string) $_SERVER['PATH_INFO'], '/');
}
if ($token === '' && preg_match('#/contract/sign/([a-f0-9]{64})#', (string) ($_SERVER['REQUEST_URI'] ?? ''), $match)) {
    $token = $match[1];
}

if (!preg_match('/\A[a-f0-9]{64}\z/', $token)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Invalid contract link.';
    exit;
}

$_GET['token'] = $token;
require $appRoot . '/student-contract.php';
