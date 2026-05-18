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
        'hero_title' => 'Global Education & Career Journey — Simplified',
        'hero_description' => 'Xander Global Scholars supports students and professionals with admissions, visas, scholarships, jobs, and travel — all in one trusted platform.',
        'hero_badge' => 'Trusted by students worldwide',
        'hero_trust_1' => 'Admissions',
        'hero_trust_2' => 'Scholarships',
        'hero_trust_3' => 'Visas & jobs',
        'hero_scroll' => 'Explore below',
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

        // Homepage services insights hub
        'insights_eyebrow' => 'Student success hub',
        'insights_title' => 'Your Complete Path Abroad — One Institution',
        'insights_description' => 'From first consultation to landing abroad: admissions, funding, visas, jobs, and travel — guided step by step.',
        'insights_journey_title' => 'Your journey at a glance',
        'insights_at_glance' => 'At a glance',
        'insights_services_count' => 'End-to-end services',
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
        'hero_badge' => 'La confiance des étudiants du monde entier',
        'hero_trust_1' => 'Admissions',
        'hero_trust_2' => 'Bourses',
        'hero_trust_3' => 'Visas & emplois',
        'hero_scroll' => 'Découvrir',
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

        // Homepage services insights hub
        'insights_eyebrow' => 'Hub réussite étudiante',
        'insights_title' => 'Votre parcours international — une seule institution',
        'insights_description' => 'De la première consultation à l\'arrivée : admissions, financement, visas, emplois et voyage — accompagnement pas à pas.',
        'insights_journey_title' => 'Votre parcours en bref',
        'insights_at_glance' => 'En un coup d\'œil',
        'insights_services_count' => 'Services complets',
        'insights_cta_all' => 'Voir tous les services',
        'insight_tip_admissions' => 'Candidatures assistées par IA avec relecture conseiller — études ou travail à l\'étranger.',
        'insight_tip_scholarships' => 'Bourses institutionnelles et prêts éducation jusqu\'à 90 % de financement.',
        'insight_tip_i20' => 'Support I-20 conforme SEVIS avec coordination universitaire pour les USA.',
        'insight_tip_credit' => 'Transfert de crédits vers universités partenaires pour raccourcir votre parcours.',
        'insight_tip_visa' => 'Préparation documents, entretiens blancs et accompagnement visa.',
        'insight_tip_jobs' => 'Placements en Europe avec relocalisation et logement.',
        'insight_tip_airticket' => 'Tarifs étudiants, modifications flexibles et coordination voyage.',
        
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
        'loans_home_title' => 'Prêts étudiants des institutions',
        'loans_home_desc' => 'Programmes de prêts publiés par nos institutions partenaires.',
        'loan_apply_cta' => 'Voir le prêt',
        'opp_mixed_title' => 'Bourses et prêts étudiants',
        'opp_mixed_desc' => 'Opportunités de financement des institutions partenaires.',
        'promo_section_title' => 'Planifiez votre parcours avec Xander',
        'promo_section_desc' => 'Postulez, financez vos études ou parlez à un conseiller.',
        'promo_apply_title' => 'Commencer votre candidature',
        'promo_apply_desc' => 'Admissions assistées par IA avec relecture conseiller.',
        'promo_apply_cta' => 'Postuler',
        'promo_loan_title' => 'Prêts et bourses',
        'promo_loan_desc' => 'Financement jusqu\'à 90 % et partenaires prêts.',
        'promo_loan_cta' => 'Voir le financement',
        'promo_consult_title' => 'Consultation gratuite',
        'promo_consult_desc' => 'Session gratuite avec nos conseillers.',
        'promo_consult_cta' => 'Réserver',
        
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
  position: relative;
  min-height: min(88vh, 780px);
  padding: clamp(2.5rem, 6vh, 4.5rem) 20px clamp(3rem, 8vh, 5rem);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  background: linear-gradient(165deg, #f8fafc 0%, #ffffff 42%, #fff8f0 100%);
}

.hero-bg {
  position: absolute;
  inset: 0;
  pointer-events: none;
  overflow: hidden;
}

.hero-grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(1, 47, 107, 0.04) 1px, transparent 1px),
    linear-gradient(90deg, rgba(1, 47, 107, 0.04) 1px, transparent 1px);
  background-size: 48px 48px;
  mask-image: radial-gradient(ellipse 80% 70% at 50% 40%, #000 20%, transparent 75%);
  opacity: 0.5;
}

.hero-orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(60px);
  opacity: 0.55;
  animation: heroOrbFloat 14s ease-in-out infinite;
}

.hero-orb--1 {
  width: min(420px, 55vw);
  height: min(420px, 55vw);
  background: rgba(30, 74, 140, 0.35);
  top: -8%;
  left: -6%;
  animation-delay: 0s;
}

