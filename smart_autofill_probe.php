<?php
header('Content-Type: text/plain; charset=UTF-8');

function probe_line($label, $value)
{
    if (is_bool($value)) {
        $value = $value ? 'yes' : 'no';
    } elseif ($value === null) {
        $value = 'null';
    } elseif (is_array($value)) {
        $value = implode(', ', $value);
    }

    echo $label . ': ' . $value . PHP_EOL;
}

function probe_has_text($path, $needle)
{
    if (!is_readable($path)) {
        return false;
    }

    $content = @file_get_contents($path);
    if ($content === false) {
        return false;
    }

    return strpos($content, $needle) !== false;
}

function probe_first_env_keys($path, $limit)
{
    $keys = array();
    if (!is_readable($path)) {
        return $keys;
    }

    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return $keys;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }

        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }

        $keys[] = substr($line, 0, $pos);
        if (count($keys) >= $limit) {
            break;
        }
    }

    return $keys;
}

function probe_tail_lines($path, $maxLines)
{
    if (!is_readable($path)) {
        return array();
    }

    $lines = @file($path, FILE_IGNORE_NEW_LINES);
    if (!is_array($lines)) {
        return array();
    }

    return array_slice($lines, -$maxLines);
}

function probe_parse_php_file($path)
{
    if (!is_readable($path)) {
        return 'unreadable';
    }

    $code = @file_get_contents($path);
    if ($code === false) {
        return 'read_failed';
    }

    try {
        token_get_all($code, TOKEN_PARSE);
        return 'ok';
    } catch (ParseError $e) {
        return 'parse_error: ' . $e->getMessage();
    } catch (Throwable $e) {
        return 'error: ' . $e->getMessage();
    }
}

function probe_shell_lint($path)
{
    if (!function_exists('shell_exec')) {
        return 'shell_exec_unavailable';
    }

    $disabled = (string)ini_get('disable_functions');
    if ($disabled !== '' && stripos($disabled, 'shell_exec') !== false) {
        return 'shell_exec_disabled';
    }

    $escaped = escapeshellarg($path);
    $phpBin = defined('PHP_BINARY') ? PHP_BINARY : 'php';
    $output = @shell_exec($phpBin . ' -l ' . $escaped . ' 2>&1');
    if (!is_string($output) || trim($output) === '') {
        return 'no_output';
    }

    return trim($output);
}

function probe_dir_status($path)
{
    return 'exists=' . (is_dir($path) ? 'yes' : 'no') .
        ', writable=' . (is_dir($path) && is_writable($path) ? 'yes' : 'no');
}

function probe_file_status($path)
{
    return 'exists=' . (is_file($path) ? 'yes' : 'no') .
        ', readable=' . (is_file($path) && is_readable($path) ? 'yes' : 'no') .
        ', size=' . (is_file($path) ? (string)filesize($path) : '0');
}

$root = __DIR__;
$aiFile = $root . '/student_ai_autofill.php';
$uploadFile = $root . '/upload_file.php';
$debugFile = $root . '/smart_autofill_debug.php';
$envFile = $root . '/.env';
$tempDir = $root . '/temp';
$tempAutofillDir = $tempDir . '/autofill';
$uploadsDir = $root . '/uploads';
$runtimeLog = $root . '/student_ai_autofill_error.log';
$uploadLog = $root . '/upload_debug.log';
$bootLog = $root . '/student_ai_autofill_boot.log';

echo "Xander Smart Autofill Probe" . PHP_EOL;
echo str_repeat('=', 32) . PHP_EOL;

probe_line('timestamp', date('c'));
probe_line('php_version', PHP_VERSION);
probe_line('php_sapi', PHP_SAPI);
probe_line('document_root', isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '');
probe_line('script_filename', isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '');
probe_line('server_software', isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '');

echo PHP_EOL . '[functions]' . PHP_EOL;
probe_line('function.str_contains', function_exists('str_contains'));
probe_line('function.str_starts_with', function_exists('str_starts_with'));
probe_line('function.random_bytes', function_exists('random_bytes'));
probe_line('function.mime_content_type', function_exists('mime_content_type'));

