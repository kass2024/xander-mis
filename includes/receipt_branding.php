<?php
declare(strict_types=1);

require_once __DIR__ . '/receipt_stored_html.php';

/**
 * Receipt branding - Bujumbura office admins get dual-logo receipts.
 */

function xander_receipt_ensure_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function xander_receipt_logo_url(string $filename): string
{
    $root = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');
    $path = $root . '/' . ltrim(str_replace('\\', '/', $filename), '/');

    if (!is_file($path)) {
        return htmlspecialchars($filename, ENT_QUOTES, 'UTF-8');
    }

    $docRoot = isset($_SERVER['DOCUMENT_ROOT'])
        ? rtrim(str_replace('\\', '/', (string) $_SERVER['DOCUMENT_ROOT']), '/')
        : '';

    if ($docRoot !== '' && str_starts_with(strtolower($path), strtolower($docRoot))) {
        $web = '/' . ltrim(substr($path, strlen($docRoot)), '/');
        return htmlspecialchars(str_replace(' ', '%20', $web), ENT_QUOTES, 'UTF-8');
    }

    return htmlspecialchars(str_replace(' ', '%20', $filename), ENT_QUOTES, 'UTF-8');
}

function xander_receipt_is_bujumbura(?string $officeName): bool
{
    return strcasecmp(trim((string) $officeName), 'Bujumbura') === 0;
}

/** @return array<string, mixed> */
function xander_get_receipt_branding(mysqli $conn, ?int $adminId = null): array
{
    xander_receipt_ensure_session();

    $branding = [
        'dual'        => false,
        'office_name' => '',
        'primary'     => [
            'name' => 'Xander Global Scholars',
            'logo' => xander_receipt_logo_url('XANDER GLOBAL SCHOLARS LOGO.png'),
        ],
        'secondary'   => [
            'name' => 'HEERA 10 (SURL)',
            'logo' => xander_receipt_logo_url('hera-logo.jpeg'),
        ],
    ];

    if ($adminId === null || $adminId <= 0) {
        if (!empty($_SESSION['id'])) {
            $adminId = (int) $_SESSION['id'];
        } elseif (!empty($_SESSION['admin_id'])) {
            $adminId = (int) $_SESSION['admin_id'];
        }
    }

    $officeName = trim((string) ($_SESSION['office_name'] ?? ''));

    if ($adminId > 0) {
        $stmt = $conn->prepare(
            'SELECT o.office_name
             FROM admins a
             LEFT JOIN offices o ON o.id = a.office_id
             WHERE a.id = ?
             LIMIT 1'
        );
        if ($stmt) {
            $stmt->bind_param('i', $adminId);
            $stmt->execute();
            $stmt->bind_result($officeNameDb);
            if ($stmt->fetch()) {
                $officeName = trim((string) $officeNameDb);
            }
            $stmt->close();
        }
    } elseif (!empty($_SESSION['office_id']) && $officeName === '') {
        $officeId = (int) $_SESSION['office_id'];
        $stmt = $conn->prepare('SELECT office_name FROM offices WHERE id = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('i', $officeId);
            $stmt->execute();
            $stmt->bind_result($officeNameDb);
            if ($stmt->fetch()) {
                $officeName = trim((string) $officeNameDb);
            }
            $stmt->close();
        }
    }

    $branding['office_name'] = $officeName;
    $branding['dual']        = xander_receipt_is_bujumbura($officeName);

    return $branding;
}

