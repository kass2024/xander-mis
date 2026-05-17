<?php
declare(strict_types=1);

/* =====================================================
   0. BOOTSTRAP (NO OUTPUT EVER)
===================================================== */
ob_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/receipt_branding.php';
ob_end_clean();
xander_receipt_ensure_session();
$receiptBranding = xander_get_receipt_branding($conn);

header('Content-Type: application/json; charset=utf-8');

function stmt_fetch_assoc(mysqli_stmt $stmt): ?array
{
    $meta = $stmt->result_metadata();
    if (!$meta) {
        return null;
    }

    $row = [];
    $params = [];
    while ($field = $meta->fetch_field()) {
        $row[$field->name] = null;
        $params[] = &$row[$field->name];
    }

    if ($params) {
        call_user_func_array([$stmt, 'bind_result'], $params);
    }

    if ($stmt->fetch()) {
        return $row;
    }

    return null;
}

/* =====================================================
   1. READ & DECODE JSON INPUT
===================================================== */
$rawInput = file_get_contents('php://input');

if ($rawInput === false || trim($rawInput) === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Empty request body']);
    exit;
}

$data = json_decode($rawInput, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
    exit;
}

/* =====================================================
   2. SANITIZE INPUT
===================================================== */
$applicationId = (int) ($data['student_id'] ?? 0);
$sourceTable   = (string) ($data['table'] ?? '');
$packageId     = (int) ($data['package_id'] ?? 0);
$method        = trim((string) ($data['payment_method'] ?? ''));
$comment       = trim((string) ($data['comment'] ?? ''));
$items         = $data['items'] ?? [];
$requestId     = trim((string) ($data['request_id'] ?? ''));
if (strlen($requestId) > 100) {
    $requestId = substr($requestId, 0, 100);
}

/* =====================================================
   3. VALIDATION
===================================================== */
$allowedTables = [
    'student_applications',
    'malta_applications',
    'turkey_applications'
];

if (
    $applicationId <= 0 ||
    $packageId <= 0 ||
    $method === '' ||
    !in_array($sourceTable, $allowedTables, true) ||
    !is_array($items) ||
    empty($items)
) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid or missing required fields']);
    exit;
}

/* =====================================================
   3b. IDEMPOTENCY (same browser submit twice)
===================================================== */
if ($requestId !== '') {
    $stmt = $conn->prepare(
        "SELECT receipt_no
         FROM application_payments
         WHERE reference = ? AND status = 'PAID' AND receipt_no IS NOT NULL AND receipt_no <> ''
         ORDER BY id DESC
         LIMIT 1"
    );
    $stmt->bind_param('s', $requestId);
    $stmt->execute();
    $existing = stmt_fetch_assoc($stmt);
    $stmt->close();

    if ($existing && !empty($existing['receipt_no'])) {
        $dupReceiptNo = (string) $existing['receipt_no'];
        $dupTotal = 0.0;
        $stmt = $conn->prepare(
            "SELECT total_amount FROM payment_receipts WHERE receipt_no = ? LIMIT 1"
        );
        $stmt->bind_param('s', $dupReceiptNo);
        $stmt->execute();
        $stmt->bind_result($dupTotal);
        $stmt->fetch();
        $stmt->close();

        echo json_encode([
            'success'     => true,
            'message'     => 'Payment already recorded',
            'receipt_no'  => $dupReceiptNo,
            'total_paid'  => number_format((float) $dupTotal, 2, '.', ''),
            'items_count' => count($items),
            'duplicate'   => true,
        ]);
        exit;
    }
}

/* =====================================================
   4. START TRANSACTION
===================================================== */
$conn->begin_transaction();

