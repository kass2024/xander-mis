<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

$root = __DIR__;
$results = [];

function debug_bool($value): string
{
    return $value ? 'yes' : 'no';
}

function debug_mask_secret(?string $value): array
{
    $value = (string)$value;
    if ($value === '') {
        return ['present' => false, 'preview' => ''];
    }

    $prefix = substr($value, 0, 10);
    $suffix = strlen($value) > 4 ? substr($value, -4) : '';

    return [
        'present' => true,
        'length' => strlen($value),
        'preview' => $prefix . '...' . $suffix,
    ];
}

function debug_dir_status(string $path): array
{
    return [
        'path' => $path,
        'exists' => is_dir($path),
        'writable' => is_dir($path) ? is_writable($path) : false,
    ];
}

function debug_file_status(string $path): array
{
    return [
        'path' => $path,
        'exists' => is_file($path),
        'readable' => is_file($path) ? is_readable($path) : false,
        'writable' => file_exists($path) ? is_writable($path) : false,
    ];
}

function debug_tail_lines(string $path, int $maxLines = 20): array
{
    if (!is_readable($path)) {
        return [];
    }

    $handle = @fopen($path, 'rb');
    if (!$handle) {
        return [];
    }

    $buffer = '';
    $chunkSize = 4096;
    $lineCount = 0;

    fseek($handle, 0, SEEK_END);
    $position = ftell($handle);

    while ($position > 0 && $lineCount <= $maxLines) {
        $readSize = ($position >= $chunkSize) ? $chunkSize : $position;
        $position -= $readSize;
        fseek($handle, $position);
        $chunk = fread($handle, $readSize);
        if ($chunk === false) {
            break;
        }

        $buffer = $chunk . $buffer;
        $lineCount = substr_count($buffer, "\n");
    }

    fclose($handle);

    $buffer = str_replace(["\r\n", "\r"], "\n", $buffer);
    $lines = explode("\n", trim($buffer));
    if (!$lines) {
        return [];
    }

    return array_slice($lines, -$maxLines);
}

require_once __DIR__ . '/helpers/load_env.php';
pcvc_load_dotenv(__DIR__);

session_start();

$requiredFiles = [
    'student-application.php',
    'application.js',
    'student_ai_autofill.php',
    'upload_file.php',
    'save_autofill_draft.php',
    'save_application.php',
    'loadApplicationData.php',
    'db.php',
    '.env',
    'helpers/load_env.php',
    'helpers/study_choices.php',
    'helpers/mailer.php',
    'helpers/student_portal_accounts.php',
    'helpers/student_portal_schema.php',
    'helpers/urls.php',
    'includes/company_branding.php',
    'PHPMailer/src/PHPMailer.php',
    'PHPMailer/src/SMTP.php',
    'PHPMailer/src/Exception.php',
];

$requiredExtensions = [
    'curl',
    'json',
    'mbstring',
    'mysqli',
    'openssl',
    'fileinfo',
    'gd',
    'zip',
];

$optionalExtensions = [
    'imagick',
];

$tempPreferred = $root . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'autofill' . DIRECTORY_SEPARATOR;
$tempFallback = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
    . DIRECTORY_SEPARATOR
    . 'xander_autofill'
    . DIRECTORY_SEPARATOR;
$uploadsDir = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;

$results['meta'] = [
    'timestamp' => date('c'),
    'php_version' => PHP_VERSION,
    'php_sapi' => PHP_SAPI,
    'os' => PHP_OS_FAMILY,
    'loaded_ini' => php_ini_loaded_file(),
    'session_id' => session_id(),
    'session_user_id' => $_SESSION['user_id'] ?? null,
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? '',
    'script_name' => $_SERVER['SCRIPT_NAME'] ?? '',
    'server_name' => $_SERVER['SERVER_NAME'] ?? '',
];

