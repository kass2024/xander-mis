<?php
declare(strict_types=1);

function xander_receipt_image_data_uri(string $filename): string
{
    $root = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');
    $path = $root . '/' . ltrim(str_replace('\\', '/', $filename), '/');

    if (!is_file($path)) {
        return '';
    }

    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $mime = match ($ext) {
        'jpg', 'jpeg' => 'image/jpeg',
        'gif'         => 'image/gif',
        'webp'        => 'image/webp',
        default       => 'image/png',
    };

    return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($path));
}

function xander_receipt_pdf_css(): string
{
    return <<<'CSS'
@page { size: A4 portrait; margin: 12mm; }
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
    font-size: 11pt;
    color: #1e293b;
    background: #f1f5f9;
    margin: 0;
    padding: 8mm;
}
/* Table frame: Dompdf renders borders on tables reliably (div borders often drop). */
.page-frame {
    width: 100%;
    border-collapse: collapse;
    border: 3px solid #012F6B;
    background-color: #012F6B;
}
.page-inner {
    background-color: #ffffff;
    padding: 0;
    vertical-align: top;
    border: 3px solid #012F6B;
}
.accent-bar {
    height: 6px;
    background-color: #012F6B;
    background-image: linear-gradient(90deg, #012F6B 0%, #254D81 45%, #ff8c42 100%);
}
.inner { padding: 14mm 16mm 16mm; }
.hdr {
    text-align: center;
    padding-bottom: 10px;
    border-bottom: 2px solid #e2e8f0;
    margin-bottom: 14px;
}
.hdr-logos { display: table; width: 100%; margin-bottom: 10px; }
.hdr-logos-row { display: table-row; }
.hdr-logo-cell {
    display: table-cell;
    width: 50%;
    vertical-align: middle;
    padding: 6px;
}
.hdr-logo-cell.single { width: 100%; }
.logo-box {
    background: linear-gradient(145deg, #f8fafc, #eef2ff);
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    padding: 10px;
    text-align: center;
}
.logo-box.hera {
    background: linear-gradient(145deg, #fffbeb, #fef3c7);
    border-color: #fde68a;
}
.logo-box img { width: 56px; height: 56px; object-fit: contain; }
.logo-box .org-name {
    font-size: 9pt;
    font-weight: 700;
    color: #012F6B;
    margin-top: 6px;
    line-height: 1.25;
}
.logo-box.hera .org-name { color: #92400e; }
.doc-title {
    font-size: 16pt;
    font-weight: 800;
    color: #012F6B;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    margin: 8px 0 4px;
}
.doc-sub { font-size: 9pt; color: #64748b; }
.doc-sub a { color: #254D81; text-decoration: none; }
.badge {
    display: inline-block;
    margin-top: 8px;
    font-size: 8pt;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #fff;
    background: linear-gradient(135deg, #012F6B, #254D81);
    padding: 5px 14px;
    border-radius: 999px;
}
.meta-grid {
    width: 100%;
    margin-bottom: 16px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
}
.meta-grid table { width: 100%; border-collapse: collapse; }
.meta-grid td {
    padding: 8px 12px;
    font-size: 10pt;
    border-bottom: 1px solid #e2e8f0;
}
.meta-grid tr:last-child td { border-bottom: none; }
.meta-grid tr:nth-child(odd) td { background: #f8fafc; }
.meta-label {
    width: 38%;
    font-weight: 700;
    color: #012F6B;
    border-right: 1px solid #e2e8f0;
}
.meta-value { color: #334155; }
.items-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 12px;
    border: 1px solid #cbd5e1;
}
.items-table thead th {
    background: #012F6B;
    color: #fff;
    font-size: 9pt;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 10px 12px;
    text-align: left;
}
.items-table thead th.amt { text-align: right; }
.items-table tbody td {
    padding: 10px 12px;
    border-bottom: 1px solid #e2e8f0;
    font-size: 10pt;
}
.items-table tbody tr:nth-child(even) td { background: #f8fafc; }
.items-table .amt { text-align: right; font-weight: 600; white-space: nowrap; }
.total-box {
    background: #012F6B;
    color: #fff;
    border-radius: 8px;
    padding: 14px 16px;
    margin-bottom: 14px;
}
.total-box table { width: 100%; }
.total-box .lbl {
    font-size: 11pt;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}
.total-box .val {
    text-align: right;
    font-size: 18pt;
    font-weight: 800;
}
.pay-box {
    border-left: 4px solid #ff8c42;
    background: #fff7ed;
    padding: 10px 14px;
    border-radius: 0 8px 8px 0;
    margin-bottom: 16px;
    font-size: 10pt;
}
.pay-box strong { color: #9a3412; }
.footer {
    text-align: center;
    padding-top: 14px;
    border-top: 2px dashed #cbd5e1;
    color: #64748b;
    font-size: 9pt;
}
.footer .thanks {
    font-size: 12pt;
    font-weight: 700;
    color: #012F6B;
    margin-bottom: 4px;
}
.watermark {
    text-align: center;
    font-size: 36pt;
    font-weight: 800;
    color: #012F6B;
    opacity: 0.06;
    letter-spacing: 0.2em;
    margin: 12px 0 0;
}
CSS;
}

function xander_receipt_build_stored_html(array $data, array $branding): string
{
    $receiptNo    = htmlspecialchars((string) ($data['receipt_no'] ?? ''), ENT_QUOTES, 'UTF-8');
    $studentId    = htmlspecialchars((string) ($data['student_id'] ?? ''), ENT_QUOTES, 'UTF-8');
    $studentName  = htmlspecialchars(trim((string) ($data['student_name'] ?? '')), ENT_QUOTES, 'UTF-8');
    $packageTitle = htmlspecialchars(trim((string) ($data['package_title'] ?? '')), ENT_QUOTES, 'UTF-8');
    $method       = htmlspecialchars((string) ($data['method'] ?? ''), ENT_QUOTES, 'UTF-8');
    $currency     = htmlspecialchars(trim((string) ($data['currency'] ?? '')), ENT_QUOTES, 'UTF-8');
    $items        = $data['items'] ?? [];
    $total        = number_format((float) ($data['total'] ?? 0), 2);
    $dateStr      = htmlspecialchars(
        (string) ($data['created_at'] ?? date('Y-m-d H:i')),
        ENT_QUOTES,
        'UTF-8'
    );

    $pLogo = xander_receipt_image_data_uri('XANDER GLOBAL SCHOLARS LOGO.png');
    $sLogo = xander_receipt_image_data_uri('hera-logo.jpeg');
    if ($pLogo === '') {
        $pLogo = $branding['primary']['logo'];
    }
    if ($sLogo === '') {
        $sLogo = $branding['secondary']['logo'];
    }

    $amtPrefix = $currency !== '' ? $currency . ' ' : '';

    ob_start();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Receipt <?= $receiptNo ?></title>
<style><?= xander_receipt_pdf_css() ?></style>
</head>
<body>
<table class="page-frame" width="100%" cellspacing="0" cellpadding="0" style="width:100%;border-collapse:collapse;border:3px solid #012F6B;">
<tr>
<td class="page-inner" style="background-color:#ffffff;padding:0;vertical-align:top;border:3px solid #012F6B;">
    <div class="accent-bar"></div>
    <div class="inner">
        <div class="hdr">
            <div class="hdr-logos">
                <div class="hdr-logos-row">
                <?php if (!empty($branding['dual'])): ?>
                    <div class="hdr-logo-cell">
                        <div class="logo-box">
                            <img src="<?= $pLogo ?>" alt="">
                            <div class="org-name"><?= htmlspecialchars($branding['primary']['name'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                    <div class="hdr-logo-cell">
                        <div class="logo-box hera">
                            <img src="<?= $sLogo ?>" alt="">
                            <div class="org-name"><?= htmlspecialchars($branding['secondary']['name'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="hdr-logo-cell single">
                        <div class="logo-box">
                            <img src="<?= $pLogo ?>" alt="">
                            <div class="org-name"><?= htmlspecialchars($branding['primary']['name'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                <?php endif; ?>
                </div>
            </div>
            <div class="doc-title">Official Payment Receipt</div>
            <div class="doc-sub">
                <a href="https://xanderglobalscholars.com">xanderglobalscholars.com</a>
                &nbsp;·&nbsp; admission@xanderglobalscholars.com
            </div>
            <span class="badge">Verified payment record</span>
        </div>

        <div class="meta-grid">
            <table>
                <tr>
                    <td class="meta-label">Receipt number</td>
                    <td class="meta-value"><?= $receiptNo ?></td>
                </tr>
                <tr>
                    <td class="meta-label">Customer ID</td>
                    <td class="meta-value"><?= $studentId ?></td>
                </tr>
                <?php if ($studentName !== ''): ?>
                <tr>
                    <td class="meta-label">Customer name</td>
                    <td class="meta-value"><?= $studentName ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($packageTitle !== ''): ?>
                <tr>
                    <td class="meta-label">Fee package</td>
                    <td class="meta-value"><?= $packageTitle ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td class="meta-label">Date &amp; time</td>
                    <td class="meta-value"><?= $dateStr ?></td>
                </tr>
            </table>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="amt">Amount</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $row): ?>
                <tr>
                    <td><?= htmlspecialchars((string) ($row['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="amt"><?= $amtPrefix . number_format((float) ($row['amount'] ?? 0), 2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div class="total-box">
            <table>
                <tr>
                    <td class="lbl">Total paid</td>
                    <td class="val"><?= $amtPrefix . $total ?></td>
                </tr>
            </table>
        </div>

        <div class="pay-box">
            <strong>Payment method:</strong> <?= $method ?>
        </div>

        <div class="footer">
            <div class="thanks">Thank you for your payment</div>
            <div>Please keep this receipt for your records.</div>
            <div style="margin-top:8px;">Xander Global Scholars — Finance Office</div>
        </div>
        <div class="watermark">PAID</div>
    </div>
</td>
</tr>
</table>
</body>
</html>
<?php
    return (string) ob_get_clean();
}

function xander_receipt_render_stored_html(array $data, array $branding): string
{
    return xander_receipt_build_stored_html($data, $branding);
}
