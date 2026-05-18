<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/company_branding.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/institution_portal.php';
require_once __DIR__ . '/../helpers/institution_dashboard.php';
require_once __DIR__ . '/../helpers/urls.php';

xander_institution_portal_ensure_schema($conn);

$accountId = (int) ($_SESSION['institution_account_id'] ?? 0);
$universityId = (int) ($_SESSION['institution_university_id'] ?? 0);
$contactName = (string) ($_SESSION['institution_name'] ?? '');
$uniName = (string) ($_SESSION['institution_university_name'] ?? '');
$accountEmail = (string) ($_SESSION['institution_email'] ?? '');

$university = xander_institution_load_university_by_id($conn, $universityId);
if ($university) {
    $uniName = (string) ($university['name'] ?? $uniName);
}

$allowedTabs = ['dashboard', 'scholarships', 'programs', 'applications', 'website', 'profile'];
$activeTab = (string) ($_GET['tab'] ?? 'dashboard');
if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = 'dashboard';
}
$activeSection = trim((string) ($_GET['section'] ?? ''));

$flash = '';
$flashType = 'success';
if (!empty($_SESSION['institution_flash'])) {
    $flash = (string) ($_SESSION['institution_flash']['message'] ?? '');
    $flashType = (string) ($_SESSION['institution_flash']['type'] ?? 'success');
    unset($_SESSION['institution_flash']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && pcvc_csrf_validate_post()) {
    $action = (string) ($_POST['action'] ?? '');
    $redirectTab = $activeTab;
    $redirectSection = $activeSection;

    if ($action === 'update_account') {
        $redirectTab = 'profile';
        $upd = xander_institution_update_account_profile($conn, $accountId, $_POST);
        $flash = $upd['message'];
        $flashType = $upd['ok'] ? 'success' : 'danger';
        if ($upd['ok']) {
            $_SESSION['institution_name'] = trim((string) ($_POST['contact_name'] ?? ''));
            $_SESSION['institution_email'] = xander_institution_email_norm((string) ($_POST['email'] ?? ''));
        }
    } elseif ($action === 'change_password') {
        $redirectTab = 'profile';
        $pw = xander_institution_change_account_password(
            $conn,
            $accountId,
            (string) ($_POST['current_password'] ?? ''),
            (string) ($_POST['new_password'] ?? ''),
            (string) ($_POST['new_password_confirm'] ?? '')
        );
        $flash = $pw['message'];
        $flashType = $pw['ok'] ? 'success' : 'danger';
    } elseif ($action === 'save_scholarship') {
        $redirectTab = 'scholarships';
        $brochure = isset($_FILES['brochure']) ? $_FILES['brochure'] : null;
        $save = xander_institution_save_scholarship($conn, $universityId, $_POST, $brochure);
        $flash = $save['message'];
        $flashType = $save['ok'] ? 'success' : 'danger';
        if ($save['ok'] && !empty($save['id'])) {
            $redirectSection = '';
        }
    } elseif ($action === 'delete_scholarship') {
        $redirectTab = 'scholarships';
        $sid = (int) ($_POST['scholarship_id'] ?? 0);
        $flash = xander_institution_delete_scholarship($conn, $sid, $universityId)
            ? 'Scholarship deleted.'
            : 'Could not delete scholarship.';
        $flashType = $flash === 'Scholarship deleted.' ? 'success' : 'danger';
    } elseif ($action === 'save_program') {
        $redirectTab = 'programs';
        $save = xander_institution_save_program($conn, $universityId, $_POST);
        $flash = $save['message'];
        $flashType = $save['ok'] ? 'success' : 'danger';
        $redirectSection = $save['ok'] ? '' : 'create';
    } elseif ($action === 'delete_program') {
        $redirectTab = 'programs';
        $pid = (int) ($_POST['program_id'] ?? 0);
        $flash = xander_institution_delete_program($conn, $pid, $universityId)
            ? 'Program deleted.'
            : 'Could not delete program.';
        $flashType = $flash === 'Program deleted.' ? 'success' : 'danger';
    } elseif ($action === 'update_application') {
        $redirectTab = 'applications';
        $aid = (int) ($_POST['application_id'] ?? 0);
        $upd = xander_institution_update_application($conn, $aid, $universityId, $_POST);
        $flash = $upd['message'];
        $flashType = $upd['ok'] ? 'success' : 'danger';
        $redirectSection = $activeSection;
    } elseif ($action === 'save_institution_profile') {
        $redirectTab = 'website';
        $save = xander_institution_save_profile($conn, $universityId, $_POST);
        $flash = $save['message'];
        $flashType = $save['ok'] ? 'success' : 'danger';
    }

    $_SESSION['institution_flash'] = ['message' => $flash, 'type' => $flashType];
    $qs = 'tab=' . urlencode($redirectTab);
    if ($redirectSection !== '') {
        $qs .= '&section=' . urlencode($redirectSection);
    }
    if ($action === 'update_application' && !empty($_POST['application_id'])) {
        $qs .= '&id=' . (int) $_POST['application_id'];
    }
    header('Location: index.php?' . $qs);
    exit;
}

$account = xander_institution_load_account($conn, $accountId) ?? [];
if ($account) {
    $contactName = trim((string) ($account['contact_name'] ?? $contactName));
    $accountEmail = xander_institution_email_norm((string) ($account['email'] ?? $accountEmail));
}

$userInitials = xander_institution_initials($contactName);
$overallPct = 0;
$stats = xander_institution_full_dashboard_stats($conn, $universityId);
$activity = xander_institution_recent_activity($conn, $universityId);
$typeLabels = xander_institution_program_type_labels();
$statusLabels = xander_institution_application_status_labels();

$scholarships = [];
$editScholarship = null;
$programs = [];
$editProgram = null;
$applications = [];
$viewApp = null;

if ($activeTab === 'scholarships') {
    if ($activeSection === 'create') {
        $editScholarship = [];
    } elseif ($activeSection === 'edit') {
        $editScholarship = xander_institution_load_scholarship($conn, (int) ($_GET['id'] ?? 0), $universityId);
        if (!$editScholarship) {
            $activeSection = '';
        }
    } elseif (in_array($activeSection, ['active', 'draft', 'expired'], true)) {
        $scholarships = xander_institution_list_scholarships($conn, $universityId, $activeSection);
    } else {
        $scholarships = xander_institution_list_scholarships($conn, $universityId);
    }
}

if ($activeTab === 'programs') {
    if ($activeSection === 'create') {
        $editProgram = [];
    } elseif ($activeSection === 'edit') {
        $editProgram = xander_institution_load_program($conn, (int) ($_GET['id'] ?? 0), $universityId);
        if (!$editProgram) {
            $activeSection = '';
        }
    } else {
        $filterType = in_array($activeSection, array_keys($typeLabels), true) ? $activeSection : null;
        $programs = xander_institution_list_programs($conn, $universityId, $filterType);
    }
}

if ($activeTab === 'applications') {
    $viewId = (int) ($_GET['id'] ?? 0);
    $viewAppDocs = [];
    if ($viewId > 0) {
        $viewApp = xander_institution_load_application($conn, $viewId, $universityId);
        if ($viewApp) {
            $viewAppDocs = xander_institution_list_application_documents($conn, $viewId, $universityId);
        }
    }
    $statusFilter = in_array($activeSection, array_keys($statusLabels), true) ? $activeSection : null;
    if (!$viewApp) {
        $applications = xander_institution_list_applications($conn, $universityId, $statusFilter);
    }
}

$instProfile = xander_institution_load_profile($conn, $universityId);

require __DIR__ . '/views/layout_top.php';

if ($activeTab === 'dashboard') {
    require __DIR__ . '/views/dashboard.php';
} elseif ($activeTab === 'scholarships') {
    require __DIR__ . '/views/scholarships.php';
} elseif ($activeTab === 'programs') {
    require __DIR__ . '/views/programs.php';
} elseif ($activeTab === 'applications') {
    require __DIR__ . '/views/applications.php';
} elseif ($activeTab === 'website') {
    require __DIR__ . '/views/website.php';
} else {
    require __DIR__ . '/views/profile.php';
}

require __DIR__ . '/views/layout_bottom.php';
