<?php
/**
 * Xander Global Scholars - Footer (Matching Header Design)
 * Keeping ALL original content but matching header structure
 */

// Prevent double include (re-defining constants causes fatal error on PHP)
if (defined('FOOTER_LOADED')) {
    return;
}
define('FOOTER_LOADED', true);

// Use same session/language system as header
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get language from session
if (!isset($_SESSION['current_language'])) {
    $_SESSION['current_language'] = 'en';
}

$current_lang = $_SESSION['current_language'];

// Define color constants based on your document (keeping original colors)
define('XGS_NAVY', '#012F6B');
define('XGS_BLUE', '#254D81');
define('XGS_DARK_BLUE', '#002765');
define('XGS_GOLD', '#F2A65A');
define('XGS_WHITE', '#FFFFFF');

// FOOTER TRANSLATIONS - KEEPING ALL ORIGINAL CONTENT
$footer_translations = [
    'en' => [
        // Brand
        'footer_brand_text' => 'Xander Global Scholars is an international education and career advisory platform supporting students and professionals with admissions, scholarships, visas, credit transfer, jobs, and global mobility.',
        'general_inquiries' => 'General inquiries',
        'email_address' => 'Hello@xanderglobalscholars.com',
        
        // Services (Translated to English)
        'services' => 'Services',
        'service1' => 'Study Abroad',
        'service2' => 'Business & Hotel Management',
        'service3' => 'L2D + International Studies',
        'service4' => 'Start a Free Life',
        'service5' => 'Discover the World',
        'service6' => 'Advance Forward',
        
        // Site Map (Translated to English)
        'site_map' => 'Site Map',
        'services_section' => 'Services',
        'site_service1' => 'Study & Work Abroad',
        'site_service2' => 'Application Procedure',
        'site_service3' => 'Business Life Support',
        'site_service4' => 'Academy/Recruitment',
        'site_service5' => 'YAYA Deployments',
        'site_service6' => 'Family Support',
        'resources_section' => 'Resources',
        'resource1' => 'AI & Methods Blog',
        'resource2' => 'Retirement Stories',
        'resource3' => 'Partner Canoe',
        'resource4' => 'Welcome Acquittals',
        'resource5' => 'Polygamy Games',
        
        // Interactive Map
        'interactive_map' => 'Interactive Map',
        'map_attribution' => 'Leaflet | © OpenStreetMap contributors',
        'san_francisco_location' => 'San Francisco Office',
        'loading_map' => 'Loading map...',
        'zoom_in' => 'Zoom In',
        'zoom_out' => 'Zoom Out',
        'reset_map' => 'Reset Map',
        'view_larger_map' => 'View Larger Map',
        'get_directions' => 'Get Directions',
        'call_us' => 'Call Us',
        
        // Contact (Translated to English)
        'contact_title' => 'Contact',
        'us_phone' => '+1(450)390-8614',
        'us_office' => 'San Francisco Office',
        'us_address' => 'San Francisco, CA, USA',
        'rwanda_phone' => '+250 788 242 069',
        'rwanda_office' => 'Muhanga Office',
        'rwanda_address' => 'Muhanga, Rwanda',
        'kenya_phone' => '+254 744 111 121',
        'kenya_office' => 'Nairobi Office',
        'kenya_address' => 'Nairobi, Kenya',
        'uganda_phone' => '+256 767 418 006',
        'uganda_office' => 'Kampala Office',
        'uganda_address' => 'Kampala, Uganda',
        
        // Social
        'follow_us' => 'Follow Us',
        
        // Legal
        'privacy_policy' => 'Privacy Policy',
        'terms_conditions' => 'Terms & Conditions',
        'payment_refund' => 'Payment & Refund Policy',
        'copyright' => '© %s XANDER TECH LLC. All Rights Reserved.',
        
        // Chat
        'chat_with_us' => '💬 Chat with Us',
        'whatsapp_chat' => 'WhatsApp',
        'live_support' => 'Live Support',
        'welcome_message' => 'Hello! 👋<br><strong>Welcome to Xander Global Scholars!</strong><br>How can I assist you today?',
        'help_options' => 'Select a topic:',
        'admissions_help' => 'Admissions & Applications',
        'visa_help' => 'Visa & Immigration',
        'scholarships_help' => 'Scholarships & Funding',
        'general_help' => 'General Questions',
        'type_message' => 'Type your message...',
        'chat_placeholder' => 'Ask about admissions, visas, scholarships...',
        'send' => 'Send',
        'chat_connected' => 'Connected',
        'chat_error' => 'Connection issue. Please try again.',
        'chat_typing' => 'Typing...',
        'quick_replies' => 'Quick Questions:',
        'quick_question1' => 'Tell me about XGS services',
        'quick_question2' => 'How to apply for study abroad?',
        'quick_question3' => 'Visa requirements',
        'quick_question4' => 'Scholarship opportunities',
        'chat_online' => 'AI Assistant Online',
        'chat_connecting' => 'Connecting to xander...',
    ],
    
    'fr' => [
        // Brand
        'footer_brand_text' => 'Xander Global Scholars est une plateforme de conseil en éducation et carrière internationale qui accompagne les étudiants et les professionnels avec les admissions, bourses, visas, transferts de crédits, emplois et mobilité mondiale.',
        'general_inquiries' => 'Demandes générales',
        'email_address' => 'Hello@xanderglobalscholars.com',
        
        // Services (Original French)
        'services' => 'Services',
        'service1' => 'Éducatif à l\'étranger',
        'service2' => 'Business & Hotel Management',
        'service3' => 'L2D + International Studies',
        'service4' => 'Démarrer une vie libre',
        'service5' => 'Connaître le monde entier',
        'service6' => 'd\'Avant',
        
        // Site Map (Original French)
        'site_map' => 'Plan du Site',
        'services_section' => 'Services',
        'site_service1' => 'Études & Travail à l\'Étranger',
        'site_service2' => 'Procédure d\'application',
        'site_service3' => 'Support Vies Affaires',
        'site_service4' => 'Académie/Recrutement',
        'site_service5' => 'Déploiements YAYA ou',
        'site_service6' => 'Surcours Familial',
        'resources_section' => 'Ressources',
        'resource1' => 'Blog Aig Téléméthodes',
        'resource2' => 'Histoires de Retraite',
        'resource3' => 'Partenaires Canliées',
        'resource4' => 'Acquittés Bienvenue',
        'resource5' => 'Jeux Polygamie',
        
        // Interactive Map
        'interactive_map' => 'Carte Interactive',
        'map_attribution' => 'Leaflet | © OpenStreetMap contributors',
        'san_francisco_location' => 'Bureau de San Francisco',
        'loading_map' => 'Chargement de la carte...',
        'zoom_in' => 'Zoomer',
        'zoom_out' => 'Dézoomer',
        'reset_map' => 'Réinitialiser',
        'view_larger_map' => 'Voir la carte agrandie',
        'get_directions' => 'Obtenir l\'itinéraire',
        'call_us' => 'Appeler',
        
        // Contact (Original French)
        'contact_title' => 'Contact',
        'us_phone' => '+1(450)390-8614',
        'us_office' => 'Bureau de San Francisco',
        'us_address' => 'San Francisco, CA',
        'rwanda_phone' => '+250 788 242 069',
        'rwanda_office' => 'Bureau de Muhanga',
        'rwanda_address' => 'Muhanga, Rwanda',
        'kenya_phone' => '+254 744 111 121',
        'kenya_office' => 'Bureau de Nairobi',
        'kenya_address' => 'Nairobi, Kenya',
        'uganda_phone' => '+256 767 418 006',
        'uganda_office' => 'Bureau de Kampala',
        'uganda_address' => 'Kampala, Uganda',
        
        // Social
        'follow_us' => 'Suivez-nous',
        
        // Legal
        'privacy_policy' => 'Politique de Confidentialité',
        'terms_conditions' => 'Conditions Générales',
        'payment_refund' => 'Politique de Paiement',
        'copyright' => '© %s XANDER TECH LLC. Tous droits réservés.',
        
        // Chat
        'chat_with_us' => '💬 Discutez avec Nous',
        'whatsapp_chat' => 'WhatsApp',
        'live_support' => 'Support en Direct',
        'welcome_message' => 'Bonjour ! 👋<br><strong>Bienvenue chez Xander Global Scholars !</strong><br>Comment puis-je vous aider aujourd\'hui ?',
        'help_options' => 'Sélectionnez un sujet :',
        'admissions_help' => 'Admissions & Candidatures',
        'visa_help' => 'Visa & Immigration',
        'scholarships_help' => 'Bourses & Financement',
        'general_help' => 'Questions Générales',
        'type_message' => 'Tapez votre message...',
        'chat_placeholder' => 'Posez des questions sur admissions, visas, bourses...',
        'send' => 'Envoyer',
        'chat_connected' => 'Connecté',
        'chat_error' => 'Problème de connexion. Veuillez réessayer.',
        'chat_typing' => 'En train d\'écrire...',
        'quick_replies' => 'Questions Rapides:',
        'quick_question1' => 'Parlez-moi des services XGS',
        'quick_question2' => 'Comment postuler à l\'étranger?',
        'quick_question3' => 'Exigences de visa',
        'quick_question4' => 'Opportunités de bourses',
        'chat_online' => 'Assistant IA En Ligne',
        'chat_connecting' => 'Connexion à MISA...',
    ]
];

