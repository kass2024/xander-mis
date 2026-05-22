<?php
/**
 * WhatsApp Footer Update Script
 * 
 * This script updates the footer.php file to replace live chat with WhatsApp
 */

echo "<h2>📱 WhatsApp Footer Update</h2>\n";

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

// Add WhatsApp CSS before closing style tag
$whatsappCSS = '
    /* ===== WHATSAPP BUTTON STYLES ===== */
    .footer-whatsapp-btn {
        position: fixed;
        right: 30px;
        bottom: 30px;
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        box-shadow: 0 10px 25px rgba(37, 211, 102, 0.4), 0 6px 10px rgba(0, 0, 0, 0.2);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 1000;
        animation: floatWhatsAppButton 3s ease-in-out infinite;
    }

    @keyframes floatWhatsAppButton {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }

    .footer-whatsapp-btn:hover {
        transform: scale(1.1) rotate(5deg);
        animation: none;
        box-shadow: 0 15px 35px rgba(37, 211, 102, 0.5), 0 8px 15px rgba(0, 0, 0, 0.3);
    }

    .footer-whatsapp-btn-inner {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
    }

    .footer-whatsapp-status-badge {
        position: absolute;
        top: 0;
        right: 0;
        width: 18px;
        height: 18px;
        background: #10b981;
        border-radius: 50%;
        border: 3px solid white;
        animation: statusPulse 2s infinite;
    }

    @keyframes statusPulse {
        0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
        100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }

    .footer-whatsapp-btn svg {
        color: white;
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
    }

    @media (max-width: 768px) {
        .footer-whatsapp-btn {
            width: 70px;
            height: 70px;
            right: 20px;
            bottom: 20px;
        }
    }

    @media (max-width: 480px) {
        .footer-whatsapp-btn {
            width: 60px;
            height: 60px;
        }
    }';

// Insert CSS before </style>
$content = str_replace('</style>', $whatsappCSS . '</style>', $content);

// Replace the chat button with WhatsApp button
$whatsappButton = '<!-- WhatsApp Chat Button -->
        <a href="https://wa.me/<?php echo str_replace([\'+\', \' \', \'(\', \')\', \'-\'], \'\', $whatsapp_number); ?>" 
           target="_blank" 
           rel="noopener noreferrer"
           class="footer-whatsapp-btn" 
           aria-label="Chat on WhatsApp">
            <div class="footer-whatsapp-btn-inner">
                <div class="footer-whatsapp-status-badge"></div>
                <svg width="48" height="48" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.149-.67.149-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414-.074-.123-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                </svg>
            </div>
        </a>';

// Replace the entire chat system section
$chatSystemPattern = '/<\?php if \(\$chat_enabled\)\: \?>.*?<\/div>\s*<\?php endif; \?>/s';

if (preg_match($chatSystemPattern, $content, $matches)) {
    $newChatSystem = '<?php if ($chat_enabled): ?>
    <!-- ================= WHATSAPP CHAT BUTTON ================= -->
    ' . $whatsappButton . '
    <?php endif; ?>';
    
    $content = preg_replace($chatSystemPattern, $newChatSystem, $content);
    echo "<p>✅ Replaced live chat system with WhatsApp button</p>\n";
} else {
    echo "<p style='color: orange;'>⚠️ Could not find chat system pattern - trying alternative approach</p>\n";
    
    // Alternative: replace just the button div
    $buttonPattern = '/<div class="footer-chat-image-btn"[^>]*>.*?<\/div>/s';
    if (preg_match($buttonPattern, $content)) {
        $content = preg_replace($buttonPattern, $whatsappButton, $content);
        echo "<p>✅ Replaced chat button with WhatsApp button</p>\n";
    } else {
        echo "<p style='color: red;'>❌ Could not find chat button to replace</p>\n";
    }
}

// Write the updated content
if (file_put_contents($footerFile, $content)) {
    echo "<p style='color: green;'>✅ Footer updated successfully!</p>\n";
    
    echo "<h3>📋 Changes Made:</h3>\n";
    echo "<ul>\n";
    echo "<li>✅ Updated WhatsApp number to +14389009784</li>\n";
    echo "<li>✅ Added WhatsApp button styling</li>\n";
    echo "<li>✅ Replaced live chat with WhatsApp direct link</li>\n";
    echo "<li>✅ Added hover effects and animations</li>\n";
    echo "</ul>\n";
    
    echo "<h3>🎯 Result:</h3>\n";
    echo "<p>The footer now has a green WhatsApp button that directly opens WhatsApp chat with +14389009784</p>\n";
    echo "<p>The button is positioned fixed at bottom-right with hover animations</p>\n";
    
} else {
    echo "<p style='color: red;'>❌ Failed to write updated footer</p>\n";
}

echo "<hr>\n";
echo "<h3>🔧 Manual Instructions (if needed):</h3>\n";
echo "<p>If the automatic update didn't work, you can manually:</p>\n";
echo "<ol>\n";
echo "<li>Open footer.php in a text editor</li>\n";
echo "<li>Find the line: \$whatsapp_number = '+1 (450) 390-8614';</li>\n";
echo "<li>Replace with: \$whatsapp_number = '+14389009784';</li>\n";
echo "<li>Replace the chat button div with the WhatsApp link</li>\n";
echo "</ol>\n";

?>
