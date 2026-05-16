<?php
declare(strict_types=1);

/* =====================================================
   SESSION & AUTH
===================================================== */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../helpers/role.php';

$loggedInAdminId = (int) ($_SESSION['admin_id'] ?? $_SESSION['id'] ?? 0);
$loggedInRole    = (string) ($_SESSION['role'] ?? '');

if ($loggedInAdminId <= 0) {
    header('Location: ../admin-login.php');
    exit;
}

$isSuperAdmin = pcvc_is_superadmin_role($loggedInRole);
$isStaffOnly  = !$isSuperAdmin && strtolower(trim($loggedInRole)) === 'staff';

if (!$isSuperAdmin && !$isStaffOnly) {
    pcvc_staff_card_render_page(
        [],
        false,
        false,
        $loggedInAdminId,
        'Access denied. Only superadmin or staff accounts can generate cards.',
        'danger'
    );
    exit;
}

/* =====================================================
   BOOTSTRAP
===================================================== */
require_once __DIR__ . '/../db.php';

/**
 * @return list<string>
 */
function pcvc_staff_card_environment_errors(): array
{
    $errors = [];

    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!is_file($autoload)) {
        $errors[] = 'PDF library is missing. Upload the vendor folder (Composer) to the site root, or run composer install on the server.';
    }

    $templates = [
        __DIR__ . '/templates/PARROT-STAFF CARD TEMPLATE.pdf',
        __DIR__ . '/templates/XANDER-STAFF CARD TEMPLATE.pdf',
        __DIR__ . '/templates/staff-card-template.pdf',
    ];
    $found = false;
    foreach ($templates as $path) {
        if (is_file($path)) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        $errors[] = 'Staff card PDF template not found. Upload a template to cards/templates/ (e.g. staff-card-template.pdf).';
    }

    if (!extension_loaded('gd')) {
        $errors[] = 'PHP GD extension is not enabled (required for profile photos on cards).';
    }

    return $errors;
}

function pcvc_staff_card_resolve_template(): ?string
{
    foreach (
        [
            __DIR__ . '/templates/PARROT-STAFF CARD TEMPLATE.pdf',
            __DIR__ . '/templates/XANDER-STAFF CARD TEMPLATE.pdf',
            __DIR__ . '/templates/staff-card-template.pdf',
        ] as $path
    ) {
        if (is_file($path)) {
            return $path;
        }
    }

    return null;
}

/**
 * @param list<array{id:int,full_name:string,role:string}> $staffRows
 */
