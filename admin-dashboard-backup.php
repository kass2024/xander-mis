<?php
session_start();
// Main database (e.g. student_applications)
require_once 'db.php';

// Secondary database (e.g. applications from Cyprus system)
require_once 'database.php';  // This connects to visaeofi_cyprus

$admin_id = $_SESSION['id'] ?? null;
if (!$admin_id || !isset($_SESSION['role'])) {
  header("Location: admin-login.php");
  exit;
}

$admin_id_safe = mysqli_real_escape_string($conn, $admin_id);
$result = mysqli_query($conn, "SELECT * FROM admins WHERE id = '$admin_id_safe'");
if (!$result || mysqli_num_rows($result) === 0) {
  die("Admin not found.");
}
$admin = mysqli_fetch_assoc($result);
$role = $admin['role'] ?? 'standard';
$fullName = $admin['full_name'] ?? 'Administrator';

// Flag keys and titles
$flagMap = [
  'incomplete_app' => 'Incomplete App',
  'submitted' => 'Submitted',
  'admit' => 'Admit',
  'i20_sent' => 'I-20 Sent',
  'sevis_paid' => 'Sevis Paid',
  'visa_scheduled' => 'Attended Visa Interview',
  'visa_approved' => 'Visa Approved',
  'enrolled' => 'Enrolled',
  'addn_doc' => 'Addn Doc',
  'deny' => 'Visa Denied',
  'app_start' => 'App Start',
  'appoint' => 'APPOINTMENT BOOKED'
];

// Count flags
$flagCounts = [];

$flagQuery = "
  SELECT
    SUM(incomplete_app) AS incomplete_app,
    SUM(submitted) AS submitted,
    SUM(admit) AS admit,
    SUM(i20_sent) AS i20_sent,
    SUM(sevis_paid) AS sevis_paid,
    SUM(visa_scheduled) AS visa_scheduled,
    SUM(visa_approved) AS visa_approved,
    SUM(enrolled) AS enrolled,
    SUM(addn_doc) AS addn_doc,
    SUM(deny) AS deny,
    SUM(app_start) AS app_start
  FROM (
    SELECT incomplete_app, submitted, admit, i20_sent, sevis_paid,
           visa_scheduled, visa_approved, enrolled, addn_doc, deny, app_start
    FROM student_applications

    UNION ALL

    SELECT incomplete_app, submitted, admit, i20_sent, sevis_paid,
           visa_scheduled, visa_approved, enrolled, addn_doc, deny, app_start
    FROM malta_applications

    UNION ALL

    SELECT incomplete_app, submitted, admit, i20_sent, sevis_paid,
           visa_scheduled, visa_approved, enrolled, addn_doc, deny, app_start
    FROM turkey_applications
  ) AS all_flags
";


$countRes = mysqli_query($conn, $flagQuery);
if ($countRes) {
    $flagCounts = mysqli_fetch_assoc($countRes);
} else {
    // Optional error logging
    error_log("Flag count query failed: " . mysqli_error($conn));
    $flagCounts = array_fill_keys([
        'incomplete_app', 'submitted', 'admit', 'i20_sent', 'sevis_paid',
        'visa_scheduled', 'visa_approved', 'enrolled', 'addn_doc', 'deny', 'app_start'
    ], 0);
}
// Catholic-only flag counts (student_applications only)
$catholicFlagCounts = [];

$catholicQuery = "
  SELECT
    SUM(incomplete_app) AS incomplete_app,
    SUM(submitted) AS submitted,
    SUM(admit) AS admit,
    SUM(i20_sent) AS i20_sent,
    SUM(sevis_paid) AS sevis_paid,
    SUM(visa_scheduled) AS visa_scheduled,
    SUM(visa_approved) AS visa_approved,
    SUM(enrolled) AS enrolled,
    SUM(addn_doc) AS addn_doc,
    SUM(deny) AS deny,
    SUM(app_start) AS app_start
  FROM student_applications
  WHERE university_id = 1 AND region_id = 1
";

$catholicRes = mysqli_query($conn, $catholicQuery);
if ($catholicRes) {
    $catholicFlagCounts = mysqli_fetch_assoc($catholicRes);
} else {
    error_log("Catholic flag query failed: " . mysqli_error($conn));
    $catholicFlagCounts = array_fill_keys(array_keys($flagMap), 0);
}


