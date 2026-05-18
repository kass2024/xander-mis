<?php
// ============================================
// INCLUDE HEADER FOR LANGUAGE SWITCHING LOGIC
// (Use __DIR__ so includes resolve on cPanel / subfolder installs.)
// ============================================
require_once __DIR__ . '/header.php';

// ============================================
// TRANSLATIONS FOR INDEX PAGE
// ============================================

$index_translations = [
    'en' => [
        // Hero Section
        'hero_title' => 'Global Education & Career Journey — Simplified',
        'hero_description' => 'Xander Global Scholars supports students and professionals with admissions, visas, scholarships, jobs, and travel — all in one trusted platform.',
        'start_application' => 'Start Your Application',
        'learn_more' => 'Learn More',
        
        // Stats Section
        'stats_students' => 'Students Placed',
        'stats_scholarships' => 'Scholarships Awarded',
        'stats_countries' => 'Countries Worldwide',
        'stats_partners' => 'University Partners',
        
        // Features Section
        'features_title' => 'Why Choose Xander Global Scholars',
        'feature1_title' => 'Personalized Roadmaps',
        'feature1_desc' => 'Customized plans tailored to your academic and career goals',
        'feature2_title' => 'Expert Guidance',
        'feature2_desc' => 'Certified advisors with 10+ years of industry experience',
        'feature3_title' => 'Global Network',
        'feature3_desc' => 'Direct partnerships with top universities worldwide',
        'feature4_title' => 'Financial Support',
        'feature4_desc' => 'Access to scholarships and education loan programs',
        
        // Services Header
        'services_title' => 'Our Services',
        'services_description' => 'Everything you need to study, work, study loan, credit and move abroad — guided by experts.',
        
        // New: Global Universities Section
        'universities_title' => 'Top Global Universities We Work With',
        'universities_description' => 'Direct partnerships with world-class institutions across continents',
        
        // New: Destination Countries Section
        'destinations_title' => 'Study & Work Destinations',
        'destinations_description' => 'Choose from our most popular study and work abroad destinations',
        
        // New: Process Section
        'process_title' => 'Our 5-Step Success Process',
        'process_description' => 'A proven methodology that ensures your international journey is smooth and successful',
        'process_step1' => 'Initial Consultation',
        'process_step2' => 'Profile Assessment',
        'process_step3' => 'University/Job Matching',
        'process_step4' => 'Application & Visa',
        'process_step5' => 'Pre-Departure & Arrival',
        'process_step1_desc' => 'Free detailed discussion about your goals and aspirations',
        'process_step2_desc' => 'Comprehensive evaluation of your academic and professional profile',
        'process_step3_desc' => 'Match with ideal universities or job opportunities',
        'process_step4_desc' => 'Complete application and visa documentation support',
        'process_step5_desc' => 'Accommodation, travel, and settling-in assistance',
        
        // Enhanced Success Stories
        'testimonials_title' => 'Success Stories',
        'testimonials_subtitle' => 'Real students, real achievements',
        
        // Testimonial 1-10
        'testimonial1' => 'Xander helped me secure a 90% scholarship at my dream university in Canada. The entire process was seamless!',
        'testimonial1_name' => 'Sarah M., Computer Science Student',
        'testimonial1_location' => 'University of Toronto, Canada',
        'testimonial1_achievement' => '90% Scholarship Awarded',
        
        'testimonial2' => 'The visa guidance and interview preparation were exceptional. Got my US student visa approved in first attempt.',
        'testimonial2_name' => 'James K., MBA Applicant',
        'testimonial2_location' => 'NYU Stern, USA',
        'testimonial2_achievement' => 'Visa Approved First Attempt',
        
        'testimonial3' => 'From application to arrival, Xander provided complete support. Their job placement service is outstanding.',
        'testimonial3_name' => 'Priya S., Healthcare Professional',
        'testimonial3_location' => 'Berlin, Germany',
        'testimonial3_achievement' => 'Job Placement in 30 Days',
        
        'testimonial4' => 'Received multiple offers from UK universities with scholarships. Xander made the impossible possible!',
        'testimonial4_name' => 'Ahmed R., Engineering Student',
        'testimonial4_location' => 'Imperial College London, UK',
        'testimonial4_achievement' => '3 University Offers',
        
        'testimonial5' => 'The credit transfer process saved me a year of study and significant tuition fees. Excellent service!',
        'testimonial5_name' => 'Lisa T., Business Student',
        'testimonial5_location' => 'University of Sydney, Australia',
        'testimonial5_achievement' => '1 Year Study Saved',
        
        'testimonial6' => 'As a working professional, Xander helped me transition to a European job with family relocation support.',
        'testimonial6_name' => 'David L., IT Professional',
        'testimonial6_location' => 'Amsterdam, Netherlands',
        'testimonial6_achievement' => 'Family Relocation Complete',
        
        'testimonial7' => 'Medical residency placement in the US seemed impossible until I worked with Xander Global Scholars.',
        'testimonial7_name' => 'Dr. Maria G., Medical Resident',
        'testimonial7_location' => 'Johns Hopkins, USA',
        'testimonial7_achievement' => 'Residency Match Success',
        
        'testimonial8' => 'Full scholarship for PhD in Artificial Intelligence. Xander\'s guidance was invaluable.',
        'testimonial8_name' => 'Kenji Y., PhD Candidate',
        'testimonial8_location' => 'ETH Zurich, Switzerland',
        'testimonial8_achievement' => 'Full PhD Scholarship',
        
        'testimonial9' => 'Work permit and job placement in Canada within 4 months of contacting Xander.',
        'testimonial9_name' => 'Rahul P., Software Developer',
        'testimonial9_location' => 'Vancouver, Canada',
        'testimonial9_achievement' => 'Job in 4 Months',
        
        'testimonial10' => 'Study abroad with my spouse seemed complicated, but Xander handled everything perfectly.',
        'testimonial10_name' => 'Sophie & Mark, Couple',
        'testimonial10_location' => 'Dublin, Ireland',
        'testimonial10_achievement' => 'Dual Admission Success',
        
        // New: Partnership Section
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
        
        // New: Blog/Resources Section
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
        'cta_title' => 'Ready to Begin Your Journey?',
        'cta_description' => 'Book a free consultation with our expert advisors today.',
        'book_consultation' => 'Book Free Consultation',
        'download_brochure' => 'Download Brochure',
        
        // Card Translations
        'card_apply' => 'Apply Now',
        'card_copy' => 'Copy Link',
        
        // Card 1: Study & Work Abroad
        'card1_title' => 'Study & Work Abroad',
        'card1_subtitle' => 'Universities, jobs, visas – all in one place',
        'card1_description' => 'Comprehensive support for your international education and career journey.',
        'card1_point1' => 'University applications',
        'card1_point2' => 'Work visa support',
        'card1_point3' => 'Real advisor guidance',
        
        // Card 2: Scholarships & Loans
        'card2_title' => 'Scholarships & Loans',
        'card2_subtitle' => 'Funding solutions tailored to your needs',
        'card2_description' => 'Access financial assistance programs designed to make your education affordable.',
        'card2_point1' => 'Up to 90% scholarships',
        'card2_point2' => 'Education loans (Canada & USA)',
        'card2_point3' => 'Financial planning support',
        'scholarships_home_title' => 'Institution Scholarships',
        'scholarships_home_desc' => 'Apply directly to scholarship programs from our partner universities and institutions.',
        
        // Card 3: I-20 Application
        'card3_title' => 'I-20 Application',
        'card3_subtitle' => 'Fast processing for US institutions',
        'card3_description' => 'Streamlined I-20 application process for US educational institutions.',
        'card3_point1' => 'SEVIS compliant',
        'card3_point2' => 'University coordination',
        'card3_point3' => 'Interview readiness',
        
        // Card 4: Credit Transfer
        'card4_title' => 'Credit Transfer',
        'card4_subtitle' => 'Transfer credits to partner universities',
        'card4_description' => 'Maximize your academic progress by transferring credits.',
        'card4_point1' => 'Transcript evaluation',
        'card4_point2' => 'Course equivalency',
        'card4_point3' => 'Reduced study duration',
        
        // Card 5: Visa Application
        'card5_title' => 'Visa Application',
        'card5_subtitle' => 'Study & visit visas with full guidance',
        'card5_description' => 'Complete visa application support for study and visit purposes.',
        'card5_point1' => 'Document preparation',
        'card5_point2' => 'Mock interviews',
        'card5_point3' => 'High approval rate',
        
        // Card 6: Apply for Job
        'card6_title' => 'Apply for Job',
        'card6_subtitle' => 'Work opportunities across Europe',
        'card6_description' => 'Launch your international career with our job placement services.',
        'card6_point1' => 'Job placement support',
        'card6_point2' => 'Accommodation assistance',
        'card6_point3' => 'Airport pickup',
        
        // Card 7: Airticketing Reservation
        'card7_title' => 'Airticketing Reservation',
        'card7_subtitle' => 'Flight bookings for students & professionals',
        'card7_description' => 'Hassle-free international and domestic flight booking.',
        'card7_point1' => 'Exclusive student/academic fares',
        'card7_point2' => 'Flexible change & cancellation',
        'card7_point3' => 'End-to-end travel support',
        
        // Page Metadata
        'page_description' => 'Xander Global Scholars - Your complete journey to international education and career success. Study abroad, scholarships, visas, and job opportunities.',
        'page_title' => 'Xander Global Scholars - Your Complete Journey to Success',
    ],
    
    'fr' => [
        // Hero Section
        'hero_title' => 'Parcours d\'Éducation & Carrière Mondial — Simplifié',
        'hero_description' => 'Xander Global Scholars accompagne les étudiants et professionnels avec admissions, visas, bourses, emplois et voyages.',
        'start_application' => 'Commencer votre candidature',
        'learn_more' => 'En Savoir Plus',
        
        // Stats Section
        'stats_students' => 'Étudiants placés',
        'stats_scholarships' => 'Bourses attribuées',
        'stats_countries' => 'Pays dans le monde',
        'stats_partners' => 'Partenaires universitaires',
        
        // Features Section
        'features_title' => 'Pourquoi choisir Xander Global Scholars',
        'feature1_title' => 'Plans personnalisés',
        'feature1_desc' => 'Plans sur mesure adaptés à vos objectifs académiques et professionnels',
        'feature2_title' => 'Guidance experte',
        'feature2_desc' => 'Conseillers certifiés avec plus de 10 ans d\'expérience',
        'feature3_title' => 'Réseau mondial',
        'feature3_desc' => 'Partenariats directs avec les meilleures universités',
        'feature4_title' => 'Support financier',
        'feature4_desc' => 'Accès aux bourses et programmes de prêts éducatifs',
        
        // Services Header
        'services_title' => 'Nos Services',
        'services_description' => 'Tout ce dont vous avez besoin pour étudier, travailler et déménager à l\'étranger.',
        
        // New: Global Universities Section
        'universities_title' => 'Top Universités Mondiales',
        'universities_description' => 'Partenariats directs avec des institutions de classe mondiale',
        
        // New: Destination Countries Section
        'destinations_title' => 'Destinations d\'Études & Travail',
        'destinations_description' => 'Choisissez parmi nos destinations les plus populaires',
        
        // New: Process Section
        'process_title' => 'Notre Processus en 5 Étapes',
        'process_description' => 'Une méthodologie éprouvée pour un parcours international réussi',
        'process_step1' => 'Consultation Initiale',
        'process_step2' => 'Évaluation de Profil',
        'process_step3' => 'Matching Université/Emploi',
        'process_step4' => 'Candidature & Visa',
        'process_step5' => 'Pré-départ & Arrivée',
        'process_step1_desc' => 'Discussion détaillée gratuite sur vos objectifs',
        'process_step2_desc' => 'Évaluation complète de votre profil académique et professionnel',
        'process_step3_desc' => 'Correspondance avec des universités ou emplois idéaux',
        'process_step4_desc' => 'Support complet pour candidature et visa',
        'process_step5_desc' => 'Assistance logement, voyage et installation',
        
        // Enhanced Success Stories
        'testimonials_title' => 'Histoires de Réussite',
        'testimonials_subtitle' => 'Vrais étudiants, vrais succès',
        
        // Testimonial 1-10
        'testimonial1' => 'Xander m\'a aidé à obtenir une bourse de 90% dans mon université de rêve au Canada.',
        'testimonial1_name' => 'Sarah M., Étudiante en informatique',
        'testimonial1_location' => 'Université de Toronto, Canada',
        'testimonial1_achievement' => 'Bourse de 90%',
        
        'testimonial2' => 'Le guide pour le visa était exceptionnel. Visa étudiant américain approuvé du premier coup.',
        'testimonial2_name' => 'James K., Candidat MBA',
        'testimonial2_location' => 'NYU Stern, USA',
        'testimonial2_achievement' => 'Visa Approuvé Première Tentative',
        
        'testimonial3' => 'De la candidature à l\'arrivée, Xander a fourni un soutien complet.',
        'testimonial3_name' => 'Priya S., Professionnelle de la santé',
        'testimonial3_location' => 'Berlin, Allemagne',
        'testimonial3_achievement' => 'Emploi en 30 Jours',
        
        'testimonial4' => 'Plusieurs offres d\'universités britanniques avec bourses. Xander a rendu l\'impossible possible!',
        'testimonial4_name' => 'Ahmed R., Étudiant en ingénierie',
        'testimonial4_location' => 'Imperial College London, UK',
        'testimonial4_achievement' => '3 Offres d\'Université',
        
        'testimonial5' => 'Le transfert de crédits m\'a fait économiser un an d\'études et des frais de scolarité.',
        'testimonial5_name' => 'Lisa T., Étudiante en commerce',
        'testimonial5_location' => 'Université de Sydney, Australie',
        'testimonial5_achievement' => '1 An d\'Études Économisé',
        
        'testimonial6' => 'Transition professionnelle en Europe avec support de relocalisation familiale.',
        'testimonial6_name' => 'David L., Professionnel IT',
        'testimonial6_location' => 'Amsterdam, Pays-Bas',
        'testimonial6_achievement' => 'Relocalisation Familiale',
        
        'testimonial7' => 'Placement en résidence médicale aux États-Unis réussi avec Xander.',
        'testimonial7_name' => 'Dr. Maria G., Résidente Médicale',
        'testimonial7_location' => 'Johns Hopkins, USA',
        'testimonial7_achievement' => 'Match Résidence Réussi',
        
        'testimonial8' => 'Bourse complète pour doctorat en intelligence artificielle.',
        'testimonial8_name' => 'Kenji Y., Candidat Doctorat',
        'testimonial8_location' => 'ETH Zurich, Suisse',
        'testimonial8_achievement' => 'Bourse Doctorat Complète',
        
        'testimonial9' => 'Permis de travail et emploi au Canada en 4 mois.',
        'testimonial9_name' => 'Rahul P., Développeur Logiciel',
        'testimonial9_location' => 'Vancouver, Canada',
        'testimonial9_achievement' => 'Emploi en 4 Mois',
        
        'testimonial10' => 'Études à l\'étranger avec conjoint géré parfaitement par Xander.',
        'testimonial10_name' => 'Sophie & Mark, Couple',
        'testimonial10_location' => 'Dublin, Irlande',
        'testimonial10_achievement' => 'Admission Double Réussie',
        
        // New: Partnership Section
        'partners_title' => 'Reconnu par les Leaders',
        'partners_description' => 'Collaboration avec institutions éducatives et financières leaders',
        
        // Banking & Financial Partners
        'banking_partners_title' => 'Partenaires Bancaires & Financiers',
        'banking_partners_desc' => 'Institutions bancaires de confiance pour les prêts étudiants et services financiers',
        
        // Testing & Certification Partners
        'testing_partners_title' => 'Partenaires de Tests & Certification',
        'testing_partners_desc' => 'Centres de tests officiels et organismes de certification pour l\'éducation internationale',
        
        // Travel & Insurance Partners
        'travel_partners_title' => 'Partenaires Voyage & Assurance',
        'travel_partners_desc' => 'Compagnies aériennes et assureurs préférés pour les étudiants internationaux',
        
        // Technology & Education Partners
        'tech_partners_title' => 'Partenaires Technologie & Éducation',
        'tech_partners_desc' => 'Plateformes technologiques leaders pour l\'apprentissage numérique et le développement de carrière',
        
        // New: Blog/Resources Section
        'resources_title' => 'Dernières Ressources & Insights',
        'resources_description' => 'Restez informé des dernières tendances en éducation internationale',
        'resource1_title' => 'Top 10 Bourses 2024',
        'resource1_desc' => 'Guide complet des opportunités entièrement financées',
        'resource2_title' => 'Délais Visa 2024',
        'resource2_desc' => 'Délais mis à jour pour toutes destinations',
        'resource3_title' => 'Tendances Marché du Travail',
        'resource3_desc' => 'Carrières demandées par pays',
        'read_more' => 'Lire Plus',
        
        // CTA Section
        'cta_title' => 'Prêt à commencer votre voyage ?',
        'cta_description' => 'Réservez une consultation gratuite avec nos conseillers experts.',
        'book_consultation' => 'Réserver une consultation',
        'download_brochure' => 'Télécharger la brochure',
        
        // Card Translations
        'card_apply' => 'Postuler maintenant',
        'card_copy' => 'Copier le lien',
        
        // Card 1: Study & Work Abroad
        'card1_title' => 'Étudier & Travailler à l\'Étranger',
        'card1_subtitle' => 'Universités, emplois, visas – tout au même endroit',
        'card1_description' => 'Soutien complet pour votre parcours international.',
        'card1_point1' => 'Candidatures universitaires',
        'card1_point2' => 'Support visa travail',
        'card1_point3' => 'Guidance conseiller',
        
        // Card 2: Scholarships & Loans
        'card2_title' => 'Bourses & Prêts',
        'card2_subtitle' => 'Solutions de financement adaptées',
        'card2_description' => 'Accédez à des programmes d\'aide financière abordable.',
        'card2_point1' => 'Bourses jusqu\'à 90%',
        'card2_point2' => 'Prêts éducation (Canada & USA)',
        'card2_point3' => 'Support financier',
        'scholarships_home_title' => 'Bourses des institutions',
        'scholarships_home_desc' => 'Postulez directement aux programmes de bourses de nos universités et institutions partenaires.',
        
        // Card 3: I-20 Application
        'card3_title' => 'Demande I-20',
        'card3_subtitle' => 'Traitement rapide pour les USA',
        'card3_description' => 'Processus de demande I-20 rationalisé.',
        'card3_point1' => 'Conforme SEVIS',
        'card3_point2' => 'Coordination universitaire',
        'card3_point3' => 'Préparation entretien',
        
        // Card 4: Credit Transfer
        'card4_title' => 'Transfert de Crédits',
        'card4_subtitle' => 'Transférez vos crédits',
        'card4_description' => 'Maximisez vos progrès académiques.',
        'card4_point1' => 'Évaluation relevés',
        'card4_point2' => 'Équivalence de cours',
        'card4_point3' => 'Durée réduite',
        
        // Card 5: Visa Application
        'card5_title' => 'Demande de Visa',
        'card5_subtitle' => 'Visas études & visite',
        'card5_description' => 'Support complet pour les demandes de visa.',
        'card5_point1' => 'Préparation documents',
        'card5_point2' => 'Entretiens simulés',
        'card5_point3' => 'Taux d\'approbation élevé',
        
        // Card 6: Apply for Job
        'card6_title' => 'Postuler à un Emploi',
        'card6_subtitle' => 'Opportunités en Europe',
        'card6_description' => 'Lancez votre carrière internationale.',
        'card6_point1' => 'Support placement',
        'card6_point2' => 'Assistance logement',
        'card6_point3' => 'Transfert aéroport',
        
        // Card 7: Airticketing Reservation
        'card7_title' => 'Réservation de Billets',
        'card7_subtitle' => 'Vols pour étudiants & professionnels',
        'card7_description' => 'Réservation de vols sans tracas.',
        'card7_point1' => 'Tarifs étudiants',
        'card7_point2' => 'Modification flexible',
        'card7_point3' => 'Support voyage',
        
        // Page Metadata
        'page_description' => 'Xander Global Scholars - Votre parcours complet vers la réussite de l\'éducation internationale et de carrière.',
        'page_title' => 'Xander Global Scholars - Votre parcours vers le succès',
    ]
];