.hero-orb--2 {
  width: min(360px, 45vw);
  height: min(360px, 45vw);
  background: rgba(232, 119, 34, 0.28);
  top: 10%;
  right: -8%;
  animation-delay: -4s;
}

.hero-orb--3 {
  width: min(280px, 38vw);
  height: min(280px, 38vw);
  background: rgba(13, 148, 136, 0.22);
  bottom: 5%;
  left: 35%;
  animation-delay: -7s;
}

@keyframes heroOrbFloat {
  0%, 100% { transform: translate(0, 0) scale(1); }
  33% { transform: translate(24px, -18px) scale(1.06); }
  66% { transform: translate(-16px, 12px) scale(0.96); }
}

.hero-content {
  max-width: 920px;
  margin: 0 auto;
  text-align: center;
  position: relative;
  z-index: 2;
  will-change: transform, opacity;
}

.hero-animate {
  opacity: 0;
  animation: heroFadeUp 0.95s cubic-bezier(0.22, 1, 0.36, 1) forwards;
}

.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.45rem 1rem 0.45rem 0.65rem;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.85);
  border: 1px solid rgba(1, 47, 107, 0.1);
  box-shadow: 0 4px 20px rgba(1, 47, 107, 0.08);
  font-size: 0.82rem;
  font-weight: 700;
  color: var(--primary-navy);
  margin-bottom: 1.25rem;
  animation-delay: 0.05s;
}

.hero-badge-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #22c55e;
  box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.25);
  animation: heroPulse 2s ease-in-out infinite;
}

@keyframes heroPulse {
  0%, 100% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.15); opacity: 0.85; }
}

@keyframes heroFadeUp {
  from { opacity: 0; transform: translateY(28px); }
  to { opacity: 1; transform: translateY(0); }
}

.hero-title {
  font-size: clamp(2.1rem, 5.5vw, 3.45rem);
  font-weight: 900;
  line-height: 1.12;
  margin: 0 0 1.25rem;
  letter-spacing: -0.03em;
  background: linear-gradient(
    120deg,
    var(--primary-navy) 0%,
    var(--dark-blue) 35%,
    #1e4a8c 55%,
    var(--accent-gold) 85%,
    var(--primary-navy) 100%
  );
  background-size: 220% auto;
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  animation: heroFadeUp 0.95s cubic-bezier(0.22, 1, 0.36, 1) forwards, heroTitleShine 8s linear infinite;
  animation-delay: 0.15s, 0s;
  opacity: 0;
}

@keyframes heroTitleShine {
  0% { background-position: 0% center; }
  100% { background-position: 220% center; }
}

.hero-description {
  font-size: clamp(1rem, 2.2vw, 1.2rem);
  color: var(--text-light);
  max-width: 680px;
  margin: 0 auto 1.75rem;
  line-height: 1.75;
  animation-delay: 0.28s;
}

.hero-trust {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 0.5rem 0.75rem;
  margin-bottom: 1.75rem;
  animation-delay: 0.38s;
}

.hero-trust span {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.8rem;
  font-weight: 600;
  color: #334155;
  padding: 0.35rem 0.75rem;
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.7);
  border: 1px solid rgba(1, 47, 107, 0.08);
  transition: transform 0.25s, box-shadow 0.25s;
}

.hero-trust span:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(1, 47, 107, 0.1);
}

.hero-trust i {
  color: var(--accent-gold);
  font-size: 0.75rem;
}

.hero-cta {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
  animation-delay: 0.48s;
}

.hero-cta .cta-button {
  animation: heroFadeUp 0.95s cubic-bezier(0.22, 1, 0.36, 1) forwards;
}

.hero-cta .cta-primary { animation-delay: 0.52s; opacity: 0; }
.hero-cta .cta-secondary { animation-delay: 0.62s; opacity: 0; }

.hero-scroll-hint {
  margin-top: 2.5rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.35rem;
  color: #64748b;
  font-size: 0.78rem;
  font-weight: 600;
  text-decoration: none;
  animation: heroFadeUp 0.95s cubic-bezier(0.22, 1, 0.36, 1) forwards, heroBounce 2.2s ease-in-out infinite;
  animation-delay: 0.75s, 1.2s;
  opacity: 0;
  cursor: pointer;
  border: none;
  background: none;
  font-family: inherit;
}

.hero-scroll-hint i {
  font-size: 1.1rem;
  color: var(--accent-gold);
}

@keyframes heroBounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(6px); }
}

.hero-section .cta-primary {
  box-shadow: 0 10px 32px rgba(1, 47, 107, 0.3);
}