// Dashboard cards
$cards = [

  'all_admissions' => [
    'title' => 'All university admissions',
    'icon' => 'bi-mortarboard',
    'links' => [
      'application-list.php' => 'Student application Report',
      'students-manage.php' => 'Applicants Management',
      'receipt_viewer.php' => 'Check payment Receipt',
    ]
  ],
  'loan_applications' => [
    'title' => 'Study Loan Applications',
    'icon' => 'bi-bank',
    'links' => [
      'loan-applicants-report.php' => 'Loan Application list',
      'loan_search.php' => 'User-iD',
    ]
  ],
  'I-20_applications' => [
    'title' => 'I-20 Applications',
    'icon' => 'bi-file-earmark-text',
    'links' => [
      'form-20-report.php' => 'I-20 Applicant List',
      
    ]
  ],
  'staff_reporting' => [
    'title' => 'Staff Management',
    'icon' => 'bi-people',
    'links' => [
      'staff-management.php'=> 'Manage staff ',
      'tasks.php'=> 'Task Allocation',
       'admin/contracts-admin.php'=> 'View staffs Contracts',
      'salary-report.php'=> 'View Requested Salaries ',
      'leave-approvals.php'=> 'Manage Permissions ',
      'overtime-approvals.php'=> 'Overtime Management ',
      'jobs_report.php'=> 'Check job report ',
      'admin-payroll.php'=> 'Payroll ',
      'cards/generate_staff_card.php'=> 'Generate staff cards',
      
      
    ]
  ],
  'commission_request' => [
    'title' => 'Commission Request',
    'icon' => 'bi-cash-coin',
    'links' => [
      'Commission-Request.php' => 'Request commission',
      'commission-requests-report.php' => 'All Requests'
      
      
    ]
  ],
  'credit_transfer' => [
    'title' => 'Credit Transfer Applications',
    'icon' => 'bi-arrow-left-right',
    'links' => [
      'Credit-Transfer-report.php' => 'Transfer Requests list',
      'transfer-status.php' => 'Review Status',
      'credit-search.php' => 'credit userID'
    ]
  ],
  'visit_study_visa' => [
    'title' => 'Visit And Study Visa',
    'icon' => 'bi-globe2',
    'links' => [
      'visa-report.php' => 'Applicant List',
      'visa-status.php' => 'Visa Status'
    ]
    ],
    'staff_attendance' => [
    'title' => 'Staff Attendance',
    'icon' => 'bi-calendar-check',
    'links' => [
      'attendance-ui.php' => 'Take attendance',
      'job_todo_list.php' => 'Job Do List',
      'salary.php' => 'Salary Request',
      'admin/contract.php'=> 'Sign your contract',
      'leave-request.php'=> 'Permission Request ',
      'staff_overtime_request.php'=> 'Overtime request ',
      'my-leaves.php' =>'Check permission status ',
      'attendance-report.php' => 'Attendance Report',
      'jobs_report.php'=> 'Check job report ',
      'cards/generate_staff_card.php'=> 'Generate your service card',
      
    ]
  ],

'university_portal' => [
    'title' => 'Apply for Student',
    'icon' => 'bi-person-plus',
    'links' => [
      'student-application.php' => 'Apply Now',
      'agent-student-manage.php' => 'Manage Students',
      'userid-search.php' => 'User-id',
      
    ]
],

'marketing' => [
    'title' => 'Marketing Materials',
    'icon' => 'bi-megaphone',
    'links' => [
      'upload-materials.php' => 'Upload Marketing materials',
      'get-materials.php' => 'Get Marketing materials',
      
    ]
],
'ticketing' => [
    'title' => 'Air Ticketing Reservation',
    'icon' => 'bi-ticket-perforated',
    'links' => [
      'reservation-report.php' => 'Check Reservation',
     
      
    ]
],
'jobsabrod' => [
    'title' => 'Jobs Application',
    'icon' => 'bi-briefcase',
    'links' => [
      'job-applicant.php' => 'Check job Applicants',
     
      
    ]
],
'platform' => [
    'title' => 'Platforms management',
    'icon' => 'bi-diagram-3',
    'links' => [
      'platforms.php' => 'Platforms management',
      
      
    ]
],

'contracts' => [
    'title' => 'Student contract',
    'icon' => 'bi-file-earmark-lock',
    'links' => [
      'admin-generate-student-contract.php' => 'Issue contract link ',
      'admin-contracts.php' => 'View students Contracts',
      'admin-contracts-special.php' => 'Special students Contracts',
      
      
    ]
],
'chart' => [
    'title' => 'Live Chat Assistant',
    'icon' => 'bi-chat-dots',
    'links' => [
      'admin/chat-dashboard.php' => 'Live chat dashboard',
    
      
      
    ]
],
];
$allowedCardsByRole = [
  'superadmin' => array_merge(array_keys($cards), ['application_flag_summary','agent_report','university_portal','admin_chat','start_fish','schools','marketing','abroad']),
  'standard' => ['university_admissions', 'loan_applications', 'I-20_applications', 'application_flag_summary', 'all_admissions','agent_report','university_portal','commission_request','staff_attendance','schools','marketing'],
  'Catholic university of America' => ['university_admissions', 'application_flag_summary','schools','marketing','abroad'], // ✅ Added flag access here
  'staff' => ['staff_attendance','agent_report','university_portal','commission_request','all_admissions','loan_applications','schools','marketing','contracts'],
  'agent' => ['staff_attendance','agent_report','university_portal','commission_request','all_admissions','schools','marketing',]
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>

  <!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">


  <style>
  :root {
    --navy: #012F6B;
    --navy-2: #254D81;
    --navy-3: #002765;
    --gold: #F2A65A;
    --white: #FFFFFF;
    --page-bg: #F3F6FB;
    --surface: #F7F9FD;
    --surface-2: #F1F6FF;
    --border: #E6ECF5;
    --text: #012F6B;
    --muted: #5B6B85;
    --focus: rgba(1, 47, 107, .18);
  }

  body {
    font-family: 'Segoe UI', sans-serif;
    background: var(--page-bg);
    margin: 0;
    padding: 20px;
    color: var(--text);
  }

  .dashboard-container {
    max-width: 1480px;
    margin: auto;
  }

  .dashboard-header {
    background: linear-gradient(135deg, var(--navy-3), var(--navy-2));
    color: #fff;
    padding: 20px 30px;
    border-radius: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
  }

  .dashboard-header h2 {
    font-size: 1.5rem;
    margin: 0;
  }

  .logout-btn {
    background: #dc3545;
    color: #fff;
    border: none;
    padding: 10px 18px;
    border-radius: 6px;
    cursor: pointer;
    margin-top: 10px;
  }

  .dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
    gap: 28px;
    margin-top: 30px;
  }

  .dashboard-card {
    background: var(--surface);
    border-radius: 14px;
    box-shadow: 0 8px 22px rgba(0, 0, 0, 0.06);
    cursor: pointer;
    overflow: hidden;
    border: 3px solid var(--navy);
    min-height: 260px;
    display: flex;
    flex-direction: column;
    transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
  }

  .dashboard-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 34px rgba(0, 0, 0, 0.10);
    border-color: var(--navy-3);
  }

  .dashboard-card-header {
    position: relative;
    padding: 26px 22px;
    text-align: center;
    font-weight: bold;
    border-bottom: 1px solid var(--border);
    background: linear-gradient(135deg, var(--navy-3), var(--navy-2));
    transition: background 0.3s ease;
    font-size: 1.1rem;
    color: var(--white);
    min-height: 92px;
  }

  .dashboard-card-title {
    display: inline-flex;
    align-items: center;
    gap: 10px;
  }

  .dashboard-card-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(242, 166, 90, .22);
    border: 1px solid rgba(242, 166, 90, .65);
    color: var(--white);
    flex: 0 0 auto;
  }

  .dashboard-card-icon i {
    font-size: 1.25rem;
    line-height: 1;
  }

  .dashboard-card-header:hover {
    background: linear-gradient(135deg, var(--navy-2), var(--navy));
  }

  .arrow-icon {
    color: rgba(255,255,255,.9);
  }

  .dashboard-card-header::after { content: none; }

  .dashboard-submenu {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.4s ease, padding 0.3s ease;
    padding: 0 0;
    background: var(--surface);
  }

  .dashboard-card.open .dashboard-submenu {
    max-height: 520px;
    padding: 10px 0;
  }

  .dashboard-submenu a {
    display: block;
    padding: 14px 22px;
    text-decoration: none;
    color: var(--navy);
    transition: all 0.25s ease;
    font-size: 15.5px;
    border-left: 3px solid transparent;
  }

  .dashboard-submenu a:hover {
    background: var(--surface-2);
    transform: translateX(5px);
    border-left-color: var(--gold);
    color: var(--navy-3);
  }

  .dashboard-submenu a:focus-visible {
    outline: none;
    box-shadow: 0 0 0 .2rem var(--focus);
    border-left-color: var(--gold);
  }

  .summary-card {
    border: 3px solid var(--navy);
    border-radius: 14px;
    overflow: hidden;
    background: var(--surface);
    box-shadow: 0 8px 22px rgba(0, 0, 0, 0.06);
    min-height: 360px;
  }

  .flag-summary-card.summary-card {
    padding: 0;
    border-radius: 14px;
    box-shadow: 0 8px 22px rgba(0, 0, 0, 0.06);
  }

  .agent-tracking-card.summary-card {
    padding: 0;
    border-radius: 14px;
    box-shadow: 0 8px 22px rgba(0, 0, 0, 0.06);
  }

  .summary-card .summary-header {
    padding: 22px 24px;
    background: linear-gradient(135deg, var(--navy-3), var(--navy-2));
    color: var(--white);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
  }

  .summary-card .summary-title {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    font-weight: 700;
    font-size: 1.1rem;
    margin: 0;
  }

  .summary-card .summary-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(242, 166, 90, .22);
    border: 1px solid rgba(242, 166, 90, .65);
    color: var(--white);
    flex: 0 0 auto;
  }

  .summary-card .summary-icon i {
    font-size: 1.25rem;
    line-height: 1;
  }

  .summary-card .summary-body {
    padding: 24px;
    min-height: 260px;
  }

  .summary-card .flag-buttons {
    margin-top: 0;
  }

  .summary-card .flag-btn {
    background: var(--surface);
    color: var(--navy-3);
    border: 1px solid rgba(1, 47, 107, .20);
    box-shadow: 0 8px 18px rgba(0,0,0,.06);
    padding: 16px 18px;
    font-size: 15.5px;
    min-height: 56px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
  }

  .summary-card .flag-btn:hover {
    background: var(--surface-2);
    transform: translateY(-1px);
    border-color: rgba(1, 47, 107, .35);
  }

  .summary-card .flag-btn span {
    background: var(--gold);
    color: var(--navy-3);
    padding: 4px 10px;
    border-radius: 999px;
  }

  .flag-summary-card {
    background: var(--surface);
    margin-top: 40px;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  }

  .flag-summary-card h3 {
    margin-top: 0;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .flag-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 20px;
  }

  .flag-btn {
    padding: 8px 14px;
    background: linear-gradient(135deg, var(--navy), var(--navy-2));
    color: #fff;
    font-size: 14px;
    border: none;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    text-decoration: none;
    transition: background 0.3s ease, transform 0.2s ease;
  }

  .flag-btn:hover {
    background: linear-gradient(135deg, var(--navy-2), var(--navy-3));
    transform: scale(1.05);
  }

  .flag-btn span {
    background: #fff;
    color: var(--navy);
    padding: 2px 6px;
    border-radius: 12px;
    font-weight: bold;
    font-size: 12px;
  }

  .agent-tracking-card {
    background: var(--surface);
    margin-top: 30px;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
  }

  .agent-tracking-card h3 {
    font-size: 1.2rem;
    margin-bottom: 15px;
  }

  #agentChart {
    max-width: 100%;
    height: auto;
  }

  /* ---- NEW: toast & bell dropdown styling ---- */
  .toast { position: fixed; right: 20px; bottom: 20px; z-index: 1080; }
  .notif-item-unread { background: var(--surface-2); }
  .notif-item-read { opacity:.75; }
  /* -------------------------------------------- */

  @media(max-width: 768px) {
    body {
      padding: 10px;
    }

    .dashboard-card {
      min-height: 0;
    }

    .dashboard-header {
      flex-direction: column;
      align-items: flex-start;
      padding: 15px;
    }

    .dashboard-header h2 {
      font-size: 1.3rem;
    }

    .logout-btn {
      width: 100%;
      margin-top: 10px;
    }

    .dashboard-card-header {
      padding: 14px;
      font-size: 1rem;
      min-height: 0;
    }

    .dashboard-submenu a {
      padding: 8px 15px;
      font-size: 14px;
    }

    .flag-summary-card {
      padding: 15px;
    }

    .flag-buttons {
      gap: 10px;
    }

    .agent-tracking-card {
      padding: 15px;
    }

    #agentChart {
      height: 180px !important;
    }

    table.dataTable {
      width: 100% !important;
      font-size: 14px;
    }
  }
  .dashboard-card {
  border: 3px solid var(--navy);
  transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
  overflow: hidden;
}

.dashboard-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 14px 34px rgba(0, 0, 0, 0.10);
  border-color: var(--navy-3);
}

.dashboard-card-header {
  background: linear-gradient(135deg, var(--navy-3), var(--navy-2));
  font-size: 1.1rem;
  font-weight: 700;
  position: relative;
  cursor: pointer;
  color: var(--white);
}

.arrow-icon {
  transition: transform 0.3s ease;
}

.dashboard-card.open .arrow-icon {
  transform: rotate(180deg);
}

.dashboard-submenu a i {
  font-size: 0.85rem;
  color: var(--muted);
}
/* =====================================================
   SYSTEM SETTINGS – FINAL MODERN UI (PRODUCTION READY)
===================================================== */

/* =========================
   MODALS (GLASS + GRADIENT)
========================= */
.modal-content {
  border-radius: 18px;
  border: none;
  background: linear-gradient(180deg, var(--surface) 0%, var(--surface-2) 100%);
  box-shadow: 0 30px 60px rgba(0,0,0,.25);
  overflow: hidden;
}