// Function to translate footer text
if (!function_exists('ft')) {
    function ft($key) {
        global $footer_translations, $current_lang;
        return isset($footer_translations[$current_lang][$key]) ? $footer_translations[$current_lang][$key] : $key;
    }
}

// Configuration - KEEPING ALL ORIGINAL SETTINGS
$chat_enabled = true;
$whatsapp_number = '+1 (450) 390-8614';
$current_year = date('Y');
$site_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";

// Social media - KEEPING ALL ORIGINAL LINKS
$social_links = [
    'facebook' => 'https://www.facebook.com/profile.php?id=61572855147899#',
    'instagram' => 'https://www.instagram.com/xander_global_scholars',
    'linkedin' => 'https://www.linkedin.com/in/xander-global-scholars-82b76a34a/?trk=public-profile-join-page',
    'twitter' => 'https://x.com/xander_global?s=21',
    'tiktok' => 'https://www.tiktok.com/@xanderglobalscholars',
    'youtube' => '#'
];

// Services list (keeping ALL original services)
$services = [
    ['name' => 'service1', 'icon' => 'fa-graduation-cap'],
    ['name' => 'service2', 'icon' => 'fa-briefcase'],
    ['name' => 'service3', 'icon' => 'fa-globe'],
    ['name' => 'service4', 'icon' => 'fa-rocket'],
    ['name' => 'service5', 'icon' => 'fa-compass'],
    ['name' => 'service6', 'icon' => 'fa-forward']
];

// Site Map Links (keeping ALL original links)
$site_map_links = [
    'services' => [
        ['name' => 'site_service1'],
        ['name' => 'site_service2'],
        ['name' => 'site_service3'],
        ['name' => 'site_service4'],
        ['name' => 'site_service5'],
        ['name' => 'site_service6']
    ],
    'resources' => [
        ['name' => 'resource1'],
        ['name' => 'resource2'],
        ['name' => 'resource3'],
        ['name' => 'resource4'],
        ['name' => 'resource5']
    ]
];

// Contact information (keeping ALL original contacts)
$contacts = [
    [
        'phone' => 'us_phone',
        'label' => 'us_office',
        'address' => 'us_address'
    ],
    [
        'phone' => 'rwanda_phone',
        'label' => 'rwanda_office',
        'address' => 'rwanda_address'
    ],
    [
        'phone' => 'kenya_phone',
        'label' => 'kenya_office',
        'address' => 'kenya_address'
    ],
    [
        'phone' => 'uganda_phone',
        'label' => 'uganda_office',
        'address' => 'uganda_address'
    ]
];

// Fixed San Francisco location for map (keeping original)
$san_francisco_location = [
    'title' => 'san_francisco_location',
    'lat' => 37.7749,
    'lng' => -122.4194,
    'phone' => 'us_phone',
    'address' => 'San Francisco, California, USA',
    'google_maps_link' => 'https://maps.google.com/?q=San+Francisco,+CA,+USA'
];

