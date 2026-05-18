<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/db.php';

/* =====================================================
   1. ADMIN SECURITY
===================================================== */
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    exit('Unauthorized access.');
}

/* =====================================================
   2. DATABASE VALIDATION
===================================================== */
if (!isset($conn) || $conn->connect_error) {
    http_response_code(500);
    exit('Database connection error.');
}

/* =====================================================
   3. BUILD BASE URL
===================================================== */
$scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'];
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

$contractPage = $basePath . '/student-contract.php';
$baseUrl      = "{$scheme}://{$host}{$contractPage}";

/* =====================================================
   4. INITIAL STATE
===================================================== */
$contractLink = null;
$message      = null;

/* =====================================================
   5. HANDLE REQUEST
===================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $studentId = !empty($_POST['student_id']) ? (int) $_POST['student_id'] : null;

    if ($studentId) {
        $stmt = $conn->prepare(
            "SELECT contract_token
             FROM student_contracts
             WHERE student_id = ?
               AND status IN ('draft','signed')
             ORDER BY id DESC
             LIMIT 1"
        );
        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing) {
            $contractLink = $baseUrl . '?token=' . $existing['contract_token'];
            $message = 'Existing contract found. Reusing the same link.';
        }
    }

    if (!$contractLink) {
        $contractToken = bin2hex(random_bytes(32));

        $stmt = $conn->prepare(
            "INSERT INTO student_contracts
             (contract_token, student_id, status, created_at)
             VALUES (?, ?, 'draft', NOW())"
        );
        $stmt->bind_param('si', $contractToken, $studentId);
        $stmt->execute();
        $stmt->close();

        $contractLink = $baseUrl . '?token=' . $contractToken;
        $message = 'New contract issued successfully.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Issue Student Contract | Xander Global Scholars</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/contract-modern.css">

<style>
.issue-shell {
    max-width: 640px;
    margin: 48px auto;
    background: #fff;
    padding: 40px 36px;
    border-radius: 16px;
    box-shadow: 0 16px 48px rgba(15, 23, 42, .10);
}
.issue-shell h1 {
    text-align: center;
    margin: 0 0 6px;
    font-size: 24px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.01em;
}
.issue-shell .subtitle {
    text-align: center;
    color: #64748b;
    margin: 0 0 28px;
    font-size: 14px;
}
.issue-shell .btn {
    width: 100%;
    padding: 13px 16px;
    font-size: 15px;
    font-weight: 600;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all .15s;
    font-family: inherit;
}
.issue-shell .btn-primary {
    background: linear-gradient(135deg, #1d4ed8, #2563eb);
    color: #fff;
    box-shadow: 0 4px 12px rgba(37, 99, 235, .28);
}
.issue-shell .btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(37, 99, 235, .36);
}
.issue-shell .btn-success {
    background: linear-gradient(135deg, #16a34a, #22c55e);
    color: #fff;
    margin-top: 10px;
    box-shadow: 0 4px 12px rgba(22, 163, 74, .25);
}
.issue-shell .btn-success:hover { transform: translateY(-1px); }
.issue-shell .btn-back {
    background: #eef2ff;
    color: #1e3a8a;
    margin-bottom: 22px;
    width: auto;
    padding: 8px 16px;
    font-size: 13px;
}
.issue-shell .btn-back:hover { background: #fff; box-shadow: 0 1px 4px rgba(15,23,42,.08); }
.issue-shell .alert-success {
    background: #dcfce7;
    color: #14532d;
    padding: 12px 16px;
    border-radius: 8px;
    border: 1px solid #86efac;
    margin-bottom: 18px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.issue-shell .link-box {
    margin-top: 22px;
    padding: 18px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
}
.issue-shell .link-box label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 8px;
}
.issue-shell .link-box input {
    width: 100%;
    padding: 11px 14px;
    border-radius: 8px;
    border: 1.5px solid #cbd5e1;
    background: #fff;
    font-size: 13px;
    font-family: "SF Mono", Consolas, monospace;
    color: #0f172a;
    outline: none;
}
.issue-shell .link-box input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,.18);
}
</style>
</head>

<body class="xgs-contract-body">

<?php include 'header.php'; ?>

<main class="issue-shell">

    <a href="admin-dashboard.php">
        <button class="btn btn-back" type="button">← Back to Dashboard</button>
    </a>

    <h1>📄 Issue Student Contract</h1>
    <p class="subtitle">Generate or retrieve a persistent student contract link</p>

    <?php if ($message): ?>
        <div class="alert-success">✓ <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post">
        <button class="btn btn-primary" type="submit">📄 Issue / Retrieve Contract</button>
    </form>

    <?php if ($contractLink): ?>
        <div class="link-box">
            <label>Contract Link</label>
            <input type="text" id="contractLink" value="<?= htmlspecialchars($contractLink) ?>" readonly>
            <button class="btn btn-success" type="button" onclick="copyLink()">📋 Copy Link</button>
            <div id="copyMsg" style="display:none;text-align:center;color:#16a34a;margin-top:10px;font-weight:600;font-size:13px;">
                ✓ Contract link copied
            </div>
        </div>
    <?php endif; ?>

</main>

<?php include 'footer.php'; ?>

<script>
function copyLink() {
    const input = document.getElementById('contractLink');
    const msg = document.getElementById('copyMsg');
    input.select();
    document.execCommand('copy');
    msg.style.display = 'block';
    setTimeout(() => msg.style.display = 'none', 2000);
}
</script>

</body>
</html>