.modal-header {
  background: linear-gradient(135deg, var(--navy-3), var(--navy-2));
  color: #fff;
  border-bottom: none;
  padding: 18px 22px;
}

.modal-title {
  font-weight: 600;
  font-size: 1.05rem;
}

.modal-header .btn-close {
  filter: invert(1);
  opacity: .9;
}

.modal-body {
  padding: 22px;
  background: transparent;
}

.modal-footer {
  background: var(--surface-2);
  border-top: none;
  padding: 14px 22px;
}

/* =========================
   FORMS (SOFT & CLEAN)
========================= */
.form-label {
  font-weight: 500;
  font-size: .85rem;
  margin-bottom: 6px;
  color: var(--navy-3);
}

.form-control,
.form-select {
  border-radius: 12px;
  padding: 10px 14px;
  border: 1px solid #d0d5dd;
  font-size: .9rem;
  background: #fff;
  transition: all .2s ease;
}

.form-control:focus,
.form-select:focus {
  border-color: var(--navy);
  box-shadow: 0 0 0 .2rem var(--focus);
}

/* =========================
   ACTION BUTTONS
========================= */
.modal-footer .btn {
  border-radius: 12px;
  padding: 8px 20px;
  font-size: .85rem;
}

/* Primary (Save / Add) */
.btn-primary {
  background: linear-gradient(135deg, var(--navy), var(--navy-2));
  border: none;
  box-shadow: 0 6px 16px rgba(1, 47, 107, .25);
}

.btn-primary:hover {
  background: linear-gradient(135deg, var(--navy-2), var(--navy-3));
  transform: translateY(-1px);
}

.btn-outline-light {
  color: var(--gold);
  border-color: rgba(242, 166, 90, .55);
}

.btn-outline-light:hover {
  background: rgba(242, 166, 90, .12);
  border-color: rgba(242, 166, 90, .85);
  color: var(--gold);
}

/* Secondary */
.btn-secondary {
  border-radius: 12px;
}

/* Edit buttons */
.btn-outline-secondary {
  background: #fff7ed;
  border: 1px solid #fed7aa;
  color: #c2410c;
  border-radius: 10px;
}

.btn-outline-secondary:hover {
  background: #ffedd5;
  color: #9a3412;
}

/* =========================
   TABLES INSIDE MODALS
========================= */
.modal .table {
  font-size: .85rem;
}

.modal .table thead th {
  background: var(--surface-2);
  font-weight: 600;
  color: var(--navy-3);
  border-bottom: none;
}

.modal .table tbody tr:hover {
  background: var(--surface-2);
}

.modal .table td {
  vertical-align: middle;
}

/* =========================
   BADGES
========================= */
.badge {
  font-weight: 500;
  padding: 5px 10px;
  border-radius: 10px;
}

/* =========================
   TOASTS (PREMIUM)
========================= */
.toast-container {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 1090;
}

.toast {
  border-radius: 14px;
  box-shadow: 0 14px 35px rgba(0,0,0,.25);
  border: none;
  overflow: hidden;
}

.toast-body {
  font-size: .85rem;
  line-height: 1.4;
}

/* Toast variants */
.toast-success {
  background: linear-gradient(135deg, #198754, #157347);
  color: #fff;
}

.toast-danger {
  background: linear-gradient(135deg, #dc3545, #b02a37);
  color: #fff;
}

.toast-info {
  background: linear-gradient(135deg, #0dcaf0, #0aa2c0);
  color: #fff;
}

.toast-warning {
  background: linear-gradient(135deg, #ffc107, #e0a800);
  color: #212529;
}

/* Toast close button */
.toast .btn-close {
  filter: invert(1);
  opacity: .85;
}
/* ===============================
   University Select – Enhanced UI
=============================== */

#program_university {
  border-radius: 12px;
  padding: 10px 14px;
  font-size: 0.9rem;
}

/* Options base */
#program_university option {
  padding: 8px 10px;
  font-size: 0.9rem;
}

/* Universities WITH programs */
#program_university option.option-has-programs {
  font-weight: 600;
  color: var(--navy-3);
  background-color: var(--surface-2);
}

/* ===============================
   University Select – SOLID NATIVE UI
=============================== */

#program_university {
  border-radius: 12px;
  font-size: 0.9rem;
}

/* Base option */
#program_university option {
  padding: 6px 10px;
  font-size: 0.9rem;
}

/* Universities WITH programs */
#program_university option.option-has-programs {
  font-weight: 600;
  color: var(--navy);
  background-color: var(--surface-2);
}

/* High program count (20+) */
#program_university option.option-has-programs[data-program-count-high="1"] {
  color: #dc3545;               /* danger red */
  font-weight: 700;
}

/* Selected option (most reliable styling) */
#program_university option:checked {
  background-color: var(--navy-3) !important;
  color: #ffffff !important;
}


</style>

</head>

<body>

<div class="dashboard-container">

  <!-- HEADER with PROFILE DROPDOWN -->
  <div class="dashboard-header" style="display: flex; justify-content: space-between; align-items: center;">
    <h2>Welcome, <?= htmlspecialchars($fullName) ?></h2>

    <div style="display: flex; align-items: center;">
      <img src="uploads/<?= $admin['profile_photo'] ?? 'default_avatar.png' ?>" 
           alt="Profile" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; margin-right: 10px;">
      <div class="dropdown me-2">
        <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
          <?= htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']) ?>
        </button>
       <ul class="dropdown-menu dropdown-menu-end">

  <li>
    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
      <i class="bi bi-person me-2"></i> My Profile
    </a>
  </li>

  <li>
    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changePassModal">
      <i class="bi bi-key me-2"></i> Change Password
    </a>
  </li>

  <?php if ($role === 'superadmin'): ?>
    <li>
      <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#adminSettingsModal">
        <i class="bi bi-gear me-2"></i> System Settings
      </a>
    </li>
  <?php endif; ?>

  <li><hr class="dropdown-divider"></li>

  <li>
    <form action="admin-logout.php" method="POST" style="margin: 0;">
      <button type="submit" class="dropdown-item text-danger">
        <i class="bi bi-box-arrow-right me-2"></i> Logout
      </button>
    </form>
  </li>

</ul>

      </div>

      <!-- NEW: Create Reminder button + Bell dropdown (notifications) -->
      <button class="btn btn-outline-light me-2" data-bs-toggle="modal" data-bs-target="#createReminderModal">
        <i class="bi bi-plus-circle me-1"></i> Create Reminder
      </button>

      <div class="dropdown">
        <button id="notifBell" class="btn btn-light position-relative" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-bell"></i>
          <span id="notifBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">0</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end p-0" style="min-width: 360px; max-height: 420px; overflow:auto;" id="notifMenu">
          <li class="px-3 py-2 fw-semibold border-bottom">Notifications</li>
          <div id="notifItems"></div>
          <li class="text-center p-2"><a class="small" href="notifications.php">View all</a></li>
        </ul>
      </div>
      <!-- /NEW -->
    </div>
  </div>

  <!-- Dashboard Grid -->
<div class="dashboard-grid">
  <?php foreach ($cards as $key => $card): ?>
    <?php if (in_array($key, $allowedCardsByRole[$role] ?? [])): ?>
      <div class="dashboard-card">
        <div class="dashboard-card-header d-flex justify-content-between align-items-center px-3 py-3">
          <span class="fw-bold dashboard-card-title">
            <span class="dashboard-card-icon">
              <i class="bi <?= htmlspecialchars($card['icon'] ?? 'bi-grid') ?>"></i>
            </span>
            <span><?= htmlspecialchars($card['title']) ?></span>
          </span>
          <i class="arrow-icon bi bi-chevron-down"></i>
        </div>
        <div class="dashboard-submenu">
          <?php foreach ($card['links'] as $link => $label): ?>
            <a href="<?= $link ?>">
              <i class="bi bi-chevron-right me-1"></i> <?= htmlspecialchars($label) ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  <?php endforeach; ?>
</div>



   <!-- Show General Application Flag Summary only for NON-Catholic users -->
<?php if (in_array('application_flag_summary', $allowedCardsByRole[$role] ?? []) && $role !== 'Catholic university of America'): ?>
  <div class="flag-summary-card summary-card">
    <div class="summary-header">
      <div class="summary-title">
        <span class="summary-icon"><i class="bi bi-bar-chart"></i></span>
        <span>Students Applications Summary</span>
      </div>
    </div>
    <div class="summary-body">
    <div class="flag-buttons">
      <?php foreach ($flagMap as $key => $label): ?>
        <a class="flag-btn" href="view-applicants.php?flag=<?= $key ?>">
          <?= $label ?>
          <span><?= (int)($flagCounts[$key] ?? 0) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
    </div>
  </div>
  <!-- ==============================
     💳 SMART PAYMENT DASHBOARD