// Generate unique session ID for chat if not exists
if (!isset($_SESSION['chat_session_id'])) {
    $_SESSION['chat_session_id'] = 'xgs_' . uniqid() . '_' . time();
}
$chat_session_id = $_SESSION['chat_session_id'];
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Leaflet CSS for Fixed Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
    /* ============================================
       FOOTER STYLES - MATCHING HEADER DESIGN
       BUT KEEPING ALL ORIGINAL FUNCTIONALITY
    ============================================ */
    
    /* Using header's CSS variables but adding footer-specific ones */
    :root {
        /* Header variables */
        --primary: #1e3a5f;
        --primary-dark: #0f2542;
        --primary-light: #2d4f7c;
        --accent: #ff8c42;
        --accent-dark: #e6732f;
        --accent-light: #ffa366;
        --bg: #f8fafc;
        --card: #ffffff;
        --text: #1e293b;
        --text-light: #64748b;
        --border: #e2e8f0;
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        
        /* Chat specific variables */
        --chat-user-bg: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --chat-bot-bg: #f8f9fa;
        --chat-success: #10b981;
        --chat-error: #ef4444;
        --chat-warning: #f59e0b;
        
        /* Original footer colors for reference */
        --xgs-navy: <?php echo XGS_NAVY; ?>;
        --xgs-blue: <?php echo XGS_BLUE; ?>;
        --xgs-dark-blue: <?php echo XGS_DARK_BLUE; ?>;
        --xgs-gold: <?php echo XGS_GOLD; ?>;
        --xgs-white: <?php echo XGS_WHITE; ?>;
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    /* ===== MAIN FOOTER CONTAINER (Matches header styling) ===== */
    .footer-main {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        color: #fff;
        padding: 60px 40px 0;
        position: relative;
        border-top: 3px solid var(--accent);
        font-family: 'Inter', sans-serif;
    }

    .footer-container {
        max-width: 1400px;
        margin: 0 auto;
    }

    /* ===== FOOTER GRID (5 columns like original but matching header design) ===== */
    .footer-grid {
        display: grid;
        grid-template-columns: 1.5fr 1fr 1.2fr 1.3fr 1.5fr;
        gap: 40px;
        margin-bottom: 50px;
    }

    @media (max-width: 1200px) {
        .footer-grid {
            grid-template-columns: 1fr 1fr 1fr;
            gap: 30px;
        }
        
        .footer-map-column {
            grid-column: span 3;
        }
    }

    @media (max-width: 992px) {
        .footer-grid {
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .footer-map-column {
            grid-column: span 2;
        }
    }

    @media (max-width: 768px) {
        .footer-grid {
            grid-template-columns: 1fr;
            gap: 40px;
        }
        
        .footer-map-column {
            grid-column: span 1;
        }
        
        .footer-main {
            padding: 40px 20px 0;
        }
    }

    /* ===== FOOTER COLUMNS (Matching header style) ===== */
    .footer-column h3 {
        color: var(--accent);
        font-size: 1.1rem;
        font-weight: 700;
        margin-bottom: 24px;
        text-transform: uppercase;
        letter-spacing: 1px;
        position: relative;
        padding-bottom: 10px;
    }

    .footer-column h3::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 40px;
        height: 2px;
        background: var(--accent);
    }

    /* ===== BRAND COLUMN (Enhanced to match header but keeping content) ===== */
    .footer-logo-container {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 20px;
    }

    .footer-logo-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: #fff;
        box-shadow: 0 4px 12px rgba(255, 140, 66, 0.3);
        transition: var(--transition);
    }

    .footer-logo-icon:hover {
        transform: rotate(15deg) scale(1.1);
    }

    .footer-logo-text {
        font-size: 1.5rem;
        font-weight: 900;
        line-height: 1.2;
        text-transform: uppercase;
    }

    .footer-logo-text span {
        display: block;
        color: var(--accent);
    }

    .footer-brand-description {
        color: rgba(255, 255, 255, 0.85);
        line-height: 1.7;
        margin-bottom: 25px;
        font-size: 0.95rem;
    }

    /* Email Contact (Matching header button style) */
    .footer-email-contact {
        background: rgba(255, 255, 255, 0.08);
        border: 2px solid rgba(255, 140, 66, 0.2);
        border-radius: 8px;
        padding: 15px;
        transition: var(--transition);
    }

    .footer-email-contact:hover {
        background: rgba(255, 255, 255, 0.12);
        border-color: var(--accent);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(255, 140, 66, 0.2);
    }

    .footer-email-contact a {
        color: #fff;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .footer-email-icon {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }

    .footer-email-content {
        flex: 1;
    }

    .footer-email-title {
        font-weight: 600;
        font-size: 0.9rem;
        color: var(--accent);
        margin-bottom: 4px;
    }

    .footer-email-address {
        font-weight: 700;
        font-size: 0.95rem;
        word-break: break-all;
    }

    /* ===== SERVICES COLUMN (Matching header navigation style) ===== */
    .footer-services-list {
        display: grid;
        gap: 12px;
    }

    .footer-service-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 0;
        color: rgba(255, 255, 255, 0.85);
        text-decoration: none;
        transition: var(--transition);
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .footer-service-item:last-child {
        border-bottom: none;
    }

    .footer-service-item i {
        color: var(--accent);
        font-size: 16px;
        width: 24px;
        text-align: center;
        transition: var(--transition);
    }

    .footer-service-item:hover {
        color: #fff;
        transform: translateX(8px);
    }

    .footer-service-item:hover i {
        color: var(--accent-light);
        transform: scale(1.2);
    }

    /* ===== SITE MAP COLUMN (Matching header dropdown style) ===== */
    .footer-sitemap-section {
        margin-bottom: 20px;
    }

    .footer-sitemap-section:last-child {
        margin-bottom: 0;
    }

    .footer-sitemap-title {
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--accent);
        margin-bottom: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: block;
    }

    .footer-sitemap-list {
        display: grid;
        gap: 10px;
    }

    .footer-sitemap-item {
        color: rgba(255, 255, 255, 0.85);
        text-decoration: none;
        font-size: 0.9rem;
        padding: 8px 0;
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .footer-sitemap-item::before {
        content: '›';
        color: var(--accent);
        font-size: 16px;
        line-height: 1;
        transition: var(--transition);
    }

    .footer-sitemap-item:hover {
        color: #fff;
        transform: translateX(5px);
    }

    .footer-sitemap-item:hover::before {
        transform: translateX(3px);
        color: var(--accent-light);
    }

    /* ===== MAP COLUMN (Keeping original map functionality but matching header style) ===== */
    .footer-map-column {
        display: flex;
        flex-direction: column;
    }

    .footer-map-wrapper {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 8px;
        padding: 0;
        overflow: hidden;
        border: 2px solid rgba(255, 140, 66, 0.2);
        height: 280px;
        position: relative;
        flex: 1;
        min-height: 280px;
        width: 100%;
    }

    #footerFixedMap {
        width: 100%;
        height: 100%;
        border-radius: 6px;
    }

    .footer-map-controls {
        position: absolute;
        bottom: 15px;
        right: 15px;
        display: flex;
        gap: 10px;
        z-index: 1000;
    }

    .footer-map-btn {
        width: 40px;
        height: 40px;
        background: rgba(30, 58, 95, 0.9);
        border: 2px solid var(--accent);
        border-radius: 6px;
        color: var(--accent);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        transition: var(--transition);
        box-shadow: var(--shadow-lg);
    }

    .footer-map-btn:hover {
        background: var(--accent);
        color: var(--primary);
        transform: scale(1.1);
    }

    .footer-map-loading {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: var(--accent);
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 10px;
        z-index: 1;
        background: rgba(0, 0, 0, 0.7);
        padding: 12px 20px;
        border-radius: 6px;
        backdrop-filter: blur(4px);
    }

    /* ===== CONTACT COLUMN (Matching header style) ===== */
    .footer-contact-list {
        display: grid;
        gap: 16px;
    }

    .footer-contact-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 15px;
        background: rgba(255, 255, 255, 0.03);
        border-radius: 8px;
        transition: var(--transition);
        border: 1px solid rgba(255, 140, 66, 0.1);
    }

    .footer-contact-item:hover {
        background: rgba(255, 255, 255, 0.08);
        transform: translateY(-2px);
        border-color: var(--accent);
        box-shadow: var(--shadow-lg);
    }

    .footer-contact-icon {
        width: 36px;
        height: 36px;
        background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        color: #fff;
        flex-shrink: 0;
    }

    .footer-contact-details {
        flex: 1;
        min-width: 0;
    }

    .footer-contact-phone {
        color: #fff;
        font-weight: 700;
        text-decoration: none;
        display: block;
        font-size: 0.95rem;
        margin-bottom: 4px;
        transition: var(--transition);
        word-break: break-all;
    }

    .footer-contact-phone:hover {
        color: var(--accent);
    }

    .footer-contact-label {
        font-size: 0.85rem;
        color: var(--accent);
        font-weight: 600;
        margin-bottom: 2px;
        display: block;
    }

    .footer-contact-address {
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.7);
        line-height: 1.4;
    }

    /* ===== SOCIAL LINKS (Matching header social style) ===== */
    .footer-social-links {
        display: flex;
        gap: 12px;
        margin-top: 24px;
        flex-wrap: wrap;
    }

    .footer-social-link {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.05);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        text-decoration: none;
        transition: var(--transition);
        border: 1px solid rgba(255, 140, 66, 0.2);
    }

    .footer-social-link:hover {
        background: var(--accent);
        color: var(--primary);
        transform: translateY(-4px) rotate(10deg);
        box-shadow: 0 8px 20px rgba(255, 140, 66, 0.3);
        border-color: var(--accent);
    }

    /* ===== FOOTER BOTTOM (Matching header bottom style) ===== */
    .footer-bottom {
        background: var(--primary-dark);
        padding: 25px 0;
        border-top: 1px solid rgba(255, 140, 66, 0.1);
        width: 100%;
    }

    .footer-bottom-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 40px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }

    @media (max-width: 768px) {
        .footer-bottom-container {
            padding: 0 20px;
        }
    }

    @media (max-width: 480px) {
        .footer-bottom-container {
            flex-direction: column;
            text-align: center;
            gap: 12px;
        }
    }

    .footer-links {
        display: flex;
        gap: 24px;
        flex-wrap: wrap;
    }

    @media (max-width: 480px) {
        .footer-links {
            justify-content: center;
            gap: 16px;
        }
    }

    .footer-link {
        color: rgba(255, 255, 255, 0.7);
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
        transition: var(--transition);
        padding: 6px 0;
        position: relative;
    }

    .footer-link::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 0;
        height: 2px;
        background: var(--accent);
        transition: width 0.3s ease;
    }

    .footer-link:hover {
        color: #fff;
    }

    .footer-link:hover::after {
        width: 100%;
    }

    .footer-copyright {
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.9rem;
        font-weight: 500;
    }

    .footer-copyright strong {
        color: var(--accent);
        font-weight: 700;
    }

    /* ===== MODERN CHAT SYSTEM ===== */
    .footer-chat-system {
        position: fixed;
        right: 30px;
        bottom: 30px;
        z-index: 99999;
        pointer-events: auto;
    }

    /* Animated Chat Button */
    .footer-chat-image-btn {
        width: 80px;
        height: 80px;
        cursor: pointer;
        position: relative;
        animation: floatChatButton 3s ease-in-out infinite;
        transition: var(--transition);
        filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.2));
    }

    @keyframes floatChatButton {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-15px); }
    }

    .footer-chat-image-btn:hover {
        transform: scale(1.1) rotate(5deg);
        animation: none;
        filter: drop-shadow(0 15px 30px rgba(0, 0, 0, 0.3));
    }

    .footer-chat-image-btn img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        border-radius: 50%;
        border: 3px solid white;
        box-shadow: 0 0 0 4px rgba(255, 140, 66, 0.3);
    }

    /* Chat Pulse Effect */
    .footer-chat-image-btn::before {
        content: '';
        position: absolute;
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
        opacity: 0.3;
        z-index: -1;
        animation: pulseChatButton 2s infinite;
    }

    @keyframes pulseChatButton {
        0% { transform: scale(1); opacity: 0.3; }
        70% { transform: scale(1.3); opacity: 0; }
        100% { transform: scale(1.3); opacity: 0; }
    }

    /* Online Status Badge */
    .footer-chat-status-badge {
        position: absolute;
        top: 0;
        right: 0;
        width: 18px;
        height: 18px;
        background: var(--chat-success);
        border-radius: 50%;
        border: 3px solid white;
        animation: statusPulse 2s infinite;
    }

    @keyframes statusPulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
        100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }

    /* ===== MODERN CHAT WINDOW ===== */
    .footer-chat-window {
        position: fixed;
        right: 30px;
        bottom: 120px;
        width: 380px;
        height: 580px;
        background: white;
        border-radius: 24px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        display: none;
        flex-direction: column;
        z-index: 10001;
        overflow: hidden;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        animation: slideUpWindow 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        border: 1px solid rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
    }

    @keyframes slideUpWindow {
        from {
            opacity: 0;
            transform: translateY(30px) scale(0.9);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    /* Chat Header - Modern Design */
    .footer-chat-header {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: white;
        position: relative;
        overflow: hidden;
    }

    .footer-chat-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.1) 50%, transparent 70%);
        animation: shineHeader 3s infinite;
    }

    @keyframes shineHeader {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }

    .footer-chat-title {
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 700;
        font-size: 16px;
    }

    .footer-chat-title-avatar {
        width: 36px;
        height: 36px;
        background: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        color: var(--primary);
        font-weight: bold;
    }

    .footer-chat-status {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        opacity: 0.9;
        margin-top: 4px;
    }

    .footer-chat-status-dot {
        width: 8px;
        height: 8px;
        background: var(--chat-success);
        border-radius: 50%;
        animation: blinkStatus 1.5s infinite;
    }

    @keyframes blinkStatus {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    .footer-chat-close {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        font-size: 20px;
        cursor: pointer;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition);
        backdrop-filter: blur(10px);
        z-index: 1;
    }

    .footer-chat-close:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: rotate(90deg);
    }

    /* Chat Body - Modern Scroll */
    .footer-chat-body {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
        background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 100%);
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    /* Custom Scrollbar */
    .footer-chat-body::-webkit-scrollbar {
        width: 6px;
    }

    .footer-chat-body::-webkit-scrollbar-track {
        background: transparent;
    }

    .footer-chat-body::-webkit-scrollbar-thumb {
        background: linear-gradient(transparent, var(--accent));
        border-radius: 10px;
    }

    /* ===== MODERN MESSAGE BUBBLES ===== */
    .footer-chat-message {
        max-width: 80%;
        padding: 14px 18px;
        border-radius: 22px;
        font-size: 14px;
        line-height: 1.5;
        animation: messageSlide 0.3s ease-out;
        word-wrap: break-word;
        position: relative;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    @keyframes messageSlide {
        from {
            opacity: 0;
            transform: translateY(15px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    /* Bot Message - Modern Gradient */
    .footer-message-bot {
        background: var(--chat-bot-bg);
        border: 1px solid var(--border);
        align-self: flex-start;
        border-bottom-left-radius: 8px;
        position: relative;
        margin-left: 8px;
    }

    .footer-message-bot::before {
        content: '';
        position: absolute;
        left: -8px;
        top: 0;
        border-width: 8px 8px 8px 0;
        border-style: solid;
        border-color: transparent var(--border) transparent transparent;
    }

    .footer-message-bot strong {
        color: var(--primary);
        font-weight: 700;
    }

    /* User Message - Modern Gradient */
    .footer-message-user {
        background: var(--chat-user-bg);
        color: white;
        align-self: flex-end;
        border-bottom-right-radius: 8px;
        position: relative;
        margin-right: 8px;
        animation: messagePop 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }

    @keyframes messagePop {
        0% { transform: scale(0); }
        70% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }

    .footer-message-user::after {
        content: '';
        position: absolute;
        right: -8px;
        top: 0;
        border-width: 8px 0 8px 8px;
        border-style: solid;
        border-color: transparent transparent transparent #667eea;
    }

    /* Typing Indicator */
    .footer-typing-indicator {
        background: var(--chat-bot-bg);
        border: 1px solid var(--border);
        border-radius: 22px;
        padding: 12px 20px;
        align-self: flex-start;
        display: none;
        margin-left: 8px;
    }

    .footer-typing-dots {
        display: flex;
        gap: 4px;
    }

    .footer-typing-dot {
        width: 8px;
        height: 8px;
        background: var(--primary);
        border-radius: 50%;
        animation: typingBounce 1.4s infinite;
    }

    .footer-typing-dot:nth-child(2) { animation-delay: 0.2s; }
    .footer-typing-dot:nth-child(3) { animation-delay: 0.4s; }

    @keyframes typingBounce {
        0%, 60%, 100% { transform: translateY(0); }
        30% { transform: translateY(-8px); }
    }

    /* ===== MODERN QUICK REPLIES ===== */
    .footer-quick-replies {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-top: 15px;
        animation: fadeInUp 0.5s ease-out;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .footer-quick-reply {
        background: white;
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 10px 15px;
        font-size: 12px;
        color: var(--text);
        cursor: pointer;
        transition: var(--transition);
        text-align: center;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .footer-quick-reply:hover {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        border-color: var(--primary);
    }

    /* ===== MODERN CHAT INPUT ===== */
    .footer-chat-input {
        padding: 20px;
        background: white;
        border-top: 1px solid var(--border);
        display: flex;
        gap: 12px;
        align-items: center;
        position: relative;
    }

    .footer-chat-input::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, var(--accent), transparent);
    }

    .footer-chat-input input {
        flex: 1;
        border: 2px solid transparent;
        background: linear-gradient(white, white) padding-box,
                    linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%) border-box;
        border-radius: 25px;
        padding: 14px 20px;
        font-size: 14px;
        outline: none;
        transition: var(--transition);
        font-family: inherit;
    }

    .footer-chat-input input:focus {
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        transform: translateY(-1px);
    }

    .footer-chat-send {
        background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
        border: none;
        border-radius: 50%;
        width: 48px;
        height: 48px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 18px;
        transition: var(--transition);
        box-shadow: 0 4px 15px rgba(255, 140, 66, 0.4);
        position: relative;
        overflow: hidden;
    }

    .footer-chat-send::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, 
            transparent, 
            rgba(255, 255, 255, 0.3), 
            transparent
        );
        transition: var(--transition);
    }

    .footer-chat-send:hover::before {
        left: 100%;
    }

    .footer-chat-send:hover {
        transform: scale(1.1) rotate(10deg);
        box-shadow: 0 6px 20px rgba(255, 140, 66, 0.6);
    }

    /* ===== RESPONSIVE DESIGN ===== */
    @media (max-width: 768px) {
        .footer-chat-system {
            right: 20px;
            bottom: 20px;
        }
        
        .footer-chat-window {
            right: 20px;
            bottom: 110px;
            width: calc(100vw - 40px);
            max-width: 400px;
            height: 70vh;
        }
        
        .footer-chat-image-btn {
            width: 70px;
            height: 70px;
        }
        
        .footer-quick-replies {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 480px) {
        .footer-chat-system {
            right: 15px;
            bottom: 15px;
        }
        
        .footer-chat-window {
            right: 15px;
            bottom: 100px;
            width: calc(100vw - 30px);
            height: 75vh;
        }
        
        .footer-chat-image-btn {
            width: 60px;
            height: 60px;
        }
        
        .footer-chat-message {
            max-width: 90%;
        }
    }

    /* ===== MAP Z-INDEX FIX ===== */
    .footer-map-wrapper,
    #footerFixedMap {
        position: relative;
        z-index: 1;
    }

    /* ===== AI CHAT ENHANCEMENTS ===== */
    .footer-chat-loading {
        display: none;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: rgba(255, 255, 255, 0.95);
        padding: 20px 30px;
        border-radius: 16px;
        box-shadow: var(--shadow-xl);
        z-index: 10;
        backdrop-filter: blur(10px);
    }

    .footer-chat-error {
        background: linear-gradient(135deg, var(--chat-error) 0%, #ff8e8e 100%);
        color: white;
        padding: 12px 18px;
        border-radius: 16px;
        margin: 10px 20px;
        font-size: 13px;
        text-align: center;
        animation: shakeError 0.5s;
    }

    @keyframes shakeError {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }
    </style>
</head>
<body>
    <!-- ================= FOOTER SECTION ================= -->
    <footer class="footer-main">
        <div class="footer-container">
            <div class="footer-grid">
                <!-- Brand Column (KEEPING ALL ORIGINAL CONTENT) -->
                <div class="footer-column">
                    <div class="footer-logo-container">
                        <div class="footer-logo-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="footer-logo-text">
                            XANDER<br><span>GLOBAL SCHOLARS</span>
                        </div>
                    </div>
                    
                    <p class="footer-brand-description">
                        <?php echo ft('footer_brand_text'); ?>
                    </p>
                    
                    <div class="footer-email-contact">
                        <a href="mailto:<?php echo ft('email_address'); ?>">
                            <div class="footer-email-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="footer-email-content">
                                <div class="footer-email-title"><?php echo ft('general_inquiries'); ?></div>
                                <div class="footer-email-address"><?php echo ft('email_address'); ?></div>
                            </div>
                        </a>
                    </div>
                </div>
                
                <!-- Services Column (KEEPING ALL ORIGINAL CONTENT) -->
                <div class="footer-column">
                    <h3><?php echo ft('services'); ?></h3>
                    <div class="footer-services-list">
                        <?php foreach ($services as $service): ?>
                        <a href="#" class="footer-service-item">
                            <i class="fas <?php echo $service['icon']; ?>"></i>
                            <span><?php echo ft($service['name']); ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Site Map Column (KEEPING ALL ORIGINAL CONTENT) -->
                <div class="footer-column">
                    <h3><?php echo ft('site_map'); ?></h3>
                    
                    <!-- Services Section -->
                    <div class="footer-sitemap-section">
                        <h4 class="footer-sitemap-title"><?php echo ft('services_section'); ?></h4>
                        <div class="footer-sitemap-list">
                            <?php foreach ($site_map_links['services'] as $link): ?>
                            <a href="#" class="footer-sitemap-item">
                                <?php echo ft($link['name']); ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Resources Section -->
                    <div class="footer-sitemap-section">
                        <h4 class="footer-sitemap-title"><?php echo ft('resources_section'); ?></h4>
                        <div class="footer-sitemap-list">
                            <?php foreach ($site_map_links['resources'] as $link): ?>
                            <a href="#" class="footer-sitemap-item">
                                <?php echo ft($link['name']); ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Fixed Map Column - San Francisco Only (KEEPING ORIGINAL FUNCTIONALITY) -->
                <div class="footer-column footer-map-column">
                    <h3><?php echo ft('interactive_map'); ?></h3>
                    <div class="footer-map-wrapper">
                        <div id="footerFixedMap">
                            <div class="footer-map-loading">
                                <i class="fas fa-spinner fa-spin"></i>
                                <?php echo ft('loading_map'); ?>
                            </div>
                        </div>
                        <div class="footer-map-controls">
                            <button class="footer-map-btn" id="footerMapZoomIn" title="<?php echo ft('zoom_in'); ?>">
                                <i class="fas fa-search-plus"></i>
                            </button>
                            <button class="footer-map-btn" id="footerMapZoomOut" title="<?php echo ft('zoom_out'); ?>">
                                <i class="fas fa-search-minus"></i>
                            </button>
                            <button class="footer-map-btn" id="footerMapReset" title="<?php echo ft('reset_map'); ?>">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Column (KEEPING ALL ORIGINAL CONTENT) -->
                <div class="footer-column">
                    <h3><?php echo ft('contact_title'); ?></h3>
                    
                    <div class="footer-contact-list">
                        <?php foreach ($contacts as $contact): ?>
                        <div class="footer-contact-item">
                            <div class="footer-contact-icon">
                                <i class="fas fa-phone-alt"></i>
                            </div>
                            <div class="footer-contact-details">
                                <a href="tel:<?php echo ft($contact['phone']); ?>" 
                                   class="footer-contact-phone">
                                    <?php echo ft($contact['phone']); ?>
                                </a>
                                <div class="footer-contact-label"><?php echo ft($contact['label']); ?></div>
                                <div class="footer-contact-address"><?php echo ft($contact['address']); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="footer-social-links">
                        <a href="<?php echo $social_links['facebook']; ?>" class="footer-social-link" title="Facebook" target="_blank" rel="noopener noreferrer">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="<?php echo $social_links['instagram']; ?>" class="footer-social-link" title="Instagram" target="_blank" rel="noopener noreferrer">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="<?php echo $social_links['linkedin']; ?>" class="footer-social-link" title="LinkedIn" target="_blank" rel="noopener noreferrer">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="<?php echo $social_links['twitter']; ?>" class="footer-social-link" title="Twitter" target="_blank" rel="noopener noreferrer">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="<?php echo $social_links['tiktok']; ?>" class="footer-social-link" title="TikTok" target="_blank" rel="noopener noreferrer">
                            <i class="fab fa-tiktok"></i>
                        </a>
                        <a href="<?php echo $social_links['youtube']; ?>" class="footer-social-link" title="YouTube" target="_blank" rel="noopener noreferrer">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer Bottom (KEEPING ALL ORIGINAL CONTENT) -->
        <div class="footer-bottom">
            <div class="footer-bottom-container">
                <div class="footer-links">
                    <a href="privacy.php" class="footer-link"><?php echo ft('privacy_policy'); ?></a>
                    <a href="terms.php" class="footer-link"><?php echo ft('terms_conditions'); ?></a>
                    <a href="refund.php" class="footer-link"><?php echo ft('payment_refund'); ?></a>
                </div>
                <div class="footer-copyright">
                    <?php printf(ft('copyright'), $current_year); ?>
                </div>
            </div>
        </div>
    </footer>
    
    <?php if ($chat_enabled): ?>
    <!-- ================= MODERN CHAT SYSTEM ================= -->
    <div class="footer-chat-system">
        <!-- Animated Chat Button with Image -->
        <div class="footer-chat-image-btn" id="footerChatToggle" aria-label="Live Chat">
            <div class="footer-chat-status-badge"></div>
            <img src="assets/chat/live-chat.webp" alt="XGS Live Support" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iODAiIGhlaWdodD0iODAiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cGF0aCBkPSJNMjAgMkg0QzIuOSAyIDIgMi45IDIgNHYxNmMwIDEuMS45IDIgMiAyaDE2YzEuMSAwIDItLjkgMi0yVjRjMC0xLjEtLjktMi0yLTJ6TTggMThINGwtMi0yVjRoMTZ2MTJsLTIgMkg4eiIgZmlsbD0iIzY2N0VFQSIvPjxwYXRoIGQ9Ik0xMiAxMmMtMS4xIDAtMi0uOS0yLTJzLjktMiAyLTIgMiAuOSAyIDItLjkgMi0yIDJ6IiBmaWxsPSIjNzY0QkEyIi8+PC9zdmc+'">
        </div>
        
        <!-- Modern Chat Window -->
        <div class="footer-chat-window" id="footerChatWindow" hidden>
            <div class="footer-chat-header">
                <div class="footer-chat-title">
                    <div class="footer-chat-title-avatar">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div>
                        <div>Xander AI Assistant</div>
                        <div class="footer-chat-status">
                            <div class="footer-chat-status-dot"></div>
                            <span><?php echo ft('chat_online'); ?></span>
                        </div>
                    </div>
                </div>
                <button class="footer-chat-close" id="footerCloseChat" aria-label="Close chat">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="footer-chat-body" id="footerChatBody">
                <!-- Welcome Message -->
                <div class="footer-chat-message footer-message-bot">
                    <?php echo ft('welcome_message'); ?>
                </div>
                
                <!-- Quick Questions -->
                <div class="footer-quick-replies" id="quickReplies">
                    <div class="footer-quick-reply" data-question="<?php echo ft('quick_question1'); ?>">
                        <?php echo ft('quick_question1'); ?>
                    </div>
                    <div class="footer-quick-reply" data-question="<?php echo ft('quick_question2'); ?>">
                        <?php echo ft('quick_question2'); ?>
                    </div>
                    <div class="footer-quick-reply" data-question="<?php echo ft('quick_question3'); ?>">
                        <?php echo ft('quick_question3'); ?>
                    </div>
                    <div class="footer-quick-reply" data-question="<?php echo ft('quick_question4'); ?>">
                        <?php echo ft('quick_question4'); ?>
                    </div>
                </div>
                
                <!-- Typing Indicator -->
                <div class="footer-typing-indicator" id="typingIndicator">
                    <div class="footer-typing-dots">
                        <div class="footer-typing-dot"></div>
                        <div class="footer-typing-dot"></div>
                        <div class="footer-typing-dot"></div>
                    </div>
                </div>
            </div>
            
            <!-- Chat Input -->
            <div class="footer-chat-input">
                <input type="text" 
                       id="footerChatInput" 
                       placeholder="<?php echo ft('chat_placeholder'); ?>" 
                       aria-label="<?php echo ft('type_message'); ?>"
                       maxlength="500"
                       autocomplete="off">
                <button class="footer-chat-send" id="footerSendChat" aria-label="<?php echo ft('send'); ?>">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

   <!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    /* =========================
       MAP INITIALIZATION
    ========================= */
    function initMap() {
        const mapContainer = document.getElementById('footerFixedMap');
        if (!mapContainer || typeof L === 'undefined') return;

        // Remove old map if exists (important for reinit)
        if (window.footerMap) {
            window.footerMap.remove();
            window.footerMap = null;
        }

        // Hide loading text
        const loading = mapContainer.querySelector('.footer-map-loading');
        if (loading) loading.style.display = 'none';

        // Create map
        const map = L.map('footerFixedMap', {
            zoomControl: false
        }).setView([37.7749, -122.4194], 13);

        // Tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: "<?php echo ft('map_attribution'); ?>",
            maxZoom: 18
        }).addTo(map);

        // Marker
        const marker = L.marker([37.7749, -122.4194])
            .addTo(map)
            .bindPopup(
                "<b><?php echo ft('san_francisco_location'); ?></b><br><?php echo ft('us_address'); ?>"
            )
            .openPopup();

        // Controls
        document.getElementById('footerMapZoomIn')?.addEventListener('click', () => map.zoomIn());
        document.getElementById('footerMapZoomOut')?.addEventListener('click', () => map.zoomOut());
        document.getElementById('footerMapReset')?.addEventListener('click', () => {
            map.setView([37.7749, -122.4194], 13);
            marker.openPopup();
        });

        window.footerMap = map;
    }

    /* =========================
       MODERN CHAT INITIALIZATION
       Using your chat-api.php backend
    ========================= */
    function initChat() {
        const chatToggle = document.getElementById('footerChatToggle');
        const chatWindow = document.getElementById('footerChatWindow');
        const closeChat  = document.getElementById('footerCloseChat');
        const chatInput  = document.getElementById('footerChatInput');
        const sendChat   = document.getElementById('footerSendChat');
        const chatBody   = document.getElementById('footerChatBody');
        const typingIndicator = document.getElementById('typingIndicator');
        const quickReplies = document.getElementById('quickReplies');

        // Session ID for chat (from PHP)
        const sessionId = '<?php echo $chat_session_id; ?>';
        let isChatOpen = false;

        // OPEN CHAT
        chatToggle.addEventListener('click', function () {
            if (!isChatOpen) {
                chatWindow.style.display = 'flex';
                chatWindow.hidden = false;
                isChatOpen = true;
                
                // Add entrance animation
                chatWindow.style.animation = 'none';
                setTimeout(() => {
                    chatWindow.style.animation = 'slideUpWindow 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55)';
                }, 10);
                
                chatInput.focus();
                chatBody.scrollTop = chatBody.scrollHeight;
                
                // Hide quick replies after first interaction
                if (quickReplies && localStorage.getItem('xgsHasChatted') === 'true') {
                    quickReplies.style.display = 'none';
                }
            } else {
                closeChatWindow();
            }
        });

        // CLOSE CHAT
        closeChat.addEventListener('click', closeChatWindow);

        function closeChatWindow() {
            chatWindow.style.display = 'none';
            isChatOpen = false;
        }

        // TYPING INDICATOR
        function showTyping() {
            typingIndicator.style.display = 'block';
            chatBody.scrollTop = chatBody.scrollHeight;
        }

        function hideTyping() {
            typingIndicator.style.display = 'none';
        }

        // QUICK REPLIES
        if (quickReplies) {
            quickReplies.addEventListener('click', function (e) {
                if (e.target.classList.contains('footer-quick-reply')) {
                    const question = e.target.getAttribute('data-question');
                    if (question) {
                        chatInput.value = question;
                        sendMessage();
                        
                        // Hide quick replies after use
                        quickReplies.style.display = 'none';
                        localStorage.setItem('xgsHasChatted', 'true');
                    }
                }
            });
        }

        // SEND MESSAGE TO AI BACKEND (chat-api.php)
        async function sendMessageToAI(message) {
            try {
                showTyping();
                
                const response = await fetch('chat-api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        session: sessionId,
                        message: message
                    })
                });

                const data = await response.json();
                hideTyping();

                if (data.reply) {
                    return data.reply;
                } else {
                    throw new Error('No reply from AI');
                }
            } catch (error) {
                console.error('Chat error:', error);
                hideTyping();
                return "<?php echo ft('chat_error'); ?>";
            }
        }

        // ADD MESSAGE TO CHAT
        function addMessage(text, isUser = false) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `footer-chat-message ${isUser ? 'footer-message-user' : 'footer-message-bot'}`;
            messageDiv.innerHTML = text;
            
            chatBody.appendChild(messageDiv);
            
            // Add animation
            messageDiv.style.animation = 'messageSlide 0.3s ease-out';
            
            chatBody.scrollTop = chatBody.scrollHeight;
            return messageDiv;
        }

        // SEND MESSAGE FUNCTION
        async function sendMessage() {
            const text = chatInput.value.trim();
            if (!text) return;

            // Add user message
            addMessage(text, true);
            
            // Clear input
            chatInput.value = '';
            
            // Save to localStorage that user has chatted
            localStorage.setItem('xgsHasChatted', 'true');
            
            // Hide quick replies on first message
            if (quickReplies && !localStorage.getItem('xgsQuickRepliesHidden')) {
                quickReplies.style.animation = 'fadeOut 0.3s ease-out';
                setTimeout(() => {
                    quickReplies.style.display = 'none';
                    localStorage.setItem('xgsQuickRepliesHidden', 'true');
                }, 300);
            }

            try {
                // Get AI response from your backend
                const aiReply = await sendMessageToAI(text);
                
                // Add AI response with slight delay for natural feel
                setTimeout(() => {
                    addMessage(aiReply, false);
                }, 500);
                
            } catch (error) {
                console.error('Chat error:', error);
                addMessage("<?php echo ft('chat_error'); ?>", false);
            }
        }

        // EVENT LISTENERS
        sendChat.addEventListener('click', sendMessage);

        chatInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Input auto-resize for multiline
        chatInput.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 100) + 'px';
        });

        // Click outside to close
        document.addEventListener('click', function (e) {
            if (isChatOpen && 
                !chatWindow.contains(e.target) && 
                !chatToggle.contains(e.target)) {
                closeChatWindow();
            }
        });

        // Escape key to close
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && isChatOpen) {
                closeChatWindow();
            }
        });

        // Initialize chat state
        if (localStorage.getItem('xgsHasChatted') === 'true') {
            if (quickReplies) {
                quickReplies.style.display = 'none';
            }
        }
    }

    /* =========================
       INIT EVERYTHING
    ========================= */
    initMap();
    initChat();

    // Reinitialize safely (language change, ajax reload, etc.)
    window.reinitializeFooter = function () {
        initMap();
        initChat();
    };
});
</script>

    <?php endif; ?>
</body>
</html>