$results['env'] = [
    'gemini_api_key' => debug_mask_secret(getenv('GEMINI_API_KEY') ?: ''),
    'anthropic_api_key' => debug_mask_secret(getenv('ANTHROPIC_API_KEY') ?: ''),
    'document_ai_primary' => getenv('DOCUMENT_AI_PRIMARY') ?: 'gemini',
    'document_ai_fast_mode' => getenv('DOCUMENT_AI_FAST_MODE') ?: '0',
    'document_ai_concurrency' => getenv('DOCUMENT_AI_CONCURRENCY') ?: '3',
    'document_ai_dual_provider' => getenv('DOCUMENT_AI_DUAL_PROVIDER') ?: '0',
    'gemini_model' => getenv('GEMINI_MODEL') ?: '',
    'anthropic_model' => getenv('ANTHROPIC_MODEL') ?: '',
    'smtp_host' => debug_mask_secret(getenv('SMTP_HOST') ?: ''),
    'smtp_username' => debug_mask_secret(getenv('SMTP_USERNAME') ?: ''),
    'smtp_password' => debug_mask_secret(getenv('SMTP_PASSWORD') ?: ''),
    'smtp_from_email' => debug_mask_secret(getenv('SMTP_FROM_EMAIL') ?: ''),
    'smtp_from_name' => [
        'present' => (getenv('SMTP_FROM_NAME') ?: '') !== '',
        'value' => getenv('SMTP_FROM_NAME') ?: '',
    ],
];

$results['extensions'] = [
    'required' => [],
    'optional' => [],
];

foreach ($requiredExtensions as $ext) {
    $results['extensions']['required'][$ext] = extension_loaded($ext);
}
foreach ($optionalExtensions as $ext) {
    $results['extensions']['optional'][$ext] = extension_loaded($ext);
}

$results['functions'] = [
    'curl_init' => function_exists('curl_init'),
    'mime_content_type' => function_exists('mime_content_type'),
    'imagecreatefromjpeg' => function_exists('imagecreatefromjpeg'),
    'imagecreatefrompng' => function_exists('imagecreatefrompng'),
    'imagecreatefromwebp' => function_exists('imagecreatefromwebp'),
    'imagecreatefrombmp' => function_exists('imagecreatefrombmp'),
    'random_bytes' => function_exists('random_bytes'),
];

$results['paths'] = [
    'preferred_temp' => debug_dir_status($tempPreferred),
    'fallback_temp' => debug_dir_status($tempFallback),
    'uploads' => debug_dir_status($uploadsDir),
    'upload_debug_log' => debug_file_status($root . DIRECTORY_SEPARATOR . 'upload_debug.log'),
    'email_debug_log' => debug_file_status($root . DIRECTORY_SEPARATOR . 'email_debug.log'),
    'student_ai_autofill_error_log' => debug_file_status($root . DIRECTORY_SEPARATOR . 'student_ai_autofill_error.log'),
];

$results['required_files'] = [];
foreach ($requiredFiles as $relativePath) {
    $results['required_files'][$relativePath] = debug_file_status($root . DIRECTORY_SEPARATOR . $relativePath);
}

$results['database'] = [
    'connected' => false,
    'database_name' => null,
    'error' => null,
];

try {
    require __DIR__ . '/db.php';
    if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
        $results['database']['connected'] = true;
        $dbName = $conn->query('SELECT DATABASE() AS db_name');
        if ($dbName instanceof mysqli_result) {
            $row = $dbName->fetch_assoc();
            $results['database']['database_name'] = $row['db_name'] ?? null;
            $dbName->close();
        }
    } else {
        $results['database']['error'] = 'Database connection was not initialized.';
    }
} catch (Throwable $e) {
    $results['database']['error'] = $e->getMessage();
}

$writeTests = [];
foreach ([
    'preferred_temp' => $tempPreferred,
    'fallback_temp' => $tempFallback,
    'uploads' => $uploadsDir,
] as $label => $dir) {
    if (!is_dir($dir) || !is_writable($dir)) {
        $writeTests[$label] = 'skipped';
        continue;
    }

    $probe = $dir . 'debug_probe_' . bin2hex(random_bytes(4)) . '.tmp';
    $ok = @file_put_contents($probe, 'debug probe');
    if ($ok === false) {
        $writeTests[$label] = 'failed';
        continue;
    }

    @unlink($probe);
    $writeTests[$label] = 'ok';
}
$results['write_tests'] = $writeTests;

$results['log_tails'] = [
    'upload_debug_log' => debug_tail_lines($root . DIRECTORY_SEPARATOR . 'upload_debug.log', 25),
    'email_debug_log' => debug_tail_lines($root . DIRECTORY_SEPARATOR . 'email_debug.log', 25),
    'student_ai_autofill_error_log' => debug_tail_lines($root . DIRECTORY_SEPARATOR . 'student_ai_autofill_error.log', 25),
];

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
