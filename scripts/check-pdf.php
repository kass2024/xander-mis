<?php
$path = 'C:/xampp/htdocs/Xander/uploads/contracts/contract_2.pdf';
if (!file_exists($path)) {
    echo "PDF missing\n";
    exit(1);
}
echo "Size: " . filesize($path) . " bytes\n";
$raw = file_get_contents($path);
echo "Pages (PDF /Count): " . (preg_match_all('/\/Type\s*\/Page[^s]/', $raw) ?: 0) . "\n";
echo (strpos($raw, 'Page 1 of') !== false || preg_match('/Page\s+\d+\s+of\s+\d+/', $raw) ? "Page number text likely present\n" : "Page number text NOT found in raw PDF\n");
