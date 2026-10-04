<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/service_contract_rules.php';
require_once dirname(__DIR__) . '/includes/contract_fee_catalog.php';

$failed = 0;

function expect(bool $condition, string $message): void
{
    global $failed;
    if ($condition) {
        echo "OK  {$message}\n";
        return;
    }
    $failed++;
    echo "FAIL {$message}\n";
}

expect(!xander_sc_staff_authorized(null), 'missing staff id is blocked');
expect(!xander_sc_staff_authorized(0), 'zero staff id is blocked');
expect(xander_sc_staff_authorized(4), 'positive staff id is allowed');

$catalog = xander_contract_fee_catalog();
$visitCountries = array_column(xander_sc_countries_for_service_catalog($catalog, 'visit'), 'name');
expect(in_array('Canada', $visitCountries, true), 'visit countries include Canada');
expect(in_array('United States', $visitCountries, true), 'visit countries include United States');
expect(!in_array('Spain', $visitCountries, true), 'visit countries do not include a work-only destination');

$workCountries = array_column(xander_sc_countries_for_service_catalog($catalog, 'work'), 'name');
expect(in_array('Spain', $workCountries, true), 'work countries include Spain');
expect(in_array('Canada', $workCountries, true), 'work countries include Canada job programs');
expect(in_array('Finland', $workCountries, true), 'work countries include the September job list');
expect(!in_array('United Kingdom', $workCountries, true), 'work countries omit visit-only United Kingdom');

$spainJobs = xander_sc_catalog_offerings($catalog, 'work', 'Spain');
expect(count($spainJobs) >= 2, 'Spain has standard and credit job plans');
expect(
    xander_sc_catalog_offerings($catalog, 'work', 'Canada') !== [],
    'Canada has French program job offerings'
);
$canadaVisits = xander_sc_catalog_offerings($catalog, 'visit', 'Canada');
expect($canadaVisits !== [], 'Canada has visit packages');
expect(
    xander_sc_pick_offering($spainJobs, (string) $canadaVisits[0]['id']) === null,
    'a Canada visit package is not a Spain job'
);

$studyRows = [
    ['id' => 'program:9', 'service_type' => 'study', 'country_ref' => 'id:1', 'active' => true, 'school_name' => 'North College', 'program_name' => 'Business'],
    ['id' => 'program:4', 'service_type' => 'study', 'country_ref' => 'id:2', 'active' => true, 'school_name' => 'Other College', 'program_name' => 'Nursing'],
];
$canadaStudy = xander_sc_filter_study_rows($studyRows, 'id:1');
expect(count($canadaStudy) === 1 && $canadaStudy[0]['id'] === 'program:9', 'study offerings filter by country');
expect(
    xander_sc_reject_offering($canadaStudy[0], 'work', 'id:1') !== null,
    'a study offering is rejected when the service is work'
);
expect(
    xander_sc_reject_offering($canadaStudy[0], 'study', 'id:2') !== null,
    'a Canada study program is rejected for a different country'
);
expect(xander_sc_reject_offering($canadaStudy[0], 'study', 'id:1') === null, 'matching study offering is accepted');
expect(xander_sc_reject_offering(null, 'study', 'id:1') !== null, 'missing offering is rejected');

expect(xander_sc_validate_fee('commitment', 0, 'CAD', true) !== null, 'zero commitment fee is rejected');
expect(xander_sc_validate_fee('upfront', 0, 'CAD', true) === null, 'zero upfront fee is allowed');
expect(xander_sc_validate_fee('other', 0, 'USD', true, true) === null, 'a zero other fee is allowed when it is a promotion');
expect(xander_sc_validate_fee('other', 0, 'USD', true, false) !== null, 'a zero standard other fee is rejected');
expect(xander_sc_validate_fee('promotion', 0, 'CAD', true) === null, 'zero promotion fee is allowed');
expect(xander_sc_validate_remaining(0, 'CAD', true) !== null, 'remaining balance is required');
expect(xander_sc_validate_remaining(500, 'CAD', true) === null, 'positive remaining balance is accepted');
expect(xander_sc_validate_prepared_by('', 'Hakizimana') !== null, 'staff first name is required');
expect(xander_sc_validate_prepared_by('Jean de Dieu', 'Hakizimana') === null, 'staff prepared-by name is accepted');
expect(xander_sc_validate_fee('upfront', -5, 'CAD', true) !== null, 'negative fee is rejected');
expect(xander_sc_validate_fee('', 10, 'CAD', true) !== null, 'missing fee type is rejected');
expect(xander_sc_validate_fee('upfront', 10, '', false) !== null, 'missing currency is rejected');
expect(xander_sc_validate_fee('upfront', 1500, 'CAD', true) === null, 'valid fee is accepted');