================================ -->
<div class="flag-summary-card mt-4 summary-card" id="paymentDashboard">
  <div class="summary-header">
    <div class="summary-title">
      <span class="summary-icon"><i class="bi bi-credit-card-2-front"></i></span>
      <span>Payments Overview</span>
    </div>
  </div>

  <div class="summary-body">

    <div class="row g-3 mb-4" id="payment-kpis"></div>

    <div class="row g-4 mb-4">
      <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-body">
            <h6 class="fw-bold mb-3">Payment Status Distribution</h6>
            <canvas id="paymentStatusChart" height="220"></canvas>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-body">
            <h6 class="fw-bold mb-3">Payment Methods</h6>
            <canvas id="paymentMethodChart" height="220"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm border-0">
      <div class="card-body">
        <h6 class="fw-bold mb-3">Recent Payments</h6>
        <div class="table-responsive">
          <table class="table table-hover table-sm align-middle">
            <thead class="table-light">
              <tr>
                <th>Student</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody id="recent-payments">
              <tr>
                <td colspan="4" class="text-center text-muted">Loading payments…</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php endif; ?>

<!-- Show ONLY Catholic Flag Summary for Catholic role -->
<?php if (strtolower($role) === 'catholic university of america'): ?>
  <div class="flag-summary-card summary-card">
    <div class="summary-header">
      <div class="summary-title">
        <span class="summary-icon"><i class="bi bi-mortarboard"></i></span>
        <span>Catholic University Flag Summary</span>
      </div>
    </div>
    <div class="summary-body">
    <div class="flag-buttons">
      <?php foreach ($flagMap as $key => $label): ?>
        <a class="flag-btn" href="view-applicants.php?flag=<?= $key ?>&university=1&region=1">
          <?= $label ?>
          <span><?= (int)($catholicFlagCounts[$key] ?? 0) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
    </div>
  </div>
<?php endif; ?>


  <?php if (strtolower($role) !== 'catholic university of america'): ?>
  <!-- AGENT Tracking Section -->
  <div class="agent-tracking-card summary-card">
    <div class="summary-header">
      <div class="summary-title">
        <span class="summary-icon"><i class="bi bi-person-lines-fill"></i></span>
        <span>Agent Tracking Summary</span>
      </div>
    </div>
    <div class="summary-body">
    <canvas id="agentChart" height="120" style="margin-bottom:20px;"></canvas>
    <div style="overflow-x: auto;">
      <table id="agentTable" class="display compact stripe" style="width: 100%;">
        <thead>
          <tr>
            <th>#</th>
            <th>Agent Name</th>
            <th>Email</th>
            <th>Total Students</th>
            <th>Submitted</th>
<?php
$agentsCombined = [];

// Step 1: Get all admins (treated as agents)
$agentsQuery = "SELECT email, CONCAT(first_name, ' ', last_name) AS full_name FROM admins";
$resAgents = mysqli_query($conn, $agentsQuery);
while ($agent = mysqli_fetch_assoc($resAgents)) {
    $email = strtolower(trim($agent['email'] ?? ''));
    $agentsCombined[$email] = [
        'email' => $email,
        'name' => $agent['full_name'] ?: $email,
        'total' => 0,
        'submitted' => 0,
        'admit' => 0,
        'visa_approved' => 0,
        'enrolled' => 0,
    ];
}

// Step 2: From main database (student_applications)
$mainAgentsQuery = "
  SELECT 
    sa.agent_email,
    COUNT(sa.user_id) AS total_students,
    SUM(sa.submitted = 1) AS submitted,
    SUM(sa.admit = 1) AS admit,
    SUM(sa.visa_approved = 1) AS visa_approved,
    SUM(sa.enrolled = 1) AS enrolled
  FROM student_applications sa
  WHERE sa.agent_email IS NOT NULL AND sa.agent_email != ''
  GROUP BY sa.agent_email
";
$res1 = mysqli_query($conn, $mainAgentsQuery);
while ($r = mysqli_fetch_assoc($res1)) {
    $email = strtolower(trim($r['agent_email'] ?? ''));
    if (!isset($agentsCombined[$email])) continue;
    $agentsCombined[$email]['total'] += (int)$r['total_students'];
    $agentsCombined[$email]['submitted'] += (int)$r['submitted'];
    $agentsCombined[$email]['admit'] += (int)$r['admit'];
    $agentsCombined[$email]['visa_approved'] += (int)$r['visa_approved'];
    $agentsCombined[$email]['enrolled'] += (int)$r['enrolled'];
}

// Step 3: From Cyprus database
$cyprusQuery = "
  SELECT 
    agent_email,
    agent_first_name,
    agent_last_name,
    COUNT(*) AS total_students,
    SUM(status = 'verified') AS submitted,
    SUM(is_admitted = 1) AS admit
  FROM applications
  WHERE agent_email IS NOT NULL AND agent_email != ''
  GROUP BY agent_email
";
$res2 = mysqli_query($conn2, $cyprusQuery);
while ($r = mysqli_fetch_assoc($res2)) {
    $email = strtolower(trim($r['agent_email']));
    $name = trim(($r['agent_first_name'] ?? '') . ' ' . ($r['agent_last_name'] ?? ''));
    if (!isset($agentsCombined[$email])) {
        $agentsCombined[$email] = [
            'email' => $email,
            'name' => $name ?: $email,
            'total' => 0,
            'submitted' => 0,
            'admit' => 0,
            'visa_approved' => 0,
            'enrolled' => 0,
        ];
    }
    $agentsCombined[$email]['total'] += (int)$r['total_students'];
    $agentsCombined[$email]['submitted'] += (int)$r['submitted'];
    $agentsCombined[$email]['admit'] += (int)$r['admit'];
}

// Render table and chart
$chart_labels = [];
$chart_data = [];
$i = 1;
foreach ($agentsCombined as $agent) {
    if ($role !== 'superadmin' && strtolower(trim($agent['email'] ?? '')) !== strtolower(trim($admin['email'] ?? ''))) continue;

    $chart_labels[] = $agent['name'];
    $chart_data[] = (int)$agent['total'];
    echo "<tr>
        <td>{$i}</td>
        <td>" . htmlspecialchars($agent['name']) . "</td>
        <td>" . htmlspecialchars($agent['email']) . "</td>
        <td>{$agent['total']}</td>
        <td>{$agent['submitted']}</td>
        <td>{$agent['admit']}</td>
        <td>{$agent['visa_approved']}</td>
        <td>{$agent['enrolled']}</td>
    </tr>";
    $i++;
}
?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</div>
<?php endif; ?>

</div> <!-- END dashboard-container -->

<!-- PROFILE MODAL -->
<?php include 'profile_modal.php'; ?>

<!-- PASSWORD MODAL -->
<?php include 'change_password_modal.php'; ?>

<?php if ($role === 'superadmin'): ?>
<div class="modal fade" id="adminSettingsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 shadow-lg">

      <!-- HEADER -->
      <div class="modal-header bg-light">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-gear-fill text-primary me-2"></i> System Settings
        </h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <!-- BODY -->
      <div class="modal-body">

        <!-- NAV -->
        <ul class="nav nav-pills mb-4 gap-2">
          <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-universities">
              <i class="bi bi-bank me-1"></i> Universities
            </button>
          </li>
          <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-levels">
              <i class="bi bi-layers me-1"></i> Program Levels
            </button>
          </li>
          <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-programs">
              <i class="bi bi-journal-text me-1"></i> Programs
            </button>
          </li>
        </ul>

        <div class="tab-content">

<!-- =====================================================
     UNIVERSITIES
===================================================== -->
<div class="tab-pane fade show active" id="tab-universities">

  <div class="d-flex justify-content-between align-items-center mb-3">

    <div>
      <h6 class="fw-bold mb-0">Universities</h6>
      <small class="text-muted">Manage partner universities</small>
    </div>
    <button class="btn btn-sm btn-primary" onclick="openUniversityModal()">
      <i class="bi bi-plus-circle me-1"></i> Add University
    </button>
  </div>

  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle">
      <thead class="table-light">
  <tr>
    <th style="width:40px">#</th>
    <th>University</th>
    <th>Region</th>
    <th>Country</th>
    <th>preferred Platform(s)</th>
    <th class="text-end">Action</th>
  </tr>
</thead>

      <tbody>

<?php
$q = "
  SELECT 
    u.id,
    u.name,
    u.region_id,
    u.country_id,

    r.name AS region,
    c.name AS country,

    /* 🔗 Preferred platforms (comma-separated) */
    GROUP_CONCAT(
      DISTINCT p.platform_name
      ORDER BY p.platform_name
      SEPARATOR ', '
    ) AS platforms

  FROM universities u

  LEFT JOIN regions r
    ON r.id = u.region_id

  LEFT JOIN countries c
    ON c.id = u.country_id

  LEFT JOIN university_platforms up
    ON up.university_id = u.id

  LEFT JOIN platforms p
    ON p.id = up.platform_id
   AND p.status = 'Active'

  GROUP BY
    u.id,
    u.name,
    u.region_id,
    u.country_id,
    r.name,
    c.name

  ORDER BY u.name
";


$res = mysqli_query($conn, $q);
$i = 1;

while ($row = mysqli_fetch_assoc($res)):
?>
<tr>
  <td><?= $i++ ?></td>
  <td class="fw-semibold"><?= htmlspecialchars($row['name']) ?></td>
  <td><?= htmlspecialchars($row['region'] ?? '—') ?></td>
  <td><?= htmlspecialchars($row['country'] ?? '—') ?></td>
  <td>
  <?= htmlspecialchars($row['platforms'] ?? '—') ?>
