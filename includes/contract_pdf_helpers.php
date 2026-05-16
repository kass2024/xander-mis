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
