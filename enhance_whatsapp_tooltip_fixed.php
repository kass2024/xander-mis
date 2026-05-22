<?php
/**
 * Enhance WhatsApp Tooltip - Always Visible with Attractive UI
 * 
 * This script modifies the WhatsApp button to show an always-visible
 * attractive tooltip with pointing finger emoji
 */

echo "<h1>✨ Enhancing WhatsApp Tooltip - Always Visible</h1>\n";

$filesToUpdate = [
    'footer.php',
    'zambia.php',
    'form-Worcester.php', 
    'form-west.php',
    'form-windsor.php',
    'form-West-Florida.php',
    'form-usa.php',
    'form-University of Saskatchewan (USASK).php'
];

$updatedFiles = 0;

foreach ($filesToUpdate as $filename) {
    $filepath = __DIR__ . '/' . $filename;
    
    echo "<h3>🎨 Enhancing: $filename</h3>\n";
    
    if (!file_exists($filepath)) {
        echo "<p style='color: orange;'>⚠️ File not found: $filename</p>\n";
        continue;
    }
    
    $content = file_get_contents($filepath);
    
    // New enhanced tooltip CSS - always visible with attractive design
    $enhancedTooltipCSS = '
/* ===== ENHANCED WHATSAPP TOOLTIP - ALWAYS VISIBLE ===== */
.xander-whatsapp-tooltip {
    position: absolute;
    bottom: 80px;
    right: 0;
    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
    color: white;
    padding: 16px 24px;
    border-radius: 20px;
    font-size: 15px;
    font-weight: 600;
    white-space: nowrap;
    box-shadow: 
        0 12px 40px rgba(37, 211, 102, 0.4),
        0 6px 20px rgba(0, 0, 0, 0.15),
        inset 0 1px 0 rgba(255, 255, 255, 0.3);
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.2);
    pointer-events: none;
    animation: xanderTooltipFloat 3s ease-in-out infinite;
    z-index: 10000;
}

.xander-whatsapp-tooltip::before {
    content: "👉";
    margin-right: 8px;
    font-size: 18px;
    animation: xanderPointingFinger 1.5s ease-in-out infinite;
}

@keyframes xanderTooltipFloat {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-5px); }
}

@keyframes xanderPointingFinger {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.2); }
}

.xander-whatsapp-tooltip::after {
    content: "";
    position: absolute;
    top: 100%;
    right: 24px;
    border: 10px solid transparent;
    border-top-color: #128C7E;
    transform: translateX(50%);
}

/* Enhanced hover effect */
.xander-whatsapp-float:hover .xander-whatsapp-tooltip {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 
        0 16px 50px rgba(37, 211, 102, 0.5),
        0 8px 25px rgba(0, 0, 0, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.4);
    background: linear-gradient(135deg, #128C7E 0%, #075E54 100%);
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .xander-whatsapp-tooltip {
        bottom: 70px;
        right: -80px;
        font-size: 14px;
        padding: 12px 18px;
        max-width: 200px;
        white-space: normal;
        text-align: center;
        line-height: 1.4;
    }
    
    .xander-whatsapp-tooltip::after {
        right: 90px;
    }
}

@media (max-width: 480px) {
    .xander-whatsapp-tooltip {
        bottom: 65px;
        right: -70px;
        font-size: 13px;
        padding: 10px 16px;
        max-width: 180px;
    }
    
    .xander-whatsapp-tooltip::after {
        right: 80px;
    }
}';

    // Replace the old tooltip CSS
    $oldTooltipPattern = '/\/\* Tooltip \*\/.*?\.xander-whatsapp-float:hover \.xander-whatsapp-tooltip[^{]*{[^}]*}/s';
    
    if (preg_match($oldTooltipPattern, $content)) {
        $content = preg_replace($oldTooltipPattern, $enhancedTooltipCSS, $content);
    } else {
        // If pattern not found, add the CSS after existing WhatsApp styles
        $content = str_replace(
            '.xander-whatsapp-float:hover .xander-whatsapp-welcome {',
            $enhancedTooltipCSS . "\n\n.xander-whatsapp-float:hover .xander-whatsapp-welcome {",
            $content
        );
    }
    
    // Update the tooltip text to be more engaging
    $fingerEmoji = "👉";
    $newTooltipText = $fingerEmoji . " Chat with us on WhatsApp!";
    $content = str_replace(
        'Chat with us on WhatsApp',
        $newTooltipText,
        $content
    );
    
    // Remove hover-based visibility changes
    $content = preg_replace('/\.xander-whatsapp-float:hover \.xander-whatsapp-tooltip[^{]*{[^}]*opacity:[^;]*;[^}]*}/s', '', $content);
    
    if (file_put_contents($filepath, $content)) {
        echo "<p style='color: green;'>✅ Enhanced tooltip added to $filename</p>\n";
        $updatedFiles++;
    } else {
        echo "<p style='color: red;'>❌ Failed to update $filename</p>\n";
    }
}