try {

    $receiptNo = 'RCT-' . date('Ymd-His') . '-' . random_int(100, 999);

    /* =================================================
       5. ENSURE PACKAGE ASSIGNMENT
    ================================================= */
    $stmt = $conn->prepare(
        "SELECT id FROM application_packages
         WHERE application_id = ? AND source_table = ? AND package_id = ? LIMIT 1"
    );
    $stmt->bind_param('isi', $applicationId, $sourceTable, $packageId);
    $stmt->execute();
    $assigned = stmt_fetch_assoc($stmt);
    $stmt->close();

    if (!$assigned) {
        $stmt = $conn->prepare(
            "INSERT INTO application_packages
             (application_id, source_table, package_id, assigned_at)
             VALUES (?, ?, ?, NOW())"
        );
        $stmt->bind_param('isi', $applicationId, $sourceTable, $packageId);
        $stmt->execute();
        $stmt->close();
    }

    /* =================================================
       6. PROCESS ITEM PAYMENTS
    ================================================= */
    $totalRecorded = 0.0;
    $receiptItems  = [];

    foreach ($items as $feeItemId => $amount) {

        $feeItemId = (int) $feeItemId;
        $amount    = round((float) $amount, 2);

        if ($feeItemId <= 0 || $amount <= 0) {
            throw new RuntimeException('Invalid item payment data');
        }

        $stmt = $conn->prepare(
            "SELECT name, amount FROM fee_items WHERE id = ? AND package_id = ? LIMIT 1"
        );
        $stmt->bind_param('ii', $feeItemId, $packageId);
        $stmt->execute();
        $item = stmt_fetch_assoc($stmt);
        $stmt->close();

        if (!$item) {
            throw new RuntimeException('Fee item not found');
        }

        $stmt = $conn->prepare(
            "SELECT COALESCE(SUM(amount_paid),0)
             FROM application_payments
             WHERE application_id = ? AND source_table = ? AND fee_item_id = ? AND status = 'PAID'"
        );
        $stmt->bind_param('isi', $applicationId, $sourceTable, $feeItemId);
        $stmt->execute();
        $stmt->bind_result($alreadyPaid);
        $stmt->fetch();
        $stmt->close();

        if (($alreadyPaid + $amount) > (float) $item['amount']) {
            throw new RuntimeException('Overpayment detected');
        }

        $stmt = $conn->prepare(
            "INSERT INTO application_payments
             (application_id, source_table, fee_item_id, amount_paid,
              payment_method, payment_comment, reference, receipt_no, status, paid_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'PAID', NOW())"
        );
        $paymentRef = $requestId;
        $stmt->bind_param(
            'isidssss',
            $applicationId,
            $sourceTable,
            $feeItemId,
            $amount,
            $method,
            $comment,
            $paymentRef,
            $receiptNo
        );
        $stmt->execute();
        $stmt->close();

        $totalRecorded += $amount;

        $receiptItems[] = [
            'label'  => (string)($item['name'] ?? ('Item ' . $feeItemId)),
            'amount' => $amount
        ];
    }

    /* =================================================
       7. PACKAGE COMPLETION CHECK
    ================================================= */
    $stmt = $conn->prepare(
        "SELECT SUM(fi.amount) AS expected, COALESCE(SUM(p.amount_paid),0) AS paid
         FROM fee_items fi
         LEFT JOIN application_payments p
           ON p.fee_item_id = fi.id
          AND p.application_id = ?
          AND p.source_table = ?
          AND p.status = 'PAID'
         WHERE fi.package_id = ?"
    );
    $stmt->bind_param('isi', $applicationId, $sourceTable, $packageId);
    $stmt->execute();
    $totals = stmt_fetch_assoc($stmt);
    $stmt->close();

    if ((float) ($totals['paid'] ?? 0) >= (float) ($totals['expected'] ?? 0) && (float) ($totals['expected'] ?? 0) > 0) {
        $stmt = $conn->prepare("UPDATE {$sourceTable} SET app_paid = 1 WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $applicationId);
        $stmt->execute();
        $stmt->close();
    }

    /* =================================================
       8. RECEIPT RECORD
    ================================================= */
    $studentName  = '';
    $packageTitle = '';
    $currency     = '';

    $studentRow = null;
    if ($sourceTable === 'malta_applications') {
        $stmt = $conn->prepare('SELECT name AS first_name, surname AS last_name FROM malta_applications WHERE id = ? LIMIT 1');
    } elseif ($sourceTable === 'turkey_applications') {
        $stmt = $conn->prepare('SELECT first_name, last_name FROM turkey_applications WHERE id = ? LIMIT 1');
    } else {
        $stmt = $conn->prepare('SELECT first_name, last_name FROM student_applications WHERE id = ? LIMIT 1');
    }
    if ($stmt) {
        $stmt->bind_param('i', $applicationId);
        $stmt->execute();
        $studentRow = stmt_fetch_assoc($stmt);
        $stmt->close();
    }
    if ($studentRow) {
        $studentName = trim(($studentRow['first_name'] ?? '') . ' ' . ($studentRow['last_name'] ?? ''));
    }

    $stmt = $conn->prepare('SELECT title, currency FROM fee_packages WHERE id = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('i', $packageId);
        $stmt->execute();
        $pkgRow = stmt_fetch_assoc($stmt);
        $stmt->close();
        if ($pkgRow) {
            $packageTitle = (string) ($pkgRow['title'] ?? '');
            $currency     = (string) ($pkgRow['currency'] ?? '');
        }
    }

    $receiptHtml = xander_receipt_render_stored_html([
        'receipt_no'    => $receiptNo,
        'student_id'    => $applicationId,
        'student_name'  => $studentName,
        'package_title' => $packageTitle,
        'currency'      => $currency,
        'items'         => $receiptItems,
        'total'         => $totalRecorded,
        'method'        => $method,
        'created_at'    => date('Y-m-d H:i'),
    ], $receiptBranding);

    $stmt = $conn->prepare(
        "INSERT INTO payment_receipts
         (receipt_no, application_id, source_table, package_id,
          total_amount, payment_method, receipt_html)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('sisidss', $receiptNo, $applicationId, $sourceTable, $packageId, $totalRecorded, $method, $receiptHtml);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    /* =================================================
       9. SEND RESPONSE IMMEDIATELY
    ================================================= */
    echo json_encode([
        'success'     => true,
        'message'     => 'Payment recorded successfully',
        'receipt_no'  => $receiptNo,
        'total_paid'  => number_format($totalRecorded, 2, '.', ''),
        'items_count' => count($items)
    ]);

    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }

    /* =================================================
       10. ASYNC BACKGROUND TASKS
    ================================================= */
    require_once __DIR__ . '/generateReceiptPdf.php';
    generateReceiptPdf($receiptHtml, $receiptNo);

    require_once __DIR__ . '/helpers/receipt_email.php';
    xander_send_receipt_email($conn, $receiptNo);

} catch (Throwable $e) {

    $conn->rollback();

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Payment failed',
        'error'   => $e->getMessage()
    ]);
    exit;
}
