<?php
declare(strict_types=1);

/**
 * Service cards + translations (homepage catalog moved to Services page).
 */

$service_catalog_translations = [
    'en' => [
        'page_title' => 'Our Services - Xander Global Scholars',
        'page_description' => 'Apply for study abroad, scholarships, visas, jobs, credit transfer, and travel with Xander Global Scholars.',
        'services_title' => 'Our Services',
        'services_description' => 'Everything you need to study, work, study loan, credit and move abroad — guided by experts.',
        'card_apply' => 'Apply Now',
        'card_copy' => 'Copy Link',
        'direct_header_title' => 'Service access',
        'direct_header_text' => 'You opened a direct link to one service. View all services below or apply now.',
        'back_to_all' => 'View all services',
        'card1_title' => 'Study & Work Abroad',
        'card1_subtitle' => 'Universities, jobs, visas – all in one place',
        'card1_point1' => 'University applications',
        'card2_title' => 'Scholarships & Loans',
        'card2_subtitle' => 'Funding solutions tailored to your needs',
        'card2_point1' => 'Up to 90% scholarships',
        'card3_title' => 'I-20 Application',
        'card3_subtitle' => 'Fast processing for US institutions',
        'card3_point1' => 'SEVIS compliant',
        'card4_title' => 'Credit Transfer',
        'card4_subtitle' => 'Transfer credits to partner universities',
        'card4_point1' => 'Transcript evaluation',
        'card5_title' => 'Visa Application',
        'card5_subtitle' => 'Study & visit visas with full guidance',
        'card5_point1' => 'Document preparation',
        'card6_title' => 'Apply for Job',
        'card6_subtitle' => 'Work opportunities across Europe',
        'card6_point1' => 'Job placement support',
        'card7_title' => 'Airticketing Reservation',
        'card7_subtitle' => 'Flight bookings for students & professionals',
        'card7_point1' => 'Student & academic fares',
    ],
    'fr' => [
        'page_title' => 'Nos Services - Xander Global Scholars',
        'page_description' => 'Étudier, travailler, bourses, visas et voyages avec Xander Global Scholars.',
        'services_title' => 'Nos Services',
        'services_description' => 'Tout ce dont vous avez besoin pour étudier, travailler et déménager à l\'étranger.',
        'card_apply' => 'Postuler maintenant',
        'card_copy' => 'Copier le lien',
        'direct_header_title' => 'Accès direct au service',
        'direct_header_text' => 'Vous avez ouvert un lien direct. Consultez tous les services ci-dessous.',
        'back_to_all' => 'Voir tous les services',
        'card1_title' => 'Étudier & Travailler à l\'Étranger',
        'card1_subtitle' => 'Universités, emplois, visas – tout au même endroit',
        'card1_point1' => 'Candidatures universitaires',
        'card2_title' => 'Bourses & Prêts',
        'card2_subtitle' => 'Solutions de financement adaptées',
        'card2_point1' => 'Bourses jusqu\'à 90%',
        'card3_title' => 'Demande I-20',
        'card3_subtitle' => 'Traitement rapide pour les USA',
        'card3_point1' => 'Conforme SEVIS',
        'card4_title' => 'Transfert de Crédits',
        'card4_subtitle' => 'Transférez vos crédits',
        'card4_point1' => 'Évaluation relevés',
        'card5_title' => 'Demande de Visa',
        'card5_subtitle' => 'Visas études & visite',
        'card5_point1' => 'Préparation documents',
        'card6_title' => 'Postuler à un Emploi',
        'card6_subtitle' => 'Opportunités en Europe',
        'card6_point1' => 'Support placement',
        'card7_title' => 'Réservation de Billets',
        'card7_subtitle' => 'Vols pour étudiants & professionnels',
        'card7_point1' => 'Tarifs étudiants',
    ],
];

$service_catalog_cards = [
    ['id' => 'admissions', 'icon' => '🎓', 'title_key' => 'card1_title', 'subtitle_key' => 'card1_subtitle', 'highlight_key' => 'card1_point1', 'form' => 'student-application.php'],
    ['id' => 'scholarships', 'icon' => '💰', 'title_key' => 'card2_title', 'subtitle_key' => 'card2_subtitle', 'highlight_key' => 'card2_point1', 'form' => 'master-loan.php'],
    ['id' => 'i20', 'icon' => '📄', 'title_key' => 'card3_title', 'subtitle_key' => 'card3_subtitle', 'highlight_key' => 'card3_point1', 'form' => 'form-20.php'],
    ['id' => 'credit', 'icon' => '🔁', 'title_key' => 'card4_title', 'subtitle_key' => 'card4_subtitle', 'highlight_key' => 'card4_point1', 'form' => 'credit_transfer.php'],
    ['id' => 'visa', 'icon' => '✈️', 'title_key' => 'card5_title', 'subtitle_key' => 'card5_subtitle', 'highlight_key' => 'card5_point1', 'form' => 'visa.php'],
    ['id' => 'jobs', 'icon' => '💼', 'title_key' => 'card6_title', 'subtitle_key' => 'card6_subtitle', 'highlight_key' => 'card6_point1', 'form' => 'job-application.php'],
    ['id' => 'airticket', 'icon' => '🛫', 'title_key' => 'card7_title', 'subtitle_key' => 'card7_subtitle', 'highlight_key' => 'card7_point1', 'form' => 'air-ticket-reservation.php'],
];

if (!function_exists('st')) {
    function st(string $key): string
    {
        global $service_catalog_translations, $current_lang;
        $lang = $current_lang ?? 'en';

        return $service_catalog_translations[$lang][$key]
            ?? $service_catalog_translations['en'][$key]
            ?? $key;
    }
}

function xander_service_catalog_ids(): array
{
    global $service_catalog_cards;

    return array_column($service_catalog_cards, 'id');
}