echo PHP_EOL . '[extensions]' . PHP_EOL;
probe_line('ext.curl', extension_loaded('curl'));
probe_line('ext.mysqli', extension_loaded('mysqli'));
probe_line('ext.mbstring', extension_loaded('mbstring'));
probe_line('ext.openssl', extension_loaded('openssl'));
probe_line('ext.fileinfo', extension_loaded('fileinfo'));
probe_line('ext.gd', extension_loaded('gd'));
probe_line('ext.zip', extension_loaded('zip'));
probe_line('ext.imagick', extension_loaded('imagick'));
probe_line('class.ZipArchive', class_exists('ZipArchive', false));
probe_line('class.Imagick', class_exists('Imagick', false));
probe_line('class.CURLFile', class_exists('CURLFile', false));

echo PHP_EOL . '[paths]' . PHP_EOL;
probe_line('.env', probe_file_status($envFile));
probe_line('student_ai_autofill.php', probe_file_status($aiFile));
probe_line('upload_file.php', probe_file_status($uploadFile));
probe_line('smart_autofill_debug.php', probe_file_status($debugFile));
probe_line('upload_debug.log', probe_file_status($uploadLog));
probe_line('student_ai_autofill_error.log', probe_file_status($runtimeLog));
probe_line('student_ai_autofill_boot.log', probe_file_status($bootLog));
probe_line('temp', probe_dir_status($tempDir));
probe_line('temp/autofill', probe_dir_status($tempAutofillDir));
probe_line('uploads', probe_dir_status($uploadsDir));
probe_line('sys_temp_dir', sys_get_temp_dir());
probe_line('sys_temp_dir_status', probe_dir_status(sys_get_temp_dir()));
probe_line('session_save_path', session_save_path());

echo PHP_EOL . '[env_keys]' . PHP_EOL;
probe_line('first_keys', probe_first_env_keys($envFile, 10));

echo PHP_EOL . '[deployed_patch_markers]' . PHP_EOL;
probe_line('student_ai_autofill.has_pcvc_contains', probe_has_text($aiFile, 'function pcvc_contains('));
probe_line('student_ai_autofill.has_pcvc_starts_with', probe_has_text($aiFile, 'function pcvc_starts_with('));
probe_line('student_ai_autofill.has_runtime_log', probe_has_text($aiFile, 'student_ai_autofill_error.log'));
probe_line('student_ai_autofill.still_has_str_contains', probe_has_text($aiFile, 'str_contains('));
probe_line('student_ai_autofill.still_has_str_starts_with', probe_has_text($aiFile, 'str_starts_with('));
probe_line('upload_file.has_pcvc_contains', probe_has_text($uploadFile, 'function pcvc_contains('));
probe_line('upload_file.has_pcvc_starts_with', probe_has_text($uploadFile, 'function pcvc_starts_with('));
probe_line('upload_file.still_has_match', probe_has_text($uploadFile, 'match($ext)'));
probe_line('upload_file.still_has_str_contains', probe_has_text($uploadFile, 'str_contains('));
probe_line('upload_file.still_has_str_starts_with', probe_has_text($uploadFile, 'str_starts_with('));

echo PHP_EOL . '[syntax]' . PHP_EOL;
probe_line('parse.student_ai_autofill.php', probe_parse_php_file($aiFile));
probe_line('parse.upload_file.php', probe_parse_php_file($uploadFile));
probe_line('parse.smart_autofill_debug.php', probe_parse_php_file($debugFile));
probe_line('lint.student_ai_autofill.php', probe_shell_lint($aiFile));
probe_line('lint.smart_autofill_debug.php', probe_shell_lint($debugFile));

echo PHP_EOL . '[log_tail.boot]' . PHP_EOL;
foreach (probe_tail_lines($bootLog, 12) as $line) {
    echo $line . PHP_EOL;
}

echo PHP_EOL . '[log_tail.runtime]' . PHP_EOL;
foreach (probe_tail_lines($runtimeLog, 8) as $line) {
    echo $line . PHP_EOL;
}

echo PHP_EOL . '[notes]' . PHP_EOL;
echo '- If function.str_contains=no and student_ai_autofill.still_has_str_contains=yes, live file is not patched.' . PHP_EOL;
echo '- If temp/autofill or uploads are not writable, cPanel can fail with HTTP 500.' . PHP_EOL;
echo '- If this probe works but smart_autofill_debug.php fails, the debug page itself is not fully deployed or still too old on live.' . PHP_EOL;
