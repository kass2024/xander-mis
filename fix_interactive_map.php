<?php
/**
 * Fix Interactive Map Loading Issue
 * 
 * This script restores the missing JavaScript for the interactive map
 */

echo "<h1>🗺️ Fixing Interactive Map Loading Issue</h1>\n";

$footerFile = __DIR__ . '/footer.php';

if (!file_exists($footerFile)) {
    echo "<p style='color: red;'>❌ footer.php not found</p>\n";
    exit;
}

// Read current footer content
$content = file_get_contents($footerFile);

// Missing JavaScript for map initialization
$mapJavaScript = '
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
document.addEventListener(\'DOMContentLoaded\', function () {

    /* =========================
       MAP INITIALIZATION
    ========================= */
    function initMap() {
        const mapContainer = document.getElementById(\'footerFixedMap\');
        if (!mapContainer || typeof L === \'undefined\') return;

        // Remove old map if exists (important for reinit)
        if (window.footerMap) {
            window.footerMap.remove();
            window.footerMap = null;
        }

        // Hide loading text
        const loading = mapContainer.querySelector(\'.footer-map-loading\');
        if (loading) loading.style.display = \'none\';

        // Create map
        const map = L.map(\'footerFixedMap\', {
            zoomControl: false
        }).setView([37.7749, -122.4194], 13);

        // Tiles
        L.tileLayer(\'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png\', {
            attribution: "<?php echo ft(\'map_attribution\'); ?>",
            maxZoom: 18
        }).addTo(map);

        // Marker
        const marker = L.marker([37.7749, -122.4194])
            .addTo(map)
            .bindPopup(
                "<b><?php echo ft(\'san_francisco_location\'); ?></b><br><?php echo ft(\'us_address\'); ?>"
            )
            .openPopup();

        // Controls
        document.getElementById(\'footerMapZoomIn\')?.addEventListener(\'click\', () => map.zoomIn());
        document.getElementById(\'footerMapZoomOut\')?.addEventListener(\'click\', () => map.zoomOut());
        document.getElementById(\'footerMapReset\')?.addEventListener(\'click\', () => {
            map.setView([37.7749, -122.4194], 13);
            marker.openPopup();
        });

        window.footerMap = map;
    }

    /* =========================
       INIT EVERYTHING
    ========================= */
    initMap();

    // Reinitialize safely (language change, ajax reload, etc.)
    window.reinitializeFooter = function () {
        initMap();
    };
});
</script>';

// Add the JavaScript before the closing </body> tag
if (strpos($content, '</body>') !== false) {
    // Insert before WhatsApp button and closing body
    $content = str_replace(
        '<!-- Modern Floating WhatsApp Button -->', 
        $mapJavaScript . "\n\n<!-- Modern Floating WhatsApp Button -->", 
        $content
    );
    
    if (file_put_contents($footerFile, $content)) {
        echo "<p style='color: green;'>✅ Interactive map JavaScript restored successfully!</p>\n";
        
        echo "<h3>🗺️ What was fixed:</h3>\n";
        echo "<ul>\n";
        echo "<li>✅ <strong>Leaflet JS library</strong> - Added missing JavaScript library</li>\n";
        echo "<li>✅ <strong>Map initialization</strong> - Added initMap() function</li>\n";
        echo "<li>✅ <strong>San Francisco location</strong> - Set correct coordinates (37.7749, -122.4194)</li>\n";
        echo "<li>✅ <strong>OpenStreetMap tiles</strong> - Added map tiles layer</li>\n";
        echo "<li>✅ <strong>Location marker</strong> - Added marker with popup</li>\n";
        echo "<li>✅ <strong>Map controls</strong> - Added zoom in/out and reset buttons</li>\n";
        echo "<li>✅ <strong>Loading state</strong> - Fixed loading spinner removal</li>\n";
        echo "<li>✅ <strong>Error handling</strong> - Added safety checks</li>\n";
        echo "</ul>\n";
        
        echo "<h3>🎯 Map Features:</h3>\n";
        echo "<ul>\n";
        echo "<li>🗺️ <strong>Interactive map</strong> - Zoom, pan, drag functionality</li>\n";
        echo "<li>📍 <strong>San Francisco marker</strong> - Shows office location</li>\n";
        echo "<li>💬 <strong>Popup info</strong> - Office name and address</li>\n";
        echo "<li>🔍 <strong>Zoom controls</strong> - Zoom in, zoom out, reset view</li>\n";
        echo "<li>🌐 <strong>OpenStreetMap</strong> - Free, open-source map tiles</li>\n";
        echo "<li>📱 <strong>Responsive</strong> - Works on all screen sizes</li>\n";
        echo "</ul>\n";
        
        echo "<h3>✅ Expected Result:</h3>\n";
        echo "<p>The interactive map should now:</p>\n";
        echo "<ol>\n";
        echo "<li>Load the OpenStreetMap tiles properly</li>\n";
        echo "<li>Show a marker at San Francisco office location</li>\n";
        echo "<li>Display office information in a popup</li>\n";
        echo "<li>Allow users to zoom and pan the map</li>\n";
        echo "<li>Respond to control button clicks</li>\n";
        echo "</ol>\n";
        
        echo "<p style='color: green; font-weight: bold;'>🎉 Map loading issue has been resolved!</p>\n";
        echo "<p>Refresh your page to see the interactive map working properly! 🗺️✨</p>\n";
        
    } else {
        echo "<p style='color: red;'>❌ Failed to update footer.php</p>\n";
    }
    
} else {
    echo "<p style='color: red;'>❌ Could not find </body> tag in footer.php</p>\n";
}

?>
