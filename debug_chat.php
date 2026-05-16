<?php
// debug_chat.php - Test chat router functionality
header('Content-Type: text/html; charset=utf-8');
echo '<h2>Chat Router Debug</h2>';
echo '<pre>';

// 1. Check PHP version
echo "PHP Version: " . phpversion() . "\n\n";

// 2. Check if curl is enabled
echo "cURL Enabled: " . (function_exists('curl_version') ? 'YES' : 'NO') . "\n";
if (function_exists('curl_version')) {
    $curl_info = curl_version();
    echo "cURL Version: " . $curl_info['version'] . "\n";
    echo "SSL Version: " . $curl_info['ssl_version'] . "\n\n";
}

// 3. Check file permissions
$files = [
    'chat_router.php',
    'configi-ai-xander.php',
    'db.php',
    'ai_knowledge.php'
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    echo "File: $file\n";
    echo "  Exists: " . (file_exists($path) ? 'YES' : 'NO') . "\n";
    if (file_exists($path)) {
        echo "  Readable: " . (is_readable($path) ? 'YES' : 'NO') . "\n";
        echo "  Size: " . filesize($path) . " bytes\n";
        echo "  Last Modified: " . date('Y-m-d H:i:s', filemtime($path)) . "\n";
    }
    echo "\n";
}

// 4. Test database connection
echo "Testing database connection...\n";
if (file_exists(__DIR__ . '/db.php')) {
    require_once __DIR__ . '/db.php';
    if (isset($conn) && $conn instanceof mysqli) {
        echo "  Database: Connected\n";
        echo "  Server: " . $conn->server_info . "\n";
        echo "  Host: " . $conn->host_info . "\n";
        
        // Test if tables exist
        $tables = ['chat_sessions', 'chat_messages'];
        foreach ($tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            echo "  Table '$table': " . ($result->num_rows > 0 ? 'EXISTS' : 'MISSING') . "\n";
        }
    } else {
        echo "  Database: NOT CONNECTED\n";
    }
}

// 5. Test config file
echo "\nTesting config file...\n";
if (file_exists(__DIR__ . '/configi-ai-xander.php')) {
    require_once __DIR__ . '/configi-ai-xander.php';
    
    $constants = get_defined_constants(true)['user'] ?? [];
    foreach ($constants as $name => $value) {
        if (stripos($name, 'API') !== false || stripos($name, 'KEY') !== false) {
            echo "  $name: " . (empty($value) ? 'EMPTY' : substr($value, 0, 10) . '...') . "\n";
        }
    }
}

// 6. Test API connectivity
echo "\nTesting OpenAI API connectivity...\n";
$api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : (defined('AI_API_KEY') ? AI_API_KEY : '');
if (!empty($api_key)) {
    $test_url = 'https://api.openai.com/v1/models';
    $ch = curl_init($test_url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $api_key"
        ],
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false, // Try without SSL verification
        CURLOPT_SSL_VERIFYHOST => 0
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "  HTTP Code: $http_code\n";
    echo "  cURL Error: " . ($error ? $error : 'None') . "\n";
    
    if ($http_code == 200) {
        echo "  API Connection: SUCCESS\n";
    } elseif ($http_code == 401) {
        echo "  API Connection: UNAUTHORIZED (Check API key)\n";
    } else {
        echo "  API Connection: FAILED (Code: $http_code)\n";
    }
} else {
    echo "  API Connection: NO API KEY FOUND\n";
}

echo '</pre>';

// 7. Create a simple test form
echo '<h3>Test Chat Form</h3>';
echo '<form id="testForm">
    <input type="hidden" name="session" value="test_' . time() . '">
    <textarea name="message" rows="3" cols="50">Hello, can you help me?</textarea><br>
    <button type="button" onclick="testChat()">Test Chat</button>
</form>
<div id="result"></div>

<script>
function testChat() {
    const form = document.getElementById("testForm");
    const formData = new FormData(form);
    const data = {
        session: formData.get("session"),
        message: formData.get("message")
    };
    
    fetch("chat_router.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById("result").innerHTML = 
            "<strong>Response:</strong><br>" + data.reply;
    })
    .catch(error => {
        document.getElementById("result").innerHTML = 
            "<strong>Error:</strong><br>" + error;
    });
}
</script>';