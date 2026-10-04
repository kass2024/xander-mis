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
        array $items,
        array $profile = []
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
            'profile'       => $profile,
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

    $pay = static function (string $name, float $amount, string $note = ''): array {
        return ['name' => $name, 'amount' => $amount, 'payable_stage' => 'Installment', 'note' => $note];
    };
    $facts = static function (string $roles, string $processing, string $salary, string $requirements = '', string $note = ''): array {
        return [
            'roles' => $roles,
            'processing' => $processing,
            'salary' => $salary,
            'requirements' => $requirements,
            'note' => $note,
        ];
    };
    $albania = $facts('Plumbing, electrical, masonry and construction', '~4 months', '€870/month');
    $spain = $facts('Warehouse, factory, order picking and car parts', '~3 months', '€870/month');
    $portugal = $facts('Warehouse, housekeeping, food factory and kitchen', '~3 months', '€870/month');
    $italy = $facts('Agriculture, delivery, warehouse and logistics', '~3–4 months', '€1,300–€1,500/month');
    $germany = $facts('Warehouse, factory, production and logistics', '~2 months', '€2,200/month');
    $netherlands = $facts('Healthcare, care assistant and home care', '~4 months', '€1,800/month');
    $belgium = $facts('Manufacturing, loading, warehouse and packing', '~4 months', '€2,200/month');
    $hungary = $facts('Warehouse, factory, automotive and food production', '60–90 days', '€850/month');
    $croatia = $facts('Construction, warehouse, hospitality and production', '~3 months', '€1,100–€1,250/month');
    $slovakia = $facts('Automotive, warehouse, factory and packaging', '60–90 days', '€900/month');
    $poland = $facts('General and unskilled work', '~3–4 months', '€1,000/month', 'Visa D. 12- or 18-month work permit. Accommodation may be deducted from salary.');
    $czech = $facts('Seasonal and unskilled work', '~2–3 months', '€830/month', 'Seasonal programs of 3, 6 or 9 months, and a two-year resident-card option. Accommodation may be deducted from salary.');
    $serbia = $facts('Warehouse, production, construction and factory', '45–60 days', '€940/month');
    $romania = $facts('Warehouse, factory, tailoring, food and packaging', '60–90 days', '€700/month');
    $bulgaria = $facts('Hotel, kitchen, housekeeping and gardening', '~2–3 months', '€750–€1,400/month', 'Visa D. Employment contract approximately six months. Salary depends on position and working hours.');
    $belarus = $facts('Warehouse, factory, agriculture and food production', '60–90 days', '€700/month');
    $finland = $facts('Manufacturing and production', '~3–4 months', '€1,650–€1,900/month');
    $malta = $facts('Hospitality and cleaning', '~3–4 months', '€1,250–€1,300/month');
    $cyprus = $facts('Construction', '~3–4 months', '€1,300–€1,400/month');
    $greece = $facts('Construction and agriculture', '~3–4 months', '€1,000–€1,200/month');
    $estonia = $facts('Factory and warehouse', '~3–4 months', '€1,200–€1,400/month');
    $luxembourg = $facts('Construction and industrial work', '~3–4 months', '€2,400–€2,650/month');
    $anzJobs = 'Engineering, IT, healthcare, construction, hospitality, trades, manufacturing, agriculture and logistics';
    $canadaJobs = $facts(
        'Farming, caregiving, cooking, construction, truck driving, welding, mechanics, housekeeping, IT and healthcare',
        '~6 months',
        'Depends on employer',
        'Generally under 50. Fluent French required. B1+ TCF/TEF may be optional for some jobs. English is an advantage.'
    );
    $russiaBase = 'Eligible age 18–55. Passport, CV, and white-background photo.';

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

        // —— Job Seeker (fees: 18 Sept 2026; jobs: 17 Sept 2026) ——
        $mk('p546', 'EU-JS-AL-PL', 'job', 'Albania — Pay Later', 3300, [
            $pay('Before work permit', 0),
            $pay('After visa', 1000),
            $pay('Within three months of starting work', 2300),
        ], $albania),
        $mk('p547', 'EU-JS-AL-ST', 'job', 'Albania — Standard', 2800, [
            $pay('Before work permit', 800),
            $pay('After work permit', 1000),
            $pay('After visa', 1000),
        ], $albania),
        $mk('p533', 'EU-JS-ES-CR', 'job', 'Spain — Credit', 4000, [
            $pay('Before work permit', 650),
            $pay('Within six months of starting work', 3350),
        ], $spain),
        $mk('p531', 'EU-JS-ES-01', 'job', 'Spain — Standard', 2500, [
            $pay('Before work permit', 1000),
            $pay('After work permit', 750),
            $pay('After visa', 750),
        ], $spain),
        $mk('p545', 'EU-JS-PT-CR', 'job', 'Portugal — Credit', 4000, [
            $pay('Before work permit', 650),
            $pay('Within six months of starting work', 3350),
        ], $portugal),
        $mk('p532', 'EU-JS-PT-01', 'job', 'Portugal — Standard', 2600, [
            $pay('Before work permit', 750),
            $pay('After work permit', 1000),
            $pay('After visa', 850),
        ], $portugal),
        $mk('p568', 'EU-JS-IT-CR', 'job', 'Italy — Credit', 4000, [
            $pay('Before work permit', 2000),
            $pay('After visa or within three months', 2000),
        ], $italy),
        $mk('p535', 'EU-JS-IT-01', 'job', 'Italy — Standard', 3600, [
            $pay('Before work permit', 1100),
            $pay('After work permit', 1200),
            $pay('After visa', 1300),
        ], $italy),
        $mk('p561', 'EU-JS-IT-NEW', 'job', 'Italy — New Offer', 7000, [
            $pay('Before work permit', 1000),
            $pay('Second payment', 1000),
            $pay('After visa', 5000),
        ], $italy),
        $mk('p548', 'EU-JS-NL-CR', 'job', 'Netherlands — Credit', 3300, [
            $pay('Before work permit', 1000),
            $pay('Within three months of starting work', 2300),
        ], $netherlands),
        $mk('p549', 'EU-JS-NL-ST', 'job', 'Netherlands — Standard', 3000, [
            $pay('Before work permit', 1200),
            $pay('After work permit', 1000),
            $pay('After visa', 800),
        ], $netherlands),
        $mk('p550', 'EU-JS-BE-CR', 'job', 'Belgium — Credit', 4500, [
            $pay('Before work permit', 2250),
            $pay('Within three months of starting work', 2250),
        ], $belgium),
        $mk('p551', 'EU-JS-BE-ST', 'job', 'Belgium — Standard', 4000, [
            $pay('Before work permit', 1000),
            $pay('After work permit', 2000),
            $pay('After visa', 1000),
        ], $belgium),
        $mk('p536', 'EU-JS-DE-01', 'job', 'Germany — Standard', 3100, [
            $pay('Before work permit', 1100),
            $pay('After work permit', 1000),
            $pay('After visa', 1000),
        ], $germany),
        $mk('p552', 'EU-JS-DE-CK', 'job', 'Germany — Chancenkarte', 2150, [
            $pay('Initial payment', 650),
            $pay('After visa approval', 1500, 'Blocked-account funds are separate'),
        ], $facts('Germany Opportunity Card (Chancenkarte)', '', '', 'Blocked-account funds are separate from the service fee.')),
        $mk('p534', 'EU-JS-HU-01', 'job', 'Hungary', 2500, [
            $pay('Before work permit', 1000),
            $pay('After work permit', 750),
            $pay('After visa', 750),
        ], $hungary),
        $mk('p553', 'EU-JS-HR-ST', 'job', 'Croatia — Standard', 2350, [
            $pay('Before work permit', 750),
            $pay('After work permit', 800),
            $pay('Final payment', 800),
        ], $croatia),
        $mk('p557', 'EU-JS-HR-CR', 'job', 'Croatia — Credit', 6000, [
            $pay('Before work permit', 1000),
            $pay('Second payment', 1000),
            $pay('After six months of working', 4000),
        ], $croatia),
        $mk('p537', 'EU-JS-SK-01', 'job', 'Slovakia', 2500, [
            $pay('Before work permit', 800),
            $pay('After work permit', 850),
            $pay('Final payment', 850),
        ], $slovakia),
        $mk('p538', 'EU-JS-PL-01', 'job', 'Poland', 2100, [
            $pay('Before work permit', 1000),
            $pay('After work permit', 700),
            $pay('After visa', 400),
        ], $poland),
        $mk('p539', 'EU-JS-CZ-01', 'job', 'Czech Republic', 1200, [
            $pay('Before work permit', 750),
            $pay('After work permit', 450),
            $pay('After visa', 0),
        ], $czech),
        $mk('p540', 'EU-JS-RS-01', 'job', 'Serbia', 2150, [
            $pay('Before work permit', 750),
            $pay('After work permit', 800),
            $pay('Final payment', 600),
        ], $serbia),
        $mk('p541', 'EU-JS-RO-01', 'job', 'Romania', 2100, [
            $pay('Before work permit', 1000),
            $pay('After work permit', 700),
            $pay('After visa', 400),
        ], $romania),
        $mk('p542', 'EU-JS-BG-01', 'job', 'Bulgaria', 3500, [
            $pay('Before work permit', 1500),
            $pay('After work permit', 1000),
            $pay('After visa', 1000),
        ], $bulgaria),
        $mk('p543', 'EU-JS-BY-01', 'job', 'Belarus', 3500, [
            $pay('Before work permit', 1500),
            $pay('After work permit', 1000),
            $pay('After visa', 1000),
        ], $belarus),
        $mk('p554', 'EU-JS-FI-01', 'job', 'Finland', 6500, [
            $pay('Before work permit', 1500),
            $pay('Second payment', 1000),
            $pay('After visa', 4000),
        ], $finland),
        $mk('p555', 'EU-JS-MT-01', 'job', 'Malta', 7000, [
            $pay('Before work permit', 1000),
            $pay('Second payment', 1000),
            $pay('After visa', 5000),
        ], $malta),
        $mk('p556', 'EU-JS-CY-01', 'job', 'Cyprus', 5500, [
            $pay('Before work permit', 1000),
            $pay('Second payment', 1000),
            $pay('After six months of working', 3500),
        ], $cyprus),
        $mk('p558', 'EU-JS-GR-01', 'job', 'Greece', 7000, [
            $pay('Before work permit', 1000),
            $pay('Second payment', 1000),
            $pay('After visa', 5000),
        ], $greece),
        $mk('p559', 'EU-JS-EE-01', 'job', 'Estonia', 5500, [
            $pay('Before work permit', 1000),
            $pay('Second payment', 1000),
            $pay('After six months of working', 3500),
        ], $estonia),
        $mk('p560', 'EU-JS-LU-01', 'job', 'Luxembourg', 7500, [
            $pay('Before work permit', 1000),
            $pay('Second payment', 1000),
            $pay('After visa', 5500),
        ], $luxembourg),
        $mk('p562', 'EU-JS-ANZ-01', 'job', 'Australia and New Zealand', 2000, [
            $pay('Start application', 500),
            $pay('During processing', 700),
            $pay('Before visa processing', 800),
        ], $facts(
            $anzJobs,
            '~3–4 months',
            'From USD $23/hour',
            'Australia: bachelor’s degree or a two-to-three-year university diploma; maximum age 45; IELTS or PTE may be required. New Zealand: skilled or semi-skilled experience; maximum age 45; IELTS or PTE may be required.'
        )),
        $mk('p563', 'EU-JS-CA-A', 'job', 'Canada — French Program A', 7000, [
            $pay('Before recruitment', 2000),
            $pay('Work permit and medical stage', 2000),
            $pay('After visa approval', 3000),
        ], $facts($canadaJobs['roles'], $canadaJobs['processing'], $canadaJobs['salary'], $canadaJobs['requirements'], 'No English video is required. Submit the required documents.')),
        $mk('p564', 'EU-JS-CA-B', 'job', 'Canada — French Program B', 7000, [
            $pay('Before recruitment', 850),
            $pay('Work permit and medical stage', 2650),
            $pay('After visa approval', 3500),
        ], $facts($canadaJobs['roles'], $canadaJobs['processing'], $canadaJobs['salary'], $canadaJobs['requirements'], 'French and English are mandatory. Record a one-minute English video about your CV.')),
        $mk('p565', 'EU-JS-RU-ST', 'job', 'Russia — Standard', 950, [
            $pay('Before work permit', 400),
            $pay('After visa approval', 550),
        ], $facts('Airport, warehouse, industrial and construction work', '~2 months', 'Up to USD $2,500/month', $russiaBase, 'Men’s and women’s standard plan.')),
        $mk('p566', 'EU-JS-RU-CR', 'job', 'Russia — Men’s Credit', 2800, [
            $pay('Before work permit', 0),
            $pay('After visa approval', 1000),
            $pay('After starting work', 1800, '€600 monthly for three months'),
        ], $facts('Airport, warehouse, industrial and construction work', '~2 months', 'Up to USD $2,500/month', $russiaBase, 'Men’s credit plan.')),
        $mk('p567', 'EU-JS-RU-BR', 'job', 'Russia — Burundi and RDC girls', 2000, [
            $pay('Before work permit', 1000),
            $pay('After visa approval', 1000),
        ], $facts('Airport, warehouse, industrial and construction work', '~2 months', 'Up to USD $2,500/month', $russiaBase, 'For Burundi and RDC girls only.')),
        $mk('p544', 'EU-JS-EXP-01', 'job', 'Expedited / Express Processing (1–4 Months)', 250, [
            ['name' => 'Additional fee', 'amount' => 250, 'payable_stage' => 'Pre-Admission', 'note' => 'Payable upfront · Non-refundable'],
        ]),
    ];
}