// Function to get index translation (avoid fatal if this file is ever loaded twice)
if (!function_exists('it')) {
    function it($key) {
        global $index_translations, $current_lang;
        return isset($index_translations[$current_lang][$key]) ? $index_translations[$current_lang][$key] : $key;
    }
}

// Define cards with translation keys
$cards = [
    [
        'id' => 'admissions',
        'icon' => '🎓',
        'title_key' => 'card1_title',
        'subtitle_key' => 'card1_subtitle',
        'description_key' => 'card1_description',
        'points_keys' => ['card1_point1', 'card1_point2', 'card1_point3'],
        'form' => 'student-application.php',
        'color' => '#012F6B'
    ],
    [
        'id' => 'scholarships',
        'icon' => '💰',
        'title_key' => 'card2_title',
        'subtitle_key' => 'card2_subtitle',
        'description_key' => 'card2_description',
        'points_keys' => ['card2_point1', 'card2_point2', 'card2_point3'],
        'form' => '#scholarships',
        'color' => '#254D81'
    ],
    [
        'id' => 'i20',
        'icon' => '📄',
        'title_key' => 'card3_title',
        'subtitle_key' => 'card3_subtitle',
        'description_key' => 'card3_description',
        'points_keys' => ['card3_point1', 'card3_point2', 'card3_point3'],
        'form' => 'form-20.php',
        'color' => '#002765'
    ],
    [
        'id' => 'credit',
        'icon' => '🔁',
        'title_key' => 'card4_title',
        'subtitle_key' => 'card4_subtitle',
        'description_key' => 'card4_description',
        'points_keys' => ['card4_point1', 'card4_point2', 'card4_point3'],
        'form' => 'credit_transfer.php',
        'color' => '#012F6B'
    ],
    [
        'id' => 'visa',
        'icon' => '✈️',
        'title_key' => 'card5_title',
        'subtitle_key' => 'card5_subtitle',
        'description_key' => 'card5_description',
        'points_keys' => ['card5_point1', 'card5_point2', 'card5_point3'],
        'form' => 'visa.php',
        'color' => '#254D81'
    ],
    [
        'id' => 'jobs',
        'icon' => '💼',
        'title_key' => 'card6_title',
        'subtitle_key' => 'card6_subtitle',
        'description_key' => 'card6_description',
        'points_keys' => ['card6_point1', 'card6_point2', 'card6_point3'],
        'form' => 'job-application.php',
        'color' => '#002765'
    ],
    [
        'id' => 'airticket',
        'icon' => '🛫',
        'title_key' => 'card7_title',
        'subtitle_key' => 'card7_subtitle',
        'description_key' => 'card7_description',
        'points_keys' => ['card7_point1', 'card7_point2', 'card7_point3'],
        'form' => 'air-ticket-reservation.php',
        'color' => '#012F6B'
    ]
];

