<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/contract_admin_helpers.php';

/* =====================================================
   1. ADMIN AUTH
===================================================== */
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    exit('Unauthorized access');
}

/* =====================================================
   2. CSRF TOKEN
===================================================== */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* =====================================================
   3. BASE PATH (LIVE + LOCAL SAFE)
===================================================== */
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

/* =====================================================
   4. FETCH SIGNED CONTRACTS (LOGIC UNCHANGED)
===================================================== */
$sql = xander_admin_signed_contracts_sql('student_contracts_burundi', 'student_signatures_burundi');

$result = $conn->query($sql);

if (!$result) {
    die('Database query failed: ' . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Burundi Signed Contracts | Xander Global Scholars</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/contract-modern.css">

<style>
body { padding: 32px 20px; }
.btn-view{ background:#2563eb; }
.btn-view:hover{ background:#1d4ed8; }
</style>

<script>
function sendContract(form, btn){
    if(!confirm("Send contract email to student?")) return false;

    btn.disabled = true;
    btn.innerText = "Sending...";

    fetch(form.action, {
        method: "POST",
        body: new FormData(form)
    })
    .then(() => location.reload())
    .catch(() => {
        alert("Failed to send email");
        btn.disabled = false;
        btn.innerText = "Send";
    });

    return false;
}
</script>
</head>

<body class="xgs-contract-body">

<div class="container xgs-admin-shell">

<?php if (!empty($_GET['deleted'])): ?>
<div class="xgs-alert success"><span>✓ Contract deleted from database.</span><button type="button" class="close-btn" onclick="this.parentElement.remove()">×</button></div>
<?php elseif (!empty($_GET['error']) && $_GET['error'] === 'delete_failed'): ?>
<div class="xgs-alert error"><span>✗ Could not delete contract. Please try again.</span><button type="button" class="close-btn" onclick="this.parentElement.remove()">×</button></div>
<?php endif; ?>

<div class="header xgs-admin-header">
    <h1>Signed Student Contracts <span style="font-size:12px;font-weight:500;color:#64748b;background:#eef2ff;padding:4px 10px;border-radius:999px;margin-left:8px;">Burundi</span></h1>
    <a href="<?= $basePath ?>/admin-dashboard.php" class="back-btn xgs-back-btn">
        ← Back to Dashboard
    </a>
</div>

<div class="xgs-admin-table-wrap">
<table class="xgs-admin-table">
<thead>
<tr>
    <th>#</th>
    <th>Student</th>
    <th>Email</th>
    <th>Status</th>
    <th>Signed At</th>
    <th>Actions</th>
</tr>
</thead>
<tbody>

<?php if ($result->num_rows === 0): ?>
<tr>
    <td colspan="6" class="xgs-empty-state">
        <strong>📭 No signed contracts found</strong>
        <p>Contracts will appear here once students sign them.</p>
    </td>
</tr>
<?php endif; ?>

<?php $i = 1; while ($row = $result->fetch_assoc()): ?>
<tr>

<td><?= $i++ ?></td>

<td><strong><?= htmlspecialchars(trim((string) ($row['student_name'] ?? ''))) ?></strong></td>

<td><?= htmlspecialchars($row['email'] ?? '—') ?></td>

<td><span class="xgs-badge signed">✓ SIGNED</span></td>

<td><?= htmlspecialchars($row['signed_at'] ?? '—') ?></td>

<td class="actions xgs-actions">

<a class="btn btn-view xgs-admin-btn send" target="_blank"
   href="<?= $basePath ?>/student-contract-burundi.php?token=<?= urlencode($row['contract_token']) ?>">
👁 View
</a>

<a class="btn btn-pdf xgs-admin-btn pdf"
   href="<?= $basePath ?>/admin-download-contract-burundi.php?id=<?= (int)$row['contract_id'] ?>">
📄 PDF
</a>

<form action="<?= $basePath ?>/admin-send-contract-special.php"
      method="post"
      onsubmit="return sendContract(this,this.querySelector('button'))"
      style="display:inline;">

<input type="hidden" name="contract_id" value="<?= (int)$row['contract_id'] ?>">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

<button class="btn xgs-admin-btn <?= empty($row['sent_at']) ? 'send' : 'resend' ?>">
<?= empty($row['sent_at']) ? '✉ Send' : '↻ Resend' ?>
</button>
</form>

<form action="<?= $basePath ?>/admin-delete-contract-burundi.php"
      method="post"
      onsubmit="return confirm('⚠️ Delete this contract permanently?\nThis action cannot be undone.')"
      style="display:inline;">

<input type="hidden" name="contract_id" value="<?= (int)$row['contract_id'] ?>">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

<button class="btn btn-del xgs-admin-btn del">🗑 Delete</button>
</form>

<?php if (!empty($row['sent_at'])): ?>
<span class="small-text" style="font-size:11px;color:#64748b;margin-left:6px;white-space:nowrap;">
    📧 Last sent <?= htmlspecialchars($row['sent_at']) ?>
</span>
<?php endif; ?>

</td>
</tr>
<?php endwhile; ?>

</tbody>
</table>
</div>

</div>

</body>
</html>
