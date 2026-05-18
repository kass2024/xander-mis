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
   4. CHECK FOR SUCCESS/ERROR MESSAGES
===================================================== */
$showSuccess = isset($_GET['sent']) && $_GET['sent'] == '1';
$showError = isset($_GET['error']) && !empty($_GET['error']);

/* =====================================================
   5. FETCH SIGNED CONTRACTS
===================================================== */
$sql = xander_admin_signed_contracts_sql('student_contracts', 'student_signatures');

$result = $conn->query($sql);

if (!$result) {
    error_log('Database query failed: ' . $conn->error);
    die('An error occurred while fetching contracts. Please try again.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Signed Student Contracts | Xander Global Scholars</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/contract-modern.css">

<style>
/* Page-specific tweaks for admin contracts list */
body { padding: 32px 20px; }
.container { /* preserved for backward compatibility */ }
.btn, .status, .actions, .alert, .alert-success, .alert-error,
.empty-state, .loading, .header, .back-btn, table th, table td { /* will be styled by below */ }
</style>
</head>

<body class="xgs-contract-body">

<div class="container xgs-admin-shell">

<div class="header xgs-admin-header">
    <h1>Signed Student Contracts</h1>
    <a href="<?= $basePath ?>/admin-dashboard.php" class="back-btn xgs-back-btn">
        ← Back to Dashboard
    </a>
</div>

<?php if ($showSuccess): ?>
<div class="alert alert-success xgs-alert success" id="successAlert">
    <span>✅ Email sent successfully!</span>
    <button type="button" class="close-btn" onclick="this.parentElement.remove()" aria-label="Close">×</button>
</div>
<?php endif; ?>

<?php if ($showError): ?>
<div class="alert alert-error xgs-alert error" id="errorAlert">
    <span>❌ <?= htmlspecialchars(urldecode($_GET['error'])) ?></span>
    <button type="button" class="close-btn" onclick="this.parentElement.remove()" aria-label="Close">×</button>
</div>
<?php endif; ?>

<div class="table-responsive xgs-admin-table-wrap">
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
    <td colspan="6" class="empty-state xgs-empty-state">
        <strong>📭 No signed contracts found</strong>
        <p>Contracts will appear here once students sign them.</p>
    </td>
</tr>
<?php endif; ?>

<?php $i = 1; while ($row = $result->fetch_assoc()): ?>
<?php
    $fullName = trim((string) ($row['student_name'] ?? ''));
    $fullName = !empty($fullName) ? $fullName : 'Unknown Student';
    $hasBeenSent = !empty($row['sent_at']);
?>
<tr>
    <td><?= $i++ ?></td>
    <td><strong><?= htmlspecialchars($fullName) ?></strong></td>
    <td><?= htmlspecialchars($row['email'] ?? '—') ?></td>
    <td><span class="status signed xgs-badge signed">✓ SIGNED</span></td>
    <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($row['signed_at'] ?? 'now'))) ?></td>
    <td class="actions xgs-actions">

        <!-- PDF Button -->
        <a class="btn btn-pdf xgs-admin-btn pdf"
           href="<?= $basePath ?>/admin-download-contract.php?id=<?= (int)$row['contract_id'] ?>"
           target="_blank"
           rel="noopener noreferrer"
           title="Download PDF">
            📄 PDF
        </a>

        <!-- Send/Resend Form -->
        <form action="<?= $basePath ?>/admin-send-contract.php"
              method="post"
              onsubmit="return sendContract(this, this.querySelector('button'))"
              style="display:inline;">

            <input type="hidden" name="contract_id" value="<?= (int)$row['contract_id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <button class="btn <?= $hasBeenSent ? 'btn-resend' : 'btn-send' ?> xgs-admin-btn <?= $hasBeenSent ? 'resend' : 'send' ?>"
                    title="<?= $hasBeenSent ? 'Resend contract email' : 'Send contract email' ?>">
                <?= $hasBeenSent ? '↻ Resend' : '✉ Send' ?>
            </button>
        </form>

        <!-- Delete Form -->
        <form action="<?= $basePath ?>/admin-delete-contract.php"
              method="post"
              onsubmit="return confirm('⚠️ Delete this contract permanently?\nThis action cannot be undone.')"
              style="display:inline;">

            <input type="hidden" name="contract_id" value="<?= (int)$row['contract_id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <button class="btn btn-del xgs-admin-btn del" title="Delete contract permanently">🗑 Delete</button>
        </form>

        <?php if ($hasBeenSent): ?>
            <span class="small-text" style="font-size:11px; color:#64748b; margin-left:6px; white-space:nowrap;" title="Last email sent">
                📧 <?= htmlspecialchars(date('Y-m-d H:i', strtotime($row['sent_at']))) ?>
            </span>
        <?php endif; ?>

    </td>
</tr>
<?php endwhile; ?>

</tbody>
</table>
</div>

</div>

<script>
// Production-ready sendContract function with improved error handling
function sendContract(form, btn) {
    if (!confirm("Send contract email to student?")) {
        return false;
    }

    // Disable button and show loading state
    btn.disabled = true;
    const originalText = btn.innerText;
    btn.innerHTML = '<span class="loading"></span> Sending...';

    // Get form data
    const formData = new FormData(form);

    // Add timestamp to prevent caching
    formData.append('_t', Date.now());

    fetch(form.action, {
        method: "POST",
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(async response => {
        // Check if response is OK
        if (!response.ok) {
            const text = await response.text();
            throw new Error(`Server error (${response.status}): ${text.substring(0, 100)}`);
        }
        
        // Try to parse JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Non-JSON response:', text.substring(0, 200));
            throw new Error('Invalid server response format');
        }
        
        return response.json();
    })
    .then(data => {
        if (data && data.success === true) {
            // Show success message
            showNotification('Email sent successfully!', 'success');
            
            // Update button to "Resend"
            btn.classList.remove('btn-send');
            btn.classList.add('btn-resend');
            btn.innerHTML = 'Resend';
            
            // Reload to show updated timestamp
            setTimeout(() => {
                window.location.href = window.location.pathname + '?sent=1';
            }, 1000);
        } else {
            throw new Error(data?.error || 'Failed to send email');
        }
    })
    .catch(error => {
        console.error('Send contract error:', error);
        
        // Show error notification
        showNotification('Failed to send email: ' + error.message, 'error');
        
        // Restore button
        btn.disabled = false;
        btn.innerHTML = originalText;
    });

    return false;
}

// Notification helper
function showNotification(message, type = 'success') {
    // Remove any existing notification
    const existingAlert = document.querySelector('.alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    // Create new notification
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
        <span>${type === 'success' ? '✅' : '❌'} ${message}</span>
        <button type="button" class="close-btn" onclick="this.parentElement.remove()">×</button>
    `;
    
    // Insert at top of container
    const container = document.querySelector('.container');
    container.insertBefore(alert, container.querySelector('.table-responsive'));
    
    // Auto-remove after 5 seconds for success messages
    if (type === 'success') {
        setTimeout(() => {
            if (alert.parentElement) {
                alert.remove();
            }
        }, 5000);
    }
}

// Add global error handler for fetch promises
window.addEventListener('unhandledrejection', function(event) {
    console.error('Unhandled promise rejection:', event.reason);
    alert('An unexpected error occurred. Please check the console for details.');
});

// Auto-hide success message after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const successAlert = document.getElementById('successAlert');
    if (successAlert) {
        setTimeout(() => {
            if (successAlert.parentElement) {
                successAlert.remove();
            }
        }, 5000);
    }
});
</script>

</body>
</html>