// Define testimonials
$testimonials = [
    ['key' => 'testimonial1', 'initial' => 'SM'],
    ['key' => 'testimonial2', 'initial' => 'JK'],
    ['key' => 'testimonial3', 'initial' => 'PS'],
    ['key' => 'testimonial4', 'initial' => 'AR'],
    ['key' => 'testimonial5', 'initial' => 'LT'],
    ['key' => 'testimonial6', 'initial' => 'DL'],
    ['key' => 'testimonial7', 'initial' => 'MG'],
    ['key' => 'testimonial8', 'initial' => 'KY'],
    ['key' => 'testimonial9', 'initial' => 'RP'],
    ['key' => 'testimonial10', 'initial' => 'SM']
];

// Define universities
$universities = [
    ['name' => 'Lasalle College', 'country' => 'Canada', 'rank' => '#1 in Canada'],
    ['name' => 'University of Niagara Falls Canada', 'country' => 'Canada', 'rank' => 'Top 10 Worldwide'],
    ['name' => 'University of Sydney', 'country' => 'Australia', 'rank' => '#1 in Australia'],
    ['name' => 'ETH Zurich', 'country' => 'Switzerland', 'rank' => '#1 in Europe'],
    ['name' => 'University of Tokyo', 'country' => 'Japan', 'rank' => 'Top 20 Worldwide'],
    ['name' => 'Florida Atlantic University', 'country' => 'USA', 'rank' => '#1 Worldwide'],
    ['name' => 'Webster University', 'country' => 'USA', 'rank' => 'Top 10 USA'],
    ['name' => 'Catholic University of America', 'country' => 'USA', 'rank' => '#1 in USA']
];

// Define destinations
$destinations = [
    ['country' => 'Canada', 'flag' => '🇨🇦', 'students' => '1500+', 'description' => 'Top destination for quality education and PR'],
    ['country' => 'USA', 'flag' => '🇺🇸', 'students' => '1200+', 'description' => 'World-class universities & research opportunities'],
    ['country' => 'UK', 'flag' => '🇬🇧', 'students' => '900+', 'description' => 'Historic universities & 2-year post-study work'],
    ['country' => 'Australia', 'flag' => '🇦🇺', 'students' => '800+', 'description' => 'Sunny lifestyle & strong job market'],
    ['country' => 'Germany', 'flag' => '🇩🇪', 'students' => '700+', 'description' => 'Tuition-free education & engineering hub'],
    ['country' => 'Netherlands', 'flag' => '🇳🇱', 'students' => '500+', 'description' => 'English-taught programs & innovation center']
];

// Get card parameter from URL for direct access
$direct_card = isset($_GET['card']) ? $_GET['card'] : null;
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="<?php echo it('page_description'); ?>">
<title><?php echo it('page_title'); ?></title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css">

