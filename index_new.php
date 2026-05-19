<?php
// ============================================
// INCLUDE HEADER FOR LANGUAGE SWITCHING LOGIC
// (Use __DIR__ so includes resolve on cPanel / subfolder installs.)
// ============================================
require_once __DIR__ . '/header.php';

if (!empty($_GET['card'])) {
    $card = preg_replace('/[^a-z0-9_-]/', '', (string) $_GET['card']);
    $qs = 'card=' . rawurlencode($card);
    if (!empty($current_lang)) {
        $qs .= '&lang=' . rawurlencode((string) $current_lang);
    }
    header('Location: services.php?' . $qs);
    exit;
}

// ============================================
// TRANSLATIONS FOR INDEX PAGE
// ============================================

$index_translations = [
    'en' => [
        // Hero Section
        'hero_title' => 'Transform Your Future with Global Education',
        'hero_description' => 'Join thousands of students who have successfully studied abroad with our comprehensive support system',
        'hero_badge' => '🌟 Trusted by 5000+ Students Worldwide',
        'hero_trust_1' => 'University Admissions',
        'hero_trust_2' => '90% Scholarships',
        'hero_trust_3' => 'Visa Success',
        'hero_scroll' => 'Begin Your Journey',
        'start_application' => 'Start Application',
        'learn_more' => 'Explore Services',
        
        // Stats Section
        'stats_students' => '5000+ Students',
        'stats_scholarships' => '$2M+ Scholarships',
        'stats_countries' => '25+ Countries',
        'stats_partners' => '200+ Partners',
        
        // Features Section
        'features_title' => 'Why Students Choose Xander Global Scholars',
        'feature1_title' => 'Complete Support System',
        'feature1_desc' => 'From applications to arrival, we handle every step of your journey',
        'feature2_title' => 'Maximum Funding',
        'feature2_desc' => 'Access to scholarships up to 90% and education loan partners',
        'feature3_title' => 'Expert Guidance',
        'feature3_desc' => '10+ years of experience in international education',
        'feature4_title' => 'Global Network',
        'feature4_desc' => 'Direct partnerships with top universities worldwide',
        
        // Services Header
        'services_title' => 'Our Complete Service Suite',
        'services_description' => 'Everything you need for successful international education and career growth',

        // Homepage services insights hub
        'insights_eyebrow' => 'Student Success Hub',
        'insights_title' => 'Your Complete Path Abroad — One Platform',
        'insights_description' => 'From first consultation to landing abroad: admissions, funding, visas, jobs, and travel — guided step by step.',
        'insights_journey_title' => 'Your journey at a glance',
        'insights_at_glance' => 'At a glance',
        'insights_services_count' => '7 Services',
        'insights_cta_all' => 'Explore all services',
        'insight_tip_admissions' => 'AI-assisted applications with advisor review — study or work abroad in one flow.',
        'insight_tip_scholarships' => 'Match with institution scholarships and education loan partners up to 90% funding.',
        'insight_tip_i20' => 'SEVIS-ready I-20 support with university coordination for US study.',
        'insight_tip_credit' => 'Transfer credits to partner universities and shorten your degree timeline.',
        'insight_tip_visa' => 'Document prep, mock interviews, and high-approval visa guidance.',
        'insight_tip_jobs' => 'Work placements across Europe with relocation and accommodation support.',
        'insight_tip_airticket' => 'Student fares, flexible changes, and full travel coordination.',
        
        // New: Global Universities Section
        'universities_title' => 'Top Global Universities We Work With',
        'universities_description' => 'Direct partnerships with world-class institutions across continents',
        
        // New: Destination Countries Section
        'destinations_title' => 'Popular Study Destinations',
        'destinations_description' => 'Choose from our most popular study and work abroad destinations',
        
        // New: Process Section
        'process_title' => 'Your 5-Step Success Journey',
        'process_description' => 'A proven methodology that ensures your international journey is smooth and successful',
        'process_step1' => 'Free Consultation',
        'process_step2' => 'Profile Assessment',
        'process_step3' => 'University/Job Matching',
        'process_step4' => 'Application & Visa',
        'process_step5' => 'Pre-Departure Support',
        'process_step1_desc' => 'Detailed discussion about your goals and aspirations',
        'process_step2_desc' => 'Comprehensive evaluation of your academic profile',
        'process_step3_desc' => 'Match with ideal universities or job opportunities',
        'process_step4_desc' => 'Complete application and visa documentation support',
        'process_step5_desc' => 'Accommodation, travel, and settling-in assistance',
        
        // Enhanced Success Stories
        'testimonials_title' => 'Success Stories',
        'testimonials_subtitle' => 'Real students, real achievements',
        
        // Testimonial 1-10
        'testimonial1' => 'Xander helped me secure a 90% scholarship at my dream university in Canada. The entire process was seamless!',
        'testimonial1_name' => 'Sarah M.',
        'testimonial1_location' => 'University of Toronto',
        'testimonial1_achievement' => '90% Scholarship',
        
        'testimonial2' => 'The visa guidance and interview preparation were exceptional. Got my US student visa approved in first attempt.',
        'testimonial2_name' => 'James K.',
        'testimonial2_location' => 'NYU Stern',
        'testimonial2_achievement' => 'Visa Approved First Attempt',
        
        'testimonial3' => 'From application to arrival, Xander provided complete support. Their job placement service is outstanding.',
        'testimonial3_name' => 'Priya S.',
        'testimonial3_location' => 'Berlin, Germany',
        'testimonial3_achievement' => 'Job Placement in 30 Days',
        
        'testimonial4' => 'Received multiple offers from UK universities with scholarships. Xander made the impossible possible!',
        'testimonial4_name' => 'Ahmed R.',
        'testimonial4_location' => 'Imperial College London',
        'testimonial4_achievement' => '3 University Offers',
        
        'testimonial5' => 'The credit transfer process saved me a year of study and significant tuition fees. Excellent service!',
        'testimonial5_name' => 'Lisa T.',
        'testimonial5_location' => 'University of Sydney',
        'testimonial5_achievement' => '1 Year Study Saved',
        
        // Partnership Section
        'partners_title' => 'Trusted by Industry Leaders',
        'partners_description' => 'We collaborate with leading educational and financial institutions worldwide',
        
        // Banking & Financial Partners
        'banking_partners_title' => 'Banking & Financial Partners',
        'banking_partners_desc' => 'Trusted banking institutions for student loans and financial services',
        
        // Testing & Certification Partners
        'testing_partners_title' => 'Testing & Certification Partners',
        'testing_partners_desc' => 'Official testing centers and certification bodies for international education',
        
        // Travel & Insurance Partners
        'travel_partners_title' => 'Travel & Insurance Partners',
        'travel_partners_desc' => 'Preferred airlines and insurance providers for international students',
        
        // Technology & Education Partners
        'tech_partners_title' => 'Technology & Education Partners',
        'tech_partners_desc' => 'Leading technology platforms for digital learning and career development',
        
        // Resources Section
        'resources_title' => 'Latest Insights & Resources',
        'resources_description' => 'Stay updated with the latest in international education and career trends',
        'resource1_title' => 'Top 10 Scholarships for 2025',
        'resource1_desc' => 'Complete guide to fully-funded opportunities',
        'resource2_title' => 'Visa Processing Times 2025',
        'resource2_desc' => 'Updated timelines for all major destinations',
        'resource3_title' => 'Job Market Trends Abroad',
        'resource3_desc' => 'In-demand careers in key countries',
        'read_more' => 'Read More',
        
        // CTA Section
        'cta_title' => 'Ready to Transform Your Future?',
        'cta_description' => 'Book a free consultation with our expert advisors today.',
        'book_consultation' => 'Book Free Consultation',
        'download_brochure' => 'Download Brochure',
        
        // Card Translations
        'card_apply' => 'Apply Now',
        'card_copy' => 'Copy Link',
        
        // Card 1: Study & Work Abroad
        'card1_title' => 'Study & Work Abroad',
        'card1_subtitle' => 'Complete guidance for international education and career opportunities',
        'card1_description' => 'Navigate international admissions with expert advice tailored to your academic background and career goals.',
        'card1_point1' => 'Personalized university and course shortlisting',
        'card1_point2' => 'Expert review of SOPs, LORs, and resumes',
        'card1_point3' => 'Student visa interview coaching',
        'card1_point4' => 'Part-time work and post-study visa guidance',
        
        // Card 2: Scholarships & Loans
        'card2_title' => 'Scholarships & Loans',
        'card2_subtitle' => 'Financial aid solutions to fund your international education',
        'card2_description' => 'Find the right mix of scholarships, grants, and loans so your international education stays within reach.',
        'card2_point1' => 'Merit-based and need-based scholarship matching',
        'card2_point2' => 'Scholarship essay and application support',
        'card2_point3' => 'Fast-track student loan approvals with partner banks',
        'card2_point4' => 'Collateral-free loan guidance for eligible students',
        'scholarships_home_title' => 'Institution Scholarships',
        'scholarships_home_desc' => 'Apply directly to scholarship programs from our partner universities and institutions.',
        'loans_home_title' => 'Education Loan Opportunities',
        'loans_home_desc' => 'Student loan programs published by partner institutions.',
        'loan_apply_cta' => 'Explore loan',
        'opp_mixed_title' => 'Scholarships & Education Loans',
        'opp_mixed_desc' => 'Funding opportunities from partner institutions — apply directly.',
        'promo_section_title' => 'Plan Your Journey with Xander',
        'promo_section_desc' => 'Apply, fund your studies, or speak with an advisor — we guide you end to end.',
        'promo_apply_title' => 'Start your study abroad application',
        'promo_apply_desc' => 'AI-assisted admissions with expert review — universities, visas, and documents in one flow.',
        'promo_apply_cta' => 'Apply now',
        'promo_loan_title' => 'Education loans & scholarships',
        'promo_loan_desc' => 'Explore funding up to 90% and loan partners for Canada, USA, UK, and more.',
        'promo_loan_cta' => 'View funding options',
        'promo_consult_title' => 'Free expert consultation',
        'promo_consult_desc' => 'Book a no-cost session with our advisors to plan your international journey.',
        'promo_consult_cta' => 'Book consultation',
        'inst_opp_title' => 'Opportunities from Our Partner Institutions',
        'inst_opp_desc' => 'Scholarships and education loans published by institutions through the Xander institution dashboard.',
        'inst_opp_badge' => 'Live from institution dashboard',
        
        // Card 3: I-20 Application
        'card3_title' => 'I-20 Application',
        'card3_subtitle' => 'Expedited processing for US study permits and SEVIS compliance',
        'card3_description' => 'Get your Form I-20 processed accurately and on time so you can start your US studies without delays.',
        'card3_point1' => 'Step-by-step SEVIS fee payment assistance',
        'card3_point2' => 'Financial document verification for universities',
        'card3_point3' => 'Coordination with university international offices',
        'card3_point4' => 'Guidance on maintaining F-1 student status',
        
        // Card 4: Credit Transfer
        'card4_title' => 'Credit Transfer',
        'card4_subtitle' => 'Seamless credit evaluation and transfer to partner universities',
        'card4_description' => 'Transfer completed coursework toward your degree abroad without losing progress you have already earned.',
        'card4_point1' => 'GPA conversion and academic record review',
        'card4_point2' => 'Course-by-course equivalency mapping',
        'card4_point3' => 'Strategies to maximize accepted credit hours',
        'card4_point4' => 'Liaison with partner university registrars',
        
        // Card 5: Visa Application
        'card5_title' => 'Visa Application',
        'card5_subtitle' => 'Complete visa support for students, work, and travel purposes',
        'card5_description' => 'Expert support from document gathering through your visa interview and departure.',
        'card5_point1' => 'Checklist-driven document preparation and review',
        'card5_point2' => 'Application forms and embassy appointment booking',
        'card5_point3' => 'Mock visa interviews with personalized feedback',
        'card5_point4' => 'Pre-travel briefing and compliance reminders',
        
        // Card 6: Apply for Job
        'card6_title' => 'Apply for Job',
        'card6_subtitle' => 'International career opportunities across Europe and beyond',
        'card6_description' => 'Launch or advance your career abroad with end-to-end support from search to offer.',
        'card6_point1' => 'Curated roles matched to your skills and goals',
        'card6_point2' => 'Resume and cover letter for international markets',
        'card6_point3' => 'Interview coaching with HR and hiring managers',
        'card6_point4' => 'Access to employer and recruiter partner network',
        
        // Card 7: Airticketing Reservation
        'card7_title' => 'Airticketing Reservation',
        'card7_subtitle' => 'Specialized flight bookings for students and professionals',
        'card7_description' => 'Book flights on timelines that fit academic calendars, with fares and flexibility built for students.',
        'card7_point1' => 'Discounted student and academic fares where eligible',
        'card7_point2' => 'Group booking rates for cohorts and families',
        'card7_point3' => 'Flexible date changes and ticketing options',
        'card7_point4' => 'Travel insurance and itinerary guidance',
        
        // Page Metadata
        'page_description' => 'Xander Global Scholars - Your complete journey to international education and career success. Study abroad, scholarships, visas, and job opportunities.',
        'page_title' => 'Xander Global Scholars - Transform Your Future with Global Education',
    ],
    
    'fr' => [
        // French translations (keeping existing ones for brevity)
        'hero_title' => 'Transformez votre avenir avec l\'éducation mondiale',
        'hero_description' => 'Rejoignez des milliers d\'étudiants qui ont réussi leurs études à l\'étranger avec notre système de soutien complet',
        'hero_badge' => '🌟 Approuvé par 5000+ étudiants dans le monde',
        'hero_trust_1' => 'Admissions universitaires',
        'hero_trust_2' => 'Bourses 90%',
        'hero_trust_3' => 'Succès visa',
        'hero_scroll' => 'Commencez votre voyage',
        'start_application' => 'Commencer la candidature',
        'learn_more' => 'Explorer les services',
        
        // Stats Section
        'stats_students' => '5000+ Étudiants',
        'stats_scholarships' => '2M+$ Bourses',
        'stats_countries' => '25+ Pays',
        'stats_partners' => '200+ Partenaires',
        
        // Features Section
        'features_title' => 'Pourquoi les étudiants choisissent Xander Global Scholars',
        'feature1_title' => 'Système de soutien complet',
        'feature1_desc' => 'Des candidatures à l\'arrivée, nous gérons chaque étape de votre voyage',
        'feature2_title' => 'Financement maximum',
        'feature2_desc' => 'Accès aux bourses jusqu\'à 90% et partenaires de prêts étudiants',
        'feature3_title' => 'Guidance experte',
        'feature3_desc' => '10+ ans d\'expérience en éducation internationale',
        'feature4_title' => 'Réseau mondial',
        'feature4_desc' => 'Partenariats directs avec les meilleures universités',
        
        // Services Header
        'services_title' => 'Notre suite de services complète',
        'services_description' => 'Tout ce dont vous avez besoin pour une éducation internationale et une carrière réussies',

        // Keep all existing French translations...
        'insights_eyebrow' => 'Hub de réussite étudiante',
        'insights_title' => 'Votre chemin complet à l\'étranger — Une plateforme',
        'insights_description' => 'De la première consultation à l\'atterrissage : admissions, financement, visas, emplois et voyage.',
        'insights_journey_title' => 'Votre voyage en un coup d\'œil',
        'insights_at_glance' => 'En un coup d\'œil',
        'insights_services_count' => '7 Services',
        'insights_cta_all' => 'Explorer tous les services',
        
        // ... (keeping all other existing French translations)
        'page_description' => 'Xander Global Scholars - Votre voyage complet vers le succès en éducation internationale.',
        'page_title' => 'Xander Global Scholars - Transformez votre avenir',
    ],
];