</td>
  <td class="text-end">
  <button
  class="btn btn-sm btn-outline-secondary"
  onclick='openUniversityModal({
  id: <?= (int)$row["id"] ?>,
  name: <?= json_encode($row["name"]) ?>,
  region_id: <?= (int)$row["region_id"] ?>,
  country_id: <?= (int)$row["country_id"] ?>
})'>
  <i class="bi bi-pencil"></i>
</button>

  </td>
</tr>
<?php endwhile; ?>

      </tbody>
    </table>
  </div>
</div>

<!-- =====================================================
     PROGRAM LEVELS
===================================================== -->
<div class="tab-pane fade" id="tab-levels">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h6 class="fw-bold mb-0">Program Levels</h6>
      <small class="text-muted">
        International study levels (e.g. BSc, MSc, PhD)
      </small>
    </div>

    <button class="btn btn-sm btn-primary" onclick="openLevelModal()">
      <i class="bi bi-plus-circle me-1"></i> Add Level
    </button>
  </div>

  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>Code</th>
          <th>Level Name</th>
          <th class="text-end">Action</th>
        </tr>
      </thead>
      <tbody>
<?php
$res = mysqli_query($conn, "SELECT * FROM program_levels ORDER BY id");
$i = 1;
while ($l = mysqli_fetch_assoc($res)):
?>
        <tr>
          <td><?= $i++ ?></td>
          <td><?= htmlspecialchars($l['abbreviation']) ?></td>
          <td><?= htmlspecialchars($l['name']) ?></td>
          <td class="text-end">
            <button
              class="btn btn-sm btn-outline-secondary"
              onclick='openLevelModal(<?= json_encode($l) ?>)'>
              <i class="bi bi-pencil"></i>
            </button>
          </td>
        </tr>
<?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- =====================================================
     PROGRAMS
===================================================== -->
<div class="tab-pane fade" id="tab-programs">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h6 class="fw-bold mb-0">Programs</h6>
      <small class="text-muted">
        Programs linked to a University and Study Level
      </small>
    </div>

    <button class="btn btn-sm btn-primary" onclick="openProgramModal()">
      <i class="bi bi-plus-circle me-1"></i> Add Program
    </button>
  </div>
<!-- ✅ PROGRAM SEARCH (CORRECT PLACE) -->
<div class="mb-3">
  <div class="input-group">
    <span class="input-group-text bg-light">
      <i class="bi bi-search"></i>
    </span>
    <input
      type="text"
      id="programSearch"
      class="form-control"
      placeholder="Search by university, program, or level…"
      autocomplete="off"
    >
  </div>
</div>

  <?php
$q = "
  SELECT
    p.id,
    p.program_name,

    -- 🔑 REQUIRED FOR EDIT
    p.university_id,
    p.program_level_id,

    -- 🧾 DISPLAY VALUES
    u.name AS university,
    l.name AS level,
    l.abbreviation AS level_code

  FROM programs p
  INNER JOIN universities u ON u.id = p.university_id
  INNER JOIN program_levels l ON l.id = p.program_level_id

  ORDER BY
    u.name ASC,
    l.name ASC,
    p.program_name ASC
";


$res = mysqli_query($conn, $q);

/* Grouping */
$tree = [];

while ($row = mysqli_fetch_assoc($res)) {
    $u = $row['university'];
    $c = $row['level_code'];

    if (!isset($tree[$u][$c])) {
        $tree[$u][$c] = [
            'level_name' => $row['level'],
            'programs'   => []
        ];
    }
    $tree[$u][$c]['programs'][] = $row;
}
?>

<div class="container-fluid px-0">

<?php if (!empty($tree)): ?>
<?php foreach ($tree as $university => $levels): ?>

  <!-- ===============================
       UNIVERSITY CARD
  =============================== -->
  <div class="card shadow-sm mb-4 border-0">

    <div class="card-header bg-primary bg-opacity-10 fw-bold">
      <i class="bi bi-bank me-2"></i>
      <?= htmlspecialchars($university) ?>
    </div>

    <div class="card-body py-3">

      <?php foreach ($levels as $levelCode => $levelData): ?>

        <!-- ===============================
             LEVEL HEADER
        =============================== -->
        <div class="d-flex align-items-center mb-2 mt-3">
          <span class="badge bg-dark me-2">
            <?= htmlspecialchars($levelCode) ?>
          </span>
          <span class="fw-semibold">
            <?= htmlspecialchars($levelData['level_name']) ?>
          </span>
          <span class="ms-2 text-muted small">
            (<?= count($levelData['programs']) ?> programs)
          </span>
        </div>

        <!-- ===============================
             PROGRAM LIST
        =============================== -->
        <div class="list-group list-group-flush mb-2">

          <?php foreach ($levelData['programs'] as $p): ?>

         <div class="list-group-item d-flex justify-content-between align-items-center border-0 border-bottom py-2"
     data-program
     data-name="<?= htmlspecialchars(strtolower($p['program_name'])) ?>"
     data-university="<?= htmlspecialchars(strtolower($university)) ?>"
     data-level="<?= htmlspecialchars(strtolower($levelCode . ' ' . $levelData['level_name'])) ?>">


            <div class="d-flex align-items-center">
              <i class="bi bi-dot fs-4 text-secondary me-2"></i>
              <div>
                <div class="fw-medium">
                  <?= htmlspecialchars($p['program_name']) ?>
                </div>
                <div class="small text-muted">
                  <?= htmlspecialchars($university) ?>
                </div>
              </div>
            </div>

            <!-- ACTIONS -->
            <div class="btn-group">

              <button
                class="btn btn-sm btn-outline-secondary"
                onclick='openProgramModal(<?= json_encode($p) ?>)'
                title="Edit">
                <i class="bi bi-pencil"></i>
              </button>

              <button
                class="btn btn-sm btn-outline-danger"
                onclick="deleteProgram(<?= (int)$p['id'] ?>)"
                title="Delete">
                <i class="bi bi-trash"></i>
              </button>

            </div>

          </div>

          <?php endforeach; ?>
        </div>

      <?php endforeach; ?>

    </div>
  </div>

<?php endforeach; ?>
<?php else: ?>

  <div class="text-center text-muted py-5">
    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
    No programs added yet
  </div>

<?php endif; ?>

</div>


</div>


      <!-- FOOTER -->
      <div class="modal-footer bg-light">
        <small class="text-muted me-auto">
          <i class="bi bi-shield-lock me-1"></i> Superadmin only
        </small>
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>
<?php endif; ?>
<!-- ===============================
     PROGRAM MODAL (SMART + MULTI)
     - University ↔ Program (direct)
     - Level ↔ Program (global table)
================================ -->
<div class="modal fade" id="programModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">

      <!-- HEADER -->
      <div class="modal-header">
        <h5 class="modal-title" id="programModalTitle">
          <i class="bi bi-journal-plus me-2"></i> Add Program(s)
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <!-- BODY -->
      <div class="modal-body">

        <!-- UNIVERSITY (SEARCHABLE / LARGE LIST SAFE) -->
        <div class="mb-3">
          <label class="form-label">University</label>
          <select
            class="form-select"
            id="program_university"
            name="university_id"
            required
          >
            <option value="">Select university…</option>
            <?php
            $u = mysqli_query($conn, "SELECT id, name FROM universities ORDER BY name");
            while ($row = mysqli_fetch_assoc($u)) {
              echo '<option value="'.$row['id'].'">'.
                   htmlspecialchars($row['name']).
                   '</option>';
            }
            ?>
          </select>
        </div>

        <!-- PROGRAM LEVEL (GLOBAL, NOT DEPENDENT) -->
        <div class="mb-3">
          <label class="form-label">Program Level</label>
          <select
            class="form-select"
            id="program_level"
            name="level_id"
            required
          >
            <option value="">Select level…</option>
            <?php
            $l = mysqli_query($conn, "SELECT id, name FROM program_levels ORDER BY name");
            while ($row = mysqli_fetch_assoc($l)) {
              echo '<option value="'.$row['id'].'">'.
                   htmlspecialchars($row['name']).
                   '</option>';
            }
            ?>
          </select>
        </div>

        <!-- PROGRAM NAMES (SINGLE OR MULTIPLE) -->
        <div class="mb-2">
          <label class="form-label">
            Program Name(s)
            <small class="text-muted">(Press Enter to add multiple)</small>
          </label>

          <input
            type="text"
            class="form-control"
            id="program_input"
            placeholder="e.g. Computer Science"
            autocomplete="off"
          >
<!-- MODE SELECT -->
<div class="mb-3">
  <label class="form-label">Input Mode</label>
  <div class="d-flex gap-3">
    <div class="form-check">
      <input class="form-check-input" type="radio" name="program_mode" id="mode_manual" value="manual" checked>
      <label class="form-check-label" for="mode_manual">
        Manual Entry
      </label>
    </div>
    <div class="form-check">
      <input class="form-check-input" type="radio" name="program_mode" id="mode_ai" value="ai">
      <label class="form-check-label" for="mode_ai">
        Smart Paste (AI)
      </label>
    </div>
  </div>
</div>

          <!-- PROGRAM CHIPS -->
          <div
            id="program_list"
            class="d-flex flex-wrap gap-2 mt-2"
          ></div>
        </div>
