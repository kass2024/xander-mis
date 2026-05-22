<?php
// Diagnostic: confirm the live admin-dashboard.php is being served by Apache.
// Visit this in the same browser/tab you use for the admin dashboard:
//   http://localhost/Xander/_mkt_debug.php
// (Or whatever host you normally use.)
declare(strict_types=1);

$dashFile = __DIR__ . '/admin-dashboard.php';
$exists   = file_exists($dashFile);
$mtime    = $exists ? date('Y-m-d H:i:s', filemtime($dashFile)) : 'N/A';
$size     = $exists ? filesize($dashFile) : 0;

$src = $exists ? file_get_contents($dashFile) : '';
$hasV3Marker    = (strpos($src, 'BUILD_2026_05_22_MKT_v3') !== false);
$hasMktComment  = (strpos($src, 'MARKETING_MATERIALS_v3 force-visible') !== false);
$hasMktSpan     = (strpos($src, '<span>Marketing Materials</span>') !== false);
$hasAdmMenuGate = (strpos($src, "if (adm_menu('marketing'))") !== false);

// What URL is being requested
$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host  = $_SERVER['HTTP_HOST'] ?? 'unknown';
$path  = $_SERVER['REQUEST_URI'] ?? '?';
$here  = "{$proto}://{$host}{$path}";

header('Cache-Control: no-store, no-cache, must-revalidate');
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>Marketing menu debug</title>
<style>
body{font-family:system-ui,sans-serif;background:#0f172a;color:#e2e8f0;padding:24px;max-width:900px;margin:0 auto;line-height:1.5}
h1{color:#fbbf24}
.row{display:flex;gap:8px;padding:6px 0;border-bottom:1px solid #334155}
.k{min-width:280px;color:#94a3b8}
.v{font-family:ui-monospace,monospace}
.yes{color:#22c55e;font-weight:700}
.no{color:#ef4444;font-weight:700}
.note{background:#1e293b;padding:14px;border-left:4px solid #fbbf24;margin-top:18px;border-radius:6px}
code{background:#1e293b;padding:2px 6px;border-radius:4px}
</style></head><body>
<h1>Marketing Menu Diagnostic</h1>
<div class="row"><div class="k">This URL</div><div class="v"><?= htmlspecialchars($here, ENT_QUOTES, 'UTF-8') ?></div></div>
<div class="row"><div class="k">PHP version</div><div class="v"><?= PHP_VERSION ?></div></div>
<div class="row"><div class="k">admin-dashboard.php exists</div><div class="v <?= $exists ? 'yes' : 'no' ?>"><?= $exists ? 'YES' : 'NO' ?></div></div>
<div class="row"><div class="k">admin-dashboard.php path</div><div class="v"><?= htmlspecialchars($dashFile, ENT_QUOTES, 'UTF-8') ?></div></div>
<div class="row"><div class="k">admin-dashboard.php last modified</div><div class="v"><?= $mtime ?></div></div>
<div class="row"><div class="k">admin-dashboard.php size (bytes)</div><div class="v"><?= number_format($size) ?></div></div>
<div class="row"><div class="k">Has BUILD_2026_05_22_MKT_v3 marker</div><div class="v <?= $hasV3Marker ? 'yes' : 'no' ?>"><?= $hasV3Marker ? 'YES' : 'NO' ?></div></div>
<div class="row"><div class="k">Has &quot;MARKETING_MATERIALS_v3 force-visible&quot;</div><div class="v <?= $hasMktComment ? 'yes' : 'no' ?>"><?= $hasMktComment ? 'YES' : 'NO' ?></div></div>
<div class="row"><div class="k">Has Marketing Materials span</div><div class="v <?= $hasMktSpan ? 'yes' : 'no' ?>"><?= $hasMktSpan ? 'YES' : 'NO' ?></div></div>
<div class="row"><div class="k">Has stale adm_menu('marketing') gate</div><div class="v <?= $hasAdmMenuGate ? 'no' : 'yes' ?>"><?= $hasAdmMenuGate ? 'STILL THERE — bad' : 'removed — good' ?></div></div>

<div class="note">
<p><strong>How to use this page</strong></p>
<ol>
  <li>If the rows above all say <span class="yes">YES</span>, then the live <code>admin-dashboard.php</code> on this server already contains the new Marketing menu. The only remaining possibility is that your browser is showing a cached copy.</li>
  <li>To verify the browser cache theory, open <code>admin-dashboard.php</code> in a brand-new Incognito / Private window, or press <kbd>Ctrl</kbd>+<kbd>F5</kbd>. Then in the dashboard tab press <kbd>Ctrl</kbd>+<kbd>U</kbd> to view source and search for <code>BUILD_2026_05_22_MKT_v3</code>.</li>
  <li>If your dashboard's view-source does <em>not</em> contain <code>BUILD_2026_05_22_MKT_v3</code> but this page says it's in the file, your browser is serving a cached copy of the dashboard. Clear site data (DevTools → Application → Clear storage), or use a different browser.</li>
</ol>
</div>
</body></html>