// Translation function
if (!function_exists('it')) {
    function it(string $key): string {
        global $index_translations, $current_lang;
        $lang = $current_lang ?? 'en';
        return $index_translations[$lang][$key] ?? $index_translations['en'][$key] ?? $key;
    }
}

// Page metadata
$pageTitle = it('page_title');
$pageDescription = it('page_description');
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang ?? 'en', ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?>">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* ==========================================================
           XANDER GLOBAL SCHOLARS - MODERN HOMEPAGE 2025
           Premium, responsive, and conversion-optimized design
        ========================================================== */
        
        :root {
            --primary-navy: #012F6B;
            --primary-blue: #254D81;
            --accent-orange: #F2A65A;
            --accent-orange-dark: #E6892E;
            --accent-mint: #0d9488;
            --text-primary: #0F172A;
            --text-secondary: #64748b;
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-tertiary: #f1f5f9;
            --border-light: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            line-height: 1.6;
            color: var(--text-primary);
            background: var(--bg-primary);
            overflow-x: hidden;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        /* ===== HERO SECTION ===== */
        .hero {
            min-height: 100vh;
            background: 
                radial-gradient(ellipse at top right, rgba(242, 166, 90, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at bottom left, rgba(1, 47, 107, 0.1) 0%, transparent 50%),
                linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(1,47,107,0.03)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.4;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 900px;
            margin: 0 auto;
        }
        
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, rgba(242, 166, 90, 0.1), rgba(242, 166, 90, 0.05));
            border: 1px solid rgba(242, 166, 90, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--accent-orange-dark);
            margin-bottom: 2rem;
            animation: fadeInDown 0.8s ease;
        }
        
        .hero-title {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--primary-navy) 0%, var(--primary-blue) 50%, var(--accent-orange) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: fadeInUp 0.8s ease 0.2s both;
        }
        
        .hero-description {
            font-size: clamp(1.1rem, 2vw, 1.3rem);
            color: var(--text-secondary);
            margin-bottom: 3rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            animation: fadeInUp 0.8s ease 0.4s both;
        }
        
        .hero-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeInUp 0.8s ease 0.6s both;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-navy), var(--primary-blue));
            color: white;
            box-shadow: 0 10px 25px rgba(1, 47, 107, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(1, 47, 107, 0.4);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.9);
            color: var(--primary-navy);
            border: 2px solid var(--border-light);
            backdrop-filter: blur(10px);
        }
        
        .btn-secondary:hover {
            background: var(--primary-navy);
            color: white;
            border-color: var(--primary-navy);
            transform: translateY(-2px);
        }
        
        /* ===== STATS SECTION ===== */
        .stats {
            padding: 5rem 0;
            background: var(--bg-secondary);
            position: relative;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            text-align: center;
        }
        
        .stat-card {
            background: var(--bg-primary);
            padding: 2rem 1.5rem;
            border-radius: 1rem;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            border: 1px solid var(--border-light);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
            border-color: var(--accent-orange);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 900;
            background: linear-gradient(135deg, var(--primary-navy), var(--accent-orange));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        /* ===== FEATURES SECTION ===== */
        .features {
            padding: 5rem 0;
            background: var(--bg-primary);
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }
        
        .section-title {
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 800;
            margin-bottom: 1rem;
            color: var(--primary-navy);
        }
        
        .section-description {
            font-size: 1.2rem;
            color: var(--text-secondary);
            max-width: 700px;
            margin: 0 auto;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }
        
        .feature-card {
            background: var(--bg-primary);
            padding: 2.5rem 2rem;
            border-radius: 1rem;
            text-align: center;
            border: 1px solid var(--border-light);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-navy), var(--accent-orange));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-xl);
            border-color: var(--accent-orange);
        }
        
        .feature-card:hover::before {
            transform: scaleX(1);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, rgba(1, 47, 107, 0.1), rgba(242, 166, 90, 0.1));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--primary-navy);
            transition: var(--transition);
        }
        
        .feature-card:hover .feature-icon {
            transform: scale(1.1) rotate(5deg);
            background: linear-gradient(135deg, var(--primary-navy), var(--accent-orange));
            color: white;
        }
        
        .feature-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--primary-navy);
        }
        
        .feature-description {
            color: var(--text-secondary);
            line-height: 1.7;
        }
        
        /* ===== DYNAMIC SERVICES SECTION ===== */
        .dynamic-services {
            padding: 5rem 0;
            background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%);
        }
        
        /* ===== OPPORTUNITIES SECTION ===== */
        .opportunities {
            padding: 5rem 0;
            background: var(--bg-primary);
        }
        
        /* ===== PROCESS SECTION ===== */
        .process {
            padding: 5rem 0;
            background: var(--bg-secondary);
        }
        
        .process-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }
        
        .process-step {
            text-align: center;
            position: relative;
        }
        
        .process-step::after {
            content: '';
            position: absolute;
            top: 30px;
            right: -20px;
            width: 40px;
            height: 2px;
            background: linear-gradient(90deg, var(--accent-orange), transparent);
        }
        
        .process-step:last-child::after {
            display: none;
        }
        
        .step-number {
            width: 60px;
            height: 60px;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, var(--primary-navy), var(--accent-orange));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .step-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--primary-navy);
        }
        
        .step-description {
            color: var(--text-secondary);
            line-height: 1.6;
        }
        
        /* ===== TESTIMONIALS SECTION ===== */
        .testimonials {
            padding: 5rem 0;
            background: var(--bg-primary);
        }
        
        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }
        
        .testimonial-card {
            background: var(--bg-primary);
            padding: 2rem;
            border-radius: 1rem;
            border: 1px solid var(--border-light);
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            position: relative;
        }
        
        .testimonial-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }
        
        .testimonial-content {
            font-style: italic;
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            line-height: 1.7;
            position: relative;
            padding-left: 2rem;
        }
        
        .testimonial-content::before {
            content: '"';
            position: absolute;
            left: 0;
            top: 0;
            font-size: 3rem;
            color: var(--accent-orange);
            opacity: 0.3;
        }
        
        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .author-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-navy), var(--accent-orange));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
        }
        
        .author-info h4 {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 0.2rem;
            color: var(--primary-navy);
        }
        
        .author-info p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .testimonial-achievement {
            display: inline-block;
            background: rgba(242, 166, 90, 0.1);
            color: var(--accent-orange-dark);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }
        
        /* ===== CTA SECTION ===== */
        .cta {
            padding: 5rem 0;
            background: linear-gradient(135deg, var(--primary-navy) 0%, var(--primary-blue) 100%);
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .cta::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="cta-grid" width="20" height="20" patternUnits="userSpaceOnUse"><path d="M 20 0 L 0 0 0 20" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23cta-grid)"/></svg>');
        }
        
        .cta-content {
            position: relative;
            z-index: 2;
        }
        
        .cta-title {
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 800;
            margin-bottom: 1rem;
        }
        
        .cta-description {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .cta-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-cta {
            background: white;
            color: var(--primary-navy);
        }
        
        .btn-cta:hover {
            background: var(--accent-orange);
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-cta-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
        }
        
        .btn-cta-outline:hover {
            background: white;
            color: var(--primary-navy);
        }
        
        /* ===== ANIMATIONS ===== */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }
        
        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 768px) {
            .hero {
                padding: 2rem 0;
                min-height: auto;
            }
            
            .hero-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 300px;
            }
            
            .stats-grid,
            .features-grid,
            .process-grid,
            .testimonials-grid {
                grid-template-columns: 1fr;
            }
            
            .process-step::after {
                display: none;
            }
            
            .container {
                padding: 0 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .hero-title {
                font-size: 2rem;
            }
            
            .hero-description {
                font-size: 1rem;
            }
            
            .section-title {
                font-size: 1.5rem;
            }
            
            .feature-card,
            .testimonial-card {
                padding: 1.5rem;
            }
        }
        
        /* ===== SCROLL TO TOP ===== */
        .scroll-top {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-navy), var(--accent-orange));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
            z-index: 1000;
        }
        
        .scroll-top.visible {
            opacity: 1;
            visibility: visible;
        }
        
        .scroll-top:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }
    </style>
