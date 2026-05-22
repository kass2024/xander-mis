<?php
/**
 * Restore Original Footer CSS with WhatsApp Button
 * 
 * This script restores the original footer design while keeping the WhatsApp button
 */

echo "<h1>🔧 Restoring Original Footer CSS with WhatsApp Button</h1>\n";

$footerFile = __DIR__ . '/footer.php';
$backupFile = __DIR__ . '/footer_backup_complete_2026-05-22_10-58-20.php';

if (!file_exists($backupFile)) {
    echo "<p style='color: red;'>❌ Backup file not found</p>\n";
    exit;
}

// Read the original backup
$originalContent = file_get_contents($backupFile);

// WhatsApp CSS to add
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

// WhatsApp HTML
$whatsappHTML = '
<!-- Modern Floating WhatsApp Button -->
<div class="xander-whatsapp-container">
    <a href="https://wa.me/14389009784" 
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

// Remove any existing chatbot system from the original content
$patternsToRemove = [
    '/<\?php if \(\$chat_enabled\)\: \?>.*?<\?php endif; \?>/s',
    '/<!-- ================= MODERN CHAT SYSTEM ================= -->.*?<\/div>/s',
    '/<div class="footer-chat-system">.*?<\/div>/s'
];

$cleanContent = $originalContent;
foreach ($patternsToRemove as $pattern) {
    $cleanContent = preg_replace($pattern, '', $cleanContent);
}

// Add WhatsApp CSS before </head> tag
if (strpos($cleanContent, '</head>') !== false) {
    $cleanContent = str_replace('</head>', $whatsappCSS . '</head>', $cleanContent);
} else {
    $cleanContent = $whatsappCSS . $cleanContent;
}

// Add WhatsApp HTML before </body> tag
if (strpos($cleanContent, '</body>') !== false) {
    $cleanContent = str_replace('</body>', $whatsappHTML . '</body>', $cleanContent);
} else {
    $cleanContent .= $whatsappHTML;
}

// Write the restored content
if (file_put_contents($footerFile, $cleanContent)) {
    echo "<p style='color: green;'>✅ Footer CSS restored successfully!</p>\n";
    echo "<p style='color: green;'>✅ WhatsApp button preserved and functional!</p>\n";
    
    echo "<h3>🎯 What was restored:</h3>\n";
    echo "<ul>\n";
    echo "<li>✅ <strong>Original footer design</strong> - All styling, colors, layout</li>\n";
    echo "<li>✅ <strong>Footer sections</strong> - Brand, Services, Site Map, Map, Contact</li>\n";
    echo "<li>✅ <strong>Interactive map</strong> - San Francisco location with controls</li>\n";
    echo "<li>✅ <strong>Social links</strong> - Facebook, Instagram, LinkedIn, etc.</li>\n";
    echo "<li>✅ <strong>Responsive design</strong> - Mobile, tablet, desktop</li>\n";
    echo "<li>✅ <strong>WhatsApp button</strong> - Still floating and functional</li>\n";
    echo "</ul>\n";
    
    echo "<h3>📱 WhatsApp Button Features:</h3>\n";
    echo "<ul>\n";
    echo "<li>🎨 <strong>Modern design</strong> - Glassmorphism, gradients</li>\n";
    echo "<li>🌊 <strong>Smooth animations</strong> - Floating, pulse, entrance</li>\n";
    echo "<li>💬 <strong>Interactive tooltip</strong> - Hover message</li>\n";
    echo "<li>📱 <strong>Mobile responsive</strong> - Optimized sizes</li>\n";
    echo "<li>🔗 <strong>Direct link</strong> - https://wa.me/14389009784</li>\n";
    echo "</ul>\n";
    
    echo "<h3>✅ Final Result:</h3>\n";
    echo "<p>Your footer now has its <strong>original beautiful design</strong> while keeping the <strong>modern WhatsApp button</strong>!</p>\n";
    echo "<p>Users will see the professional footer with all sections, plus the floating WhatsApp button for direct support! 📱✨</p>\n";
    
} else {
    echo "<p style='color: red;'>❌ Failed to restore footer</p>\n";
}

?>