function pcvc_staff_card_render_page(
    array $staffRows,
    bool $isSuperAdmin,
    bool $isStaffOnly,
    int $loggedInAdminId,
    ?string $flashMessage = null,
    string $flashType = 'warning'
): void {
    $envErrors = pcvc_staff_card_environment_errors();
    $errorParam = isset($_GET['error']) ? trim((string) $_GET['error']) : '';
    if ($errorParam !== '') {
        $flashMessage = $errorParam;
        $flashType = 'danger';
    }

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Generate Staff Cards</title>
        <style>
            body { font-family: Arial, sans-serif; background:#f5f6fa; margin:0; padding:20px; }
            .box {
                max-width: 680px;
                margin: 20px auto;
                background: #fff;
                padding: 25px;
                border-radius: 8px;
                box-shadow: 0 10px 25px rgba(0,0,0,.08);
            }
            h2 { margin: 0 0 8px; color: #012F6B; }
            .hint { color:#555; margin-bottom:16px; }
            .list { max-height: 320px; overflow-y:auto; border:1px solid #ddd; padding:10px; border-radius:6px; }
            label.row { display:block; padding:6px 0; cursor:pointer; }
            .actions { margin-top:20px; text-align:center; }
            button.primary {
                background:#c00; color:#fff; border:none;
                padding:12px 24px; border-radius:6px; cursor:pointer; font-size:15px; font-weight:600;
            }
            button.primary:disabled { opacity:.55; cursor:not-allowed; }
            .alert { padding:12px 14px; border-radius:6px; margin-bottom:14px; font-size:14px; }
            .alert-danger { background:#fde8e8; color:#9b1c1c; border:1px solid #f5c2c2; }
            .alert-warning { background:#fff8e6; color:#7a5d00; border:1px solid #ffe08a; }
            .alert-success { background:#e8f7ee; color:#1e6b3a; border:1px solid #b8e6c8; }
            .env-list { margin:8px 0 0; padding-left:18px; }
            #errorModal {
                display:none; position:fixed; inset:0; background:rgba(0,0,0,.45);
                align-items:center; justify-content:center; z-index:9999; padding:16px;
            }
            #errorModal.open { display:flex; }
            #errorModal .modal-box {
                background:#fff; max-width:480px; width:100%; border-radius:10px;
                padding:22px; box-shadow:0 16px 40px rgba(0,0,0,.2);
            }
            #errorModal h3 { margin:0 0 10px; color:#9b1c1c; }
            #errorModal p { margin:0 0 16px; line-height:1.5; color:#333; white-space:pre-wrap; }
            #errorModal button { background:#012F6B; color:#fff; border:none; padding:10px 18px; border-radius:6px; cursor:pointer; }
        </style>
    </head>
    <body>
        <div class="box">
            <h2>Generate Staff Cards</h2>
            <p class="hint">Select staff members, then click Generate Cards. The PDF opens in a new browser tab.</p>

            <?php if ($flashMessage): ?>
            <div class="alert alert-<?= htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8') ?>
            </div>
            <?php endif; ?>

            <?php if ($envErrors): ?>
            <div class="alert alert-danger">
                <strong>Setup required before cards can be generated:</strong>
                <ul class="env-list">
                    <?php foreach ($envErrors as $err): ?>
                    <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if ($isSuperAdmin && $staffRows): ?>
            <form method="get" id="cardForm" action="generate_staff_card.php" target="_blank">
                <label class="row">
                    <input type="checkbox" id="selectAll" onclick="toggleAll(this)">
                    <strong>Select All</strong>
                </label>

                <div class="list">
                    <?php foreach ($staffRows as $row): ?>
                    <label class="row">
                        <input type="checkbox" class="staff-cb" name="ids[]" value="<?= (int) $row['id'] ?>">
                        <?= htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8') ?>
                        (<?= htmlspecialchars($row['role'], ENT_QUOTES, 'UTF-8') ?>)
                    </label>
                    <?php endforeach; ?>
                </div>

                <div class="actions">
                    <button type="submit" class="primary" id="generateBtn" <?= $envErrors ? 'disabled' : '' ?>>
                        Generate Cards
                    </button>
                </div>
            </form>
            <?php elseif ($isStaffOnly): ?>
            <div class="alert alert-success">You can generate your own service card below.</div>
            <div class="actions">
                <a class="primary" style="display:inline-block;text-decoration:none"
                   href="generate_staff_card.php?generate=1&amp;id=<?= $loggedInAdminId ?>"
                   target="_blank" rel="noopener">Generate My Card</a>
            </div>
            <?php else: ?>
            <div class="alert alert-warning">No staff accounts found to list.</div>
            <?php endif; ?>
        </div>

        <div id="errorModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
            <div class="modal-box">
                <h3 id="modalTitle">Cannot generate cards</h3>
                <p id="modalMessage"></p>
                <button type="button" onclick="closeErrorModal()">OK</button>
            </div>
        </div>

        <script>
        function toggleAll(source) {
            document.querySelectorAll('.staff-cb').forEach(function (cb) {
                cb.checked = source.checked;
            });
        }

        function showErrorModal(message) {
            var modal = document.getElementById('errorModal');
            var msg = document.getElementById('modalMessage');
            if (!modal || !msg) {
                alert(message);
                return;
            }
            msg.textContent = message;
            modal.classList.add('open');
        }

        function closeErrorModal() {
            var modal = document.getElementById('errorModal');
            if (modal) modal.classList.remove('open');
        }

        <?php if ($envErrors): ?>
        showErrorModal(<?= json_encode(implode("\n\n", $envErrors), JSON_UNESCAPED_UNICODE) ?>);
        <?php endif; ?>

        var form = document.getElementById('cardForm');
        if (form) {
            form.addEventListener('submit', function (e) {
                var checked = form.querySelectorAll('.staff-cb:checked');
                if (!checked.length) {
                    e.preventDefault();
                    showErrorModal('Please select at least one staff member before generating cards.');
                    return false;
                }
                <?php if ($envErrors): ?>
                e.preventDefault();
                showErrorModal(<?= json_encode(implode("\n\n", $envErrors), JSON_UNESCAPED_UNICODE) ?>);
                return false;
                <?php endif; ?>
                return true;
            });
        }
        </script>
    </body>
    </html>
    <?php
}

/* =====================================================
   RESOLVE TARGET STAFF LIST
===================================================== */
$staffIds = [];
$generateNow = isset($_GET['generate']) || isset($_GET['ids']) || isset($_GET['id']) || isset($_GET['all']);

/* Staff: own card only */
if ($isStaffOnly) {
    if ($generateNow) {
        $staffIds[] = $loggedInAdminId;
    } else {
        pcvc_staff_card_render_page([], false, true, $loggedInAdminId);
        exit;
    }
}

/* Superadmin selection */
if ($isSuperAdmin) {
    if (isset($_GET['id']) && ctype_digit((string) $_GET['id'])) {
        $staffIds[] = (int) $_GET['id'];
    } elseif (isset($_GET['ids'])) {
        if (is_array($_GET['ids'])) {
            $staffIds = array_map('intval', array_filter($_GET['ids'], 'ctype_digit'));
        } elseif (is_string($_GET['ids']) && $_GET['ids'] !== '') {
            $staffIds = array_map('intval', array_filter(array_map('trim', explode(',', $_GET['ids'])), 'ctype_digit'));
        }
    } elseif (isset($_GET['all'])) {
        $res = $conn->query("SELECT id FROM admins WHERE role IN ('staff','superadmin')");
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $staffIds[] = (int) $r['id'];
            }
        }
    }
}

/* Show picker when superadmin has not submitted a selection yet */
if ($isSuperAdmin && !$generateNow) {
    $rows = [];
    $result = $conn->query("
        SELECT id,
               COALESCE(NULLIF(TRIM(full_name), ''), TRIM(CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,''))), username) AS full_name,
               role
        FROM admins
        WHERE role IN ('staff','superadmin')
           OR REPLACE(REPLACE(REPLACE(LOWER(TRIM(COALESCE(role,''))), ' ', ''), '_', ''), '-', '') = 'superadmin'
        ORDER BY full_name
    ");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $name = trim((string) ($row['full_name'] ?? ''));
            if ($name === '') {
                $name = 'Staff #' . (int) $row['id'];
            }
            $rows[] = [
                'id' => (int) $row['id'],
                'full_name' => $name,
                'role' => (string) ($row['role'] ?? 'staff'),
            ];
        }
    }
    pcvc_staff_card_render_page($rows, true, false, $loggedInAdminId);
    exit;
}

