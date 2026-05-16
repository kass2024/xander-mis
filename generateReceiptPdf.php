<?php
function generateReceiptPdf(string $receiptHtml, string $receiptNo): void
{
    $pdfDir = __DIR__ . '/receipts';
    if (!is_dir($pdfDir)) {
        mkdir($pdfDir, 0755, true);
    }
    
    $pdfFile = $pdfDir . '/' . $receiptNo . '.pdf';

    $autoload = __DIR__ . '/vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
    }

    if (class_exists('Dompdf\\Dompdf')) {
        try {
            $options = new \Dompdf\Options();
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('dpi', 144);
            $options->set('defaultFont', 'DejaVu Sans');

            $httpContext = stream_context_create([
                'http' => [
                    'timeout' => 12,
                    'follow_location' => 1,
                    'user_agent' => 'XanderReceiptBot/1.0',
                ],
                'https' => [
                    'timeout' => 12,
                    'follow_location' => 1,
                    'user_agent' => 'XanderReceiptBot/1.0',
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);
            $options->setHttpContext($httpContext);

            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($receiptHtml);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            file_put_contents($pdfFile, $dompdf->output());
            error_log("Receipt PDF generated: $receiptNo");
            return;
        } catch (\Throwable $e) {
            error_log("Receipt PDF generation failed for $receiptNo: " . $e->getMessage());
        }
    }

    file_put_contents($pdfFile . '.html', $receiptHtml);
    error_log("Receipt HTML saved (PDF lib missing): $receiptNo");
}
?>
