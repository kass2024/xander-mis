<?php
/**
 * Simple WhatsApp Icon Replacement
 * 
 * This script replaces the current chat icon with a clean WhatsApp icon
 * that directly opens WhatsApp chat when clicked
 */

echo "<h2>📱 Simple WhatsApp Icon Replacement</h2>\n";

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

// Update WhatsApp number
$content = str_replace(
    '$whatsapp_number = \'+1 (450) 390-8614\';',
    '$whatsapp_number = \'+14389009784\';',
    $content
);

echo "<p>✅ Updated WhatsApp number to +14389009784</p>\n";

// Add simple WhatsApp icon CSS
$whatsappCSS = '
    /* ===== SIMPLE WHATSAPP ICON ===== */
    .footer-whatsapp-icon {
        position: fixed;
        right: 30px;
        bottom: 30px;
        width: 60px;
        height: 60px;
        background: #25D366;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);
        transition: all 0.3s ease;
        z-index: 1000;
        cursor: pointer;
        text-decoration: none;
    }

    .footer-whatsapp-icon:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 20px rgba(37, 211, 102, 0.4);
    }

    .footer-whatsapp-icon svg {
        width: 32px;
        height: 32px;
        color: white;
    }

    @media (max-width: 768px) {
        .footer-whatsapp-icon {
            width: 50px;
            height: 50px;
            right: 20px;
            bottom: 20px;
        }
        .footer-whatsapp-icon svg {
            width: 28px;
            height: 28px;
        }
    }';

// Insert CSS before closing style tag
$content = str_replace('</style>', $whatsappCSS . '</style>', $content);

// Create the simple WhatsApp icon HTML
$whatsappIcon = '
    <!-- Simple WhatsApp Icon -->
    <a href="https://wa.me/<?php echo str_replace([\'+\', \' \', \'(\', \')\', \'-\'], \'\', $whatsapp_number); ?>" 
       target="_blank" 
       rel="noopener noreferrer"
       class="footer-whatsapp-icon" 
       title="Chat on WhatsApp">
        <svg viewBox="0 0 24 24" fill="currentColor">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.149-.67.149-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414-.074-.123-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
        </svg>
    </a>';

// Remove the entire chat system and replace with simple WhatsApp icon
$chatSystemPattern = '/<\?php if \(\$chat_enabled\)\: \?>.*?<\?php endif; \?>/s';

if (preg_match($chatSystemPattern, $content, $matches)) {
    $newChatSystem = '<?php if ($chat_enabled): ?>' . $whatsappIcon . '<?php endif; ?>';
    $content = preg_replace($chatSystemPattern, $newChatSystem, $content);
    echo "<p>✅ Replaced entire chat system with simple WhatsApp icon</p>\n";
} else {
    echo "<p style='color: orange;'>⚠️ Could not find chat system pattern - trying alternative approach</p>\n";
    
    // Alternative: Find and replace just the chat button
    $patterns = [
        '/<div class="footer-chat-image-btn"[^>]*>.*?<\/div>/s',
        '/<div class="footer-chat-system">.*?<\/div>/s',
        '/<!-- ================= MODERN CHAT SYSTEM ================= -->.*?<\?php endif; \?>/s'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $whatsappIcon, $content);
            echo "<p>✅ Replaced chat system with WhatsApp icon</p>\n";
            break;
        }
    }
}

// Write the updated content
if (file_put_contents($footerFile, $content)) {
    echo "<p style='color: green;'>✅ Footer updated successfully!</p>\n";
    
    echo "<h3>📋 What Was Done:</h3>\n";
    echo "<ul>\n";
    echo "<li>✅ Removed complex chat bubble system</li>\n";
    echo "<li>✅ Added simple, clean WhatsApp icon</li>\n";
    echo "<li>✅ Updated WhatsApp number to +14389009784</li>\n";
    echo "<li>✅ Icon is green WhatsApp color (#25D366)</li>\n";
    echo "<li>✅ Direct link to WhatsApp chat</li>\n";
    echo "<li>✅ Hover effects and responsive design</li>\n";
    echo "</ul>\n";
    
    echo "<h3>🎯 Result:</h3>\n";
    echo "<p>You now have a clean, simple WhatsApp icon in the bottom-right corner</p>\n";
    echo "<p>When users click it, it directly opens WhatsApp chat with +14389009784</p>\n";
    echo "<p>No complex chat bubble - just a straightforward WhatsApp icon!</p>\n";
    
} else {
    echo "<p style='color: red;'>❌ Failed to write updated footer</p>\n";
}

echo "<hr>\n";
echo "<h3>🎨 Icon Features:</h3>\n";
echo "<ul>\n";
echo "<li>📱 <strong>Official WhatsApp SVG icon</strong></li>\n";
echo "<li>🎨 <strong>WhatsApp green color</strong> (#25D366)</li>\n";
echo "<li>👆 <strong>Direct WhatsApp link</strong> - no intermediate steps</li>\n";
echo "<li>📱 <strong>Mobile responsive</strong> - smaller on phones</li>\n";
echo "<li>✨ <strong>Hover effects</strong> - scales up on hover</li>\n";
echo "<li>🔗 <strong>Opens in new tab</strong> - WhatsApp web/app</li>\n";
echo "</ul>\n";

echo "<h3>🚀 How to Apply:</h3>\n";
echo "<ol>\n";
echo "<li>Visit: <code>http://localhost/Xander/simple_whatsapp_icon.php</code></li>\n";
echo "<li>The script will automatically update your footer</li>\n";
echo "<li>Refresh your website to see the new WhatsApp icon</li>\n";
echo "<li>Test clicking it to ensure WhatsApp opens correctly</li>\n";
echo "</ol>\n";

echo "<h3>🔧 Manual Alternative:</h3>\n";
echo "<p>If the script doesn't work, you can manually:</p>\n";
echo "<ol>\n";
echo "<li>Open <code>c:\\xampp\\htdocs\\Xander\\footer.php</code></li>\n";
echo "<li>Find the chat system section (around line 1783)</li>\n";
echo "<li>Replace the entire chat system with the WhatsApp icon HTML</li>\n";
echo "<li>Add the CSS styles before </style></li>\n";
echo "</ol>\n";

?>