<!-- =========================
     AI SMART PASTE (NEW)
========================= -->
<div class="mt-4">
  <label class="form-label">
    Smart Paste (AI)
    <small class="text-muted">
      Paste multiple programs (Word / PDF / website)
    </small>
  </label>

  <textarea
    id="ai_program_text"
    class="form-control"
    rows="6"
    placeholder="Paste program list here…"
  ></textarea>

  <button
    type="button"
    class="btn btn-outline-primary btn-sm mt-2"
    id="analyzeProgramsBtn"
  >
    🧠 Analyze & Prepare Programs
  </button>

  <div class="form-text">
    AI will extract and clean program names. You can review before saving.
  </div>
</div>

        <!-- INFO -->
       <div class="alert alert-light small mb-0">
  <i class="bi bi-info-circle me-1"></i>
  Programs will be created for the selected
  <strong>University</strong>.
  <span class="text-muted">
    (Program level is auto-detected in AI mode)
  </span>
</div>
      </div>

      <!-- FOOTER -->
      <div class="modal-footer">
        <button
          class="btn btn-secondary"
          data-bs-dismiss="modal"
        >
          Cancel
        </button>
        <button
          class="btn btn-primary"
          id="saveProgramsBtn"
        >
          <i class="bi bi-save me-1"></i>
          Save Program(s)
        </button>
      </div>

    </div>
  </div>
</div>

<div class="modal fade" id="universityModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="uniModalTitle">Add University</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form id="universityForm">
        <input type="hidden" name="action" value="save_university">
        <input type="hidden" name="id" id="uni_id">

        <div class="modal-body">

          <div class="mb-3">
            <label class="form-label">University Name</label>
            <input type="text" class="form-control" name="name" id="uni_name" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Region</label>
            <select class="form-select" name="region_id" id="uni_region" required>
              <option value="">Select region</option>
              <?php
              $r = mysqli_query($conn, "SELECT id, name FROM regions ORDER BY name");
              while ($row = mysqli_fetch_assoc($r)) {
                echo '<option value="'.$row['id'].'">'.htmlspecialchars($row['name']).'</option>';
              }
              ?>
            </select>
          </div>
<div class="mb-3">
  <label class="form-label">Country</label>
  <select
    class="form-select"
    name="country_id"
    id="uni_country"
    required
  >
    <option value="">Select country</option>
    <?php
    $c = mysqli_query($conn, "SELECT id, name FROM countries ORDER BY name");
    while ($row = mysqli_fetch_assoc($c)) {
      echo '<option value="'.$row['id'].'">'.
           htmlspecialchars($row['name']).
           '</option>';
    }
    ?>
  </select>
  <div class="mb-3">
  <label class="form-label">
    Platforms
    <small class="text-muted">(multiple allowed)</small>
  </label>

  <select
    class="form-select"
    name="platform_ids[]"
    id="uni_platforms"
    multiple
  >
    <?php
    $p = mysqli_query($conn, "
      SELECT id, platform_name
      FROM platforms
      WHERE status = 'Active'
      ORDER BY platform_name
    ");
    while ($row = mysqli_fetch_assoc($p)) {
      echo '<option value="'.$row['id'].'">'.
           htmlspecialchars($row['platform_name']).
           '</option>';
    }
    ?>
  </select>
</div>

</div>

        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-primary" type="submit">Save University</button>
        </div>
      </form>

    </div>
  </div>
</div>
<!-- ===============================
     PROGRAM LEVEL MODAL
================================ -->
<div class="modal fade" id="levelModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="levelModalTitle">Add Level</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form id="levelForm">
        <input type="hidden" name="action" value="save_level">
        <input type="hidden" name="id" id="level_id">

        <div class="modal-body">

          <div class="mb-3">
            <label class="form-label">Level Code</label>
            <input
              type="text"
              class="form-control"
              id="level_abbreviation"
              name="abbreviation"
              required
            >
          </div>

          <div class="mb-3">
            <label class="form-label">Level Name</label>
            <input
              type="text"
              class="form-control"
              id="level_name"
              name="name"
              required
            >
          </div>

        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Level</button>
        </div>
      </form>

    </div>
  </div>
</div>
<div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer"></div>

<script src="settings.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {

  const modeManual = document.getElementById('mode_manual');
  const modeAI     = document.getElementById('mode_ai');

  const university = document.getElementById('program_university');
  const level      = document.getElementById('program_level');

  const manualInput = document.getElementById('program_input');
  const aiBox       = document.getElementById('ai_program_text');
  const aiBtn       = document.getElementById('analyzeProgramsBtn');

  function setMode(mode) {
    if (mode === 'manual') {
      // ✅ Manual mode
      level.disabled = false;
      level.required = true;

      manualInput.disabled = false;

      aiBox.disabled = true;
      aiBtn.disabled = true;

      aiBox.value = '';

    } else {
      // 🤖 AI mode
      level.disabled = true;
      level.required = false;
      level.value = '';

      manualInput.disabled = true;
      manualInput.value = '';

      aiBox.disabled = false;
      aiBtn.disabled = false;
    }
  }

  // Default
  setMode('manual');

  modeManual.addEventListener('change', () => setMode('manual'));
  modeAI.addEventListener('change', () => setMode('ai'));
});
</script>

<script>
/**
 * SAFE program chip renderer
 * Does NOT depend on other scripts
 */
window.addProgramChip = function (programName) {
  if (!programName) return;

  const list = document.getElementById('program_list');
  if (!list) {
    console.error('[AI-UI] program_list container not found');
    return;
  }

  // Prevent duplicates (case-insensitive)
  const existing = Array.from(list.querySelectorAll('[data-program]'))
    .some(el => el.dataset.program.toLowerCase() === programName.toLowerCase());

  if (existing) return;

  const chip = document.createElement('span');
  chip.className = 'badge bg-primary-subtle text-primary border px-2 py-1 d-flex align-items-center gap-2';
  chip.dataset.program = programName;

  chip.innerHTML = `
    <span class="fw-normal">${programName}</span>
    <button type="button"
            class="btn-close btn-close-sm"
            aria-label="Remove"
            style="font-size: 0.6rem;">
    </button>
  `;

  chip.querySelector('.btn-close').addEventListener('click', () => {
  chip.remove();
  if (typeof window.updateProgramSaveState === 'function') {
    window.updateProgramSaveState();
  }
});

  list.appendChild(chip);
  // ✅ CRITICAL: re-check save conditions after AI adds a chip
if (typeof window.updateProgramSaveState === 'function') {
  window.updateProgramSaveState();
}
};
</script>

<script>
(function () {

  // ===============================
  // SAFE DOM READY
  // ===============================
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAIParser);
  } else {
    initAIParser();
  }

  function initAIParser() {
    console.log('[AI-JS] Initializing AI program parser');

    var btn = document.getElementById('analyzeProgramsBtn');
    if (!btn) {
      console.warn('[AI-JS] analyzeProgramsBtn not found');
      return;
    }

    // Prevent double binding
    if (btn.dataset.bound === '1') {
      console.log('[AI-JS] Button already bound');
      return;
    }
    btn.dataset.bound = '1';

    btn.addEventListener('click', onAnalyzeClick);
    console.log('[AI-JS] Button bound successfully');
  }

  // ===============================
  // CLICK HANDLER
  // ===============================
  async function onAnalyzeClick() {
    console.log('[AI-JS] Analyze clicked');

    var textarea = document.getElementById('ai_program_text');
    if (!textarea) {
      alert('Program text area not found');
      return;
    }

    var text = textarea.value.trim();
    console.log('[AI-JS] Text length:', text.length);

    if (!text) {
      alert('Paste program list first');
      return;
    }

    var universityEl = document.getElementById('program_university');
    var university   = universityEl ? universityEl.value : '';

    console.log('[AI-JS] University:', university);

    if (!university) {
      alert('Please select a University first.');
      return;
    }

    var btn = document.getElementById('analyzeProgramsBtn');
    btn.disabled = true;
    btn.textContent = 'Analyzing…';

    try {
      console.log('[AI-JS] Sending request to ai_parse_programs.php');

      var response = await fetch('ai_parse_programs.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        credentials: 'same-origin',
        body: JSON.stringify({
          text: text,
          university_id: university
        })
      });

      console.log('[AI-JS] HTTP status:', response.status);

      var raw = await response.text();
      console.log('[AI-JS] Raw response:', raw);

      if (!response.ok) {
        throw new Error('HTTP ' + response.status + ': ' + raw);
      }

      var data;
      try {
        data = JSON.parse(raw);
      } catch (e) {
        throw new Error('Invalid JSON from server');
      }

      if (data.error) {
        throw new Error(data.error);
      }

      if (!Array.isArray(data.programs)) {
        throw new Error('Invalid response format: programs[] missing');
      }

      if (data.programs.length === 0) {
        alert('AI found no programs.');
        return;
      }

      console.log('[AI-JS] Programs detected:', data.programs.length);

      // Add chips
      data.programs.forEach(function (name) {
        if (typeof window.addProgramChip === 'function') {
          window.addProgramChip(name);
        } else {
          console.warn('[AI-JS] addProgramChip() not defined');
        }
      });

      textarea.value = '';

      // Optional UX feedback
      if (typeof window.showToast === 'function') {
        window.showToast(
          'AI Completed',
          data.programs.length + ' programs extracted',
          false,
          'success'
        );
      }

    } catch (err) {
      console.error('[AI-JS] ERROR:', err);
      alert('AI analysis failed:\n' + err.message);
    } finally {
      btn.disabled = false;
      btn.textContent = '🧠 Analyze & Prepare Programs';
      console.log('[AI-JS] Reset button state');
    }
  }

})();
</script>