$state = ['service' => '', 'country_ref' => 'id:1', 'offering_id' => 'program:9', 'offering' => ['id' => 'program:9']];
$changed = xander_sc_apply_change($state, 'service', 'work');
expect($changed['country_ref'] === '' && $changed['offering_id'] === '' && $changed['offering'] === null, 'service change clears country and offering');
$withCountry = xander_sc_apply_change(['service' => 'study', 'country_ref' => 'id:1', 'offering_id' => 'program:9', 'offering' => ['id' => 'program:9']], 'country', ['ref' => 'id:2', 'name' => 'United States']);
expect($withCountry['offering_id'] === '' && $withCountry['country_name'] === 'United States', 'country change clears the offering');

$a = xander_sc_new_token();
$b = xander_sc_new_token();
expect(xander_sc_token_is_valid($a) && $a !== $b, 'contract tokens are unique 64-character hex values');
expect(!xander_sc_token_is_valid('123'), 'short tokens are rejected');
expect(!xander_sc_token_is_valid('student@example.com'), 'email is not a valid public token');

$now = '2026-09-27 12:00:00';
expect(xander_sc_sign_block_reason(['status' => 'cancelled', 'expires_at' => ''], $now) !== null, 'cancelled contract cannot be signed');
expect(xander_sc_sign_block_reason(['status' => 'expired', 'expires_at' => ''], $now) !== null, 'expired contract cannot be signed');
expect(xander_sc_sign_block_reason(['status' => 'signed', 'expires_at' => ''], $now) !== null, 'signed contract cannot be signed twice');
expect(xander_sc_sign_block_reason(['status' => 'pending_signature', 'expires_at' => '2026-09-27 11:00:00'], $now) !== null, 'past expiry blocks signing');
expect(xander_sc_sign_block_reason(['status' => 'viewed', 'expires_at' => '2026-10-01 00:00:00'], $now) === null, 'viewed contract can still be signed');
expect(xander_sc_sign_preserves_snapshot(), 'signing does not update snapshot columns');

$studyFacts = xander_sc_renderer_facts('study', ['school_name' => 'North College', 'program_name' => 'Business', 'level' => 'Bachelor']);
expect($studyFacts['show_study'] && str_contains($studyFacts['study_text'], 'North College') && $studyFacts['work_text'] === '' && $studyFacts['visit_text'] === '', 'study renderer contains study fields only');
$workFacts = xander_sc_renderer_facts('work', ['job_title' => 'Warehouse Worker', 'category' => 'Job seeker']);
expect($workFacts['show_work'] && str_contains($workFacts['work_text'], 'Warehouse Worker') && !$workFacts['show_study'], 'work renderer contains work fields only');
$visitFacts = xander_sc_renderer_facts('visit', ['package_name' => 'Canada Visit Visa – Full Package']);
expect($visitFacts['show_visit'] && str_contains($visitFacts['visit_text'], 'Canada Visit Visa') && !$visitFacts['show_study'] && !$visitFacts['show_work'], 'visit renderer contains visit fields only');

expect(xander_sc_format_reference(123, '2026') === 'CTR-2026-000123', 'reference format is CTR-year-sequence');
expect(xander_sc_package_matches_country('Language pathway', ['France'], 'France'), 'a service is available in a country staff selected');
expect(!xander_sc_package_matches_country('Language pathway', ['Spain'], 'France'), 'a service stays unavailable in a country that was not selected');
expect(xander_sc_package_matches_country('Job in Spain', [], 'Spain'), 'an older title still makes the service available in that country');
expect(xander_sc_sections_for_service('study') === ['study', 'credit'], 'study contracts can use study and credit services');

if ($failed > 0) {
    fwrite(STDERR, "{$failed} test(s) failed.\n");
    exit(1);
}

echo "All service contract logic tests passed.\n";