<style>
/* =========================================================
   ENHANCED LANDING PAGE WITH NEW SECTIONS
   Optimized with modern design and animations
   XANDER COLOR CODE IMPLEMENTED
========================================================= */
:root {
  /* Official Xander Colors from document */
  --primary-navy: #012F6B;      /* Deep Navy Blue */
  --secondary-blue: #254D81;    /* Secondary Blue */
  --dark-blue: #002765;         /* Dark Blue Accent */
  --accent-gold: #F2A65A;       /* Gold/Warm Yellow */
  --pure-white: #FFFFFF;        /* White Background */
  
  /* Derived Colors */
  --primary-light: rgba(1, 47, 107, 0.08);
  --accent-light: rgba(242, 166, 90, 0.12);
  --teal-light: rgba(45, 212, 191, 0.1);
  
  /* Neutral Colors */
  --bg: #F8FAFC;
  --bg-light: #FFFFFF;
  --card: #FFFFFF;
  --text: #1E293B;
  --text-light: #64748B;
  --text-muted: #94A3B8;
  --border: #E2E8F0;
  --border-light: #F1F5F9;
  
  /* Shadows */
  --shadow-sm: 0 2px 4px rgba(1, 47, 107, 0.04);
  --shadow-md: 0 4px 12px rgba(1, 47, 107, 0.08);
  --shadow-lg: 0 8px 20px rgba(1, 47, 107, 0.12);
  
  /* Transitions */
  --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  --transition-slow: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body {
  margin: 0;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
  background: var(--bg);
  color: var(--text);
  line-height: 1.6;
  overflow-x: hidden;
  -webkit-font-smoothing: antialiased;
}

/* ===== COMMON STYLES ===== */
.section-padding {
  padding: 70px 20px;
}

.section-header {
  text-align: center;
  max-width: 800px;
  margin: 0 auto 50px;
}

.section-title {
  font-size: 2.5rem;
  font-weight: 800;
  color: var(--primary-navy);
  margin-bottom: 15px;
  position: relative;
  display: inline-block;
}

.section-title::after {
  content: '';
  position: absolute;
  bottom: -12px;
  left: 50%;
  transform: translateX(-50%);
  width: 80px;
  height: 5px;
  background: linear-gradient(90deg, var(--accent-gold), var(--secondary-blue));
  border-radius: 3px;
}

.section-subtitle {
  font-size: 1.2rem;
  color: var(--accent-gold);
  font-weight: 600;
  margin-bottom: 10px;
  letter-spacing: 0.5px;
}

.section-description {
  font-size: 1.1rem;
  color: var(--text-light);
  line-height: 1.6;
  max-width: 600px;
  margin: 0 auto;
}

/* ===== HERO SECTION ===== */
.hero-section {
  min-height: 90vh;
  background: linear-gradient(135deg, #FFFFFF 0%, #F8FAFC 100%);
  position: relative;
  padding: 60px 20px 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.hero-bg-pattern {
  position: absolute;
  width: 100%;
  height: 100%;
  background-image: 
    radial-gradient(circle at 20% 80%, rgba(1, 47, 107, 0.03) 0%, transparent 50%),
    radial-gradient(circle at 80% 20%, rgba(242, 166, 90, 0.03) 0%, transparent 50%);
}

.hero-content {
  max-width: 1000px;
  margin: 0 auto;
  text-align: center;
  position: relative;
  z-index: 2;
}

.hero-title {
  font-size: 3.2rem;
  font-weight: 900;
  line-height: 1.1;
  margin-bottom: 25px;
  background: linear-gradient(135deg, var(--primary-navy) 0%, var(--dark-blue) 50%, var(--accent-gold) 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  letter-spacing: -0.02em;
}

.hero-description {
  font-size: 1.2rem;
  color: var(--text-light);
  max-width: 750px;
  margin: 0 auto 35px;
  line-height: 1.7;
}

.hero-cta {
  display: flex;
  gap: 20px;
  justify-content: center;
  margin-top: 40px;
  flex-wrap: wrap;
}

.cta-button {
  padding: 18px 36px;
  font-size: 1.1rem;
  font-weight: 600;
  border-radius: 12px;
  border: none;
  cursor: pointer;
  transition: var(--transition);
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 200px;
  justify-content: center;
  position: relative;
  overflow: hidden;
}

.cta-button::after {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
  transition: left 0.7s;
}

.cta-button:hover::after {
  left: 100%;
}

.cta-primary {
  background: linear-gradient(135deg, var(--primary-navy) 0%, var(--secondary-blue) 100%);
  color: white;
  box-shadow: 0 8px 25px rgba(1, 47, 107, 0.25);
}

.cta-primary:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 35px rgba(1, 47, 107, 0.35);
}

.cta-secondary {
  background: white;
  color: var(--primary-navy);
  border: 2px solid var(--border);
  box-shadow: 0 4px 15px rgba(0,0,0,0.05);
}

.cta-secondary:hover {
  background: var(--primary-light);
  border-color: var(--primary-navy);
  transform: translateY(-5px);
}

/* ===== STATS SECTION ===== */
.stats-section {
  background: linear-gradient(135deg, var(--primary-navy) 0%, var(--dark-blue) 100%);
  color: white;
  padding: 70px 20px;
  position: relative;
}

.stats-grid {
  max-width: 1200px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 30px;
  text-align: center;
}

.stat-item {
  padding: 30px 20px;
  position: relative;
  z-index: 1;
}

.stat-number {
  font-size: 3.2rem;
  font-weight: 800;
  margin-bottom: 10px;
  color: var(--accent-gold);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.stat-number i {
  font-size: 2.2rem;
  opacity: 0.8;
}

.stat-label {
  font-size: 1.1rem;
  font-weight: 500;
  opacity: 0.9;
  letter-spacing: 0.5px;
}

/* ===== FEATURES SECTION ===== */
.features-section {
  padding: 80px 20px;
  background: white;
}

.features-grid {
  max-width: 1200px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 30px;
}

.feature-card {
  text-align: center;
  padding: 40px 25px;
  background: var(--bg);
  border-radius: 20px;
  transition: var(--transition);
  border: 1px solid var(--border-light);
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
  background: linear-gradient(90deg, var(--accent-gold), var(--secondary-blue));
}

.feature-card:hover {
  transform: translateY(-10px);
  box-shadow: var(--shadow-lg);
}

.feature-icon {
  width: 80px;
  height: 80px;
  margin: 0 auto 25px;
  background: linear-gradient(135deg, var(--primary-light), rgba(37, 77, 129, 0.1));
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 32px;
  color: var(--primary-navy);
  transition: var(--transition);
}

.feature-card:hover .feature-icon {
  transform: scale(1.1) rotate(5deg);
}

.feature-card h4 {
  font-size: 1.3rem;
  font-weight: 700;
  color: var(--primary-navy);
  margin-bottom: 15px;
}

.feature-card p {
  color: var(--text-light);
  font-size: 1rem;
  line-height: 1.6;
}

/* ===== SERVICES SECTION ===== */
.services-section {
  padding: 80px 20px;
  background: linear-gradient(135deg, #F8FAFC 0%, #F0F4F8 100%);
}

.services-grid {
  max-width: 1400px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
  gap: 30px;
  padding: 30px 0;
}

.service-card {
  background: white;
  border-radius: 20px;
  padding: 35px 30px;
  box-shadow: var(--shadow-md);
  border: 1px solid var(--border-light);
  transition: var(--transition);
  position: relative;
  overflow: hidden;
  display: none; /* Initially hidden for direct card access */
}

.service-card.show-card {
  display: block;
  animation: fadeInUp 0.6s ease forwards;
}

.service-card.highlight-card {
  box-shadow: 0 0 0 3px var(--accent-gold), var(--shadow-lg);
  transform: translateY(-5px);
}

.service-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 6px;
  height: 100%;
  background: linear-gradient(to bottom, var(--primary-navy), var(--secondary-blue));
}

.service-card:hover {
  transform: translateY(-10px);
  box-shadow: var(--shadow-lg);
}

.card-header {
  display: flex;
  align-items: flex-start;
  gap: 20px;
  margin-bottom: 25px;
}

.card-icon {
  width: 70px;
  height: 70px;
  background: linear-gradient(135deg, var(--primary-light), rgba(37, 77, 129, 0.1));
  border-radius: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 32px;
  color: var(--primary-navy);
  flex-shrink: 0;
  transition: var(--transition);
}

.service-card:hover .card-icon {
  transform: rotate(10deg) scale(1.1);
}

.card-title-group h3 {
  font-size: 1.4rem;
  font-weight: 800;
  color: var(--primary-navy);
  margin-bottom: 8px;
}

.card-subtitle {
  font-size: 1rem;
  color: var(--accent-gold);
  font-weight: 600;
  letter-spacing: 0.5px;
}

.card-description {
  color: var(--text-light);
  line-height: 1.6;
  margin-bottom: 25px;
  font-size: 1rem;
}

.card-features {
  list-style: none;
  margin-bottom: 30px;
}

.card-features li {
  padding: 10px 0;
  padding-left: 32px;
  position: relative;
  color: var(--text);
  font-size: 0.95rem;
  border-bottom: 1px solid var(--border-light);
}

.card-features li:last-child {
  border-bottom: none;
}

.card-features li::before {
  content: '✓';
  position: absolute;
  left: 0;
  color: var(--accent-gold);
  font-weight: 800;
  font-size: 1.2rem;
}

.card-actions {
  display: flex;
  gap: 15px;
}

.card-button {
  flex: 1;
  padding: 14px 20px;
  border-radius: 10px;
  border: none;
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 1rem;
  position: relative;
  overflow: hidden;
}

.apply-button {
  background: linear-gradient(135deg, var(--primary-navy), var(--secondary-blue));
  color: white;
  box-shadow: 0 6px 15px rgba(1, 47, 107, 0.2);
}

.apply-button:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 20px rgba(1, 47, 107, 0.3);
}

.copy-button {
  background: white;
  color: var(--primary-navy);
  border: 2px solid var(--border);
}

.copy-button:hover {
  background: var(--primary-light);
  border-color: var(--primary-navy);
  transform: translateY(-3px);
}

/* Direct card access header */
.direct-card-header {
  background: linear-gradient(135deg, var(--primary-navy) 0%, var(--dark-blue) 100%);
  color: white;
  padding: 30px 20px;
  text-align: center;
  margin-bottom: 40px;
  border-radius: 0 0 20px 20px;
  display: none;
}

.direct-card-header.show-header {
  display: block;
  animation: slideInDown 0.5s ease;
}

.direct-card-header h2 {
  font-size: 2rem;
  margin-bottom: 10px;
  color: white;
}

.direct-card-header p {
  opacity: 0.9;
  margin-bottom: 20px;
}

.back-to-all {
  background: rgba(255, 255, 255, 0.15);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.3);
  padding: 10px 20px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  transition: var(--transition);
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.back-to-all:hover {
  background: rgba(255, 255, 255, 0.25);
  transform: translateY(-2px);
}

/* ===== INSTITUTION SCHOLARSHIPS (homepage) ===== */
.scholarships-home {
  padding: 80px 20px;
  background: linear-gradient(180deg, #f8fafc 0%, #fff 100%);
}

.scholarships-home-grid {
  max-width: 1200px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 24px;
}

.scholarship-home-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 8px 24px rgba(1, 47, 107, 0.06);
  transition: transform 0.2s, box-shadow 0.2s;
  display: flex;
  flex-direction: column;
}

.scholarship-home-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 16px 40px rgba(1, 47, 107, 0.12);
}

.sch-card-top {
  display: flex;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 10px;
  font-size: 0.8rem;
}

.sch-uni {
  font-weight: 700;
  color: var(--primary);
}

.sch-country {
  color: #64748b;
}

.scholarship-home-card h3 {
  font-size: 1.15rem;
  font-weight: 800;
  color: var(--primary);
  margin: 0 0 8px;
}

.sch-tagline {
  font-size: 0.9rem;
  color: #475569;
  margin: 0 0 8px;
}

.sch-summary {
  font-size: 0.88rem;
  color: #64748b;
  flex: 1;
  margin-bottom: 12px;
}

.sch-meta {
  list-style: none;
  padding: 0;
  margin: 0 0 16px;
  font-size: 0.82rem;
  color: #334155;
}

.sch-meta li {
  margin-bottom: 4px;
}

.sch-meta i {
  width: 18px;
  color: var(--accent);
}

.sch-apply-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: var(--primary);
  color: #fff !important;
  text-decoration: none;
  font-weight: 700;
  padding: 12px 20px;
  border-radius: 10px;
  margin-top: auto;
}

.sch-apply-btn:hover {
  background: var(--secondary);
  color: #fff;
}

/* ===== UNIVERSITIES SECTION ===== */
.universities-section {
  padding: 80px 20px;
  background: white;
}

.universities-grid {
  max-width: 1200px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 25px;
  padding: 30px 0;
}

.university-card {
  background: var(--bg);
  padding: 30px 25px;
  border-radius: 16px;
  text-align: center;
  border: 1px solid var(--border-light);
  transition: var(--transition);
  position: relative;
  overflow: hidden;
}

.university-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--shadow-md);
}

.university-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, var(--accent-gold), var(--secondary-blue));
}

.university-flag {
  font-size: 2.5rem;
  margin-bottom: 15px;
  display: block;
}

.university-card h4 {
  font-size: 1.2rem;
  font-weight: 700;
  color: var(--primary-navy);
  margin-bottom: 8px;
}

.university-country {
  color: var(--text-light);
  font-size: 0.95rem;
  margin-bottom: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
}

.university-rank {
  background: linear-gradient(135deg, var(--accent-gold), var(--secondary-blue));
  color: white;
  padding: 6px 15px;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 600;
  display: inline-block;
  margin-top: 10px;
}

/* ===== DESTINATIONS SECTION ===== */
.destinations-section {
  padding: 80px 20px;
  background: linear-gradient(135deg, #F8FAFC 0%, #F0F4F8 100%);
}

.destinations-grid {
  max-width: 1200px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 25px;
  padding: 30px 0;
}

.destination-card {
  background: white;
  padding: 30px;
  border-radius: 16px;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border-light);
  transition: var(--transition);
  display: flex;
  align-items: center;
  gap: 20px;
}

.destination-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--shadow-md);
}

.destination-flag {
  font-size: 3rem;
  flex-shrink: 0;
}

.destination-info h4 {
  font-size: 1.3rem;
  font-weight: 700;
  color: var(--primary-navy);
  margin-bottom: 8px;
}

.destination-stats {
  color: var(--accent-gold);
  font-weight: 600;
  font-size: 0.95rem;
  margin-bottom: 8px;
}

.destination-desc {
  color: var(--text-light);
  font-size: 0.9rem;
  line-height: 1.5;
}

/* ===== PROCESS SECTION ===== */
.process-section {
  padding: 80px 20px;
  background: white;
  position: relative;
}

.process-steps {
  max-width: 1000px;
  margin: 0 auto;
  position: relative;
}

.process-steps::before {
  content: '';
  position: absolute;
  top: 40px;
  left: 40px;
  right: 40px;
  height: 3px;
  background: linear-gradient(90deg, var(--accent-gold), var(--secondary-blue));
  z-index: 1;
}

.process-step {
  display: flex;
  align-items: flex-start;
  gap: 30px;
  margin-bottom: 50px;
  position: relative;
  z-index: 2;
}

.step-number {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, var(--primary-navy), var(--secondary-blue));
  border-radius: 50%;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  font-weight: 800;
  flex-shrink: 0;
  position: relative;
  border: 5px solid white;
  box-shadow: 0 4px 15px rgba(1, 47, 107, 0.2);
}

.step-content {
  flex: 1;
  padding-top: 15px;
}

.step-content h4 {
  font-size: 1.4rem;
  font-weight: 700;
  color: var(--primary-navy);
  margin-bottom: 10px;
}

