<?php
declare(strict_types=1);

function xander_burundi_contract_paths(): array
{
    $root = realpath(__DIR__ . '/../contracts') ?: (__DIR__ . '/../contracts');
    return [
        'header'    => $root . DIRECTORY_SEPARATOR . 'header.png',
        'footer'    => $root . DIRECTORY_SEPARATOR . 'footer.png',
        'signature' => $root . DIRECTORY_SEPARATOR . 'Xander-signatture.jpeg',
        'pdf'       => $root . DIRECTORY_SEPARATOR . 'HEERA-Xander CLIENT CONTRACT-MAY 2026.pdf',
    ];
}

function xander_burundi_img_src(string $path, bool $forPdf = false): string
{
    if (!is_file($path)) {
        return '';
    }
    if ($forPdf) {
        $mime = str_ends_with(strtolower($path), '.png') ? 'image/png' : 'image/jpeg';
        return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($path));
    }
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
    $real = realpath($path) ?: $path;
    if ($docRoot && str_starts_with(strtolower($real), strtolower($docRoot))) {
        $web = str_replace('\\', '/', substr($real, strlen($docRoot)));
        return str_replace(' ', '%20', $web);
    }
    return 'contracts/' . basename($path);
}
