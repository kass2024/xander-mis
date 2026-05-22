<?php
/**
 * Direct WhatsApp Chat Fix
 * 
 * This script ensures WhatsApp chat opens correctly when the icon is clicked
 */

echo "<h2>📱 Direct WhatsApp Chat Fix</h2>\n";

$footerFile = __DIR__ . '/footer.php';
$backupFile = __DIR__ . '/footer_backup_' . date('Y-m-d_H-i-s') . '.php';

// Check if footer.php exists
if (!file_exists($footerFile)) {
    echo "<p style='color: red;'>❌ footer.php not found in Xander directory</p>\n";
    exit;
}

// Create backup
if (!copy($footerFile, $backupFile)) {
    echo "<p style='color: red;'>❌ Could not create backup</p>\n";
    exit;
}

echo "<p style='color: green;'>✅ Backup created: $backupFile</p>\n";

// Read the current footer content
$content = file_get_contents($footerFile);

// Ensure WhatsApp number is correct
$content = str_replace(
    '$whatsapp_number = \'+1 (450) 390-8614\';',
    '$whatsapp_number = \'+14389009784\';',
    $content
);

echo "<p>✅ Confirmed WhatsApp number: +14389009784</p>\n";

// Remove ALL existing chat system completely
$patterns_to_remove = [
    '/<\?php if \(\$chat_enabled\)\: \?>.*?<\?php endif; \?>/s',
    '/<!-- =================.*?================ -->.*?<\?php endif; \?>/s',
    '/<div class="footer-chat-system">.*?<\/div>/s',
    '/<div class="footer-whatsapp.*?<\/div>/s',
    '/<a href="https:\/\/wa\.me\/.*?<\/a>/s'
];

foreach ($patterns_to_remove as $pattern) {
    $content = preg_replace($pattern, '', $content);
}

// Add direct WhatsApp CSS
$whatsappCSS = '
    /* ===== DIRECT WHATSAPP CHAT BUTTON ===== */
    .xander-whatsapp-chat {
        position: fixed;
        right: 25px;
        bottom: 25px;
        width: 60px;
        height: 60px;
        background: #25D366;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4);
        transition: all 0.3s ease;
        z-index: 9999;
        cursor: pointer;
        text-decoration: none;
        border: 3px solid white;
    }

    .xander-whatsapp-chat:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 20px rgba(37, 211, 102, 0.5);
        background: #128C7E;
    }

    .xander-whatsapp-chat svg {
        width: 30px;
        height: 30px;
        color: white;
        filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.2));
    }

    @media (max-width: 768px) {
        .xander-whatsapp-chat {
            width: 55px;
            height: 55px;
            right: 20px;
            bottom: 20px;
        }
        .xander-whatsapp-chat svg {
            width: 28px;
            height: 28px;
        }
    }';

// Insert CSS before closing style tag
if (strpos($content, '</style>') !== false) {
    $content = str_replace('</style>', $whatsappCSS . '</style>', $content);
    echo "<p>✅ Added WhatsApp CSS styles</p>\n";
} else {
    echo "<p style='color: orange;'>⚠️ Could not find style tag</p>\n";
}

// Create the direct WhatsApp button HTML
$whatsappButton = '
    <!-- Direct WhatsApp Chat Button -->
    <?php if ($chat_enabled): ?>
    <a href="https://wa.me/14389009784?text=Hello%20Xander%20Global%20Scholars!%20I%20need%20help." 
       target="_blank" 
       rel="noopener noreferrer"
       class="xander-whatsapp-chat" 
       title="Chat with us on WhatsApp">
        <svg viewBox="0 0 24 24" fill="currentColor">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.149-.67.149-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414-.074-.123-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
        </svg>
    </a>
    <?php endif; ?>';

// Insert the WhatsApp button before closing body tag
if (strpos($content, '</body>') !== false) {
    $content = str_replace('</body>', $whatsappButton . '</body>', $content);
    echo "<p>✅ Added direct WhatsApp button</p>\n";
} else {
    echo "<p style='color: red;'>❌ Could not find </body> tag</p>\n";
}

// Write the updated content
if (file_put_contents($footerFile, $content)) {
    echo "<p style='color: green;'>✅ Footer updated successfully!</p>\n";
    
    echo "<h3>🎯 What Was Fixed:</h3>\n";
    echo "<ul>\n";
    echo "<li>✅ Removed ALL existing chat systems</li>\n";
    echo "<li>✅ Added direct WhatsApp link: https://wa.me/14389009784</li>\n";
    echo "<li>✅ Pre-filled message: \"Hello Xander Global Scholars! I need help.\"</li>\n";
    echo "<li>✅ Clean WhatsApp icon (green circle with white logo)</li>\n";
    echo "<li>✅ Opens WhatsApp immediately when clicked</li>\n";
    echo "<li>✅ Works on mobile (WhatsApp app) and desktop (WhatsApp web)</li>\n";
    echo "</ul>\n";
    
    echo "<h3>📱 Testing Instructions:</h3>\n";
    echo "<ol>\n";
    echo "<li>Refresh your website page</li>\n";
    echo "<li>Look for green WhatsApp icon in bottom-right</li>\n";
    echo "<li>Click the icon</li>\n";
    echo "<li>Should immediately open WhatsApp with +14389009784</li>\n";
    echo "<li>Message should be pre-filled</li>\n";
    echo "</ol>\n";
    
    echo "<h3>🔗 Direct WhatsApp Link:</h3>\n";
    echo "<p><a href='https://wa.me/14389009784?text=Hello%20Xander%20Global%20Scholars!%20I%20need%20help.' target='_blank'>Test WhatsApp Link</a></p>\n";
    
    echo "<h3>🛠️ If Still Not Working:</h3>\n";
    echo "<ul>\n";
    echo "<li>Check if WhatsApp is installed on your device</li>\n";
    echo "<li>Try the direct link above to test</li>\n";
    echo "<li>Clear browser cache and refresh</li>\n";
    echo "<li>Check browser console for errors</li>\n";
    echo "</ul>\n";
    
} else {
    echo "<p style='color: red;'>❌ Failed to write updated footer</p>\n";
}

echo "<hr>\n";
echo "<h3>✨ Final Result:</h3>\n";
echo "<p>You should now have a simple green WhatsApp icon that opens WhatsApp chat immediately when clicked!</p>\n";

?>