$staffIds = array_values(array_unique(array_filter($staffIds, static fn($id) => $id > 0)));

if ($staffIds === []) {
    pcvc_staff_card_render_page(
        [],
        $isSuperAdmin,
        $isStaffOnly,
        $loggedInAdminId,
        'No staff members were selected. Please go back and check at least one name.',
        'warning'
    );
    exit;
}

/* =====================================================
   GENERATE PDF
===================================================== */
$envErrors = pcvc_staff_card_environment_errors();
if ($envErrors !== []) {
    $msg = implode(' ', $envErrors);
    if ($isSuperAdmin) {
        header('Location: generate_staff_card.php?error=' . rawurlencode($msg));
        exit;
    }
    pcvc_staff_card_render_page([], $isSuperAdmin, $isStaffOnly, $loggedInAdminId, $msg, 'danger');
    exit;
}

$templatePath = pcvc_staff_card_resolve_template();
if ($templatePath === null) {
    $msg = 'Staff card PDF template not found on the server.';
    header('Location: generate_staff_card.php?error=' . rawurlencode($msg));
    exit;
}

try {
    require_once __DIR__ . '/../vendor/autoload.php';
} catch (Throwable $e) {
    header('Location: generate_staff_card.php?error=' . rawurlencode('Could not load PDF library: ' . $e->getMessage()));
    exit;
}

use setasign\Fpdi\Fpdi;

