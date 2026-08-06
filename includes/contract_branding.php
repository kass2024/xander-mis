<?php

declare(strict_types=1);



/**

 * Canonical contract signature, stamp, and letterhead asset paths.

 *

 * @return array<string, array{path:string, exists:bool, role:string, used_by:string}>

 */

function xander_contract_branding_catalog(): array

{

    $root = dirname(__DIR__);

    $stamp = xander_contract_stamp_asset_path();

    $items = [

        'student_contract_stamp' => [

            'path'     => $stamp,

            'role'     => 'Company stamp (student contract web view & PDF)',

            'used_by'  => 'student-contract.php, generate-contract-pdf.php',

        ],

        'student_contract_signature' => [

            'path'     => xander_contract_signature_asset_path(),

            'role'     => 'Authorized signatory handwriting (alongside stamp)',

            'used_by'  => 'student-contract.php, generate-contract-pdf.php',

        ],

        'pdf_employer_signature' => [

            'path'     => $root . '/admin/employer-signature.jpeg',

            'role'     => 'Company stamp for signed PDFs (legacy path)',

            'used_by'  => 'generate-contract-pdf.php, partner contracts',

        ],

        'contracts_employer_signature' => [

            'path'     => $root . '/contracts/employer-signature.jpeg',

            'role'     => 'Duplicate stamp asset (employment / legacy templates)',

            'used_by'  => 'contracts/contract_template.php',

        ],

        'letterhead' => [

            'path'     => $root . '/assets/letterhead.png',

            'role'     => 'PDF letterhead banner',

            'used_by'  => 'generate-contract-pdf.php, contracts/contract_template.php',

        ],

        'burundi_signature' => [

            'path'     => $root . '/contracts/Xander-signatture.jpeg',

            'role'     => 'Burundi contract signature image',

            'used_by'  => 'includes/burundi_contract_assets.php, student-contract-burundi.php',

        ],

        'burundi_header' => [

            'path'     => $root . '/contracts/header.png',

            'role'     => 'Burundi contract header / letterhead strip',

            'used_by'  => 'student-contract-burundi.php, generate-contract-pdf-burundi.php',

        ],

        'burundi_footer' => [

            'path'     => $root . '/contracts/footer.png',

            'role'     => 'Burundi contract footer strip',

            'used_by'  => 'student-contract-burundi.php, generate-contract-pdf-burundi.php',

        ],

    ];



    $out = [];

    foreach ($items as $key => $meta) {

        $path = str_replace('\\', '/', $meta['path']);

        $out[$key] = [

            'path'     => $path,

            'exists'   => is_file($path),

            'role'     => $meta['role'],

            'used_by'  => $meta['used_by'],

        ];

    }

    return $out;

}



/** Primary company stamp/signature image on disk. */

function xander_contract_stamp_asset_path(): string

{

    $root = dirname(__DIR__);

    $candidates = [

        $root . '/assets/signatures/xander-stamp.jpeg',

        $root . '/assets/signatures/xander-stamp.jpg',

        $root . '/admin/employer-signature.jpeg',

        $root . '/contracts/employer-signature.jpeg',

        $root . '/assets/signatures/xander-signature.png',

        $root . '/admin/employer-signature.png',

        $root . '/contracts/employer-signature.png',

    ];

    foreach ($candidates as $path) {

        if (is_file($path)) {

            return $path;

        }

    }

    return $candidates[0];

}



/** Handwritten authorized signatory image (shown alongside company stamp). */

function xander_contract_signature_asset_path(): string

{

    $root = dirname(__DIR__);

    $candidates = [

        $root . '/assets/signatures/xander-signature.jpeg',

        $root . '/assets/signatures/xander-signature.jpg',

        $root . '/admin/employer-hand-signature.jpeg',

        $root . '/contracts/employer-hand-signature.jpeg',

    ];

    foreach ($candidates as $path) {

        if (is_file($path)) {

            return $path;

        }

    }

    return $candidates[0];

}



/** Best available letterhead for student contract HTML. */

function xander_contract_letterhead_web_src(): string

{

    if (is_file(dirname(__DIR__) . '/assets/letterhead.png')) {

        return 'assets/letterhead.png';

    }

    return '';

}



/** Best available employer stamp/signature file for PDF generation. */

function xander_contract_employer_signature_path(): string

{

    return xander_contract_stamp_asset_path();

}



/** Web-relative URL/path for company stamp in HTML contract pages. */

function xander_contract_web_signature_src(): string

{

    return xander_contract_branding_web_path(xander_contract_stamp_asset_path(), 'assets/signatures/xander-stamp.jpeg');

}



/** Web-relative path for authorized handwritten signature. */

function xander_contract_hand_signature_web_src(): string

{

    return xander_contract_branding_web_path(xander_contract_signature_asset_path(), 'assets/signatures/xander-signature.jpeg');

}



function xander_contract_branding_web_path(string $absolutePath, string $fallback): string

{

    $root = dirname(__DIR__);

    if (!is_file($absolutePath)) {

        return $fallback;

    }

    return str_replace('\\', '/', substr($absolutePath, strlen($root) + 1));

}



/** Ensure stamp exists in standard locations (copy from canonical if missing). */

function xander_contract_ensure_branding_assets(): void

{

    static $done = false;

    if ($done) {

        return;

    }

    $done = true;



    $root = dirname(__DIR__);

    $source = xander_contract_stamp_asset_path();

    if (!is_file($source)) {

        return;

    }



    $targets = [

        $root . '/assets/signatures/xander-stamp.jpeg',

        $root . '/admin/employer-signature.jpeg',

        $root . '/contracts/employer-signature.jpeg',

    ];



    foreach ($targets as $target) {

        if (is_file($target)) {

            continue;

        }

        $targetDir = dirname($target);

        if (!is_dir($targetDir)) {

            @mkdir($targetDir, 0775, true);

        }

        @copy($source, $target);

    }



    $sigSource = xander_contract_signature_asset_path();

    if (!is_file($sigSource)) {

        return;

    }

    $sigTargets = [

        $root . '/assets/signatures/xander-signature.jpeg',

        $root . '/admin/employer-hand-signature.jpeg',

    ];

    foreach ($sigTargets as $target) {

        if (is_file($target)) {

            continue;

        }

        $targetDir = dirname($target);

        if (!is_dir($targetDir)) {

            @mkdir($targetDir, 0775, true);

        }

        @copy($sigSource, $target);

    }

}



function xander_contract_branding_data_uri(string $path): string

{

    if (!is_file($path)) {

        return '';

    }

    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

    $mime = match ($ext) {

        'jpg', 'jpeg' => 'image/jpeg',

        'webp' => 'image/webp',

        default => 'image/png',

    };

    return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($path));

}



/** Data URI for company stamp used in PDFs and inline HTML. */

function xander_contract_stamp_data_uri(): string

{

    return xander_contract_branding_data_uri(xander_contract_stamp_asset_path());

}



/** Data URI for authorized handwritten signature. */

function xander_contract_hand_signature_data_uri(): string

{

    return xander_contract_branding_data_uri(xander_contract_signature_asset_path());

}



/** Web path for company stamp (same image for HTML img src). */

function xander_contract_stamp_web_src(): string

{

    return xander_contract_web_signature_src();

}

