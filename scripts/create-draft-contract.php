<?php
declare(strict_types=1);
require __DIR__ . '/../db.php';

$token = bin2hex(random_bytes(32));
$stmt = $conn->prepare("INSERT INTO student_contracts (student_id, contract_token, status, created_at) VALUES (NULL, ?, 'draft', NOW())");
$stmt->bind_param('s', $token);
$stmt->execute();
$stmt->close();

echo "Draft token: {$token}\n";
echo "URL: http://localhost/xander/student-contract.php?token={$token}\n";

$html = file_get_contents("http://localhost/xander/student-contract.php?token=" . urlencode($token));
preg_match_all('/<label class="package-item"/', $html, $labels);
preg_match_all('/ checked/', $html, $checked);
preg_match_all('/data-readonly="0"/', $html, $ro);

echo "label.package-item count: " . count($labels[0]) . "\n";
echo "checked attributes: " . count($checked[0]) . "\n";
echo "readonly=0: " . count($ro[0]) . "\n";