<!-- /NEW -->

<!-- Card toggle JS -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  const cards = document.querySelectorAll('.dashboard-card');

  cards.forEach(card => {
    const header = card.querySelector('.dashboard-card-header');
    if (!header) return;

    header.addEventListener('click', function () {
      // Close other cards (accordion behavior)
      cards.forEach(c => {
        if (c !== card) c.classList.remove('open');
      });

      // Toggle current
      card.classList.toggle('open');
    });
  });
});
</script>


<!-- DataTable JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
  $('#agentTable').DataTable({
    pageLength: 10,
    lengthChange: false,
    order: [[3, 'desc']],
    language: {
      emptyTable: "No agents yet.",
      search: "Search agent:",
      paginate: {
        previous: "Prev",
        next: "Next"
      }
    }
  });
});
</script>

<!-- ChartJS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const ctx = document.getElementById('agentChart')?.getContext('2d');
  if (ctx) {
    const agentChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: <?= json_encode($chart_labels ?? []) ?>,
        datasets: [{
          label: 'Total Students per Agent',
          data: <?= json_encode($chart_data ?? []) ?>,
          backgroundColor: 'rgba(0, 123, 255, 0.6)',
          borderColor: 'rgba(0, 123, 255, 1)',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        scales: {
          y: {
            beginAtZero: true,
            ticks: { stepSize: 1 }
          }
        }
      }
    });
  }
});
</script>

<!-- NEW: Reminder modal logic + notifications polling -->
<script>
(function(){
  const audienceSelect = document.getElementById('audienceSelect');
  const audienceValue  = document.getElementById('audienceValue');
  if (audienceSelect) {
    audienceSelect.addEventListener('change', () => {
      const v = audienceSelect.value;
      if (v === 'me') { audienceValue.classList.add('d-none'); audienceValue.value=''; }
      else {
        audienceValue.classList.remove('d-none');
        audienceValue.placeholder = (v==='role') ? 'e.g. superadmin, staff, agent'
                               : (v==='specific_admin') ? 'admin_id (number)'
                               : 'user@example.com';
      }
    });
  }

  const createForm = document.getElementById('createReminderForm');
  if (createForm) {
    createForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(createForm);
      try {
        const res = await fetch('reminders/save_reminder.php', { method: 'POST', body: fd, credentials: 'same-origin' });
        const j = await res.json();
        if (j.ok) {
          createForm.reset();
          if (audienceSelect) audienceSelect.value='me';
          if (audienceValue)  audienceValue.classList.add('d-none');
          const mm = bootstrap.Modal.getInstance(document.getElementById('createReminderModal'));
          if (mm) mm.hide();
          showToast('Reminder saved', 'Your reminder has been scheduled.');
        } else {
          showToast('Failed', j.msg || 'Could not save reminder', true);
        }
      } catch(err){
        showToast('Error', 'Network/server error', true);
      }
    });
  }

  // Lightweight toast
window.showToast = (title, msg, danger = false, type = null) => {
  const container = document.getElementById('toastContainer');
  if (!container) return;

  // Determine Bootstrap background
  let bgClass = 'primary';
  if (type) {
    bgClass = type;                 // success | danger | info | warning
  } else if (danger === true) {
    bgClass = 'danger';
  }

  const el = document.createElement('div');
  el.className = `toast align-items-center text-bg-${bgClass} mb-2`;
  el.role = 'alert';
  el.ariaLive = 'assertive';
  el.ariaAtomic = 'true';

  el.innerHTML = `
    <div class="d-flex">
      <div class="toast-body">
        <strong>${title}</strong><br>
        ${msg}
      </div>
      <button type="button"
              class="btn-close btn-close-white me-2 m-auto"
              data-bs-dismiss="toast"></button>
    </div>
  `;

  container.appendChild(el);

  const toast = new bootstrap.Toast(el, {
    delay: 3500,
    autohide: true
  });

  toast.show();

  el.addEventListener('hidden.bs.toast', () => el.remove());
};

  // Notifications polling
  async function refreshNotifs() {
    try {
      const r = await fetch('reminders/fetch_notifications.php', {credentials:'same-origin'});
      if (!r.ok) return;
      const j = await r.json();
      const badge = document.getElementById('notifBadge');
      const list  = document.getElementById('notifItems');
      if (!badge || !list) return;

      list.innerHTML = '';
      (j.items||[]).forEach(n=>{
        const li = document.createElement('li');
        li.className = 'dropdown-item ' + (n.is_read ? 'notif-item-read' : 'notif-item-unread');
        li.innerHTML = `
          <div class="d-flex flex-column">
            <div class="fw-semibold">${n.title}</div>
            <div class="small text-muted">${n.created_at ?? ''}</div>
            <div class="small">${n.body || ''}</div>
            <div class="text-end mt-1">
              <button class="btn btn-sm btn-link p-0 markRead" data-id="${n.id}">Mark read</button>
              ${n.link_url?`<a class="btn btn-sm btn-link p-0 ms-2" href="${n.link_url}">Open</a>`:''}
            </div>
          </div>`;
        list.appendChild(li);
      });
      if ((j.unread||0) > 0) { badge.textContent=j.unread; badge.classList.remove('d-none'); }
      else badge.classList.add('d-none');
    } catch(e){ /* silent */ }
  }

  document.addEventListener('click', async (e)=>{
    if (e.target.classList.contains('markRead')) {
      const id = e.target.dataset.id;
      await fetch('reminders/mark_read.php',{method:'POST',body:new URLSearchParams({id}),credentials:'same-origin'});
      refreshNotifs();
    }
  });

  refreshNotifs();
  setInterval(refreshNotifs, 30000); // every 30s
})();
</script>
<!-- /NEW -->
<script>
  // set to false to silence logs
  window.debugReminders = true;

  async function postFormDebug(url, data) {
    if (window.debugReminders) console.log('[reminders] POST →', url, data);
    const res  = await fetch(url, {
      method: 'POST',
      body: new URLSearchParams(data),
      credentials: 'same-origin'
    });
    const text = await res.text();
    if (window.debugReminders) console.log('[reminders] ←', res.status, text);
    let json;
    try { json = JSON.parse(text); }
    catch (e) { throw new Error('Invalid JSON from server: ' + text); }
    if (!res.ok || json.ok === false) {
      throw new Error(json?.msg || ('HTTP ' + res.status));
    }
    return json;
  }

  // hook your existing Save button handler WITHOUT removing current logic
  (function(){
    const form = document.getElementById('reminderForm'); // your reminder form id
    if (!form) return;

    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      // collect the same fields you already send
      const fd = new FormData(form);
      const data = Object.fromEntries(fd.entries());

      // channels[] checkboxes come as separate entries — normalize:
      data.channels = [];
      form.querySelectorAll('input[name="channels[]"]:checked').forEach(ch => data.channels.push(ch.value));

      try {
        const j = await postFormDebug('reminders/save_reminder.php', data);
        showToast('Reminder saved', 'Your reminder has been scheduled.');
        // close modal safely if you use Bootstrap modal
        document.querySelector('#createReminderModal .btn-close')?.click();
      } catch (err) {
        console.error('[reminders] save failed:', err);
        showToast('Error', err.message || 'Network/server error', true);
      }
    });

    // also log notifications fetch & mark-read (keep existing logic)
    const _fetch = window.fetch;
    window.fetch = async function(url, opts) {
      if (window.debugReminders && (''+url).includes('reminders/'))
        console.log('[reminders] fetch', url, opts || {});
      const r = await _fetch.apply(this, arguments);
      if (window.debugReminders && (''+url).includes('reminders/')) {
        const t = r.clone();
        let body = '';
        try { body = await t.text(); } catch(_) {}
        console.log('[reminders] resp', r.status, url, body);
      }
      return r;
    };
  })();
</script>
<!-- put this AFTER your existing scripts (already safe to add twice/no conflict) -->
<script>
  // set to false to silence logs
  window.debugReminders = true;

  async function postFormDebug(url, data) {
    if (window.debugReminders) console.log('[reminders] POST →', url, data);
    const res  = await fetch(url, { method:'POST',
                                    headers:{'Content-Type':'application/json'},
                                    body: JSON.stringify(data),
                                    credentials:'same-origin' });
    const text = await res.text();
    if (window.debugReminders) console.log('[reminders] ←', res.status, text);
    let json;
    try { json = JSON.parse(text); }
    catch (e) { throw new Error('Invalid JSON from server: ' + text); }
    if (!res.ok || json.ok === false) throw new Error(json?.msg || ('HTTP ' + res.status));
    return json;
  }

  // Wrap window.fetch to log any call to /reminders/*
  (function(){
    const _fetch = window.fetch;
    window.fetch = async function(url, opts) {
      if (window.debugReminders && (''+url).includes('reminders/'))
        console.log('[reminders] fetch', url, opts || {});
      const r = await _fetch.apply(this, arguments);
      if (window.debugReminders && (''+url).includes('reminders/')) {
        const t = r.clone(); let body = '';
        try { body = await t.text(); } catch(_) {}
        console.log('[reminders] resp', r.status, url, body);
      }
      return r;
    };
  })();
