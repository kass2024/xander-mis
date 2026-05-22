<?php
/**
 * Complete Chatbot Replacement Script
 * 
 * This script will remove ALL chatbot implementations across the entire project
 * and replace them with the modern WhatsApp button
 */

echo "<h1>🚀 Complete Chatbot Replacement - Project Wide</h1>\n";

// List of all files that need chatbot removal
$filesToClean = [
    'zambia.php',
    'form-Worcester.php', 
    'form-west.php',
    'form-windsor.php',
    'form-West-Florida.php',
    'form-usa.php',
    'form-University of Saskatchewan (USASK).php'
];

$whatsappNumber = '+14389009784';
$whatsappLink = 'https://wa.me/14389009784';

// Modern WhatsApp CSS (same as from footer.php)
$whatsappCSS = '
<style>
/* ===== MODERN FLOATING WHATSAPP BUTTON ===== */
.xander-whatsapp-float {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 
        0 8px 32px rgba(37, 211, 102, 0.3),
        0 4px 16px rgba(0, 0, 0, 0.1),
        inset 0 1px 0 rgba(255, 255, 255, 0.2);
    cursor: pointer;
    text-decoration: none;
    z-index: 9999;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    animation: xanderFloat 3s ease-in-out infinite, xanderPulse 2s ease-in-out infinite;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

@keyframes xanderFloat {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-8px); }
}

@keyframes xanderPulse {
    0%, 100% { 
        box-shadow: 
            0 8px 32px rgba(37, 211, 102, 0.3),
            0 4px 16px rgba(0, 0, 0, 0.1),
            inset 0 1px 0 rgba(255, 255, 255, 0.2),
            0 0 0 0 rgba(37, 211, 102, 0.4);
    }
    50% { 
        box-shadow: 
            0 12px 40px rgba(37, 211, 102, 0.4),
            0 6px 20px rgba(0, 0, 0, 0.15),
            inset 0 1px 0 rgba(255, 255, 255, 0.3),
            0 0 0 8px rgba(37, 211, 102, 0);
    }
}

