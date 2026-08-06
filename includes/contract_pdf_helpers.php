<?php
declare(strict_types=1);

function xander_pdf_date(?string $date): string
{
    $date = trim((string) $date);
    if ($date === '' || $date === '0000-00-00') {
        return '____________';
    }
    $ts = strtotime($date);
    return $ts ? date('F j, Y', $ts) : $date;
}

function xander_pdf_esc(?string $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

/** Normalize browser date strings to MySQL DATE (Y-m-d). */
function xander_normalize_contract_date(?string $raw): ?string
{
    $raw = trim((string) $raw);
    if ($raw === '') {
        return null;
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
        return $raw;
    }
    $ts = strtotime($raw);
    return $ts ? date('Y-m-d', $ts) : null;
}

/**
 * Flatten a browser canvas signature (data URI) onto a white PNG for PDF embedding.
 * Removes transparency / stray tint so Dompdf always shows a clean white box.
 */
function xander_pdf_signature_on_white(string $dataUri): string
{
    if (!str_starts_with($dataUri, 'data:image')) {
        return $dataUri;
    }

    if (!extension_loaded('gd')) {
        return $dataUri;
    }

    $raw = preg_replace('#^data:image/\w+;base64,#', '', $dataUri);
    $binary = base64_decode($raw, true);
    if ($binary === false) {
        return $dataUri;
    }

    $src = @imagecreatefromstring($binary);
    if ($src === false) {
        return $dataUri;
    }

    $w = imagesx($src);
    $h = imagesy($src);
    if ($w < 1 || $h < 1) {
        imagedestroy($src);
        return $dataUri;
    }

    $dest = imagecreatetruecolor($w, $h);
    if ($dest === false) {
        imagedestroy($src);
        return $dataUri;
    }

    $white = imagecolorallocate($dest, 255, 255, 255);
    imagefilledrectangle($dest, 0, 0, $w, $h, $white);

    imagealphablending($dest, true);
    imagesavealpha($dest, false);

    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $rgba = imagecolorat($src, $x, $y);
            $a = ($rgba >> 24) & 0x7F;
            if ($a >= 120) {
                continue;
            }
            $r = ($rgba >> 16) & 0xFF;
            $g = ($rgba >> 8) & 0xFF;
            $b = $rgba & 0xFF;
            // Drop common signature-pad salmon / coral backgrounds
            if ($r >= 230 && $g >= 150 && $g <= 230 && $b >= 150 && $b <= 230) {
                continue;
            }
            $col = imagecolorallocate($dest, $r, $g, $b);
            imagesetpixel($dest, $x, $y, $col);
        }
    }

    imagedestroy($src);

    ob_start();
    imagepng($dest);
    $png = ob_get_clean();
    imagedestroy($dest);

    if ($png === false || $png === '') {
        return $dataUri;
    }

    return 'data:image/png;base64,' . base64_encode($png);
}

/** Footer page numbers — letterhead stays in document flow on page 1 only. */
function xander_dompdf_add_page_numbers(\Dompdf\Dompdf $dompdf): void
{
    $canvas = $dompdf->getCanvas();
    if (!$canvas) {
        return;
    }
    $fontMetrics = $dompdf->getFontMetrics();
    $font = $fontMetrics->getFont('helvetica', 'normal')
        ?? $fontMetrics->getFont('times', 'normal')
        ?? $fontMetrics->getFont('dejavu sans', 'normal')
        ?? $fontMetrics->getFont(null, 'normal');
    if (!$font) {
        return;
    }
    try {
        $canvas->page_text(480, 820, 'Page {PAGE_NUM} of {PAGE_COUNT}', $font, 9, [0.33, 0.33, 0.33]);
    } catch (\Throwable $e) {
        error_log('[contract-pdf] page_text failed: ' . $e->getMessage());
    }
}