.step-content p {
  color: var(--text-light);
  line-height: 1.6;
}

/* ===== ENHANCED TESTIMONIALS SECTION ===== */
.testimonials-section {
  padding: 100px 20px;
  background: linear-gradient(135deg, var(--primary-navy) 0%, var(--dark-blue) 100%);
  color: white;
  position: relative;
  overflow: hidden;
}

.testimonials-header {
  text-align: center;
  max-width: 800px;
  margin: 0 auto 60px;
}

.testimonials-header h2 {
  color: white;
  font-size: 2.8rem;
  margin-bottom: 15px;
}

.testimonials-header .section-description {
  color: rgba(255, 255, 255, 0.85);
}

.testimonials-container {
  max-width: 1200px;
  margin: 0 auto;
  position: relative;
}

.testimonials-track {
  display: flex;
  gap: 30px;
  padding: 20px 10px;
  overflow-x: auto;
  scroll-behavior: smooth;
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.testimonials-track::-webkit-scrollbar {
  display: none;
}

.testimonial-card {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.15);
  padding: 40px 35px;
  border-radius: 20px;
  min-width: 350px;
  flex-shrink: 0;
  transition: var(--transition);
  position: relative;
  overflow: hidden;
}

.testimonial-card::before {
  content: '"';
  position: absolute;
  top: 20px;
  left: 25px;
  font-size: 100px;
  color: rgba(255, 255, 255, 0.1);
  font-family: serif;
  line-height: 1;
}

.testimonial-card:hover {
  background: rgba(255, 255, 255, 0.15);
  transform: translateY(-10px);
}

.testimonial-text {
  font-size: 1.1rem;
  line-height: 1.7;
  color: rgba(255, 255, 255, 0.95);
  margin-bottom: 30px;
  position: relative;
  z-index: 1;
}

.testimonial-author {
  display: flex;
  align-items: center;
  gap: 15px;
}

.author-avatar {
  width: 60px;
  height: 60px;
  background: linear-gradient(135deg, var(--accent-gold), var(--secondary-blue));
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  font-size: 1.2rem;
  flex-shrink: 0;
}

.author-info h5 {
  font-weight: 700;
  color: white;
  margin-bottom: 5px;
  font-size: 1.1rem;
}

.author-info p {
  color: rgba(255, 255, 255, 0.8);
  font-size: 0.9rem;
  margin-bottom: 5px;
}

.author-achievement {
  background: rgba(242, 166, 90, 0.2);
  color: var(--accent-gold);
  padding: 5px 12px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  display: inline-block;
  margin-top: 5px;
}

.testimonial-controls {
  display: flex;
  justify-content: center;
  gap: 15px;
  margin-top: 40px;
}

.testimonial-btn {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: white;
  font-size: 1.2rem;
  cursor: pointer;
  transition: var(--transition);
  display: flex;
  align-items: center;
  justify-content: center;
}

.testimonial-btn:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: scale(1.1);
}

.testimonial-indicators {
  display: flex;
  justify-content: center;
  gap: 10px;
  margin-top: 30px;
}

.indicator {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.3);
  cursor: pointer;
  transition: var(--transition);
}

.indicator.active {
  background: var(--accent-gold);
  transform: scale(1.2);
}

/* ===== ENHANCED PARTNERS SECTION ===== */
.partners-section {
  padding: 100px 20px;
  background: linear-gradient(135deg, #FFFFFF 0%, #F8FAFC 100%);
  position: relative;
}

.partners-container {
  max-width: 1400px;
  margin: 0 auto;
}

.partners-category {
  margin-bottom: 60px;
}

.partners-category:last-child {
  margin-bottom: 0;
}

.category-header {
  text-align: center;
  margin-bottom: 40px;
}

.category-title {
  font-size: 1.8rem;
  font-weight: 700;
  color: var(--primary-navy);
  margin-bottom: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
}

.category-title i {
  color: var(--accent-gold);
  font-size: 1.5rem;
}

.category-description {
  color: var(--text-light);
  max-width: 600px;
  margin: 0 auto;
  font-size: 1rem;
}

.partners-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  padding: 20px 0;
}

.partner-card {
  background: var(--bg-light);
  border-radius: 16px;
  padding: 25px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border: 2px solid var(--border-light);
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
  min-height: 180px;
  box-shadow: var(--shadow-sm);
}

.partner-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, var(--primary-navy), var(--accent-gold));
  opacity: 0;
  transition: opacity 0.3s ease;
}

.partner-card:hover {
  transform: translateY(-8px);
  box-shadow: var(--shadow-lg);
  border-color: var(--accent-gold);
}

.partner-card:hover::before {
  opacity: 1;
}

.partner-logo {
  max-width: 100%;
  max-height: 60px;
  object-fit: contain;
  filter: grayscale(100%);
  opacity: 0.8;
  transition: all 0.4s ease;
  margin-bottom: 20px;
}

.partner-card:hover .partner-logo {
  filter: grayscale(0%);
  opacity: 1;
  transform: scale(1.05);
}

.partner-name {
  font-size: 1rem;
  font-weight: 600;
  color: var(--primary-navy);
  text-align: center;
  margin-bottom: 8px;
}

.partner-role {
  font-size: 0.8rem;
  color: var(--accent-gold);
  font-weight: 500;
  text-align: center;
  padding: 4px 12px;
  background: rgba(242, 166, 90, 0.1);
  border-radius: 12px;
}

/* Category-specific styles */
.banking-partners .partner-card {
  background: linear-gradient(135deg, #F8FAFC 0%, #FFFFFF 100%);
  border-color: rgba(37, 77, 129, 0.2);
}

.testing-partners .partner-card {
  background: linear-gradient(135deg, rgba(1, 47, 107, 0.05) 0%, #FFFFFF 100%);
  border-color: rgba(1, 47, 107, 0.2);
}

.travel-partners .partner-card {
  background: linear-gradient(135deg, rgba(242, 166, 90, 0.05) 0%, #FFFFFF 100%);
  border-color: rgba(242, 166, 90, 0.2);
}

.tech-partners .partner-card {
  background: linear-gradient(135deg, rgba(1, 47, 107, 0.05) 0%, #FFFFFF 100%);
  border-color: rgba(1, 47, 107, 0.2);
}

/* ===== RESOURCES SECTION ===== */
.resources-section {
  padding: 80px 20px;
  background: linear-gradient(135deg, #F8FAFC 0%, #F0F4F8 100%);
}

.resources-grid {
  max-width: 1200px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 30px;
  padding: 30px 0;
}

.resource-card {
  background: white;
  padding: 35px 30px;
  border-radius: 16px;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border-light);
  transition: var(--transition);
}

.resource-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--shadow-md);
}

.resource-icon {
  width: 60px;
  height: 60px;
  background: linear-gradient(135deg, var(--primary-light), rgba(37, 77, 129, 0.1));
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: var(--primary-navy);
  margin-bottom: 20px;
}

.resource-card h4 {
  font-size: 1.3rem;
  font-weight: 700;
  color: var(--primary-navy);
  margin-bottom: 12px;
}

.resource-card p {
  color: var(--text-light);
  line-height: 1.6;
  margin-bottom: 25px;
  font-size: 0.95rem;
}

.resource-link {
  color: var(--accent-gold);
  font-weight: 600;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: var(--transition);
}

.resource-link:hover {
  color: var(--primary-navy);
  gap: 12px;
}

/* ===== CTA SECTION ===== */
.cta-section {
  padding: 100px 20px;
  background: linear-gradient(135deg, var(--primary-navy) 0%, var(--dark-blue) 100%);
  color: white;
  text-align: center;
  position: relative;
  overflow: hidden;
}

.cta-content {
  max-width: 800px;
  margin: 0 auto;
  position: relative;
  z-index: 2;
}

.cta-content h2 {
  font-size: 2.8rem;
  font-weight: 800;
  margin-bottom: 20px;
}

.cta-content p {
  font-size: 1.2rem;
  opacity: 0.9;
  margin-bottom: 40px;
  line-height: 1.6;
}

.cta-buttons {
  display: flex;
  gap: 20px;
  justify-content: center;
  flex-wrap: wrap;
}

.cta-button-white {
  background: white;
  color: var(--primary-navy);
}

.cta-button-outline {
  background: transparent;
  color: white;
  border: 2px solid rgba(255, 255, 255, 0.3);
}

.cta-button-outline:hover {
  background: rgba(255, 255, 255, 0.1);
  border-color: white;
}

/* ===== ANIMATIONS ===== */
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

@keyframes float {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-20px);
  }
}

