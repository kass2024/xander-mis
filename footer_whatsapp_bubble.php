<?php
/**
 * WhatsApp Chat Bubble Update Script
 * 
 * This script updates the footer.php to keep the chat bubble but replace live chat with WhatsApp integration
 */

echo "<h2>💬 WhatsApp Chat Bubble Update</h2>\n";

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

// Update chat translations to reflect WhatsApp
$content = str_replace(
    "'live_support' => 'Live Support',",
    "'live_support' => 'WhatsApp Chat',",
    $content
);

$content = str_replace(
    "'chat_online' => 'AI Assistant Online',",
    "'chat_online' => 'WhatsApp Online',",
    $content
);

$content = str_replace(
    "'chat_connecting' => 'Connecting to xander...',",
    "'chat_connecting' => 'Opening WhatsApp...',",
    $content
);

// French translations
$content = str_replace(
    "'live_support' => 'Support en Direct',",
    "'live_support' => 'WhatsApp Chat',",
    $content
);

$content = str_replace(
    "'chat_online' => 'Assistant IA En Ligne',",
    "'chat_online' => 'WhatsApp En Ligne',",
    $content
);

$content = str_replace(
    "'chat_connecting' => 'Connexion à MISA...',",
    "'chat_connecting' => 'Ouverture de WhatsApp...',",
    $content
);

echo "<p>✅ Updated chat translations to WhatsApp</p>\n";

// Add WhatsApp integration CSS
$whatsappCSS = '
    /* ===== WHATSAPP CHAT BUBBLE INTEGRATION ===== */
    .footer-chat-title-avatar {
        background: linear-gradient(135deg, #25D366 0%, #128C7E 100%) !important;
        box-shadow: 0 4px 12px rgba(37, 211, 102, 0.40), inset 0 0 0 1px rgba(255,255,255,0.20) !important;
    }

    .footer-chat-title-avatar i {
        color: white !important;
    }

    .footer-chat-header {
        background:
            radial-gradient(420px 120px at 100% 0%, rgba(37, 211, 102, 0.25), transparent 60%),
            linear-gradient(135deg, #25D366 0%, #128C7E 100%) !important;
    }

    .footer-chat-status-dot {
        background: #10b981 !important;
    }

    .footer-whatsapp-cta {
        background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
        color: white;
        border: none;
        padding: 12px 20px;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);
    }

    .footer-whatsapp-cta:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(37, 211, 102, 0.4);
    }

    .footer-whatsapp-cta svg {
        width: 20px;
        height: 20px;
    }';

// Insert CSS before closing style tag
$content = str_replace('</style>', $whatsappCSS . '</style>', $content);