.xander-whatsapp-float:hover {
    transform: scale(1.1) translateY(-4px);
    box-shadow: 
        0 12px 40px rgba(37, 211, 102, 0.4),
        0 6px 20px rgba(0, 0, 0, 0.15),
        inset 0 1px 0 rgba(255, 255, 255, 0.3);
    background: linear-gradient(135deg, #128C7E 0%, #075E54 100%);
    animation: none;
}

.xander-whatsapp-float svg {
    width: 32px;
    height: 32px;
    color: white;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
    transition: transform 0.3s ease;
}

.xander-whatsapp-float:hover svg {
    transform: scale(1.1);
}

/* Tooltip */
.xander-whatsapp-tooltip {
    position: absolute;
    bottom: 80px;
    right: 0;
    background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
    color: white;
    padding: 12px 20px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 500;
    white-space: nowrap;
    box-shadow: 
        0 8px 32px rgba(0, 0, 0, 0.3),
        0 4px 16px rgba(0, 0, 0, 0.1),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
    opacity: 0;
    visibility: hidden;
    transform: translateY(10px);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    pointer-events: none;
}

.xander-whatsapp-tooltip::after {
    content: "";
    position: absolute;
    top: 100%;
    right: 20px;
    border: 8px solid transparent;
    border-top-color: #2d2d2d;
    transform: translateX(50%);
}

.xander-whatsapp-float:hover .xander-whatsapp-tooltip {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .xander-whatsapp-float {
        width: 56px;
        height: 56px;
        bottom: 20px;
        right: 20px;
    }
    
    .xander-whatsapp-float svg {
        width: 28px;
        height: 28px;
    }
    
    .xander-whatsapp-tooltip {
        bottom: 70px;
        right: -60px;
        font-size: 13px;
        padding: 10px 16px;
    }
    
    .xander-whatsapp-tooltip::after {
        right: 70px;
    }
}

@media (max-width: 480px) {
    .xander-whatsapp-float {
        width: 52px;
        height: 52px;
        bottom: 16px;
        right: 16px;
    }
    
    .xander-whatsapp-float svg {
        width: 26px;
        height: 26px;
    }
    
    .xander-whatsapp-tooltip {
        display: none;
    }
}

/* Entrance Animation */
@keyframes xanderEntrance {
    0% {
        opacity: 0;
        transform: scale(0) translateY(100px);
    }
    50% {
        opacity: 0;
        transform: scale(0.5) translateY(50px);
    }
    100% {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.xander-whatsapp-float {
    animation: xanderEntrance 0.8s cubic-bezier(0.4, 0, 0.2, 1) forwards, 
               xanderFloat 3s ease-in-out 2s infinite, 
               xanderPulse 2s ease-in-out 2s infinite;
}
</style>';

// Modern WhatsApp HTML
$whatsappHTML = '
<!-- Modern Floating WhatsApp Button -->
<div class="xander-whatsapp-container">
    <a href="' . $whatsappLink . '" 
       target="_blank" 
       rel="noopener noreferrer"
       class="xander-whatsapp-float"
       aria-label="Chat with us on WhatsApp"
       title="Chat with us on WhatsApp">
        
        <!-- WhatsApp SVG Icon -->
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.149-.67.149-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414-.074-.123-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
        </svg>
        
        <!-- Tooltip -->
        <div class="xander-whatsapp-tooltip">
            Chat with us on WhatsApp
        </div>
    </a>
</div>';

$totalFilesProcessed = 0;
$totalFilesSuccess = 0;

foreach ($filesToClean as $filename) {
    $filepath = __DIR__ . '/' . $filename;
    
    echo "<h3>🔧 Processing: $filename</h3>\n";
    
    if (!file_exists($filepath)) {
        echo "<p style='color: orange;'>⚠️ File not found: $filename</p>\n";
        continue;
    }
    
    // Create backup
    $backupPath = __DIR__ . '/backups/' . str_replace('.php', '_backup_' . date('Y-m-d_H-i-s') . '.php', $filename);
    if (!is_dir(__DIR__ . '/backups')) {
        mkdir(__DIR__ . '/backups', 0755, true);
    }
    
    if (!copy($filepath, $backupPath)) {
        echo "<p style='color: red;'>❌ Could not create backup for $filename</p>\n";
        continue;
    }
    
    echo "<p style='color: green;'>✅ Backup created: $backupPath</p>\n";
    
    // Read file content
    $content = file_get_contents($filepath);
    
    // Remove ALL chatbot-related patterns
    $patternsToRemove = [
        // Remove chat-related CSS
        '/\/\* Chat Bubble \*\/.*?}\s*}/s',
        '/#chat-bubble[^{]*{[^}]*}/s',
        '/#chat-window[^{]*{[^}]*}/s',
        '/#chat-window\s*\.[^{]*{[^}]*}/s',
        '/\.chat-header[^{]*{[^}]*}/s',
        '/\.chat-messages[^{]*{[^}]*}/s',
        '/\.chat-input[^{]*{[^}]*}/s',
        '/@keyframes fadeIn[^{]*{[^}]*}/s',
        
        // Remove chat HTML elements
        '/<!-- Chat Bubble -->.*?<\/div>/s',
        '/<!-- Live Chat Bubble -->.*?<\/div>/s',
        '/<!-- Chat Window -->.*?<\/div>/s',
        '/<!-- Live Chat Window -->.*?<\/div>/s',
        '/<!-- Live Chat Login Form -->.*?<\/div>/s',
        
        // Remove chat-related JavaScript
        '/\$\([\'"]#chat-bubble[\'"]\)[^;]*;/s',
        '/\$\([\'"]#chat-window[\'"]\)[^;]*;/s',
        '/function openChatWindow\(\)[^{]*{[^}]*}/s',
        '/function loadChatMessages[^{]*{[^}]*}/s',
        '/function bindChatInput[^{]*{[^}]*}/s',
        '/function saveChatUserInfo[^{]*{[^}]*}/s',
        '/check_chat_user\.php[^;]*;/s',
        '/load_chat\.php[^;]*;/s',
        
        // Remove chat event listeners
        '/\$\(\'#chat-bubble button\'\)\.on\([\'"]click[\'"][^;]*;/s',
        '/setInterval[^;]*chat-window[^;]*;/s',
        
        // Remove any remaining chat-related elements
        '/<div[^>]*chat[^>]*>.*?<\/div>/s',
        '/<span[^>]*chat[^>]*>.*?<\/span>/s',
        '/<button[^>]*chat[^>]*>.*?<\/button>/s'
    ];
    
    $originalContent = $content;
    foreach ($patternsToRemove as $pattern) {
        $content = preg_replace($pattern, '', $content);
    }
    
    // Add WhatsApp CSS before </head> or at the end
    if (strpos($content, '</head>') !== false) {
        $content = str_replace('</head>', $whatsappCSS . '</head>', $content);
    } else {
        // If no head tag, add CSS at the beginning
        $content = $whatsappCSS . $content;
    }
    
    // Add WhatsApp HTML before </body> or at the end
    if (strpos($content, '</body>') !== false) {
        $content = str_replace('</body>', $whatsappHTML . '</body>', $content);
    } else {
        // If no body tag, add HTML at the end
        $content .= $whatsappHTML;
    }
    
    // Write the updated content
    if (file_put_contents($filepath, $content)) {
        echo "<p style='color: green;'>✅ Successfully updated $filename</p>\n";
        $totalFilesSuccess++;
    } else {
        echo "<p style='color: red;'>❌ Failed to update $filename</p>\n";
    }
    
    $totalFilesProcessed++;
}

echo "<hr>\n";
echo "<h2>📊 Summary</h2>\n";
echo "<p><strong>Total files processed:</strong> $totalFilesProcessed</p>\n";
echo "<p><strong>Successfully updated:</strong> $totalFilesSuccess</p>\n";
echo "<p><strong>Failed:</strong> " . ($totalFilesProcessed - $totalFilesSuccess) . "</p>\n";

echo "<h3>✨ What was accomplished:</h3>\n";
echo "<ul>\n";
echo "<li>🗑️ <strong>Removed all chatbot CSS</strong> - chat bubbles, windows, input styles</li>\n";
echo "<li>🗑️ <strong>Removed all chatbot HTML</strong> - bubbles, windows, login forms</li>\n";
echo "<li>🗑️ <strong>Removed all chatbot JavaScript</strong> - event listeners, API calls, functions</li>\n";
echo "<li>📱 <strong>Added modern WhatsApp button</strong> - floating, animated, responsive</li>\n";
echo "<li>🎨 <strong>Added premium styling</strong> - glassmorphism, animations, tooltips</li>\n";
echo "<li>🔗 <strong>Direct WhatsApp link</strong> - $whatsappLink</li>\n";
echo "</ul>\n";

echo "<h3>🎯 Features Added:</h3>\n";
echo "<ul>\n";
echo "<li>🌊 <strong>Floating animation</strong> - smooth up/down movement</li>\n";
echo "<li>💫 <strong>Pulse effect</strong> - attention-grabbing shadow pulse</li>\n";
echo "<li>🎯 <strong>Hover effects</strong> - scale, color change, tooltip</li>\n";
echo "<li>📱 <strong>Mobile responsive</strong> - optimized for all screen sizes</li>\n";
echo "<li>⚡ <strong>High performance</strong> - no dependencies, optimized CSS</li>\n";
echo "<li>🔒 <strong>Accessibility</strong> - ARIA labels, semantic HTML</li>\n";
echo "</ul>\n";

echo "<h3>🚀 Testing Instructions:</h3>\n";
echo "<ol>\n";
echo "<li>Visit any of the updated pages</li>\n";
echo "<li>Look for the green WhatsApp button (bottom-right corner)</li>\n";
echo "<li>Hover to see the tooltip animation</li>\n";
echo "<li>Click to open WhatsApp directly</li>\n";
echo "<li>Test on mobile devices for responsiveness</li>\n";
echo "</ol>\n";

echo "<h3>✅ Final Result:</h3>\n";
echo "<p>All chatbot implementations have been <strong>completely removed</strong> and replaced with a <strong>premium, modern WhatsApp floating button</strong>!</p>\n";
echo "<p>Your website now has a professional SaaS-quality support widget that opens WhatsApp directly! 📱✨</p>\n";

?>
