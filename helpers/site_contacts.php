<?php
declare(strict_types=1);

/**
 * Canonical public contact details for Xander Global Scholars.
 * Use this everywhere office phone numbers and addresses are shown.
 */
function xgs_site_contacts(): array
{
    static $contacts = null;
    if ($contacts !== null) {
        return $contacts;
    }

    $contacts = [
        'general_email' => 'Hello@xanderglobalscholars.com',
        'offices' => [
            [
                'id' => 'sanfrancisco',
                'title_en' => 'San Francisco Office',
                'title_fr' => 'Bureau de San Francisco',
                'address_en' => 'San Francisco, CA, USA',
                'address_fr' => 'San Francisco, CA, USA',
                'phone' => '+1 (450) 390-8614',
                'hours_en' => 'Mon-Fri: 9:00 AM - 6:00 PM PST',
                'hours_fr' => 'Lun-Ven: 9:00 AM - 6:00 PM PST',
                'timezone' => 'PST',
                'flag' => '🇺🇸',
                'color' => '#3B82F6',
                'lat' => 37.7749,
                'lng' => -122.4194,
                'google_maps_link' => 'https://maps.google.com/?q=San+Francisco,+CA,+USA',
            ],
            [
                'id' => 'muhanga',
                'title_en' => 'Rwanda Office',
                'title_fr' => 'Bureau du Rwanda',
                'address_en' => 'Muhanga, Rwanda',
                'address_fr' => 'Muhanga, Rwanda',
                'phone' => '+250 788 242 069',
                'hours_en' => 'Mon-Fri: 9:00 AM - 5:00 PM CAT',
                'hours_fr' => 'Lun-Ven: 9:00 AM - 5:00 PM CAT',
                'timezone' => 'CAT',
                'flag' => '🇷🇼',
                'color' => '#10B981',
                'lat' => -2.0833,
                'lng' => 29.7500,
                'google_maps_link' => 'https://maps.google.com/?q=Muhanga,+Rwanda',
            ],
        ],
    ];

    return $contacts;
}

function xgs_contact_lang(string $lang): string
{
    return $lang === 'fr' ? 'fr' : 'en';
}

function xgs_contact_email(): string
{
    return xgs_site_contacts()['general_email'];
}

function xgs_contact_offices(string $lang = 'en'): array
{
    $lang = xgs_contact_lang($lang);
    $offices = [];

    foreach (xgs_site_contacts()['offices'] as $office) {
        $offices[] = [
            'id' => $office['id'],
            'title' => $office['title_' . $lang],
            'address' => $office['address_' . $lang],
            'phone' => $office['phone'],
            'hours' => $office['hours_' . $lang],
            'timezone' => $office['timezone'],
            'flag' => $office['flag'],
            'color' => $office['color'],
            'lat' => $office['lat'],
            'lng' => $office['lng'],
            'google_maps_link' => $office['google_maps_link'],
        ];
    }

    return $offices;
}

function xgs_contact_office_display(string $lang = 'en'): array
{
    $display = [];

    foreach (xgs_contact_offices($lang) as $office) {
        $display[] = [
            'title' => $office['title'],
            'address' => $office['address'],
            'phone' => $office['phone'],
            'hours' => $office['hours'],
        ];
    }

    return $display;
}

function xgs_contact_phone_href(string $phone): string
{
    $phone = trim($phone);
    if ($phone === '') {
        return '';
    }

    if ($phone[0] !== '+') {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    return '+' . (preg_replace('/\D+/', '', substr($phone, 1)) ?? '');
}

function xgs_contact_sync_translation_keys(array &$translations, string $lang): void
{
    $lang = xgs_contact_lang($lang);
    $site = xgs_site_contacts();
    $offices = $site['offices'];

    $translations[$lang]['email_address'] = $site['general_email'];
    $translations[$lang]['info_email'] = $site['general_email'];

    $map = [
        'sanfrancisco' => $offices[0],
        'muhanga' => $offices[1],
    ];

    foreach ($map as $prefix => $office) {
        $translations[$lang][$prefix . '_title'] = $office['title_' . $lang];
        $translations[$lang][$prefix . '_address'] = $office['address_' . $lang];
        $translations[$lang][$prefix . '_phone'] = $office['phone'];
        $translations[$lang][$prefix . '_hours'] = $office['hours_' . $lang];
    }

    $translations[$lang]['us_phone'] = $offices[0]['phone'];
    $translations[$lang]['us_office'] = $offices[0]['title_' . $lang];
    $translations[$lang]['us_address'] = $offices[0]['address_' . $lang];
    $translations[$lang]['rwanda_phone'] = $offices[1]['phone'];
    $translations[$lang]['rwanda_office'] = $offices[1]['title_' . $lang];
    $translations[$lang]['rwanda_address'] = $offices[1]['address_' . $lang];
    $translations[$lang]['san_francisco_location'] = $offices[0]['title_' . $lang];
}

function xgs_contact_page_offices(): array
{
    $offices = [];

    foreach (xgs_site_contacts()['offices'] as $office) {
        $prefix = $office['id'] === 'sanfrancisco' ? 'sanfrancisco' : 'muhanga';
        $offices[] = [
            'title_key' => $prefix . '_title',
            'address_key' => $prefix . '_address',
            'phone_key' => $prefix . '_phone',
            'hours_key' => $prefix . '_hours',
            'flag' => $office['flag'],
            'color' => $office['color'],
            'timezone' => $office['timezone'],
            'lat' => $office['lat'],
            'lng' => $office['lng'],
        ];
    }

    return $offices;
}

function xgs_contact_footer_entries(): array
{
    return [
        [
            'phone' => 'us_phone',
            'label' => 'us_office',
            'address' => 'us_address',
        ],
        [
            'phone' => 'rwanda_phone',
            'label' => 'rwanda_office',
            'address' => 'rwanda_address',
        ],
    ];
}
