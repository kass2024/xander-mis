<?php
require 'C:/xampp/htdocs/Xander/db.php';
require 'C:/xampp/htdocs/Xander/generate-contract-pdf.php';

$id = (int)($argv[1] ?? 2);
$path = generateContractPDF($id);
echo "Generated: {$path}\n";
