<?php
/**
 * CHAT ROUTER – XANDER GLOBAL SCHOLARS (AI ↔ HUMAN)
 * Production Ready – MySQLi Only
 */

ob_start();
header('Content-Type: application/json');

/* ================= SECURITY ================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['reply' => 'Method not allowed']);
    exit;
}

/* ================= LOAD CORE ================= */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/configi-ai-xander.php';
require_once __DIR__ . '/ai_knowledge.php';

if (!defined('OPENAI_API_KEY') || OPENAI_API_KEY === '') {
    echo json_encode(['reply' => 'Server configuration error']);
    exit;
}

/* ================= READ INPUT ================= */
$input = json_decode(file_get_contents('php://input'), true);

$sessionId = trim($input['session'] ?? '');
$userMsg   = trim($input['message'] ?? '');

if ($sessionId === '' || $userMsg === '') {
    echo json_encode(['reply' => 'Invalid request']);
    exit;
}

/* ================= ENSURE SESSION ================= */
$stmt = $conn->prepare("
  INSERT INTO chat_sessions (session_id, mode, status, updated_at)
  VALUES (?, 'ai', 'open', NOW())
  ON DUPLICATE KEY UPDATE updated_at = NOW()
");
$stmt->bind_param('s', $sessionId);
$stmt->execute();
$stmt->close();

/* ================= STORE USER MESSAGE ================= */
$stmt = $conn->prepare("
  INSERT INTO chat_messages (session_id, sender, message, created_at, delivered)
  VALUES (?, 'user', ?, NOW(), 1)
");
$stmt->bind_param('ss', $sessionId, $userMsg);
$stmt->execute();
$stmt->close();

/* ================= GET CHAT MODE ================= */
$stmt = $conn->prepare("
  SELECT mode FROM chat_sessions WHERE session_id = ?
");
$stmt->bind_param('s', $sessionId);
$stmt->execute();
$stmt->bind_result($mode);
$stmt->fetch();
$stmt->close();

$mode = $mode ?: 'ai';

/* ================= LIVE AGENT MODE ================= */
if ($mode === 'human') {
    echo json_encode([
        'reply' =>
        'A live advisor is reviewing your message and will respond shortly.'
    ]);
    exit;
}

/* ================= CONTENT SAFETY ================= */
$blockedWords = [
    'illegal',
    'fake visa',
    'bribe',
    'guarantee visa',
    'forged documents'
];

foreach ($blockedWords as $word) {
    if (stripos($userMsg, $word) !== false) {

        $safeReply =
            'I can’t assist with that request. Please speak with a certified Xander Global Scholars advisor.';

        $stmt = $conn->prepare("
          INSERT INTO chat_messages (session_id, sender, message, created_at, delivered)
          VALUES (?, 'ai', ?, NOW(), 1)
        ");
        $stmt->bind_param('ss', $sessionId, $safeReply);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['reply' => $safeReply]);
        exit;
    }
}

/* ================= SYSTEM PROMPT ================= */
$systemPrompt = <<<PROMPT
You are MISA, the official AI Education & Global Mobility Assistant of Xander Global Scholars (XGS).

RULES:
- Use ONLY verified XGS knowledge
- Never invent universities, scholarships, or guarantees
- Never promise visa approval
- Be professional, clear, and supportive
- Suggest speaking with a certified advisor when appropriate

================= XGS KNOWLEDGE BASE =================
$XGS_KNOWLEDGE
=====================================================
PROMPT;

/* ================= OPENAI REQUEST ================= */
$payload = [
    "model" => "gpt-4o-mini",
    "messages" => [
        ["role" => "system", "content" => $systemPrompt],
        ["role" => "user", "content" => $userMsg]
    ],
    "temperature" => 0.6,
    "max_tokens" => 350
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
$aiReply = $data['choices'][0]['message']['content']
    ?? 'I’m currently unavailable. Please contact a human advisor.';

/* ================= STORE AI MESSAGE ================= */
$stmt = $conn->prepare("
  INSERT INTO chat_messages (session_id, sender, message, created_at, delivered)
  VALUES (?, 'ai', ?, NOW(), 1)
");
$stmt->bind_param('ss', $sessionId, $aiReply);
$stmt->execute();
$stmt->close();

/* ================= OUTPUT ================= */
ob_clean();
echo json_encode([
    'reply' => nl2br(htmlspecialchars($aiReply, ENT_QUOTES, 'UTF-8'))
]);
exit;
