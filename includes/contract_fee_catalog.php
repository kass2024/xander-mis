<?php
declare(strict_types=1);

/**
 * Canonical fee packages from Client Service Contract (Aug 2026).
 * contract_code p501+ maps to fee_packages via scripts/sync-fee-packages-from-catalog.php.
 */
function xander_contract_fee_catalog(): array
{
    $sym = '€';
    $order = 0;

    $mk = static function (
        string $contractCode,
        string $dbCode,
        string $section,
        string $title,
        float $total,
        array $items
    ) use ($sym, &$order): array {
        $order++;
        $lines = [];
        foreach ($items as $item) {
            $amt = number_format((float) $item['amount'], 0, '.', ',');
            $suffix = !empty($item['note']) ? ' (' . $item['note'] . ')' : '';
            $lines[] = $sym . $amt . ' – ' . $item['name'] . $suffix;
        }
        $totalFmt = $sym . number_format($total, 0, '.', ',');
        return [
            'contract_code' => $contractCode,
            'package_id'    => $order,
            'db_code'       => $dbCode,
            'section'       => $section,
            'title'         => $title,
            'currency'      => 'EUR',
            'total'         => $total,
            'total_fmt'     => $totalFmt,
            'label'         => "{$title} – {$totalFmt}",
            'lines'         => $lines,
            'items'         => $items,
        ];
    };

    $two = static function (string $pre, float $preAmt, string $preNote, string $post, float $postAmt, string $postNote = ''): array {
        $items = [
            ['name' => $pre, 'amount' => $preAmt, 'payable_stage' => 'Pre-Admission', 'note' => $preNote],
            ['name' => $post, 'amount' => $postAmt, 'payable_stage' => 'Visa Approval', 'note' => $postNote],
        ];
        return $items;
    };

    $three = static function (
        string $aName, float $aAmt, string $aNote,
        string $bName, float $bAmt, string $bNote,
        string $cName, float $cAmt, string $cNote
    ): array {
        return [
            ['name' => $aName, 'amount' => $aAmt, 'payable_stage' => 'Pre-Admission', 'note' => $aNote],
            ['name' => $bName, 'amount' => $bAmt, 'payable_stage' => 'Mid-Processing', 'note' => $bNote],
            ['name' => $cName, 'amount' => $cAmt, 'payable_stage' => 'Visa Approval', 'note' => $cNote],
        ];
    };

    return [
        // —— Study Services ——
        $mk('p501', 'EU-ST-01', 'study', 'Study Services (Self-Sponsored) – USA, Canada, Europe, South America, Asia, Africa & Australia', 1500, $two(
            'Pre-admission', 350, 'Non-refundable',
            'After visa approval', 1150, ''
        )),
        $mk('p502', 'EU-ST-02', 'study', 'Education Loan Processing – USA, Canada & Europe', 1500, $two(
            'Pre-admission', 750, '17% refundable if the loan is not approved',
            'After visa approval', 750, ''
        )),
        $mk('p503', 'EU-ST-03', 'study', 'Full Scholarship Package – Italy, Asia, Ireland, USA & Other Eligible Destinations', 2500, $two(
            'Pre-admission', 1250, '17% refundable',
            'After visa approval', 1250, ''
        )),
        $mk('p504', 'EU-ST-04', 'study', 'High School Placement – USA, Canada, Australia & Europe', 4000, $two(
            'Pre-admission', 2500, 'Non-refundable',
            'After visa approval', 1500, ''
        )),
        $mk('p505', 'EU-ST-05', 'study', 'Financing Your Study Application Fees', 2000, [
            ['name' => 'After visa approval', 'amount' => 2000, 'payable_stage' => 'Visa Approval', 'note' => 'Payment guarantee is mandatory'],
        ]),

        // —— Credit Transfer ——
        $mk('p506', 'EU-CT-01', 'credit', "Credit Transfer – Bachelor's Degree", 1500, $two(
            'Pre-admission', 750, 'Non-refundable',
            'After visa approval', 750, ''
        )),
        $mk('p507', 'EU-CT-02', 'credit', "Credit Transfer – Master's Degree", 1700, $two(
            'Pre-admission', 850, 'Non-refundable',
            'After visa approval', 850, ''
        )),
        $mk('p508', 'EU-CT-03', 'credit', 'Credit Transfer – PhD Level', 2400, $two(
            'Pre-admission', 1200, 'Non-refundable',
            'After visa approval', 1200, ''
        )),

        // —— Visit Visa ——
        $mk('p509', 'EU-VV-US-01', 'visit', 'USA Visit Visa – Full Package', 3500, $two(
            'Upfront', 1750, 'Non-refundable',
            'After visa approval', 1750, ''
        )),
        $mk('p510', 'EU-VV-US-02', 'visit', 'USA Visit Visa – Invitation Only', 1400, [
            ['name' => 'Upfront', 'amount' => 1400, 'payable_stage' => 'Pre-Admission', 'note' => ''],
        ]),
        $mk('p511', 'EU-VV-CA-01', 'visit', 'Canada Visit Visa – Full Package', 3200, $two(
            'Upfront', 1600, 'Non-refundable',
            'After visa approval', 1600, ''
        )),
        $mk('p512', 'EU-VV-CA-02', 'visit', 'Canada Visit Visa – Invitation Only', 1600, [
            ['name' => 'Upfront', 'amount' => 1600, 'payable_stage' => 'Pre-Admission', 'note' => ''],
        ]),
        $mk('p513', 'EU-VV-EU-01', 'visit', 'Europe Visit Visa – Full Package', 2600, $two(
            'Upfront', 1300, 'Non-refundable',
            'After visa approval', 1300, ''
        )),
        $mk('p514', 'EU-VV-EU-02', 'visit', 'Europe Visit Visa – Invitation Only', 600, [
            ['name' => 'Upfront', 'amount' => 600, 'payable_stage' => 'Pre-Admission', 'note' => 'Non-refundable'],
        ]),
        $mk('p515', 'EU-VV-UK-01', 'visit', 'United Kingdom Visit Visa – Full Package', 2500, $two(
            'Upfront', 1250, 'Non-refundable',
            'After visa approval', 1250, ''
        )),
        $mk('p516', 'EU-VV-UK-02', 'visit', 'United Kingdom Visit Visa – Invitation Only', 1000, [
            ['name' => 'Upfront', 'amount' => 1000, 'payable_stage' => 'Pre-Admission', 'note' => 'Non-refundable'],
        ]),
        $mk('p517', 'EU-VV-AU-01', 'visit', 'Australia Visit Visa (Business Invitation) – Full Package', 2500, $two(
            'Upfront', 1250, 'Non-refundable',
            'After visa approval', 1250, ''
        )),
        $mk('p518', 'EU-VV-AU-02', 'visit', 'Australia Visit Visa – Invitation Only', 600, [
            ['name' => 'Upfront', 'amount' => 600, 'payable_stage' => 'Pre-Admission', 'note' => 'Non-refundable'],
        ]),
        $mk('p519', 'EU-VV-NZ-01', 'visit', 'New Zealand Visit Visa (Business Invitation) – Full Package', 2500, $two(
            'Upfront', 1250, 'Non-refundable',
            'After visa approval', 1250, ''
        )),
        $mk('p520', 'EU-VV-NZ-02', 'visit', 'New Zealand Visit Visa – Invitation Only', 600, [
            ['name' => 'Upfront', 'amount' => 600, 'payable_stage' => 'Pre-Admission', 'note' => 'Non-refundable'],
        ]),
        $mk('p521', 'EU-VV-AS-01', 'visit', 'Asia Visit Visa – Full Package', 1500, $two(
            'Upfront', 750, 'Non-refundable',
            'After visa approval', 750, ''
        )),
        $mk('p522', 'EU-VV-AS-02', 'visit', 'Asia Visit Visa – Invitation Only', 500, [
            ['name' => 'Upfront', 'amount' => 500, 'payable_stage' => 'Pre-Admission', 'note' => 'Non-refundable'],
        ]),
        $mk('p523', 'EU-VV-AS-03', 'visit', 'Asia – Full Service Package + Accommodation', 2000, $two(
            'Before application', 1000, 'Non-refundable',
            'After visa approval', 1000, ''
        )),
        $mk('p524', 'EU-VV-AF-01', 'visit', 'Africa Visit Visa (Business Invitation) – Full Package', 1500, $two(
            'Upfront', 750, 'Non-refundable',
            'After visa approval', 750, ''
        )),
        $mk('p525', 'EU-VV-AF-02', 'visit', 'Africa Visit Visa – Invitation Only', 300, [
            ['name' => 'Upfront', 'amount' => 300, 'payable_stage' => 'Pre-Admission', 'note' => 'Non-refundable'],
        ]),
        $mk('p526', 'EU-VV-ME-01', 'visit', 'Middle East Visit Visa (Business Invitation) – Full Package', 1500, $two(
            'Upfront', 750, '',
            'After visa approval', 750, ''
        )),
        $mk('p527', 'EU-VV-ME-02', 'visit', 'Middle East Visit Visa – Invitation Only', 600, [
            ['name' => 'Upfront', 'amount' => 600, 'payable_stage' => 'Pre-Admission', 'note' => 'Non-refundable'],
        ]),
        $mk('p528', 'EU-VV-SA-01', 'visit', 'South America Visit Visa (Business Invitation) – Full Package', 2500, $two(
            'Upfront', 1250, '',
            'After visa approval', 1250, ''
        )),
        $mk('p529', 'EU-VV-SA-02', 'visit', 'South America Visit Visa – Invitation Only', 600, [
            ['name' => 'Upfront', 'amount' => 600, 'payable_stage' => 'Pre-Admission', 'note' => 'Non-refundable'],
        ]),
        $mk('p530', 'EU-VV-FIN-01', 'visit', 'Financing USA & Canada Visit Visa Application Fees', 4000, $two(
            'Upfront', 1500, 'Non-refundable',
            'After visa approval', 2500, ''
        )),

        // —— Job Seeker ——
        $mk('p531', 'EU-JS-ES-01', 'job', 'Spain (Semi-skilled Work)', 2500, $three(
            'Non-refundable', 1000, 'Non-refundable',
            '17% refundable installment', 750, '17% refundable',
            'After visa approval', 750, ''
        )),
        $mk('p532', 'EU-JS-PT-01', 'job', 'Portugal (Semi-skilled Work)', 2600, $three(
            'Non-refundable', 750, 'Non-refundable',
            '17% refundable installment', 1000, '17% refundable',
            'After visa approval', 850, ''
        )),
        $mk('p533', 'EU-JS-FIN-01', 'job', 'Financing Spain & Portugal Job Application Fees', 4000, $two(
            'Upfront', 650, 'Non-refundable',
            'After visa approval', 3350, ''
        )),
        $mk('p534', 'EU-JS-HU-01', 'job', 'Hungary (Semi-skilled Work / Assistant Agronomist)', 2500, $three(
            'Non-refundable', 1000, 'Non-refundable',
            '17% refundable installment', 750, '17% refundable',
            'After visa approval', 750, ''
        )),
        $mk('p535', 'EU-JS-IT-01', 'job', 'Italy (Agricultural Work & Others)', 3650, $three(
            'Non-refundable', 1100, 'Non-refundable',
            '17% refundable installment', 1250, '17% refundable',
            'After visa approval', 1300, ''
        )),
        $mk('p536', 'EU-JS-DE-01', 'job', 'Germany (Semi-skilled Work)', 3100, $three(
            'Non-refundable', 1100, 'Non-refundable',
            '17% refundable installment', 1000, '17% refundable',
            'After visa approval', 1000, ''
        )),
        $mk('p537', 'EU-JS-SK-01', 'job', 'Slovakia (Skilled Work)', 2350, $three(
            'Non-refundable', 750, 'Non-refundable',
            '17% refundable installment', 800, '17% refundable',
            'After visa approval', 800, ''
        )),
        $mk('p538', 'EU-JS-PL-01', 'job', 'Poland (Semi-skilled Work)', 2100, $three(
            'Non-refundable', 1000, 'Non-refundable',
            '17% refundable installment', 700, '17% refundable',
            'After visa approval', 400, ''
        )),
        $mk('p539', 'EU-JS-CZ-01', 'job', 'Czech Republic (Semi-skilled Work)', 1200, $two(
            'Non-refundable', 750, 'Non-refundable',
            '17% refundable installment', 450, '17% refundable'
        )),
        $mk('p540', 'EU-JS-RS-01', 'job', 'Serbia (Warehouse Worker / Semi-skilled Work)', 2150, $three(
            'Non-refundable', 750, 'Non-refundable',
            '17% refundable installment', 800, '17% refundable',
            'After visa approval', 600, ''
        )),
        $mk('p541', 'EU-JS-RO-01', 'job', 'Romania (Semi-skilled Work)', 2100, $three(
            'Non-refundable', 1000, 'Non-refundable',
            '17% refundable installment', 700, '17% refundable',
            'After visa approval', 400, ''
        )),
        $mk('p542', 'EU-JS-BG-01', 'job', 'Bulgaria (Hotel Staff / Kitchen / Housekeeping / Gardener Assistant)', 3500, $three(
            'Non-refundable', 1500, 'Non-refundable',
            '17% refundable installment', 1000, '17% refundable',
            'After visa approval', 1000, ''
        )),
        $mk('p543', 'EU-JS-BY-01', 'job', 'Belarus (General Worker, Production Assistant, Painter, Plasterer)', 3500, $three(
            'Non-refundable', 1500, 'Non-refundable',
            '17% refundable installment', 1000, '17% refundable',
            'After visa approval', 1000, ''
        )),
        $mk('p544', 'EU-JS-EXP-01', 'job', 'Expedited / Express Processing (1–4 Months)', 250, [
            ['name' => 'Additional fee (payable upfront)', 'amount' => 250, 'payable_stage' => 'Pre-Admission', 'note' => 'Non-refundable'],
        ]),
    ];
}
