<?php
require 'C:/xampp/htdocs/Xander/db.php';
$row = $conn->query("SELECT contract_token FROM student_contracts WHERE status='draft' ORDER BY id DESC LIMIT 1")->fetch_assoc();
if (!$row) { echo "no draft\n"; exit(1); }
$token = $row['contract_token'];
$html = file_get_contents("http://localhost/xander/student-contract.php?token=" . urlencode($token));
echo "HTML len: " . strlen($html) . "\n";
if (strpos($html, 'Fatal error') !== false) echo substr($html, 0, 800) . "\n";
preg_match_all('/class="package-item"/', $html, $m);
echo "package-item count: " . count($m[0]) . "\n";
preg_match_all('/name="package"/', $html, $m2);
echo "package radios: " . count($m2[0]) . "\n";
echo strpos($html, 'bc-fee-select-hint') !== false ? "hint ok\n" : "hint missing\n";
