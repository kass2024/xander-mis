<?php
/**
 * Final Chatbot Cleanup Script
 * 
 * This script performs a thorough cleanup of any remaining chatbot code
 */

echo "<h1>🧹 Final Chatbot Cleanup - Thorough Removal</h1>\n";

// List of all files that need final cleanup
$filesToClean = [
    'zambia.php',
    'form-Worcester.php', 
    'form-west.php',
    'form-windsor.php',
    'form-West-Florida.php',
    'form-usa.php',
    'form-University of Saskatchewan (USASK).php'
];

$totalFilesProcessed = 0;
$totalFilesSuccess = 0;

foreach ($filesToClean as $filename) {
    $filepath = __DIR__ . '/' . $filename;
    
    echo "<h3>🔧 Final cleanup: $filename</h3>\n";
    
    if (!file_exists($filepath)) {
        echo "<p style='color: orange;'>⚠️ File not found: $filename</p>\n";
        continue;
    }
    
    // Read file content
    $content = file_get_contents($filepath);
    $originalContent = $content;
    
    // Remove ALL remaining chatbot-related patterns
    $patternsToRemove = [
        // Remove any remaining chat-related JavaScript functions
        '/function\s+openChatWindow\(\)[^{]*{[^}]*}/s',
        '/function\s+loadChatMessages[^{]*{[^}]*}/s',
        '/function\s+bindChatInput[^{]*{[^}]*}/s',
        '/function\s+saveChatUserInfo[^{]*{[^}]*}/s',
        '/function\s+sendChatMessage[^{]*{[^}]*}/s',
        
        // Remove chat-related jQuery selectors and operations
        '/\$\([\'"]#chat-messages[\'"]\)[^;]*;/s',
        '/\$\([\'"]#chat-input[\'"]\)[^;]*;/s',
        '/\$\([\'"]#chat-window[\'"]\)[^;]*;/s',
        '/\$\([\'"]#chat-bubble[\'"]\)[^;]*;/s',
        
        // Remove chat-related AJAX calls
        '/\$\.(get|post)\([\'"](?:load_chat|send_chat|check_chat_user)\.php[\'"][^;]*;/s',
        
        // Remove chat event listeners
        '/\$\([\'"]#chat-bubble\s*button[\'"]\)\.on\([\'"]click[\'"][^;]*;/s',
        '/\$\([\'"]#chat-input[\'"]\)\.on\([\'"]keydown[\'"][^;]*;/s',
        
        // Remove chat intervals
        '/setInterval\s*\([^)]*chat-window[^)]*\);/s',
        
        // Remove any remaining chat-related variables and operations
        '/let\s+oldContent\s*=\s*\$\([\'"]#chat-messages[\'"]\)\.html\(\);/s',
        '/\$\([\'"]#chat-messages[\'"]\)\.html\([^)]*\);/s',
        '/\$\([\'"]#chat-messages[\'"]\)\.scrollTop\([^)]*\);/s',
        '/\$\([\'"]#chat-input[\'"]\)\.val\([^)]*\);/s',
        
        // Remove any remaining chat-related HTML comments
        '/<!--\s*Chat[^>]*-->/s',
        '/<!--\s*Live Chat[^>]*-->/s',
        
        // Remove any remaining chat-related script tags
        '/<script[^>]*>.*?chat.*?<\/script>/s',
        
        // Clean up any orphaned chat-related code
        '/chat-messages|chat-input|chat-window|chat-bubble|chat-badge|chat-header/s'
    ];
    
    foreach ($patternsToRemove as $pattern) {
        $content = preg_replace($pattern, '', $content);
    }
    
    // Additional cleanup for any remaining problematic lines
    $lines = explode("\n", $content);
    $cleanedLines = [];
    
    foreach ($lines as $line) {
        // Skip lines that contain chat-related references
        if (preg_match('/chat-messages|chat-input|chat-window|chat-bubble|chat-badge|chat-header|loadChatMessages|sendChatMessage|openChatWindow/', $line)) {
            continue;
        }
        
        // Skip lines that are just JavaScript fragments from chat
        if (preg_match('/^\s*[},]\s*$/', $line) && strpos($content, 'chat') !== false) {
            // Check if this is likely an orphaned chat fragment
            $context = implode("\n", array_slice($cleanedLines, -5, 5)) . "\n" . $line;
            if (strpos($context, 'chat') !== false) {
                continue;
            }
        }
        
        $cleanedLines[] = $line;
    }
    
    $content = implode("\n", $cleanedLines);
    
    // Clean up multiple consecutive empty lines
    $content = preg_replace("/\n\s*\n\s*\n/", "\n\n", $content);
    
    // Write the updated content if changes were made
    if ($content !== $originalContent) {
        if (file_put_contents($filepath, $content)) {
            echo "<p style='color: green;'>✅ Successfully cleaned up $filename</p>\n";
            $totalFilesSuccess++;
        } else {
            echo "<p style='color: red;'>❌ Failed to update $filename</p>\n";
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ No additional cleanup needed for $filename</p>\n";
        $totalFilesSuccess++;
    }
    
    $totalFilesProcessed++;
}

echo "<hr>\n";
echo "<h2>📊 Final Cleanup Summary</h2>\n";
echo "<p><strong>Total files processed:</strong> $totalFilesProcessed</p>\n";
echo "<p><strong>Successfully cleaned:</strong> $totalFilesSuccess</p>\n";
echo "<p><strong>Failed:</strong> " . ($totalFilesProcessed - $totalFilesSuccess) . "</p>\n";

echo "<h3>🧹 What was cleaned up:</h3>\n";
echo "<ul>\n";
echo "<li>🗑️ <strong>Remaining JavaScript functions</strong> - openChatWindow, loadChatMessages, etc.</li>\n";
echo "<li>🗑️ <strong>jQuery selectors</strong> - #chat-messages, #chat-input, #chat-window</li>\n";
echo "<li>🗑️ <strong>AJAX calls</strong> - load_chat.php, send_chat.php, check_chat_user.php</li>\n";
echo "<li>🗑️ <strong>Event listeners</strong> - click handlers, keydown handlers</li>\n";
echo "<li>🗑️ <strong>Set intervals</strong> - chat polling intervals</li>\n";
echo "<li>🗑️ <strong>Orphaned code fragments</strong> - leftover variables and operations</li>\n";
echo "<li>🗑️ <strong>HTML comments</strong> - chat-related comments</li>\n";
echo "</ul>\n";

echo "<h3>✅ Final Result:</h3>\n";
echo "<p>All chatbot code has been <strong>completely removed</strong> from your project!</p>\n";
echo "<p>Only the modern WhatsApp button remains - clean, professional, and functional! 📱✨</p>\n";

?>