@keyframes slideInLeft {
  from {
    opacity: 0;
    transform: translateX(-50px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(50px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes slideInDown {
  from {
    opacity: 0;
    transform: translateY(-50px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.fade-in {
  opacity: 0;
  animation: fadeInUp 0.6s ease forwards;
}

.float-animation {
  animation: float 6s ease-in-out infinite;
}

.slide-left {
  opacity: 0;
  animation: slideInLeft 0.6s ease forwards;
}

.slide-right {
  opacity: 0;
  animation: slideInRight 0.6s ease forwards;
}

/* ===== RESPONSIVE DESIGN ===== */
@media (max-width: 1200px) {
  .hero-title {
    font-size: 2.8rem;
  }
  
  .services-grid {
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  }
  
  .partners-grid {
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  }
}

@media (max-width: 992px) {
  .hero-title {
    font-size: 2.5rem;
  }
  
  .section-title {
    font-size: 2.2rem;
  }
  
  .process-steps::before {
    display: none;
  }
  
  .process-step {
    flex-direction: column;
    text-align: center;
    gap: 20px;
  }
  
  .partners-grid {
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  }
}

@media (max-width: 768px) {
  .hero-section {
    min-height: 80vh;
    padding: 40px 20px 30px;
  }
  
  .hero-title {
    font-size: 2.2rem;
  }
  
  .hero-description {
    font-size: 1.1rem;
  }
  
  .hero-cta {
    flex-direction: column;
    align-items: center;
  }
  
  .cta-button {
    width: 100%;
    max-width: 300px;
  }
  
  .services-grid {
    grid-template-columns: 1fr;
  }
  
  .section-title {
    font-size: 2rem;
  }
  
  .cta-buttons {
    flex-direction: column;
    align-items: center;
  }
  
  .card-actions {
    flex-direction: column;
  }
  
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .testimonial-card {
    min-width: 300px;
  }
  
  .destinations-grid,
  .resources-grid {
    grid-template-columns: 1fr;
  }
  
  .partners-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
  }
  
  .partner-card {
    min-height: 160px;
    padding: 20px;
  }
  
  .partner-logo {
    max-height: 50px;
    margin-bottom: 15px;
  }
}

@media (max-width: 576px) {
  .hero-title {
    font-size: 1.8rem;
  }
  
  .section-title {
    font-size: 1.8rem;
  }
  
  .section-padding {
    padding: 50px 20px;
  }
  
  .stat-number {
    font-size: 2.5rem;
  }
  
  .features-grid {
    grid-template-columns: 1fr;
  }
  
  .testimonials-header h2 {
    font-size: 2.2rem;
  }
  
  .testimonial-card {
    min-width: 280px;
    padding: 30px 25px;
  }
  
  .universities-grid {
    grid-template-columns: 1fr;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
    gap: 20px;
  }
  
  .partners-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  
  .partner-card {
    min-height: 140px;
    padding: 15px;
  }
  
  .partner-logo {
    max-height: 40px;
    margin-bottom: 10px;
  }
  
  .partner-name {
    font-size: 0.9rem;
  }
  
  .partner-role {
    font-size: 0.75rem;
  }
}
</style>
</head>
<body>

<!-- Hero Section -->
<section class="hero-section">
  <div class="hero-bg-pattern"></div>
  <div class="hero-content">
    <h1 class="hero-title fade-in"><?php echo it('hero_title'); ?></h1>
    <p class="hero-description fade-in"><?php echo it('hero_description'); ?></p>
    <div class="hero-cta">
      <button class="cta-button cta-primary fade-in" id="scrollToServices" type="button">
        <i class="fas fa-rocket"></i>
        <?php echo it('start_application'); ?>
      </button>
      <button class="cta-button cta-secondary fade-in" id="scrollToFeatures">
        <i class="fas fa-play-circle"></i>
        <?php echo it('learn_more'); ?>
      </button>
    </div>
  </div>
</section>

<!-- Direct Card Access Header -->
<div class="direct-card-header" id="directCardHeader">
  <h2>Direct Service Access</h2>
  <p>You are viewing a specific service. Click below to see all available services.</p>
  <button class="back-to-all" id="backToAll">
    <i class="fas fa-arrow-left"></i>
    Back to All Services
  </button>
</div>

<!-- Stats Section -->
<section class="stats-section">
  <div class="stats-grid">
    <div class="stat-item fade-in">
      <div class="stat-number"><i class="fas fa-user-graduate"></i>50+</div>
      <div class="stat-label"><?php echo it('stats_students'); ?></div>
    </div>
    <div class="stat-item fade-in">
      <div class="stat-number"><i class="fas fa-award"></i>$100K+</div>
      <div class="stat-label"><?php echo it('stats_scholarships'); ?></div>
    </div>
    <div class="stat-item fade-in">
      <div class="stat-number"><i class="fas fa-globe-americas"></i>20+</div>
      <div class="stat-label"><?php echo it('stats_countries'); ?></div>
    </div>
    <div class="stat-item fade-in">
      <div class="stat-number"><i class="fas fa-handshake"></i>50+</div>
      <div class="stat-label"><?php echo it('stats_partners'); ?></div>
    </div>
  </div>
</section>

<!-- Features Section -->
<section class="features-section section-padding" id="features">
  <div class="section-header">
    <h2 class="section-title"><?php echo it('features_title'); ?></h2>
  </div>
  
  <div class="features-grid">
    <div class="feature-card fade-in">
      <div class="feature-icon">
        <i class="fas fa-road"></i>
      </div>
      <h4><?php echo it('feature1_title'); ?></h4>
      <p><?php echo it('feature1_desc'); ?></p>
    </div>
    
    <div class="feature-card fade-in">
      <div class="feature-icon">
        <i class="fas fa-user-tie"></i>
      </div>
      <h4><?php echo it('feature2_title'); ?></h4>
      <p><?php echo it('feature2_desc'); ?></p>
    </div>
    
    <div class="feature-card fade-in">
      <div class="feature-icon">
        <i class="fas fa-globe-americas"></i>
      </div>
      <h4><?php echo it('feature3_title'); ?></h4>
      <p><?php echo it('feature3_desc'); ?></p>
    </div>
    
    <div class="feature-card fade-in">
      <div class="feature-icon">
        <i class="fas fa-hand-holding-usd"></i>
      </div>
      <h4><?php echo it('feature4_title'); ?></h4>
      <p><?php echo it('feature4_desc'); ?></p>
    </div>
  </div>
</section>

<!-- Services Section -->
<section class="services-section section-padding" id="services">
  <div class="section-header">
    <h2 class="section-title"><?php echo it('services_title'); ?></h2>
    <p class="section-description"><?php echo it('services_description'); ?></p>
  </div>
  
  <div class="services-grid">
    <?php foreach($cards as $c): ?>
    <div id="<?= $c['id'] ?>" class="service-card <?= ($direct_card === $c['id']) ? 'show-card highlight-card' : (empty($direct_card) ? 'show-card' : '') ?>" data-card="<?= $c['id'] ?>" data-form="<?= $c['form'] ?>">
      <div class="card-header">
        <div class="card-icon">
          <?= $c['icon'] ?>
        </div>
        <div class="card-title-group">
          <h3><?= it($c['title_key']) ?></h3>
          <p class="card-subtitle"><?= it($c['subtitle_key']) ?></p>
        </div>
      </div>
      
      <p class="card-description"><?= htmlspecialchars(it($c['description_key'])) ?></p>
      
      <ul class="card-features">
        <?php foreach($c['points_keys'] as $pt_key): ?>
        <li><?= htmlspecialchars(it($pt_key)) ?></li>
        <?php endforeach; ?>
      </ul>
      
      <div class="card-actions">
        <button class="card-button apply-button">
          <i class="fas fa-paper-plane"></i>
          <?php echo it('card_apply'); ?>
        </button>
        <button class="card-button copy-button" data-card-id="<?= $c['id'] ?>">
          <i class="fas fa-link"></i>
          <?php echo it('card_copy'); ?>
        </button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/homepage_scholarships.php';
?>

<!-- Global Universities Section -->
<section class="universities-section section-padding">
  <div class="section-header">
    <h2 class="section-title"><?php echo it('universities_title'); ?></h2>
    <p class="section-description"><?php echo it('universities_description'); ?></p>
  </div>
  
  <div class="universities-grid">
    <?php foreach($universities as $uni): ?>
    <div class="university-card fade-in">
      <div class="university-flag">
        <?php 
        $flags = [
          'Canada' => '🇨🇦',
          'UK' => '🇬🇧',
          'Australia' => '🇦🇺',
          'Switzerland' => '🇨🇭',
          'Japan' => '🇯🇵',
          'USA' => '🇺🇸',
          'Singapore' => '🇸🇬',
          'South Africa' => '🇿🇦'
        ];
        echo $flags[$uni['country']] ?? '🏫';
        ?>
      </div>
      <h4><?= $uni['name'] ?></h4>
      <p class="university-country">
        <i class="fas fa-map-marker-alt"></i>
        <?= $uni['country'] ?>
      </p>
      <span class="university-rank"><?= $uni['rank'] ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- Destinations Section -->
<section class="destinations-section section-padding">
  <div class="section-header">
    <h2 class="section-title"><?php echo it('destinations_title'); ?></h2>
    <p class="section-description"><?php echo it('destinations_description'); ?></p>
  </div>
  
  <div class="destinations-grid">
    <?php foreach($destinations as $dest): ?>
    <div class="destination-card fade-in">
      <div class="destination-flag"><?= $dest['flag'] ?></div>
      <div class="destination-info">
        <h4><?= $dest['country'] ?></h4>
        <p class="destination-stats"><?= $dest['students'] ?> Students Placed</p>
        <p class="destination-desc"><?= $dest['description'] ?></p>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- Process Section -->
<section class="process-section section-padding">
  <div class="section-header">
    <h2 class="section-title"><?php echo it('process_title'); ?></h2>
    <p class="section-description"><?php echo it('process_description'); ?></p>
  </div>
  
  <div class="process-steps">
    <div class="process-step slide-left">
      <div class="step-number">1</div>
      <div class="step-content">
        <h4><?php echo it('process_step1'); ?></h4>
        <p><?php echo it('process_step1_desc'); ?></p>
      </div>
    </div>
    
    <div class="process-step slide-right">
      <div class="step-number">2</div>
      <div class="step-content">
        <h4><?php echo it('process_step2'); ?></h4>
        <p><?php echo it('process_step2_desc'); ?></p>
      </div>
    </div>
    
    <div class="process-step slide-left">
      <div class="step-number">3</div>
      <div class="step-content">
        <h4><?php echo it('process_step3'); ?></h4>
        <p><?php echo it('process_step3_desc'); ?></p>
      </div>
    </div>
    
    <div class="process-step slide-right">
      <div class="step-number">4</div>
      <div class="step-content">
        <h4><?php echo it('process_step4'); ?></h4>
        <p><?php echo it('process_step4_desc'); ?></p>
      </div>
    </div>
    
    <div class="process-step slide-left">
      <div class="step-number">5</div>
      <div class="step-content">
        <h4><?php echo it('process_step5'); ?></h4>
        <p><?php echo it('process_step5_desc'); ?></p>
      </div>
    </div>
  </div>
</section>

<!-- Enhanced Testimonials Section -->
<section class="testimonials-section section-padding">
  <div class="testimonials-header">
    <h2 class="section-title"><?php echo it('testimonials_title'); ?></h2>
    <p class="section-subtitle"><?php echo it('testimonials_subtitle'); ?></p>
  </div>
  
  <div class="testimonials-container">
    <div class="testimonials-track" id="testimonialsTrack">
      <?php foreach($testimonials as $index => $testimonial): 
      $num = $index + 1;
      ?>
      <div class="testimonial-card fade-in">
        <p class="testimonial-text"><?php echo it($testimonial['key']); ?></p>
        <div class="testimonial-author">
          <div class="author-avatar"><?= $testimonial['initial'] ?></div>
          <div class="author-info">
            <h5><?php echo it($testimonial['key'] . '_name'); ?></h5>
            <p><?php echo it($testimonial['key'] . '_location'); ?></p>
            <span class="author-achievement"><?php echo it($testimonial['key'] . '_achievement'); ?></span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    
    <div class="testimonial-controls">
      <button class="testimonial-btn" id="prevTestimonial">
        <i class="fas fa-chevron-left"></i>
      </button>
      <button class="testimonial-btn" id="nextTestimonial">
        <i class="fas fa-chevron-right"></i>
      </button>
    </div>
    
    <div class="testimonial-indicators" id="testimonialIndicators">
      <?php for($i = 0; $i < count($testimonials); $i++): ?>
      <div class="indicator <?= $i == 0 ? 'active' : '' ?>" data-index="<?= $i ?>"></div>
      <?php endfor; ?>
    </div>
  </div>
</section>

<!-- Enhanced Partners Section -->
<section class="partners-section section-padding">
  <div class="section-header">
    <h2 class="section-title"><?php echo it('partners_title'); ?></h2>
    <p class="section-description"><?php echo it('partners_description'); ?></p>
  </div>
  
  <div class="partners-container">
    <!-- Banking & Financial Partners -->
    <div class="partners-category banking-partners">
      <div class="category-header">
        <h3 class="category-title">
          <i class="fas fa-university"></i>
          <?php echo it('banking_partners_title'); ?>
        </h3>
        <p class="category-description"><?php echo it('banking_partners_desc'); ?></p>
      </div>
      <div class="partners-grid">
        <div class="partner-card fade-in">
          <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/7/72/CIBC_Logo.svg/320px-CIBC_Logo.svg.png" alt="CIBC Bank" class="partner-logo">
          <div class="partner-name">CIBC Bank</div>
          <div class="partner-role">
            <?php echo $current_lang === 'fr' ? 'Partenaire Prêts Éducation' : 'Education Loan Partner'; ?>
          </div>
        </div>
        
        <div class="partner-card fade-in">
          <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/b/b0/Royal_Bank_of_Canada_Logo.svg/320px-Royal_Bank_of_Canada_Logo.svg.png" alt="RBC Royal Bank" class="partner-logo">
          <div class="partner-name">RBC Royal Bank</div>
          <div class="partner-role">
            <?php echo $current_lang === 'fr' ? 'Partenaire Bancaire Étudiant' : 'Student Banking Partner'; ?>
          </div>
        </div>
        
        <div class="partner-card fade-in">
          <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/c/c8/Scotiabank_Logo.svg/320px-Scotiabank_Logo.svg.png" alt="Scotiabank" class="partner-logo">
          <div class="partner-name">Scotiabank</div>
          <div class="partner-role">
            <?php echo $current_lang === 'fr' ? 'Partenaire Services Financiers' : 'Financial Services Partner'; ?>
          </div>
        </div>
        
        <div class="partner-card fade-in">
          <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/8/8f/BMO_Financial_Group_logo.svg/320px-BMO_Financial_Group_logo.svg.png" alt="BMO Bank of Montreal" class="partner-logo">
          <div class="partner-name">BMO Bank of Montreal</div>
          <div class="partner-role">
            <?php echo $current_lang === 'fr' ? 'Partenaire Bancaire International' : 'International Banking Partner'; ?>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Testing & Certification Partners -->
    <div class="partners-category testing-partners">
      <div class="category-header">
        <h3 class="category-title">
          <i class="fas fa-graduation-cap"></i>
          <?php echo it('testing_partners_title'); ?>
        </h3>
        <p class="category-description"><?php echo it('testing_partners_desc'); ?></p>
      </div>
      <div class="partners-grid">
        <div class="partner-card fade-in">
          <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/9/92/ETS_logo.svg/320px-ETS_logo.svg.png" alt="ETS" class="partner-logo">
          <div class="partner-name">ETS</div>
          <div class="partner-role">
            <?php echo $current_lang === 'fr' ? 'Tests TOEFL & GRE' : 'TOEFL & GRE Testing'; ?>
          </div>
        </div>
        
        <div class="partner-card fade-in">
          <img src="https://upload.wikimedia.org/wikipedia/en/thumb/3/3d/IELTS_logo.svg/320px-IELTS_logo.svg.png" alt="IELTS" class="partner-logo">
          <div class="partner-name">British Council IELTS</div>
          <div class="partner-role">
            <?php echo $current_lang === 'fr' ? 'Centre de Test Officiel' : 'Official Test Center'; ?>
          </div>
        </div>
        
        <div class="partner-card fade-in">
          <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/53/GMAT_logo.svg/320px-GMAT_logo.svg.png" alt="GMAC" class="partner-logo">
          <div class="partner-name">GMAC</div>
          <div class="partner-role">
            <?php echo $current_lang === 'fr' ? 'Partenaire Test GMAT' : 'GMAT Testing Partner'; ?>
          </div>
        </div>
        
        <div class="partner-card fade-in">
          <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/3/33/Duolingo_logo.svg/320px-Duolingo_logo.svg.png" alt="Duolingo" class="partner-logo">
          <div class="partner-name">Duolingo English Test</div>
          <div class="partner-role">
            <?php echo $current_lang === 'fr' ? 'Test d\'Anglais en Ligne' : 'Online English Testing'; ?>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Travel & Insurance Partners -->
    <div class="partners-category travel-partners">
      <div class="category-header">
        <h3 class="category-title">
          <i class="fas fa-plane"></i>
          <?php echo it('travel_partners_title'); ?>
        </h3>
        <p class="category-description"><?php echo it('travel_partners_desc'); ?></p>
      </div>
      <div class="partners-grid">
        <div class="partner-card fade-in">
          <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/f/fa/Air_Canada_Logo.svg/320px-Air_Canada_Logo.svg.png" alt="Air Canada" class="partner-logo">
          <div class="partner-name">Air Canada</div>
          <div class="partner-role">
            <?php echo $current_lang === 'fr' ? 'Compagnie Aérienne Préférée' : 'Preferred Airline'; ?>
          </div>
        </div>
        
        <div class="partner-card fade-in">
          <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/d/d0/Emirates_logo.svg/320px-Emirates_logo.svg.png" alt="Emirates" class="partner-logo">
          <div class="partner-name">Emirates</div>
          <div class="partner-role">
            <?php echo $current_lang === 'fr' ? 'Partenaire Voyage Mondial' : 'Global Travel Partner'; ?>
          </div>
        </div>
        
        <div class="partner-card fade-in">
          <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/6/67/Tata_AIG_logo.svg/320px-Tata_AIG_logo.svg.png" alt="Tata AIG" class="partner-logo">
          <div class="partner-name">Tata AIG</div>
          <div class="partner-role">
            <?php echo $current_lang === 'fr' ? 'Assurance Étudiants' : 'Student Insurance'; ?>
          </div>
        </div>
        
        <div class="partner-card fade-in">
          <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/6/69/Airbnb_Logo_B%C3%A9lo.svg/320px-Airbnb_Logo_B%C3%A9lo.svg.png" alt="Airbnb" class="partner-logo">
          <div class="partner-name">Airbnb</div>
          <div class="partner-role">
            <?php echo $current_lang === 'fr' ? 'Hébergement Étudiants' : 'Student Accommodation'; ?>
          </div>
        </div>
        
        <div class="partner-card fade-in">
          <img src="https://upload.wikimedia.org/wikipedia/en/thumb/7/7e/Amberstudent_logo.svg/320px-Amberstudent_logo.svg.png" alt="AmberStudent" class="partner-logo">
          <div class="partner-name">AmberStudent</div>
          <div class="partner-role">
            <?php echo $current_lang === 'fr' ? 'Logement Étudiants' : 'Student Housing'; ?>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Technology & Education Partners -->
    <div class="partners-category tech-partners">
      <div class="category-header">
        <h3 class="category-title">
          <i class="fas fa-laptop-code"></i>
          <?php echo it('tech_partners_title'); ?>
        </h3>
        <p class="category-description"><?php echo it('tech_partners_desc'); ?></p>
      </div>
      <div class="partners-grid">
        <div class="partner-card fade-in">
          <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/4/4e/Microsoft_logo.svg/320px-Microsoft_logo.svg.png" alt="Microsoft" class="partner-logo">
          <div class="partner-name">Microsoft Education</div>
          <div class="partner-role">
            <?php echo $current_lang === 'fr' ? 'Partenaire Technologie' : 'Technology Partner'; ?>
          </div>
        </div>
        
        <div class="partner-card fade-in">
          <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/a9/Google_Chrome_Logo_%282015-2019%29.svg/320px-Google_Chrome_Logo_%282015-2019%29.svg.png" alt="Google" class="partner-logo">
          <div class="partner-name">Google for Education</div>
          <div class="partner-role">
            <?php echo $current_lang === 'fr' ? 'Apprentissage Numérique' : 'Digital Learning'; ?>
          </div>
        </div>
        
        <div class="partner-card fade-in">
          <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/0/08/Coursera_logo.svg/320px-Coursera_logo.svg.png" alt="Coursera" class="partner-logo">
          <div class="partner-name">Coursera</div>
          <div class="partner-role">
            <?php echo $current_lang === 'fr' ? 'Apprentissage en Ligne' : 'Online Learning'; ?>
          </div>
        </div>
        
        <div class="partner-card fade-in">
          <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/9/9a/LinkedIn_Logo.svg/320px-LinkedIn_Logo.svg.png" alt="LinkedIn" class="partner-logo">
          <div class="partner-name">LinkedIn Learning</div>
          <div class="partner-role">
            <?php echo $current_lang === 'fr' ? 'Développement de Carrière' : 'Career Development'; ?>
          </div>
        </div>
        
        <div class="partner-card fade-in">
          <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/3/34/EdX_logo.svg/320px-EdX_logo.svg.png" alt="edX" class="partner-logo">
          <div class="partner-name">edX</div>
          <div class="partner-role">
            <?php echo $current_lang === 'fr' ? 'Cours en Ligne' : 'Online Courses'; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Resources Section -->
<section class="resources-section section-padding">
  <div class="section-header">
    <h2 class="section-title"><?php echo it('resources_title'); ?></h2>
    <p class="section-description"><?php echo it('resources_description'); ?></p>
  </div>
  
  <div class="resources-grid">
    <div class="resource-card fade-in">
      <div class="resource-icon">
        <i class="fas fa-award"></i>
      </div>
      <h4><?php echo it('resource1_title'); ?></h4>
      <p><?php echo it('resource1_desc'); ?></p>
      <a href="#" class="resource-link">
        <?php echo it('read_more'); ?>
        <i class="fas fa-arrow-right"></i>
      </a>
    </div>
    
    <div class="resource-card fade-in">
      <div class="resource-icon">
        <i class="fas fa-passport"></i>
      </div>
      <h4><?php echo it('resource2_title'); ?></h4>
      <p><?php echo it('resource2_desc'); ?></p>
      <a href="#" class="resource-link">
        <?php echo it('read_more'); ?>
        <i class="fas fa-arrow-right"></i>
      </a>
    </div>
    
    <div class="resource-card fade-in">
      <div class="resource-icon">
        <i class="fas fa-briefcase"></i>
      </div>
      <h4><?php echo it('resource3_title'); ?></h4>
      <p><?php echo it('resource3_desc'); ?></p>
      <a href="#" class="resource-link">
        <?php echo it('read_more'); ?>
        <i class="fas fa-arrow-right"></i>
      </a>
    </div>
  </div>
</section>

<!-- Final CTA Section -->
<section class="cta-section">
  <div class="cta-content">
    <h2 class="fade-in"><?php echo it('cta_title'); ?></h2>
    <p class="fade-in"><?php echo it('cta_description'); ?></p>
    <div class="cta-buttons">
      <button class="cta-button cta-button-white" id="bookConsultation">
        <i class="fas fa-calendar-check"></i>
        <?php echo it('book_consultation'); ?>
      </button>
      <button class="cta-button cta-button-outline" id="downloadBrochure">
        <i class="fas fa-download"></i>
        <?php echo it('download_brochure'); ?>
      </button>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
<script>
(function() {
  'use strict';

  // Get URL parameters
  const urlParams = new URLSearchParams(window.location.search);
  const directCardId = urlParams.get('card');

  // Direct Card Access Logic
  if (directCardId) {
    // Show direct card header
    document.getElementById('directCardHeader').classList.add('show-header');
    
    // Show only the specific card
    const allCards = document.querySelectorAll('.service-card');
    allCards.forEach(card => {
      if (card.dataset.card === directCardId) {
        card.classList.add('show-card', 'highlight-card');
      } else {
        card.classList.remove('show-card');
      }
    });
    
    // Scroll to the specific card
    setTimeout(() => {
      const cardElement = document.getElementById(directCardId);
      if (cardElement) {
        cardElement.scrollIntoView({ 
          behavior: 'smooth',
          block: 'center'
        });
      }
    }, 300);
  }

  // Back to All Services button
  document.getElementById('backToAll').addEventListener('click', function() {
    // Remove card parameter from URL without reloading page
    const newUrl = window.location.pathname;
    window.history.replaceState({}, document.title, newUrl);
    
    // Hide direct card header
    document.getElementById('directCardHeader').classList.remove('show-header');
    
    // Show all cards
    const allCards = document.querySelectorAll('.service-card');
    allCards.forEach(card => {
      card.classList.add('show-card');
      card.classList.remove('highlight-card');
    });
    
    // Scroll to services section
    document.getElementById('services').scrollIntoView({ 
      behavior: 'smooth',
      block: 'start'
    });
  });

  // Scroll to Services
  document.getElementById('scrollToServices').addEventListener('click', function() {
    document.getElementById('services').scrollIntoView({ 
      behavior: 'smooth',
      block: 'start'
    });
  });

  // Scroll to Features
  document.getElementById('scrollToFeatures').addEventListener('click', function() {
    document.getElementById('features').scrollIntoView({ 
      behavior: 'smooth',
      block: 'start'
    });
  });

  // Animation on scroll
  function animateOnScroll() {
    const elements = document.querySelectorAll('.fade-in, .slide-left, .slide-right');
    elements.forEach(el => {
      const rect = el.getBoundingClientRect();
      if (rect.top <= window.innerHeight * 0.85) {
        el.style.animationPlayState = 'running';
      }
    });
  }

  window.addEventListener('scroll', animateOnScroll);
  window.addEventListener('load', animateOnScroll);

  // Enhanced Testimonials Carousel
  const testimonialsTrack = document.getElementById('testimonialsTrack');
  const testimonialIndicators = document.getElementById('testimonialIndicators');
  const indicators = testimonialIndicators.querySelectorAll('.indicator');
  let currentTestimonial = 0;
  const testimonialWidth = 380; // card width + gap

  function updateTestimonials() {
    testimonialsTrack.scrollTo({
      left: currentTestimonial * testimonialWidth,
      behavior: 'smooth'
    });
    
    indicators.forEach((ind, index) => {
      ind.classList.toggle('active', index === currentTestimonial);
    });
  }

  document.getElementById('nextTestimonial').addEventListener('click', () => {
    currentTestimonial = (currentTestimonial + 1) % indicators.length;
    updateTestimonials();
  });

  document.getElementById('prevTestimonial').addEventListener('click', () => {
    currentTestimonial = (currentTestimonial - 1 + indicators.length) % indicators.length;
    updateTestimonials();
  });

  indicators.forEach((ind, index) => {
    ind.addEventListener('click', () => {
      currentTestimonial = index;
      updateTestimonials();
    });
  });

  // Auto-scroll testimonials
  setInterval(() => {
    currentTestimonial = (currentTestimonial + 1) % indicators.length;
    updateTestimonials();
  }, 5000);

  // User ID Management
  function getUserId() {
    let id = sessionStorage.getItem('user_id');
    if (!id) {
      id = 'user-' + Date.now() + '-' + Math.floor(Math.random() * 10000);
      sessionStorage.setItem('user_id', id);
    }
    return id;
  }

  // Apply Now Buttons
  document.querySelectorAll('.apply-button').forEach(button => {
    button.addEventListener('click', function(e) {
      e.preventDefault();
      
      const card = this.closest('.service-card');
      if (!card) return;
      
      const form = card.dataset.form;
      const type = card.dataset.card;
      const userId = getUserId();
      
      let targetUrl = '';
      switch (type) {
        case 'scholarships':
          if (form && form.startsWith('#')) {
            const el = document.querySelector(form);
            if (el) {
              el.scrollIntoView({ behavior: 'smooth', block: 'start' });
              return;
            }
          }
          targetUrl = form || '#scholarships';
          if (targetUrl.startsWith('#')) {
            const el = document.querySelector(targetUrl);
            if (el) {
              el.scrollIntoView({ behavior: 'smooth', block: 'start' });
              return;
            }
          }
          break;
  case 'visa':
  // Don't pass any ID - let visa.php generate a new one
  targetUrl = 'visa.php?country_id=&region_id=';
  console.log('Opening visa form - will generate new ID');
  break;
        case 'i20':
          targetUrl = `select-20.php?form=${encodeURIComponent(form)}&id=${encodeURIComponent(userId)}`;
          break;
        default:
          targetUrl = `${form}?id=${encodeURIComponent(userId)}`;
      }
      
      window.location.href = targetUrl;
    });
  });

  // MODIFIED: Copy Link Buttons with card-specific URLs
  document.querySelectorAll('.copy-button').forEach(btn => {
    btn.addEventListener('click', function() {
      const cardId = this.dataset.cardId;
      const card = document.getElementById(cardId);
      const cardTitle = card.querySelector('.card-title-group h3').textContent;
      
      // Create card-specific URL
      const url = `${window.location.origin}${window.location.pathname}?card=${cardId}`;
      
      // Modern clipboard API
      navigator.clipboard.writeText(url).then(() => {
        showNotification(`Link copied for: ${cardTitle}`);
        this.innerHTML = '<i class="fas fa-check"></i> Copied';
        this.style.background = '#10B981';
        this.style.color = 'white';
        this.style.borderColor = '#10B981';
        
        setTimeout(() => {
          this.innerHTML = '<i class="fas fa-link"></i> <?php echo it('card_copy'); ?>';
          this.style.background = '';
          this.style.color = '';
          this.style.borderColor = '';
        }, 2000);
      }).catch(() => {
        // Fallback
        const textarea = document.createElement('textarea');
        textarea.value = url;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        showNotification(`Link copied for: ${cardTitle}`);
      });
    });
  });

  // Additional CTA buttons
  document.getElementById('bookConsultation').addEventListener('click', () => {
    window.open('consultation.php', '_blank');
  });

  document.getElementById('downloadBrochure').addEventListener('click', () => {
    window.open('brochure.pdf', '_blank');
  });

  // Notification function
  function showNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      background: linear-gradient(135deg, var(--primary-navy), var(--secondary-blue));
      color: white;
      padding: 16px 24px;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
      z-index: 10000;
      animation: slideIn 0.3s ease;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 1rem;
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255,255,255,0.1);
      max-width: 400px;
      word-break: break-word;
    `;
    
    notification.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
    document.body.appendChild(notification);

    setTimeout(() => {
      notification.style.animation = 'slideOut 0.3s ease';
      setTimeout(() => notification.remove(), 300);
    }, 3000);
  }

  // Add animation styles
  const style = document.createElement('style');
  style.textContent = `
    @keyframes slideIn {
      from {
        transform: translateX(100%);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }
    @keyframes slideOut {
      from {
        transform: translateX(0);
        opacity: 1;
      }
      to {
        transform: translateX(100%);
        opacity: 0;
      }
    }
    
    @keyframes slideInDown {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    .fade-in, .slide-left, .slide-right {
      animation-play-state: paused;
    }
    
    .fade-in {
      animation: fadeInUp 0.6s ease forwards;
    }
    
    .slide-left {
      animation: slideInLeft 0.6s ease forwards;
    }
    
    .slide-right {
      animation: slideInRight 0.6s ease forwards;
    }
    
    /* Parallax effect for hero */
    .hero-section {
      transform: translateZ(0);
    }
    
    /* Partner logo hover effect */
    .partner-card:hover .partner-logo {
      animation: pulse 0.6s ease;
    }
    
    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.1); }
      100% { transform: scale(1.05); }
    }
  `;
  document.head.appendChild(style);

  // Add parallax effect to hero section
  window.addEventListener('scroll', function() {
    const scrolled = window.pageYOffset;
    const hero = document.querySelector('.hero-section');
    const rate = scrolled * 0.5;
    hero.style.transform = `translate3d(0, ${rate}px, 0)`;
  });

  // Initialize floating animations
  document.querySelectorAll('.float-animation').forEach(el => {
    el.style.animationDelay = `${Math.random() * 2}s`;
  });

  // If direct card access, adjust page behavior
  if (directCardId) {
    // Add a subtle background color to highlight the context
    document.body.style.backgroundColor = 'var(--bg-light)';
    
    // Auto-scroll to the card after page load
    window.addEventListener('load', function() {
      setTimeout(() => {
        const cardElement = document.getElementById(directCardId);
        if (cardElement) {
          cardElement.scrollIntoView({ 
            behavior: 'smooth',
            block: 'center'
          });
        }
      }, 500);
    });
  }

})();
</script>

</body>
</html>