</head>
<body>
    <!-- ===== HERO SECTION ===== -->
    <section class="hero" id="home">
        <div class="container">
            <div class="hero-content">
                <div class="hero-badge">
                    <i class="fas fa-star"></i>
                    <?php echo htmlspecialchars(it('hero_badge'), ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <h1 class="hero-title">
                    <?php echo htmlspecialchars(it('hero_title'), ENT_QUOTES, 'UTF-8'); ?>
                </h1>
                <p class="hero-description">
                    <?php echo htmlspecialchars(it('hero_description'), ENT_QUOTES, 'UTF-8'); ?>
                </p>
                <div class="hero-actions">
                    <a href="student-application.php" class="btn btn-primary">
                        <i class="fas fa-rocket"></i>
                        <?php echo htmlspecialchars(it('start_application'), ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                    <a href="#services" class="btn btn-secondary">
                        <i class="fas fa-compass"></i>
                        <?php echo htmlspecialchars(it('learn_more'), ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== STATS SECTION ===== -->
    <section class="stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card fade-in">
                    <div class="stat-number"><?php echo htmlspecialchars(it('stats_students'), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="stat-label"><?php echo htmlspecialchars(it('hero_trust_1'), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <div class="stat-card fade-in">
                    <div class="stat-number"><?php echo htmlspecialchars(it('stats_scholarships'), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="stat-label"><?php echo htmlspecialchars(it('hero_trust_2'), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <div class="stat-card fade-in">
                    <div class="stat-number"><?php echo htmlspecialchars(it('stats_countries'), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="stat-label"><?php echo htmlspecialchars(it('stats_countries'), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <div class="stat-card fade-in">
                    <div class="stat-number"><?php echo htmlspecialchars(it('stats_partners'), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="stat-label"><?php echo htmlspecialchars(it('stats_partners'), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== FEATURES SECTION ===== -->
    <section class="features">
        <div class="container">
            <div class="section-header fade-in">
                <h2 class="section-title"><?php echo htmlspecialchars(it('features_title'), ENT_QUOTES, 'UTF-8'); ?></h2>
                <p class="section-description"><?php echo htmlspecialchars(it('services_description'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="features-grid">
                <div class="feature-card fade-in">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="feature-title"><?php echo htmlspecialchars(it('feature1_title'), ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p class="feature-description"><?php echo htmlspecialchars(it('feature1_desc'), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <div class="feature-card fade-in">
                    <div class="feature-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <h3 class="feature-title"><?php echo htmlspecialchars(it('feature2_title'), ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p class="feature-description"><?php echo htmlspecialchars(it('feature2_desc'), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <div class="feature-card fade-in">
                    <div class="feature-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h3 class="feature-title"><?php echo htmlspecialchars(it('feature3_title'), ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p class="feature-description"><?php echo htmlspecialchars(it('feature3_desc'), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <div class="feature-card fade-in">
                    <div class="feature-icon">
                        <i class="fas fa-globe"></i>
                    </div>
                    <h3 class="feature-title"><?php echo htmlspecialchars(it('feature4_title'), ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p class="feature-description"><?php echo htmlspecialchars(it('feature4_desc'), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== DYNAMIC SERVICES SECTION (PRESERVED) ===== -->
    <?php
    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/includes/homepage_services_insights.php';
    ?>

    <!-- ===== DYNAMIC OPPORTUNITIES SECTION (PRESERVED) ===== -->
    <?php
    require_once __DIR__ . '/includes/homepage_opportunities.php';
    ?>

    <!-- ===== PROCESS SECTION ===== -->
    <section class="process" id="process">
        <div class="container">
            <div class="section-header fade-in">
                <h2 class="section-title"><?php echo htmlspecialchars(it('process_title'), ENT_QUOTES, 'UTF-8'); ?></h2>
                <p class="section-description"><?php echo htmlspecialchars(it('process_description'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="process-grid">
                <div class="process-step fade-in">
                    <div class="step-number">1</div>
                    <h3 class="step-title"><?php echo htmlspecialchars(it('process_step1'), ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p class="step-description"><?php echo htmlspecialchars(it('process_step1_desc'), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <div class="process-step fade-in">
                    <div class="step-number">2</div>
                    <h3 class="step-title"><?php echo htmlspecialchars(it('process_step2'), ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p class="step-description"><?php echo htmlspecialchars(it('process_step2_desc'), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <div class="process-step fade-in">
                    <div class="step-number">3</div>
                    <h3 class="step-title"><?php echo htmlspecialchars(it('process_step3'), ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p class="step-description"><?php echo htmlspecialchars(it('process_step3_desc'), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <div class="process-step fade-in">
                    <div class="step-number">4</div>
                    <h3 class="step-title"><?php echo htmlspecialchars(it('process_step4'), ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p class="step-description"><?php echo htmlspecialchars(it('process_step4_desc'), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <div class="process-step fade-in">
                    <div class="step-number">5</div>
                    <h3 class="step-title"><?php echo htmlspecialchars(it('process_step5'), ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p class="step-description"><?php echo htmlspecialchars(it('process_step5_desc'), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== TESTIMONIALS SECTION ===== -->
    <section class="testimonials">
        <div class="container">
            <div class="section-header fade-in">
                <h2 class="section-title"><?php echo htmlspecialchars(it('testimonials_title'), ENT_QUOTES, 'UTF-8'); ?></h2>
                <p class="section-description"><?php echo htmlspecialchars(it('testimonials_subtitle'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="testimonials-grid">
                <div class="testimonial-card fade-in">
                    <div class="testimonial-content">
                        <?php echo htmlspecialchars(it('testimonial1'), ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">SM</div>
                        <div class="author-info">
                            <h4><?php echo htmlspecialchars(it('testimonial1_name'), ENT_QUOTES, 'UTF-8'); ?></h4>
                            <p><?php echo htmlspecialchars(it('testimonial1_location'), ENT_QUOTES, 'UTF-8'); ?></p>
                            <span class="testimonial-achievement"><?php echo htmlspecialchars(it('testimonial1_achievement'), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card fade-in">
                    <div class="testimonial-content">
                        <?php echo htmlspecialchars(it('testimonial2'), ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">JK</div>
                        <div class="author-info">
                            <h4><?php echo htmlspecialchars(it('testimonial2_name'), ENT_QUOTES, 'UTF-8'); ?></h4>
                            <p><?php echo htmlspecialchars(it('testimonial2_location'), ENT_QUOTES, 'UTF-8'); ?></p>
                            <span class="testimonial-achievement"><?php echo htmlspecialchars(it('testimonial2_achievement'), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card fade-in">
                    <div class="testimonial-content">
                        <?php echo htmlspecialchars(it('testimonial3'), ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">PS</div>
                        <div class="author-info">
                            <h4><?php echo htmlspecialchars(it('testimonial3_name'), ENT_QUOTES, 'UTF-8'); ?></h4>
                            <p><?php echo htmlspecialchars(it('testimonial3_location'), ENT_QUOTES, 'UTF-8'); ?></p>
                            <span class="testimonial-achievement"><?php echo htmlspecialchars(it('testimonial3_achievement'), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== CTA SECTION ===== -->
    <section class="cta">
        <div class="container">
            <div class="cta-content fade-in">
                <h2 class="cta-title"><?php echo htmlspecialchars(it('cta_title'), ENT_QUOTES, 'UTF-8'); ?></h2>
                <p class="cta-description"><?php echo htmlspecialchars(it('cta_description'), ENT_QUOTES, 'UTF-8'); ?></p>
                <div class="cta-actions">
                    <a href="contact.php" class="btn btn-cta">
                        <i class="fas fa-calendar-check"></i>
                        <?php echo htmlspecialchars(it('book_consultation'), ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                    <a href="#" class="btn btn-cta-outline">
                        <i class="fas fa-download"></i>
                        <?php echo htmlspecialchars(it('download_brochure'), ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== SCROLL TO TOP ===== -->
    <div class="scroll-top" id="scrollTop">
        <i class="fas fa-arrow-up"></i>
    </div>

    <!-- ===== FOOTER ===== -->
    <?php include 'footer.php'; ?>

    <script>
        // ===== FADE IN ANIMATION =====
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.fade-in').forEach(el => {
            observer.observe(el);
        });

        // ===== SMOOTH SCROLLING =====
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // ===== SCROLL TO TOP =====
        const scrollTopBtn = document.getElementById('scrollTop');
        
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                scrollTopBtn.classList.add('visible');
            } else {
                scrollTopBtn.classList.remove('visible');
            }
        });

        scrollTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // ===== DYNAMIC TYPING EFFECT (Optional) =====
        const heroTitle = document.querySelector('.hero-title');
        if (heroTitle) {
            const text = heroTitle.textContent;
            heroTitle.textContent = '';
            let i = 0;
            
            function typeWriter() {
                if (i < text.length) {
                    heroTitle.textContent += text.charAt(i);
                    i++;
                    setTimeout(typeWriter, 50);
                }
            }
            
            setTimeout(typeWriter, 1000);
        }

        // ===== PARALLAX EFFECT =====
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const hero = document.querySelector('.hero');
            if (hero) {
                hero.style.transform = `translateY(${scrolled * 0.5}px)`;
            }
        });
    </script>
</body>
</html>