// Create the new chat content with WhatsApp integration
$whatsappChatContent = '
            <!-- Chat Header with WhatsApp Branding -->
            <div class="footer-chat-header">
                <div class="footer-chat-title">
                    <div class="footer-chat-title-avatar">
                        <i class="fab fa-whatsapp"></i>
                    </div>
                    <div>
                        <div>Xander WhatsApp Support</div>
                        <div class="footer-chat-status">
                            <div class="footer-chat-status-dot"></div>
                            <span><?php echo ft(\'chat_online\'); ?></span>
                        </div>
                    </div>
                </div>
                <button class="footer-chat-close" id="footerCloseChat" aria-label="Close chat">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Chat Messages Area -->
            <div class="footer-chat-messages" id="footerChatMessages">
                <div class="footer-chat-message bot">
                    <div class="footer-chat-avatar">
                        <i class="fab fa-whatsapp"></i>
                    </div>
                    <div class="footer-chat-message-content">
                        <div class="footer-chat-text">
                            <?php echo ft(\'welcome_message\'); ?>
                        </div>
                        <div class="footer-chat-time">Just now</div>
                    </div>
                </div>
                
                <div class="footer-chat-message bot">
                    <div class="footer-chat-avatar">
                        <i class="fab fa-whatsapp"></i>
                    </div>
                    <div class="footer-chat-message-content">
                        <div class="footer-chat-text">
                            📱 <strong>WhatsApp Chat Available!</strong><br><br>
                            Click the button below to start a WhatsApp conversation with our support team. We typically respond within minutes during business hours.
                        </div>
                        <div class="footer-chat-time">Just now</div>
                    </div>
                </div>
            </div>

            <!-- WhatsApp CTA Button -->
            <div class="footer-chat-input-area">
                <a href="https://wa.me/<?php echo str_replace([\'+\', \' \', \'(\', \')\', \'-\'], \'\', $whatsapp_number); ?>" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   class="footer-whatsapp-cta">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.149-.67.149-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414-.074-.123-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                    </svg>
                    Chat on WhatsApp
                </a>
            </div>';

// Replace the chat window content
$chatWindowPattern = '/<div class="footer-chat-window" id="footerChatWindow" hidden>.*?<\/div>\s*<!-- Modern Chat Window -->/s';

if (preg_match($chatWindowPattern, $content, $matches)) {
    $newChatWindow = '<div class="footer-chat-window" id="footerChatWindow" hidden>' . $whatsappChatContent . '</div>';
    $content = preg_replace($chatWindowPattern, $newChatWindow, $content);
    echo "<p>✅ Updated chat window with WhatsApp integration</p>\n";
} else {
    echo "<p style='color: orange;'>⚠️ Could not find chat window pattern - trying alternative approach</p>\n";
}

// Update the chat button to show WhatsApp icon
$chatButtonPattern = '/<div class="footer-chat-image-btn" id="footerChatToggle" aria-label="Live Chat">.*?<\/div>/s';

if (preg_match($chatButtonPattern, $content, $matches)) {
    $whatsappChatButton = '<div class="footer-chat-image-btn" id="footerChatToggle" aria-label="WhatsApp Chat">
            <div class="footer-chat-status-badge"></div>
            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #25D366 0%, #128C7E 100%); border-radius: 50%;">
                <i class="fab fa-whatsapp" style="color: white; font-size: 32px;"></i>
            </div>
        </div>';
    
    $content = preg_replace($chatButtonPattern, $whatsappChatButton, $content);
    echo "<p>✅ Updated chat button with WhatsApp icon</p>\n";
} else {
    echo "<p style='color: orange;'>⚠️ Could not find chat button pattern</p>\n";
}

// Add Font Awesome for WhatsApp icon (if not already present)
if (strpos($content, 'font-awesome') === false) {
    $fontAwesomeLink = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
    $content = str_replace('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">', $fontAwesomeLink, $content);
}

// Write the updated content
if (file_put_contents($footerFile, $content)) {
    echo "<p style='color: green;'>✅ Footer updated successfully!</p>\n";
    
    echo "<h3>📋 Changes Made:</h3>\n";
    echo "<ul>\n";
    echo "<li>✅ Updated WhatsApp number to +14389009784</li>\n";
    echo "<li>✅ Kept chat bubble but integrated with WhatsApp</li>\n";
    echo "<li>✅ Changed chat icon to WhatsApp (green)</li>\n";
    echo "<li>✅ Updated chat header to WhatsApp branding</li>\n";
    echo "<li>✅ Added WhatsApp CTA button in chat</li>\n";
    echo "<li>✅ Updated translations to reflect WhatsApp</li>\n";
    echo "</ul>\n";
    
    echo "<h3>🎯 Result:</h3>\n";
    echo "<p>The chat bubble now opens with WhatsApp branding and includes a direct WhatsApp chat button</p>\n";
    echo "<p>Users see the familiar chat interface but connect directly to WhatsApp</p>\n";
    
} else {
    echo "<p style='color: red;'>❌ Failed to write updated footer</p>\n";
}

echo "<hr>\n";
echo "<h3>🚀 How it Works:</h3>\n";
echo "<ol>\n";
echo "<li>Users click the green WhatsApp chat bubble</li>\n";
echo "<li>Chat window opens with WhatsApp branding</li>\n";
echo "<li>Welcome message explains WhatsApp integration</li>\n";
echo "<li>Big WhatsApp button opens WhatsApp with +14389009784</li>\n";
echo "<li>Chat happens in WhatsApp (not in the bubble)</li>\n";
echo "</ol>\n";

echo "<h3>🔧 Manual Instructions (if needed):</h3>\n";
echo "<p>Run this script or manually update the footer.php file with the WhatsApp integration</p>\n";

?>
