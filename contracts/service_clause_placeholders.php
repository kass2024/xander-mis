<?php
declare(strict_types=1);

/**
 * Extra service-specific legal clauses.
 *
 * The master student contract already covers education, employment, and
 * immigration services. Do not invent wording here.
 *
 * Paste counsel-approved text into the matching key. Empty strings are not
 * shown on the contract. HTML is escaped when rendered.
 *
 * @return array{study:string, work:string, visit:string}
 */
function xander_service_specific_clauses(): array
{
    return [
        'study' => '',
        'work'  => '',
        'visit' => '',
    ];
}