echo "<hr>\n";
echo "<h2>📊 Enhancement Summary</h2>\n";
echo "<p><strong>Files updated:</strong> $updatedFiles/" . count($filesToUpdate) . "</p>\n";

echo "<h3>✨ New Tooltip Features:</h3>\n";
echo "<ul>\n";
echo "<li>👀 <strong>Always Visible</strong> - No hover required, tooltip shows permanently</li>\n";
echo "<li>👉 <strong>Pointing Finger Emoji</strong> - Animated finger pointing to WhatsApp</li>\n";
echo "<li>🎨 <strong>Attractive Design</strong> - WhatsApp green gradient background</li>\n";
echo "<li>💫 <strong>Smooth Animations</strong> - Floating and finger pointing animations</li>\n";
echo "<li>🌟 <strong>Enhanced Hover</strong> - Scale and glow effects on hover</li>\n";
echo "<li>📱 <strong>Mobile Optimized</strong> - Responsive sizing and positioning</li>\n";
echo "<li>💎 <strong>Professional Look</strong> - Glassmorphism with backdrop blur</li>\n";
echo "</ul>\n";

echo "<h3>🎯 UI Improvements:</h3>\n";
echo "<ul>\n";
echo "<li>🎨 <strong>WhatsApp Brand Colors</strong> - Official green gradient</li>\n";
echo "<li>📐 <strong>Better Typography</strong> - Larger, bolder text</li>\n";
echo "<li>🔲 <strong>Enhanced Border</strong> - Thicker white border for contrast</li>\n";
echo "<li>🌊 <strong>Layered Shadows</strong> - Professional depth effects</li>\n";
echo "<li>🎭 <strong>Interactive Elements</strong> - Hover transformations</li>\n";
echo "</ul>\n";

echo "<h3>📱 Customer Experience:</h3>\n";
echo "<ol>\n";
echo "<li><strong>Page loads</strong> → Tooltip immediately visible with pointing finger</li>\n";
echo "<li><strong>Attention grabbing</strong> → Floating animation draws attention</li>\n";
echo "<li><strong>Clear call-to-action</strong> → \"👉 Chat with us on WhatsApp!\"</li>\n";
echo "<li><strong>Professional appearance</strong> → Beautiful WhatsApp-branded design</li>\n";
echo "<li><strong>Mobile friendly</strong> → Optimized for all screen sizes</li>\n";
echo "</ol>\n";

echo "<h3>🚀 Expected Results:</h3>\n";
echo "<ul>\n";
echo "<li>📈 <strong>Increased Engagement</strong> - Always-visible tooltip encourages clicks</li>\n";
echo "<li>🎯 <strong>Clear Direction</strong> - Pointing finger guides users to click</li>\n";
echo "<li>💬 <strong>More WhatsApp Chats</strong> - Attractive UI drives more conversations</li>\n";
echo "<li>👍 <strong>Professional Impression</strong> - High-quality design builds trust</li>\n";
echo "</ul>\n";

echo "<p style='color: green; font-size: 18px; font-weight: bold; text-align: center; margin-top: 30px;'>🎉 WhatsApp Tooltip Enhanced Successfully! 👉✨</p>\n";
echo "<p style='text-align: center;'>Refresh your page to see the beautiful always-visible tooltip with pointing finger! 📱💬</p>\n";

?>