</script>
<script>
document.addEventListener('DOMContentLoaded', async () => {

  const dashboard = document.getElementById('paymentDashboard');
  if (!dashboard) {
    // dashboard not visible for this role
    return;
  }

  try {
    const res = await fetch('payment_dashboard_stats.php', {
      credentials: 'same-origin'
    });

    // 🔒 IMPORTANT: check HTTP status
    if (!res.ok) {
      const text = await res.text();
      console.error('Payment API HTTP error:', res.status, text);
      throw new Error('HTTP ' + res.status);
    }

    const data = await res.json();
    console.log('Payment dashboard data:', data);

    /* ===============================
       KPI CARDS
    =============================== */
const kpis = [
  { title: 'Expected Revenue', value: data.expected, icon: 'bi-cash-stack', color: 'primary' },
  { title: 'Total Collected', value: data.collected, icon: 'bi-check-circle', color: 'success' },

  // 🔥 FIX: Outstanding is now clickable
  { title: 'Outstanding', value: data.outstanding, icon: 'bi-exclamation-circle', color: 'warning', status: 'outstanding' },

  { title: 'Fully Paid', value: data.status.fully_paid, icon: 'bi-check2-circle', color: 'success', status: 'fully_paid' },
  { title: 'Partial Paid', value: data.status.partial_paid, icon: 'bi-hourglass-split', color: 'info', status: 'partial_paid' },
  { title: 'Unpaid', value: data.status.unpaid, icon: 'bi-x-circle', color: 'danger', status: 'unpaid' }
];


const kpiWrap = document.getElementById('payment-kpis');
kpiWrap.innerHTML = kpis.map(k => `
  <div class="col-6 col-md-4 col-xl-2">
    <div class="card shadow-sm border-0 h-100 payment-kpi"
         ${k.status ? `data-status="${k.status}"` : ''}
         style="${k.status ? 'cursor:pointer' : ''}">
      <div class="card-body text-center">
        <i class="bi ${k.icon} text-${k.color} fs-3"></i>
        <div class="small text-muted mt-2">${k.title}</div>
        <div class="fs-5 fw-bold">${Number(k.value).toLocaleString()}</div>
      </div>
    </div>
  </div>
`).join('');

    /* ===============================
       STATUS DONUT
    =============================== */
    new Chart(document.getElementById('paymentStatusChart'), {
      type: 'doughnut',
      data: {
        labels: ['Fully Paid', 'Partial Paid', 'Unpaid'],
        datasets: [{
          data: [
            data.status.fully_paid,
            data.status.partial_paid,
            data.status.unpaid
          ],
          backgroundColor: ['#198754', '#0dcaf0', '#dc3545']
        }]
      },
      options: { plugins: { legend: { position: 'bottom' } } }
    });

    /* ===============================
       METHOD BAR
    =============================== */
    new Chart(document.getElementById('paymentMethodChart'), {
      type: 'bar',
      data: {
        labels: Object.keys(data.methods),
        datasets: [{
          label: 'Total Collected',
          data: Object.values(data.methods),
          backgroundColor: '#012F6B'
        }]
      },
      options: {
        responsive: true,
        scales: { y: { beginAtZero: true } }
      }
    });

    /* ===============================
       RECENT PAYMENTS
    =============================== */
    const recentBody = document.getElementById('recent-payments');
    recentBody.innerHTML = data.recent.length
      ? data.recent.map(p => `
         <tr>
  <td>${p.student && p.student.trim() ? p.student : '—'}</td>
  <td class="fw-semibold">
    ${Number(p.amount_paid || 0).toLocaleString()}
  </td>
  <td>
    <span class="badge bg-secondary">
      ${p.payment_method || '—'}
    </span>
  </td>
  <td class="text-muted">
    ${p.paid_at ? new Date(p.paid_at).toLocaleString() : '—'}
  </td>
</tr>

        `).join('')
      : `<tr><td colspan="4" class="text-center text-muted">No payments</td></tr>`;

  } catch (err) {
    console.error('Payment dashboard failed:', err);
    dashboard.innerHTML =
      `<div class="alert alert-danger mb-0">
        <strong>Payment dashboard error.</strong><br>
        Check console & API output.
      </div>`;
  }
});
</script>
<script>
document.addEventListener('click', async (e) => {
  const card = e.target.closest('.payment-kpi[data-status]');
  if (!card) return;

  const status = card.dataset.status;

const titles = {
  outstanding: 'Students With Outstanding Balance',
  fully_paid: 'Fully Paid Students',
  partial_paid: 'Partial Paid Students',
  unpaid: 'Unpaid Students'
};

  document.getElementById('paymentModalTitle').textContent = titles[status];

  const modal = new bootstrap.Modal(
    document.getElementById('paymentListModal')
  );
  modal.show();

  const body = document.getElementById('paymentModalBody');
  body.innerHTML = `<tr><td colspan="5" class="text-center">Loading…</td></tr>`;

  try {
    const res = await fetch(`payment_dashboard_stats.php?status=${status}`, {
      credentials: 'same-origin'
    });
    const data = await res.json();

    body.innerHTML = data.length
      ? data.map(s => `
        <tr>
          <td>${s.student_name}</td>
          <td>${s.email ?? '—'}</td>
          <td class="fw-semibold">${Number(s.total_paid).toLocaleString()}</td>
          <td>
            <span class="badge bg-${
              s.status === 'fully_paid' ? 'success' :
              s.status === 'partial_paid' ? 'info' : 'danger'
            }">
              ${s.status.replace('_',' ')}
            </span>
          </td>
          <td>${s.last_payment ?? '—'}</td>
        </tr>
      `).join('')
      : `<tr><td colspan="5" class="text-center text-muted">No students</td></tr>`;

  } catch (err) {
    body.innerHTML =
      `<tr><td colspan="5" class="text-danger text-center">Failed to load</td></tr>`;
  }
});
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const input = document.getElementById('programSearch');
  if (!input) return;

  input.addEventListener('input', () => {
    const q = input.value.toLowerCase().trim();
    const items = document.querySelectorAll('[data-program]');
    const cards = document.querySelectorAll('#tab-programs .card');

    items.forEach(item => {
      const text =
        item.dataset.name + ' ' +
        item.dataset.university + ' ' +
        item.dataset.level;

      item.style.display = text.includes(q) ? '' : 'none';
    });

    // Hide empty university cards
    cards.forEach(card => {
      const visible = card.querySelectorAll('[data-program]:not([style*="display: none"])');
      card.style.display = visible.length ? '' : 'none';
    });
  });
});
</script>
<script>
async function deleteProgram(id) {
  if (!confirm('Delete this program?')) return;

  try {
    const res = await fetch('settings_actions.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({
        action: 'delete_program',
        id
      })
    });

    const data = await res.json();
    if (!data.ok) throw new Error(data.msg || 'Delete failed');

    showToast('Deleted', 'Program removed successfully', false, 'success');

    document.querySelectorAll(`[onclick*="deleteProgram(${id})"]`)
      .forEach(btn => btn.closest('[data-program]')?.remove());

  } catch (e) {
    showToast('Error', e.message, true);
  }
}
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {

  const modal = document.getElementById('programModal');
  if (!modal) return;

  modal.addEventListener('shown.bs.modal', async () => {

    const select = document.getElementById('program_university');
    if (!select) return;

    // ⛔ Prevent duplicate processing
    if (select.dataset.countsLoaded === '1') return;

    try {
      const res = await fetch('university_program_counts.php', {
        credentials: 'same-origin'
      });

      if (!res.ok) {
        console.error('Program count API HTTP error:', res.status);
        return;
      }

      const json = await res.json();
      if (!json.ok || !json.counts) return;

      const counts = json.counts;

      Array.from(select.options).forEach(opt => {
        const id = opt.value;
        if (!id || !counts[id]) return;

        const count = counts[id];

        /* ===============================
           CLEAN TEXT (IDEMPOTENT)
        =============================== */
        opt.textContent = opt.textContent
          .replace(/^●\s*/, '')
          .replace(/\s—\s\d+\sprogram(s)?$/i, '');

        /* ===============================
           VISIBLE, NATIVE INDICATOR
        =============================== */
        opt.textContent = `● ${opt.textContent} — ${count} program${count > 1 ? 's' : ''}`;

        /* ===============================
           ATTRIBUTES + CLASSES
        =============================== */
        opt.classList.add('option-has-programs');
        opt.dataset.programCount = count;

        /* ===============================
           COLOR TIERS (VERY VISIBLE)
        =============================== */
        if (count >= 20) {
          // Large university
          opt.style.color = '#dc3545';      // red
          opt.style.fontWeight = '700';
          opt.dataset.programCountHigh = '1';
        } else {
          // Normal university
          opt.style.color = '#012F6B';
          opt.style.fontWeight = '600';
        }
      });

      select.dataset.countsLoaded = '1';

    } catch (e) {
      console.error('University program count failed:', e);
    }
  });

});
</script>

</body>
</html>
