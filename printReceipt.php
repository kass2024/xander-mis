<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/receipt_branding.php';

class ReceiptGenerator
{
    private mysqli $conn;
    private string $receiptNo;
    private array $receipt = [];
    private string $studentName = 'Unknown Student';
    private string $packageTitle = 'N/A';
    private string $currency = '';
    private array $items = [];
    private array $branding = [];

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function generate(string $receiptNo): void
    {
        if ($receiptNo === '') {
            $receiptNo = $this->generateSixDigitReceiptNo();
        }

        $this->receiptNo = $receiptNo;

        $this->branding = xander_get_receipt_branding($this->conn);
        $this->fetchReceipt();
        $this->fetchStudentInfo();
        $this->fetchPackageInfo();
        $this->fetchPaidItems();
        $this->render();
    }

    private function fetchReceipt(): void
    {
        $stmt = $this->conn->prepare("
            SELECT receipt_no, application_id, package_id,
                   total_amount, payment_method, created_at
            FROM payment_receipts
            WHERE receipt_no = ?
            LIMIT 1
        ");
        $stmt->bind_param('s', $this->receiptNo);
        $stmt->execute();
        $this->receipt = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();

        if (!$this->receipt) {
            $this->sendError(404, 'Receipt not found');
        }
    }

    private function fetchStudentInfo(): void
    {
        $stmt = $this->conn->prepare("
            SELECT first_name, last_name FROM (
                SELECT id, first_name, last_name FROM student_applications
                UNION ALL
                SELECT id, name AS first_name, surname AS last_name FROM malta_applications
                UNION ALL
                SELECT id, first_name, last_name FROM turkey_applications
            ) x
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->bind_param('i', $this->receipt['application_id']);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($student) {
            $this->studentName = trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
        }
    }

    private function fetchPackageInfo(): void
    {
        $stmt = $this->conn->prepare("
            SELECT title, currency
            FROM fee_packages
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->bind_param('i', $this->receipt['package_id']);
        $stmt->execute();
        $package = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($package) {
            $this->packageTitle = $package['title'] ?? 'N/A';
            $this->currency = $package['currency'] ?? '';
        }
    }

    private function fetchPaidItems(): void
    {
        $stmt = $this->conn->prepare("
            SELECT fi.name, ap.amount_paid
            FROM application_payments ap
            JOIN fee_items fi ON fi.id = ap.fee_item_id
            WHERE ap.receipt_no = ?
              AND ap.status = 'PAID'
            ORDER BY ap.id
        ");
        $stmt->bind_param('s', $this->receiptNo);
        $stmt->execute();
        $this->items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (!empty($this->items)) {
            return;
        }

        $createdAt = (string) ($this->receipt['created_at'] ?? '');
        $appId = (int) ($this->receipt['application_id'] ?? 0);
        if ($appId <= 0 || $createdAt === '') {
            return;
        }

        $stmt = $this->conn->prepare("
            SELECT fi.name, ap.amount_paid
            FROM application_payments ap
            JOIN fee_items fi ON fi.id = ap.fee_item_id
            WHERE ap.application_id = ?
              AND ap.status = 'PAID'
              AND (ap.receipt_no IS NULL OR ap.receipt_no = '')
              AND ap.paid_at >= ?
              AND ap.paid_at < (
                SELECT COALESCE(MIN(pr.created_at), '9999-12-31 23:59:59')
                FROM payment_receipts pr
                WHERE pr.application_id = ?
                  AND pr.created_at > ?
              )
            ORDER BY ap.id
        ");
        $stmt->bind_param('issi', $appId, $createdAt, $appId, $createdAt);
        $stmt->execute();
        $this->items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

    private function generateSixDigitReceiptNo(): string
    {
        $maxAttempts = 10;
        $attempt = 0;

        do {
            $receiptNo = (string) random_int(100000, 999999);

            $stmt = $this->conn->prepare(
                'SELECT receipt_no FROM payment_receipts WHERE receipt_no = ? LIMIT 1'
            );

            if (!$stmt) {
                throw new RuntimeException('Failed to prepare receipt check statement');
            }

            $stmt->bind_param('s', $receiptNo);
            $stmt->execute();
            $stmt->store_result();

            $exists = $stmt->num_rows > 0;
            $stmt->close();

            $attempt++;

            if ($attempt >= $maxAttempts) {
                throw new RuntimeException('Unable to generate unique receipt number');
            }
        } while ($exists);

        return $receiptNo;
    }

    private function render(): void
    {
        $receiptItems = [];
        foreach ($this->items as $item) {
            $receiptItems[] = [
                'label'  => (string) ($item['name'] ?? ''),
                'amount' => (float) ($item['amount_paid'] ?? 0),
            ];
        }

        $html = xander_receipt_render_stored_html([
            'receipt_no'    => $this->receiptNo,
            'student_id'    => (int) ($this->receipt['application_id'] ?? 0),
            'student_name'  => $this->studentName,
            'package_title' => $this->packageTitle !== 'N/A' ? $this->packageTitle : '',
            'currency'      => $this->currency,
            'items'         => $receiptItems,
            'total'         => (float) ($this->receipt['total_amount'] ?? 0),
            'method'        => (string) ($this->receipt['payment_method'] ?? ''),
            'created_at'    => date('Y-m-d H:i', strtotime((string) ($this->receipt['created_at'] ?? 'now'))),
        ], $this->branding);

        $printExtras = <<<'HTML'
<style>
@media screen {
    body { padding: 20px; }
    .page-frame { max-width: 210mm; margin: 0 auto; box-shadow: 0 8px 32px rgba(1, 47, 107, 0.12); }
}
@media print {
    body { background: #fff !important; padding: 0 !important; }
    .page-frame { box-shadow: none; }
}
</style>
<script>
window.addEventListener('load', function () { window.print(); });
window.onafterprint = function () { setTimeout(function () { window.close(); }, 300); };
</script>
HTML;

        echo str_replace('</head>', $printExtras . '</head>', $html);
    }

    private function sendError(int $code, string $message): void
    {
        http_response_code($code);
        exit($message);
    }
}

$receiptNo = $_GET['receipt_no'] ?? '';
(new ReceiptGenerator($conn))->generate($receiptNo);