function xander_receipt_brand_css_screen(): string
{
    return '.rb-brand{margin-bottom:12px;flex:1;min-width:0}'
        . '.rb-brand-single{display:flex;align-items:center;gap:12px}'
        . '.rb-brand-single img{width:52px;height:52px;object-fit:contain;border-radius:10px;background:#f8fafc;padding:4px;flex-shrink:0}'
        . '.rb-brand-single .rb-titles{line-height:1.25;min-width:0}'
        . '.rb-brand-single .rb-name{font-size:15px;font-weight:800;color:#012F6B;letter-spacing:.02em}'
        . '.rb-brand-single .rb-doc{font-size:11px;color:#64748b;margin-top:2px}'
        . '.rb-brand-dual{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:8px}'
        . '.rb-partner{background:linear-gradient(145deg,#f8fafc 0%,#eef2ff 100%);border:1px solid #e2e8f0;border-radius:12px;padding:10px;text-align:center}'
        . '.rb-partner img{width:48px;height:48px;object-fit:contain;margin:0 auto 6px;display:block}'
        . '.rb-partner .rb-name{font-size:11px;font-weight:800;color:#012F6B;line-height:1.2}'
        . '.rb-partner.rb-hera{background:linear-gradient(145deg,#fffbeb 0%,#fef3c7 100%);border-color:#fde68a}'
        . '.rb-partner.rb-hera .rb-name{color:#92400e}'
        . '.rb-badge{display:inline-block;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#fff;background:linear-gradient(135deg,#012F6B,#254D81);padding:4px 10px;border-radius:999px;margin-bottom:6px}'
        . '.rb-meta-ts{font-size:11px;color:#64748b;margin-top:4px}'
        . '.receipt-card .header{align-items:flex-start}';
}

function xander_receipt_render_header_screen(array $branding, string $timestamp): string
{
    $ts = htmlspecialchars($timestamp, ENT_QUOTES, 'UTF-8');
    $badge = '<span class="rb-badge">Official Payment Receipt</span>';

    if (!empty($branding['dual'])) {
        $pName = htmlspecialchars($branding['primary']['name'], ENT_QUOTES, 'UTF-8');
        $sName = htmlspecialchars($branding['secondary']['name'], ENT_QUOTES, 'UTF-8');
        $pLogo = $branding['primary']['logo'];
        $sLogo = $branding['secondary']['logo'];

        return '<div class="rb-brand">'
            . $badge
            . '<div class="rb-brand-dual">'
            . '<div class="rb-partner"><img src="' . $pLogo . '" alt="' . $pName . '"><div class="rb-name">' . $pName . '</div></div>'
            . '<div class="rb-partner rb-hera"><img src="' . $sLogo . '" alt="' . $sName . '"><div class="rb-name">' . $sName . '</div></div>'
            . '</div><div class="rb-meta-ts">' . $ts . '</div></div>';
    }

    $name = htmlspecialchars($branding['primary']['name'], ENT_QUOTES, 'UTF-8');
    $logo = $branding['primary']['logo'];

    return '<div class="rb-brand rb-brand-single">'
        . '<img src="' . $logo . '" alt="' . $name . '">'
        . '<div class="rb-titles"><div class="rb-name">' . $name . '</div>'
        . $badge
        . '<div class="rb-doc">Official Payment Receipt</div>'
        . '<div class="rb-meta-ts">' . $ts . '</div></div></div>';
}

function xander_receipt_render_header_print(array $branding): string
{
    if (!empty($branding['dual'])) {
        $pName = htmlspecialchars($branding['primary']['name'], ENT_QUOTES, 'UTF-8');
        $sName = htmlspecialchars($branding['secondary']['name'], ENT_QUOTES, 'UTF-8');
        $pLogo = $branding['primary']['logo'];
        $sLogo = $branding['secondary']['logo'];

        return '<div class="header header-dual">'
            . '<div class="partner"><img src="' . $pLogo . '" class="logo" alt=""><div class="name">' . $pName . '</div></div>'
            . '<div class="partner partner-hera"><img src="' . $sLogo . '" class="logo" alt=""><div class="name">' . $sName . '</div></div>'
            . '</div>';
    }

    $logo = $branding['primary']['logo'];
    $name = htmlspecialchars($branding['primary']['name'], ENT_QUOTES, 'UTF-8');

    return '<div class="header">'
        . '<img src="' . $logo . '" class="logo" alt="">'
        . '<div class="company"><div class="name">' . $name . '</div></div>'
        . '</div>';
}