.hero-section .cta-primary:hover {
  box-shadow: 0 16px 40px rgba(1, 47, 107, 0.4), 0 0 24px rgba(242, 166, 90, 0.2);
}

@media (prefers-reduced-motion: reduce) {
  .hero-orb, .hero-title, .hero-scroll-hint, .hero-badge-dot { animation: none !important; }
  .hero-animate, .hero-title, .hero-cta .cta-button, .hero-scroll-hint { opacity: 1; transform: none; animation: none !important; }
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
    min-height: auto;
    padding: 2rem 16px 2.75rem;
  }
  
  .hero-trust span { font-size: 0.75rem; padding: 0.3rem 0.6rem; }
  
  .hero-cta {
    flex-direction: column;
    align-items: center;
  }
  
  .cta-button {
    width: 100%;
    max-width: 300px;
  }
  
  .section-title {
    font-size: 2rem;
  }
  
  .cta-buttons {
    flex-direction: column;
    align-items: center;
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
<section class="hero-section" id="homeHero">
  <div class="hero-bg" aria-hidden="true">
    <span class="hero-orb hero-orb--1"></span>
    <span class="hero-orb hero-orb--2"></span>
    <span class="hero-orb hero-orb--3"></span>
    <div class="hero-grid"></div>
  </div>
  <div class="hero-content" id="heroContent">
    <div class="hero-badge hero-animate">
      <span class="hero-badge-dot" aria-hidden="true"></span>
      <?php echo it('hero_badge'); ?>
    </div>
    <h1 class="hero-title"><?php echo it('hero_title'); ?></h1>
    <p class="hero-description hero-animate"><?php echo it('hero_description'); ?></p>
    <div class="hero-trust hero-animate">
      <span><i class="fas fa-check-circle"></i> <?php echo it('hero_trust_1'); ?></span>
      <span><i class="fas fa-check-circle"></i> <?php echo it('hero_trust_2'); ?></span>
      <span><i class="fas fa-check-circle"></i> <?php echo it('hero_trust_3'); ?></span>
    </div>
    <div class="hero-cta hero-animate">
      <a class="cta-button cta-primary" href="services.php<?php echo !empty($current_lang) ? '?lang=' . rawurlencode((string) $current_lang) : ''; ?>">
        <i class="fas fa-rocket"></i>
        <?php echo it('start_application'); ?>
      </a>
      <button type="button" class="cta-button cta-secondary" id="scrollToFeatures">
        <i class="fas fa-play-circle"></i>
        <?php echo it('learn_more'); ?>
      </button>
    </div>
    <button type="button" class="hero-scroll-hint" id="heroScrollHint" aria-label="<?php echo htmlspecialchars(it('hero_scroll'), ENT_QUOTES, 'UTF-8'); ?>">
      <span><?php echo it('hero_scroll'); ?></span>
      <i class="fas fa-chevron-down"></i>
    </button>
  </div>
</section>

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

<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/homepage_services_insights.php';
require_once __DIR__ . '/includes/homepage_opportunities.php';
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

  // Additional CTA buttons
  document.getElementById('bookConsultation').addEventListener('click', () => {
    window.open('consultation.php', '_blank');
  });

  document.getElementById('downloadBrochure').addEventListener('click', () => {
    window.open('brochure.pdf', '_blank');
  });


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

  function scrollToInsights() {
    const el = document.getElementById('services-insights');
    if (el) {
      el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  const scrollBtn = document.getElementById('scrollToFeatures');
  const heroScrollHint = document.getElementById('heroScrollHint');
  if (scrollBtn) scrollBtn.addEventListener('click', scrollToInsights);
  if (heroScrollHint) heroScrollHint.addEventListener('click', scrollToInsights);

  // Subtle hero parallax while scrolling
  const heroSection = document.getElementById('homeHero');
  const heroContent = document.getElementById('heroContent');
  const heroOrbs = heroSection ? heroSection.querySelectorAll('.hero-orb') : [];
  let heroTicking = false;
  function updateHeroParallax() {
    if (!heroSection || !heroContent) return;
    const rect = heroSection.getBoundingClientRect();
    const h = heroSection.offsetHeight || 1;
    const progress = Math.min(Math.max(-rect.top / h, 0), 1);
    heroContent.style.transform = `translateY(${progress * 36}px)`;
    heroContent.style.opacity = String(1 - progress * 0.35);
    heroOrbs.forEach((orb, i) => {
      orb.style.transform = `translateY(${progress * (18 + i * 10)}px)`;
    });
    heroTicking = false;
  }
  window.addEventListener('scroll', () => {
    if (!heroTicking) {
      heroTicking = true;
      requestAnimationFrame(updateHeroParallax);
    }
  }, { passive: true });
  updateHeroParallax();

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