function pcvc_staff_card_gd_circle_crop(string $src, string $dest, int $size = 600): bool
{
    if (!is_file($src)) {
        return false;
    }
    $info = @getimagesize($src);
    if (!$info) {
        return false;
    }

    $srcImg = match ($info['mime'] ?? '') {
        'image/jpeg' => @imagecreatefromjpeg($src),
        'image/png' => @imagecreatefrompng($src),
        default => null,
    };
    if (!$srcImg) {
        return false;
    }

    $w = imagesx($srcImg);
    $h = imagesy($srcImg);
    $side = min($w, $h);
    $cropX = (int) (($w - $side) / 2);
    $cropY = max(0, (int) (($h - $side) * 0.25));

    $square = imagecreatetruecolor($size, $size);
    imagecopyresampled($square, $srcImg, 0, 0, $cropX, $cropY, $size, $size, $side, $side);

    $circle = imagecreatetruecolor($size, $size);
    imagesavealpha($circle, true);
    imagealphablending($circle, false);
    $transparent = imagecolorallocatealpha($circle, 0, 0, 0, 127);
    imagefill($circle, 0, 0, $transparent);

    $r = $size / 2;
    for ($x = 0; $x < $size; $x++) {
        for ($y = 0; $y < $size; $y++) {
            if ((($x - $r) ** 2 + ($y - $r) ** 2) <= ($r ** 2)) {
                imagesetpixel($circle, $x, $y, imagecolorat($square, $x, $y));
            }
        }
    }

    imagepng($circle, $dest);
    imagedestroy($srcImg);
    imagedestroy($square);
    imagedestroy($circle);

    return true;
}

try {
    $pdf = new Fpdi('P', 'mm', [69.85, 101.6]);
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(false);
    $pdf->setSourceFile($templatePath);
    $templateId = $pdf->importPage(1);

    $stmt = $conn->prepare("
        SELECT full_name, first_name, last_name, phone_number, position, profile_photo, role
        FROM admins
        WHERE id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        throw new RuntimeException('Database error: ' . $conn->error);
    }

    $rendered = 0;
    $skipped = [];

    foreach ($staffIds as $staffId) {
        $stmt->bind_param('i', $staffId);
        $stmt->execute();
        $staff = $stmt->get_result()->fetch_assoc();
        if (!$staff) {
            $skipped[] = "ID {$staffId} (not found)";
            continue;
        }

        $fullName = trim((string) ($staff['full_name'] ?? ''));
        if ($fullName === '') {
            $fullName = trim((string) (($staff['first_name'] ?? '') . ' ' . ($staff['last_name'] ?? '')));
        }
        $fullName = strtoupper($fullName !== '' ? $fullName : 'STAFF');
        $position = strtoupper(trim((string) ($staff['position'] ?? 'STAFF')));
        $cleanPhone = preg_replace('/[^0-9+]/', '', (string) ($staff['phone_number'] ?? '')) ?? '';

        $pdf->AddPage();
        $pdf->useTemplate($templateId, 0, 0, 69.85, 101.6);

        if (!empty($staff['profile_photo'])) {
            $src = __DIR__ . '/../uploads/' . basename((string) $staff['profile_photo']);
            $tmp = sys_get_temp_dir() . '/staff_' . $staffId . '_' . uniqid('', true) . '.png';
            if (is_file($src) && pcvc_staff_card_gd_circle_crop($src, $tmp)) {
                $pdf->Image($tmp, 19.5, 26, 30, 30);
                @unlink($tmp);
            }
        }

        $pdf->SetFillColor(200, 0, 0);
        $pdf->Rect(0, 61.5, 69.85, 7, 'F');
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY(0, 63.3);
        $pdf->Cell(69.85, 4, $fullName, 0, 0, 'C');

        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetTextColor(200, 0, 0);
        $pdf->SetXY(5, 71);
        $pdf->Cell(59.85, 5, $position, 0, 0, 'C');

        if ($cleanPhone !== '') {
            $formatted = preg_replace('/(\d)(?=(\d{3})+(?!\d))/', '$1 ', $cleanPhone);
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetXY(26, 80);
            $pdf->Cell(26, 5, $formatted, 0, 0, 'C');
        }

        $rendered++;
    }

    $stmt->close();

    if ($rendered === 0) {
        throw new RuntimeException(
            'No cards were created.'
            . ($skipped ? ' Skipped: ' . implode('; ', $skipped) : '')
        );
    }

    if (ob_get_length()) {
        ob_end_clean();
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="staff_cards.pdf"');
    $pdf->Output('I', 'staff_cards.pdf');
    exit;
} catch (Throwable $e) {
    error_log('generate_staff_card: ' . $e->getMessage());
    $msg = 'Card generation failed: ' . $e->getMessage();
    if ($isSuperAdmin) {
        header('Location: generate_staff_card.php?error=' . rawurlencode($msg));
        exit;
    }
    pcvc_staff_card_render_page([], $isSuperAdmin, $isStaffOnly, $loggedInAdminId, $msg, 'danger');
    exit;
}
