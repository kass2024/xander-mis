<?php
declare(strict_types=1);

/**
 * Canonical fee packages from contracts/HEERA-Xander CLIENT CONTRACT-MAY 2026.pdf (Section 5).
 * Order matches the contract: Study → Visit → Credit → Asia → Job Seeker.
 * contract_code p501–p516 maps to fee_packages.id 1–16 (same order as contract).
 */
function xander_contract_fee_catalog(): array
{
    $sym = '€';
    $mk = static function (
        string $contractCode,
        int $packageId,
        string $dbCode,
        string $section,
        string $num,
        string $title,
        float $total,
        array $items
    ) use ($sym): array {
        $lines = [];
        foreach ($items as $item) {
            $amt = number_format((float) $item['amount'], 0, '.', ',');
            $lines[] = $sym . $amt . ' – ' . $item['name'];
        }
        $totalFmt = $sym . number_format($total, 0, '.', ',');
        return [
            'contract_code' => $contractCode,
            'package_id'    => $packageId,
            'db_code'       => $dbCode,
            'section'       => $section,
            'num'           => $num,
            'title'         => $title,
            'currency'      => 'EUR',
            'total'         => $total,
            'total_fmt'     => $totalFmt,
            'label'         => "{$num} {$title} – {$totalFmt}",
            'lines'         => $lines,
            'items'         => $items,
        ];
    };

    return [
        $mk('p501', 1, 'EU-ST-01', 'study', '5.1', 'USA & Canada (Without Loan)', 1500, [
            ['name' => 'Pre-admission', 'amount' => 350, 'payable_stage' => 'Pre-Admission'],
            ['name' => 'After visa approval', 'amount' => 1150, 'payable_stage' => 'Visa Approval'],
        ]),
        $mk('p502', 2, 'EU-ST-02', 'study', '5.2', 'Education Loan Processing (USA & Canada)', 1500, [
            ['name' => 'Pre-admission', 'amount' => 750, 'payable_stage' => 'Pre-Admission'],
            ['name' => 'After visa approval', 'amount' => 750, 'payable_stage' => 'Visa Approval'],
        ]),
        $mk('p503', 3, 'EU-ST-03', 'study', '5.3', 'Europe Study', 1500, [
            ['name' => 'Pre-admission', 'amount' => 350, 'payable_stage' => 'Pre-Admission'],
            ['name' => 'After visa approval', 'amount' => 1150, 'payable_stage' => 'Visa Approval'],
        ]),
        $mk('p504', 4, 'EU-ST-010', 'study', '5.4', 'Europe Study Full scholarships', 1500, [
            ['name' => 'Pre-admission', 'amount' => 600, 'payable_stage' => 'Pre-Admission'],
            ['name' => 'After visa approval', 'amount' => 900, 'payable_stage' => 'Visa Approval'],
        ]),
        $mk('p505', 5, 'EU-ST-04', 'study', '5.5', 'High School Placement (USA, Canada & Europe)', 4000, [
            ['name' => 'Pre-admission', 'amount' => 2500, 'payable_stage' => 'Pre-Admission'],
            ['name' => 'After visa approval', 'amount' => 1500, 'payable_stage' => 'Visa Approval'],
        ]),
        $mk('p506', 6, 'EU-ST-05', 'study', '5.6', 'South Korea and China Study', 2150, [
            ['name' => 'Pre-admission', 'amount' => 1000, 'payable_stage' => 'Pre-Admission'],
            ['name' => 'After visa approval', 'amount' => 1150, 'payable_stage' => 'Visa Approval'],
        ]),
        $mk('p507', 7, 'EU-VV-01', 'visit', '5.7', 'USA & Canada Visit Visa', 4000, [
            ['name' => 'Pre-admission', 'amount' => 2600, 'payable_stage' => 'Pre-Admission'],
            ['name' => 'After visa approval', 'amount' => 1400, 'payable_stage' => 'Visa Approval'],
        ]),
        $mk('p508', 8, 'EU-VV-02', 'visit', '5.8', 'Europe Visit Visa', 2500, [
            ['name' => 'Pre-admission', 'amount' => 1625, 'payable_stage' => 'Pre-Admission'],
            ['name' => 'After visa approval', 'amount' => 875, 'payable_stage' => 'Visa Approval'],
        ]),
        $mk('p509', 9, 'EU-CT-01', 'credit', '5.9', "Bachelor's Degree", 1500, [
            ['name' => 'Pre-admission', 'amount' => 750, 'payable_stage' => 'Pre-Admission'],
            ['name' => 'After visa approval', 'amount' => 750, 'payable_stage' => 'Visa Approval'],
        ]),
        $mk('p510', 10, 'EU-CT-02', 'credit', '5.10', "Master's Degree", 1700, [
            ['name' => 'Pre-admission', 'amount' => 850, 'payable_stage' => 'Pre-Admission'],
            ['name' => 'After visa approval', 'amount' => 850, 'payable_stage' => 'Visa Approval'],
        ]),
        $mk('p511', 11, 'EU-CT-03', 'credit', '5.11', 'PhD Level', 2400, [
            ['name' => 'Pre-admission', 'amount' => 1200, 'payable_stage' => 'Pre-Admission'],
            ['name' => 'After visa approval', 'amount' => 1200, 'payable_stage' => 'Visa Approval'],
        ]),
        $mk('p512', 12, 'EU-AS-01', 'asia', '5.12', 'Documentation Support Only', 1200, [
            ['name' => 'Pre-admission', 'amount' => 600, 'payable_stage' => 'Pre-Admission'],
            ['name' => 'After visa approval', 'amount' => 600, 'payable_stage' => 'Visa Approval'],
        ]),
        $mk('p513', 13, 'EU-AS-02', 'asia', '5.13', 'Application Processing Only', 800, [
            ['name' => 'Pre-admission', 'amount' => 400, 'payable_stage' => 'Pre-Admission'],
            ['name' => 'After visa approval', 'amount' => 400, 'payable_stage' => 'Visa Approval'],
        ]),
        $mk('p514', 14, 'EU-AS-03', 'asia', '5.14', 'Full Service Package', 2000, [
            ['name' => 'Pre-admission', 'amount' => 1000, 'payable_stage' => 'Pre-Admission'],
            ['name' => 'After visa approval', 'amount' => 1000, 'payable_stage' => 'Visa Approval'],
        ]),
        $mk('p515', 15, 'EU-JS-01', 'job', '5.15', 'Expedited Processing (1-3 month)', 2500, [
            ['name' => 'Before application', 'amount' => 1250, 'payable_stage' => 'Before Application'],
            ['name' => 'After visa approval', 'amount' => 1250, 'payable_stage' => 'After Visa Approval'],
        ]),
        $mk('p516', 16, 'EU-JS-02', 'job', '5.16', 'Standard Processing (2-7 months)', 1500, [
            ['name' => 'Before application', 'amount' => 750, 'payable_stage' => 'Before Application'],
            ['name' => 'Before embassy appointment', 'amount' => 750, 'payable_stage' => 'Before Embassy Appointment'],
        ]),
    ];
}
