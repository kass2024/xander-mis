<?php
ob_start();
header('Content-Type: application/json');

require_once 'db.php';
require_once 'configi-ai-xander.php';
require_once 'ai_knowledge.php';

$data = json_decode(file_get_contents('php://input'), true);

$sessionId = $data['session'] ?? '';
$message   = trim($data['message'] ?? '');

if (!$sessionId || $message === '') {
  echo json_encode(['reply' => 'Invalid request']);
  exit;
}

/* ===== ENSURE SESSION EXISTS ===== */
$stmt = $conn->prepare("
  INSERT IGNORE INTO chat_sessions (session_id)
  VALUES (?)
");
$stmt->bind_param('s', $sessionId);
$stmt->execute();

/* ===== STORE USER MESSAGE ===== */
$stmt = $conn->prepare("
  INSERT INTO chat_messages (session_id, sender, message)
  VALUES (?, 'user', ?)
");
$stmt->bind_param('ss', $sessionId, $message);
$stmt->execute();

/* ===== CHECK MODE ===== */
$stmt = $conn->prepare("
  SELECT mode FROM chat_sessions WHERE session_id = ?
");
$stmt->bind_param('s', $sessionId);
$stmt->execute();
$stmt->bind_result($mode);
$stmt->fetch();
$stmt->close();

/* ===== HUMAN MODE ===== */
if ($mode === 'human') {
  echo json_encode([
    'reply' => 'A live advisor is reviewing your message and will respond shortly.'
  ]);
  exit;
}

/* ===== AI MODE ===== */
$systemPrompt = "You are MISA, the AI assistant of Xander Global Scholars.\n\n$XGS_KNOWLEDGE";

$payload = [
  "model" => "gpt-4o-mini",
  "messages" => [
    ["role" => "system", "content" => $systemPrompt],
    ["role" => "user", "content" => $message]
  ],
  "temperature" => 0.6,
  "max_tokens" => 300
];

$ch = curl_init("https://api.openai.com/v1/chat/completions");
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_HTTPHEADER => [
    "Content-Type: application/json",
    "Authorization: Bearer " . OPENAI_API_KEY
  ],
  CURLOPT_POSTFIELDS => json_encode($payload),
  CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
$reply = $data['choices'][0]['message']['content'] ?? 'Unable to respond now.';

/* ===== STORE AI MESSAGE ===== */
$stmt = $conn->prepare("
  INSERT INTO chat_messages (session_id, sender, message)
  VALUES (?, 'ai', ?)
");
$stmt->bind_param('ss', $sessionId, $reply);
$stmt->execute();

/* ===== UPDATE SESSION ===== */
$conn->query("
  UPDATE chat_sessions SET updated_at = NOW()
  WHERE session_id = '$sessionId'
");

ob_clean();
echo json_encode(['reply' => nl2br(htmlspecialchars($reply))]);
exit;
