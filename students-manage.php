<?php
// This file is a PHP script that displays an applicant management table.
// The original PHP logic, data fetching, and database queries are untouched.
// Only the Name column's UI and styling have been enhanced for better visual alignment.

// --- Original PHP Logic (Unchanged) ---
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require 'db.php';

session_start();
require_once __DIR__ . '/helpers/role.php';
require_once __DIR__ . '/helpers/application_spam_guard.php';
require_once __DIR__ . '/helpers/application_filters.php';

// NOTE: Do NOT compute "time ago" in PHP here.
// Student report uses browser-timezone JS; we match it in JS below for consistency.

$sessionRole = isset($_SESSION['role']) ? trim((string) $_SESSION['role']) : '';
$dbRole = '';
$adminPk = 0;
if (!empty($_SESSION['id'])) {
    $adminPk = (int) $_SESSION['id'];
} elseif (!empty($_SESSION['admin_id'])) {
    $adminPk = (int) $_SESSION['admin_id'];
}
if ($adminPk > 0) {
    $stRole = $conn->prepare('SELECT role FROM admins WHERE id = ? LIMIT 1');
    if ($stRole) {
        $stRole->bind_param('i', $adminPk);
        $stRole->execute();
        // Avoid mysqlnd-only get_result(): use bind_result() for compatibility.
        $roleVal = null;
        $stRole->bind_result($roleVal);
        if ($stRole->fetch()) {
            $dbRole = trim((string)($roleVal ?? ''));
        }
        $stRole->close();
    }
}
$canDeleteApplication = xander_is_superadmin_role($dbRole) || xander_is_superadmin_role($sessionRole);

if ($canDeleteApplication && isset($conn) && $conn instanceof mysqli) {
    pcvc_spam_purge_database($conn, 40, false, false);
}

$studentAppsVisibilitySql = pcvc_sql_application_visible_in_list('sa');

// Handle new record submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_new'])) {
    // ... (your existing POST handling code remains the same)
}

// Fetch data from ALL sources properly with country names
$all_applicants = [];

// 1. Fetch from student_applications with country name join
$query1 = $conn->query("
    SELECT 
        sa.id,
        sa.first_name,
        sa.last_name,
        sa.email,
        CONCAT(sa.area_code, ' ', sa.phone_number) as phone_number,
        sa.gender,
        sa.dob,
        COALESCE(c.name, sa.nationality) as nationality,
        sa.city,
        sa.address_line1,
        COALESCE(sa.masters_program, sa.bachelor_program, sa.phd_program) as masters_program,
        sa.destination,
        sa.application_date,
        sa.created_at,
        sa.application_id,
        sa.application_remarks,
        sa.incomplete_app,
        sa.submitted,
        sa.app_paid,
        sa.admit,
        sa.i20_sent,
        sa.sevis_paid,
        sa.visa_scheduled,
        sa.visa_approved,
        sa.enrolled,
        sa.addn_doc,
        sa.deny,
        sa.app_start
    FROM student_applications sa
    LEFT JOIN countries c 
        ON sa.nationality = c.id 
        OR sa.nationality = c.name
    WHERE {$studentAppsVisibilitySql}
    ORDER BY 
        sa.visa_approved DESC,
        sa.admit DESC,
        sa.deny DESC,
        sa.submitted DESC,
        sa.id DESC
");


if ($query1) {
    while ($row = $query1->fetch_assoc()) {
        $row['source'] = 'student_applications';
        $all_applicants[] = $row;
    }
}

// 2. Fetch malta_applications (if table exists) - with country name join
$malta_students = [];
try {
    $query2 = $conn->query("SHOW TABLES LIKE 'malta_applications'");
    if ($query2 && $query2->num_rows > 0) {
        $malta_query = $conn->query("
            SELECT 
                ma.id,
                ma.name AS first_name,
                ma.surname AS last_name,
                ma.email,
                ma.contact_number AS phone_number,
                ma.gender,
                ma.dob,
                COALESCE(c.name, ma.nationality) as nationality,
                ma.birth_place AS city,
                ma.address AS address_line1,
                ma.degree_program AS masters_program,
                'Malta' AS destination,
                ma.created_at AS application_date,
                ma.application_id,
                ma.application_remarks,
                ma.incomplete_app,
                ma.submitted,
                ma.app_paid,
                ma.admit,
                ma.i20_sent,
                ma.sevis_paid,
                ma.visa_scheduled,
                ma.visa_approved,
                ma.enrolled,
                ma.addn_doc,
                ma.deny,
                ma.app_start
            FROM malta_applications ma
            LEFT JOIN countries c ON ma.nationality = c.id OR ma.nationality = c.name
            ORDER BY 
                ma.visa_approved DESC,
                ma.admit DESC,
                ma.deny DESC,
                ma.submitted DESC,
                ma.id DESC
        ");
        
        if ($malta_query) {
            while ($row = $malta_query->fetch_assoc()) {
                $row['source'] = 'malta_applications';
                $all_applicants[] = $row;
            }
        }
    }
} catch (Exception $e) {
    // Table doesn't exist or error
}

// 3. Fetch turkey_applications (if table exists) - with country name join
try {
    $query3 = $conn->query("SHOW TABLES LIKE 'turkey_applications'");
    if ($query3 && $query3->num_rows > 0) {
        $turkey_query = $conn->query("
            SELECT 
                ta.id,
                ta.first_name,
                ta.last_name,
                ta.email,
                ta.mobile AS phone_number,
                ta.gender,
                ta.dob,
                COALESCE(c.name, ta.nationality) as nationality,
                ta.city,
                ta.address AS address_line1,
                NULL AS masters_program,
                'Turkey' AS destination,
                ta.submitted_at AS application_date,
                ta.application_id,
                ta.application_remarks,
                ta.incomplete_app,
                ta.submitted,
                ta.app_paid,
                ta.admit,
                ta.i20_sent,
                ta.sevis_paid,
                ta.visa_scheduled,
                ta.visa_approved,
                ta.enrolled,
                ta.addn_doc,
                ta.deny,
                ta.app_start
            FROM turkey_applications ta
            LEFT JOIN countries c ON ta.nationality = c.id OR ta.nationality = c.name
            ORDER BY ta.submitted_at DESC
        ");
        
        if ($turkey_query) {
            while ($row = $turkey_query->fetch_assoc()) {
                $row['source'] = 'turkey_applications';
                $all_applicants[] = $row;
            }
        }
    }
} catch (Exception $e) {
    // Table doesn't exist or error
}

// Sort by ID descending to show newest first
usort($all_applicants, function($a, $b) {
    return ($b['id'] ?? 0) - ($a['id'] ?? 0);
});

// Debug: Check what data we have
// echo "<pre>Total applicants found: " . count($all_applicants) . "</pre>";
// echo "<pre>";
// print_r($all_applicants);
// echo "</pre>";

// HTML starts here
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
  <title>Xander- Applicants Management</title>
  
  <!-- Xander Color Variables -->
  <style>
    :root {
      --deep-navy: #012F6B;
      --secondary-blue: #254D81;
      --dark-blue: #002765;
      --gold: #F2A65A;
      --white: #FFFFFF;
      --light-bg: #f8fafc;
      --text-dark: #1e293b;
      --text-muted: #64748b;
      --success: #2e7d32;
      --danger: #c62828;
      --warning: #ed6c02;
      --info: #0288d1;
    }
  </style>
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    /* Lock page scroll — only the table inner panel scrolls (horizontal bar always at bottom of panel) */
    html {
      height: 100%;
      overflow: hidden;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(180deg, var(--white) 0%, #f0f4f8 100%);
      color: var(--text-dark);
      height: 100%;
      max-height: 100%;
      min-height: 100%;
      min-height: 100vh; /* fallback when parent height is unknown (e.g. some iframe layouts) */
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    /* ===== XANDER HEADER ===== */
    .xander-header {
      background: linear-gradient(135deg, var(--deep-navy) 0%, var(--secondary-blue) 100%);
      padding: 20px 0;
      text-align: center;
      box-shadow: 0 4px 12px rgba(0, 39, 101, 0.15);
      flex-shrink: 0;
    }

    .logo-container {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 8px;
    }

    .logo-main {
      font-size: 2.5rem;
      font-weight: 800;
      color: var(--white);
      letter-spacing: 1px;
      position: relative;
      display: inline-block;
    }

    .logo-main::after {
      content: '🎓';
      position: absolute;
      top: -5px;
      right: -35px;
      font-size: 1.8rem;
    }

    .logo-subtitle {
      font-size: 1.1rem;
      font-weight: 500;
      color: var(--gold);
      letter-spacing: 0.5px;
    }

    /* ===== MAIN CONTAINER ===== */
    .main-container {
      max-width: min(1600px, 98vw);
      margin: 0 auto;
      padding: 0 clamp(12px, 2vw, 24px);
      flex: 1;
      display: flex;
      flex-direction: column;
      min-height: 0;
      overflow: hidden;
    }

    /* ===== PAGE HEADER ===== */
    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
      flex-wrap: wrap;
      gap: 15px;
      flex-shrink: 0;
    }

    .page-title {
      font-size: 28px;
      font-weight: 700;
      color: var(--deep-navy);
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .page-title::before {
      content: '🎓';
      font-size: 30px;
    }

    /* ===== BUTTONS ===== */
    .btn-primary {
      background: linear-gradient(135deg, var(--deep-navy) 0%, var(--secondary-blue) 100%);
      border: none;
      border-radius: 999px;
      padding: 10px 24px;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .btn-primary:hover {
      background: linear-gradient(135deg, var(--dark-blue) 0%, var(--deep-navy) 100%);
      transform: translateY(-2px);
      box-shadow: 0 8px 16px rgba(1, 47, 107, 0.2);
    }

    .btn-warning {
      background: linear-gradient(135deg, var(--gold) 0%, #e6953e 100%);
      border: none;
      border-radius: 999px;
      padding: 10px 24px;
      font-weight: 600;
      color: var(--deep-navy);
    }

    .btn-warning:hover {
      background: linear-gradient(135deg, #e6953e 0%, #d68938 100%);
      transform: translateY(-2px);
      box-shadow: 0 8px 16px rgba(242, 166, 90, 0.2);
    }

    /* ===== SEARCH BAR ===== */
    .search-container {
      position: relative;
      margin-bottom: 25px;
      flex-shrink: 0;
    }

    .search-box {
      width: 100%;
      padding: 14px 50px 14px 20px;
      border: 2px solid rgba(1, 47, 107, 0.2);
      border-radius: 12px;
      font-size: 16px;
      background: var(--white);
      box-shadow: 0 4px 12px rgba(1, 47, 107, 0.08);
      transition: all 0.3s ease;
    }

    .search-box:focus {
      outline: none;
      border-color: var(--gold);
      box-shadow: 0 4px 16px rgba(242, 166, 90, 0.2);
    }

    .search-icon {
      position: absolute;
      right: 20px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-muted);
      font-size: 20px;
    }

    /* ===== TABLE CONTAINER ===== */
    .table-container {
      background: var(--white);
      border-radius: 16px;
      box-shadow: 0 8px 32px rgba(1, 47, 107, 0.1);
      border: 1px solid rgba(1, 47, 107, 0.08);
      margin-bottom: 12px;
      flex: 1;
      display: flex;
      flex-direction: column;
      min-height: 0;
      overflow: hidden;
    }

    /* Outer: fills remaining height, does not scroll */
    .table-viewport {
      flex: 1;
      min-height: 0;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      border-radius: 0 0 16px 16px;
    }

    /*
      Inner: constrained height + overflow:auto → vertical AND horizontal scrollbars
      stay at the bottom/right of this visible box (not below thousands of rows).
    */
    .table-viewport-inner {
      flex: 1;
      min-height: 0;
      width: 100%;
      overflow: auto;
      scrollbar-gutter: stable;
      -webkit-overflow-scrolling: touch;
    }

    .table {
      margin: 0;
      width: max-content;
      min-width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      table-layout: fixed;
    }


    .table thead th {
      background: linear-gradient(135deg, var(--secondary-blue) 0%, var(--dark-blue) 100%);
      color: var(--white);
      border: none;
      padding: 14px 12px;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 11px;
      letter-spacing: 0.06em;
      position: sticky;
      top: 0;
      z-index: 10;
      white-space: nowrap;
      box-shadow: 0 1px 0 rgba(255,255,255,0.12);
    }

    /* Fixed left columns */
    .col-name {
      width: 200px;
      position: sticky;
      left: 0;
      z-index: 11;
      background: linear-gradient(135deg, var(--secondary-blue) 0%, var(--dark-blue) 100%);
      box-shadow: 6px 0 12px rgba(0,0,0,0.12);
    }

    .col-email {
      width: 180px;
      position: sticky;
      left: 200px;
      z-index: 11;
      background: linear-gradient(135deg, var(--secondary-blue) 0%, var(--dark-blue) 100%);
      box-shadow: 6px 0 12px rgba(0,0,0,0.12);
    }

    .col-phone {
      width: 140px;
      position: sticky;
      left: 380px;
      z-index: 11;
      background: linear-gradient(135deg, var(--secondary-blue) 0%, var(--dark-blue) 100%);
      box-shadow: 6px 0 12px rgba(0,0,0,0.12);
    }

    .table tbody td.col-name {
      background: var(--white);
      position: sticky;
      left: 0;
      z-index: 5;
      box-shadow: 4px 0 8px rgba(0,0,0,0.04);
    }

    .table tbody td.col-email {
      background: var(--white);
      position: sticky;
      left: 200px;
      z-index: 5;
      box-shadow: 4px 0 8px rgba(0,0,0,0.04);
    }

    .table tbody td.col-phone {
      background: var(--white);
      position: sticky;
      left: 380px;
      z-index: 5;
      box-shadow: 4px 0 8px rgba(0,0,0,0.04);
    }

    .table tbody tr:nth-child(even) td.col-name,
    .table tbody tr:nth-child(even) td.col-email,
    .table tbody tr:nth-child(even) td.col-phone {
      background: #f8fafc;
    }

    .table tbody tr:hover td.col-name,
    .table tbody tr:hover td.col-email,
    .table tbody tr:hover td.col-phone {
      background: rgba(242, 166, 90, 0.08);
    }

    .col-actions {
      min-width: 140px;
      width: 140px;
      position: sticky;
      right: 0;
      z-index: 11;
      background: linear-gradient(135deg, #1a4a7a 0%, var(--dark-blue) 100%);
      box-shadow: -6px 0 12px rgba(0,0,0,0.12);
    }

    .table tbody td.col-actions {
      background: var(--white);
      position: sticky;
      right: 0;
      z-index: 5;
      box-shadow: -4px 0 8px rgba(0,0,0,0.04);
    }

    .table tbody tr:nth-child(even) td.col-actions {
      background: #f8fafc;
    }

    .table tbody tr:hover td.col-actions {
      background: rgba(242, 166, 90, 0.08);
    }

    .action-stack{
      display:flex;
      flex-direction:column;
      gap:8px;
      align-items:stretch;
      justify-content:center;
      padding: 2px 0;
    }

    .btn-share-access,
    .btn-record-payment,
    .btn-delete-app{
      width: 100%;
      min-width: 0;
      white-space: nowrap;
      box-shadow: 0 8px 18px rgba(2, 6, 23, 0.10);
      -webkit-tap-highlight-color: transparent;
      touch-action: manipulation;
    }

    .btn-record-payment {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.35rem;
      padding: 0.45rem 0.55rem;
      font-size: 0.75rem;
      font-weight: 600;
      color: #fff;
      background: linear-gradient(135deg, #059669, #047857);
      border: 1px solid rgba(4, 120, 87, 0.85);
      border-radius: 8px;
      cursor: pointer;
      transition: transform 0.15s ease, filter 0.15s ease;
    }

    .btn-record-payment:hover:not(:disabled) {
      filter: brightness(1.02);
      transform: translateY(-1px);
    }

    .btn-record-payment:disabled {
      opacity: 0.55;
      cursor: not-allowed;
    }

    .btn-share-access {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.35rem;
      padding: 0.45rem 0.55rem;
      font-size: 0.75rem;
      font-weight: 600;
      color: #fff;
      background: linear-gradient(135deg, #2563eb, #1d4ed8);
      border: 1px solid rgba(29, 78, 216, 0.85);
      border-radius: 8px;
      cursor: pointer;
      transition: transform 0.15s ease, filter 0.15s ease;
    }

    .btn-share-access:hover:not(:disabled) {
      filter: brightness(1.02);
      transform: translateY(-1px);
    }

    .btn-share-access:disabled {
      opacity: 0.55;
      cursor: not-allowed;
    }

    .btn-delete-app {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.35rem;
      padding: 0.45rem 0.55rem;
      font-size: 0.75rem;
      font-weight: 600;
      color: #fff;
      background: linear-gradient(135deg, #dc2626, #b91c1c);
      border: 1px solid rgba(185, 28, 28, 0.85);
      border-radius: 8px;
      cursor: pointer;
      transition: transform 0.15s ease, filter 0.15s ease;
    }

    .btn-delete-app:hover:not(:disabled) {
      filter: brightness(1.02);
      transform: translateY(-1px);
    }

    .btn-delete-app:disabled {
      opacity: 0.45;
      cursor: not-allowed;
    }

    .table tbody tr {
      transition: background-color 0.2s ease;
      border-bottom: 1px solid rgba(1, 47, 107, 0.05);
    }

    .table tbody tr:hover {
      background-color: rgba(242, 166, 90, 0.05);
    }

    .table td {
      padding: 14px 12px;
      vertical-align: middle;
      border: none;
      font-size: 14px;
      text-align: center;
    }

    /* ===== ENHANCED NAME COLUMN STYLES (MODERN & CENTERED) ===== */
    .name-cell-wrapper {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      line-height: 1.3;
    }

    .applicant-name {
      font-weight: 600;
      font-size: 14px;
      color: #1e293b;
      text-align: center;
    }

    .application-time {
      margin-top: 6px;
      font-size: 11px;
      font-weight: 500;
      color: #64748b;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      flex-wrap: wrap;
      text-align: center;
    }

    .time-icon {
      font-size: 12px;
    }

    .time-days {
      font-weight: 600;
    }

    .time-separator {
      opacity: 0.4;
    }

    /* ===== OPTIONAL HIGH-END TOUCH: subtle highlight for "d ago" ===== */
    .application-time .time-days {
      color: #f59e0b; /* subtle amber highlight */
    }

    /* ===== STATUS DROPDOWN COLUMN ===== */
    .status-column {
      min-width: 160px;
      max-width: 180px;
    }

    .status-dropdown {
      width: 100%;
      position: relative;
    }

    .status-dropdown-toggle {
      width: 100%;
      padding: 8px 12px;
      background: var(--white);
      border: 2px solid rgba(1, 47, 107, 0.2);
      border-radius: 8px;
      font-size: 14px;
      font-weight: 500;
      text-align: left;
      cursor: pointer;
      transition: all 0.2s ease;
      display: flex;
      justify-content: space-between;
      align-items: center;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .status-dropdown-toggle:hover {
      border-color: var(--gold);
      background: rgba(242, 166, 90, 0.05);
    }

    .status-dropdown-toggle:focus {
      outline: none;
      border-color: var(--gold);
      box-shadow: 0 0 0 3px rgba(242, 166, 90, 0.2);
    }

    .status-dropdown-menu {
      position: absolute;
      top: 100%;
      left: 0;
      right: 0;
      background: var(--white);
      border: 1px solid rgba(1, 47, 107, 0.15);
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      z-index: 1000;
      max-height: 300px;
      overflow-y: auto;
      display: none;
      margin-top: 4px;
    }

    .status-dropdown-item {
      padding: 8px 12px;
      cursor: pointer;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      justify-content: space-between;
      white-space: nowrap;
    }

    .status-dropdown-item:hover {
      background: rgba(242, 166, 90, 0.1);
    }

    .status-check {
      color: var(--success);
      font-weight: bold;
      font-size: 16px;
      display: none;
    }

    .status-check.active {
      display: inline-block;
    }

    /* Status colors */
    .status-incomplete_app { color: #212529; }
    .status-submitted { color: #6c757d; }
    .status-app_paid { color: var(--success); }
    .status-admit { color: var(--deep-navy); }
    .status-i20_sent { color: var(--info); }
    .status-sevis_paid { color: #6c757d; }
    .status-visa_scheduled { color: var(--warning); }
    .status-visa_approved { color: var(--success); }
    .status-enrolled { color: var(--success); }
    .status-addn_doc { color: #343a40; }
    .status-deny { color: var(--danger); }
    .status-app_start { color: #6c757d; }

    /* ===== EDITABLE CELLS ===== */
    .editable-cell {
      cursor: pointer;
      transition: background-color 0.2s ease;
      border-radius: 4px;
      padding: 4px 8px;
    }

    .editable-cell:hover {
      background-color: rgba(242, 166, 90, 0.1);
    }

    .editable-cell:focus {
      outline: none;
      background-color: rgba(242, 166, 90, 0.15);
    }

    /* ===== FORM CONTROLS ===== */
    .form-control-sm {
      font-size: 14px;
      padding: 6px 10px;
      border-radius: 6px;
      border: 1px solid rgba(1, 47, 107, 0.2);
      transition: border-color 0.3s ease;
    }

    .form-control-sm:focus {
      border-color: var(--gold);
      box-shadow: 0 0 0 0.25rem rgba(242, 166, 90, 0.25);
    }

    textarea.form-control-sm {
      min-height: 50px;
      resize: vertical;
    }

    /* ===== MODAL STYLES ===== */
    .modal-content {
      border-radius: 12px;
      border: none;
      box-shadow: 0 8px 32px rgba(1, 47, 107, 0.2);
    }

    .modal-header {
      background: linear-gradient(135deg, var(--deep-navy) 0%, var(--secondary-blue) 100%);
      color: var(--white);
      border-radius: 12px 12px 0 0;
      padding: 20px 30px;
    }

    .modal-header .btn-close {
      filter: invert(1);
    }

    .notify-channel-btn {
      border: 2px solid #e2e8f0 !important;
      background: #fff;
      font-weight: 600;
      color: #334155;
      transition: border-color 0.15s ease, background 0.15s ease, color 0.15s ease;
    }
    .notify-channel-btn:hover {
      border-color: #94a3b8 !important;
    }
    .notify-channel-btn.active {
      border-color: #012F6B !important;
      background: #eff6ff !important;
      color: #012F6B !important;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 1200px) {
      .main-container {
        max-width: 100%;
        padding: 0 15px;
      }
    }

    @media (max-width: 768px) {
      .page-header {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .logo-main {
        font-size: 2rem;
      }
      
      .logo-subtitle {
        font-size: 1rem;
      }
      
      .page-title {
        font-size: 24px;
      }
      
      .status-column {
        min-width: 140px;
      }
    }

    /* ===== SCROLLBAR STYLING ===== */
    ::-webkit-scrollbar {
      width: 8px;
      height: 8px;
    }

    ::-webkit-scrollbar-track {
      background: rgba(1, 47, 107, 0.1);
      border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb {
      background: var(--secondary-blue);
      border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
      background: var(--deep-navy);
    }

    /* ===== PAYMENT MODAL STYLES ===== */
    #paymentModal .modal-dialog {
      max-width: 900px;
    }

    #paymentModal .modal-header {
      background: linear-gradient(135deg, var(--success) 0%, #1b5e20 100%);
    }

    /* ===== TOAST STYLES ===== */
    .toast-container {
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 20000;
    }

    .toast {
      background: var(--white);
      border: 1px solid rgba(1, 47, 107, 0.1);
      box-shadow: 0 4px 12px rgba(1, 47, 107, 0.15);
      border-radius: 8px;
      overflow: hidden;
    }

    .toast-header {
      background: linear-gradient(135deg, var(--success) 0%, #1b5e20 100%);
      color: white;
      border: none;
    }

    .toast-header .btn-close {
      filter: invert(1);
    }

    /* ===== LOADING INDICATOR ===== */
    .loading-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(255, 255, 255, 0.8);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 9999;
      display: none;
    }

    .spinner {
      width: 50px;
      height: 50px;
      border: 5px solid rgba(1, 47, 107, 0.1);
      border-radius: 50%;
      border-top-color: var(--deep-navy);
      animation: spin 1s ease-in-out infinite;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }
  </style>
</head>

<body>
<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
  <div class="spinner"></div>
</div>

<!-- Xander Header -->
<div class="xander-header">
  <div class="logo-container">
    <div class="logo-main">XANDER</div>
    <div class="logo-subtitle">GLOBAL SCHOLARS APPLICANTS</div>
  </div>
</div>

<div class="main-container">
  <!-- Page Header - Removed action buttons -->
  <div class="page-header">
    <div class="page-title">All Applicants Management Portal</div>
  </div>

  <!-- Search Bar -->
  <div class="search-container">
    <input type="text" id="searchInput" class="search-box" placeholder="🔍 Search Name, Email, Program, Destination...">
    <span class="search-icon">🔍</span>
  </div>

  <!-- Table: inner scroll area so horizontal scrollbar stays at bottom of visible panel -->
  <div class="table-container">
    <div class="table-viewport" id="applicantTableViewport">
      <div class="table-viewport-inner" id="applicantTableViewportInner">
      <table class="table table-bordered table-hover table-striped mb-0" id="applicantTable">

        <thead class="text-center">
          <tr>
            <th>#</th><th class="col-name">Name</th><th class="col-email">Email</th><th class="col-phone">Phone</th><th>Gender</th><th>DOB</th>
            <th>Nationality</th><th>City</th><th>Address</th><th>Program</th><th>Destination</th>
            <th>Applied On</th><th>Status</th><th>App ID</th><th>Remarks</th>
            <th class="col-actions">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $counter = 1; 
          $statusOptions = [
            'incomplete_app' => 'Incomplete App',
            'submitted' => 'Submitted',
            'admit' => 'Admit',
            'i20_sent' => 'I-20 Sent',
            'sevis_paid' => 'Sevis Paid',
            'visa_scheduled' => 'Visa Sch.',
            'visa_approved' => 'Visa OK',
            'enrolled' => 'Enrolled',
            'addn_doc' => 'Add Doc',
            'deny' => 'Rejected',
            'app_start' => 'App Start'
          ];
          
          foreach ($all_applicants as $s): 
            // Find current status
            $currentStatus = null;
            $currentStatusText = 'Select Status';
            foreach ($statusOptions as $key => $label) {
              if (!empty($s[$key]) && $s[$key] == 1) {
                $currentStatus = $key;
                $currentStatusText = $label;
                break;
              }
            }
            if ($currentStatus === null && !empty($s['app_paid'])) {
              $currentStatusText = 'Paid';
            }

            // Format phone number
            $phone = $s['phone_number'] ?? '';
            if (!empty($s['area_code']) && !empty($s['phone_number']) && strpos($phone, $s['area_code']) === false) {
                $phone = $s['area_code'] . ' ' . $s['phone_number'];
            }
          ?>
          <tr data-row-id="<?= $s['id'] ?>" data-source="<?= $s['source'] ?>">
            <td><?= $counter++ ?></td>

            <!-- ✅ ENHANCED NAME COLUMN (Centered, modern, report-like time under name) -->
            <td contenteditable="true" class="editable-cell col-name" data-id="<?= $s['id'] ?>" data-field="first_name">
              <div class="name-cell-wrapper">
                <div class="applicant-name">
                  <?= htmlspecialchars(ucfirst((string) ($s['first_name'] ?? '')) . ' ' . ucfirst((string) ($s['last_name'] ?? ''))) ?>
                </div>
                <?php
                  // Prefer real timestamp when available (matches report meta.created_at)
                  $dtForNameTime =
                    ($s['source'] === 'student_applications' ? ((string)($s['created_at'] ?? '') ?: (string)($s['application_date'] ?? '')) : '')
                    ?: (string)($s['application_date'] ?? '');
                ?>
                <div class="application-time js-app-time"
                     data-dt="<?= htmlspecialchars($dtForNameTime, ENT_QUOTES, 'UTF-8') ?>"
                     style="display:none"></div>
              </div>
            </td>

            <!-- Email -->
            <td contenteditable="true" class="editable-cell col-email" data-id="<?= $s['id'] ?>" data-field="email">
              <?= htmlspecialchars($s['email'] ?? '') ?>
            </td>

            <!-- Phone Number -->
            <td contenteditable="true" class="editable-cell col-phone" data-id="<?= $s['id'] ?>"
                data-field="<?= $s['source'] === 'malta_applications' ? 'contact_number' : ($s['source'] === 'turkey_applications' ? 'mobile' : 'phone_number') ?>">
              <?= htmlspecialchars($phone) ?>
            </td>

            <!-- Gender -->
            <td contenteditable="true" class="editable-cell" data-id="<?= $s['id'] ?>" data-field="gender">
              <?= htmlspecialchars($s['gender'] ?? '') ?>
            </td>

            <!-- DOB -->
            <td contenteditable="true" class="editable-cell" data-id="<?= $s['id'] ?>" data-field="dob">
              <?= htmlspecialchars($s['dob'] ?? '') ?>
            </td>

            <!-- Nationality -->
            <td contenteditable="true" class="editable-cell" data-id="<?= $s['id'] ?>" data-field="nationality">
              <?= htmlspecialchars($s['nationality'] ?? '') ?>
            </td>

            <!-- City / Birthplace -->
            <td contenteditable="true" class="editable-cell" data-id="<?= $s['id'] ?>"
                data-field="<?= $s['source'] === 'malta_applications' ? 'birth_place' : 'city' ?>">
              <?= htmlspecialchars($s['city'] ?? '') ?>
            </td>

            <!-- Address -->
            <td contenteditable="true" class="editable-cell" data-id="<?= $s['id'] ?>"
                data-field="<?= $s['source'] === 'malta_applications' ? 'address' : 'address_line1' ?>">
              <?= htmlspecialchars($s['address_line1'] ?? '') ?>
            </td>

            <!-- Master's Program -->
            <td contenteditable="true" class="editable-cell" data-id="<?= $s['id'] ?>"
                data-field="<?= $s['source'] === 'malta_applications' ? 'degree_program' : 'masters_program' ?>">
              <?= htmlspecialchars($s['masters_program'] ?? '') ?>
            </td>

            <!-- Destination -->
            <td contenteditable="true" class="editable-cell" data-id="<?= $s['id'] ?>" data-field="destination">
              <?= htmlspecialchars($s['destination'] ?? '') ?>
            </td>

            <!-- Application Date -->
            <td class="editable-cell" data-id="<?= $s['id'] ?>"
                data-field="<?= $s['source'] === 'malta_applications' ? 'created_at' : ($s['source'] === 'turkey_applications' ? 'submitted_at' : 'application_date') ?>">
              <?php
                $appliedAt = $s['source'] === 'student_applications'
                  ? ((string)($s['created_at'] ?? '') ?: (string)($s['application_date'] ?? ''))
                  : (string)($s['application_date'] ?? '');
              ?>
              <div class="application-time js-app-time"
                   data-dt="<?= htmlspecialchars($appliedAt, ENT_QUOTES, 'UTF-8') ?>"
                   style="display:none;justify-content:center"></div>
            </td>

            <!-- Status Dropdown -->
            <td class="status-column">
              <div class="status-dropdown" data-id="<?= $s['id'] ?>" data-table="<?= $s['source'] ?>">
                <button type="button" class="status-dropdown-toggle">
                  <span class="status-text"><?= htmlspecialchars($currentStatusText) ?></span>
                  <span class="dropdown-arrow">▼</span>
                </button>
                <div class="status-dropdown-menu">
                  <?php foreach ($statusOptions as $key => $label): ?>
                  <div class="status-dropdown-item status-<?= $key ?>" data-flag="<?= $key ?>">
                    <span><?= htmlspecialchars($label) ?></span>
                    <span class="status-check <?= ($currentStatus === $key) ? 'active' : '' ?>">✓</span>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </td>

            <!-- Application ID -->
            <td>
              <input type="text" class="form-control form-control-sm live-app-id" data-id="<?= $s['id'] ?>" 
                     value="<?= htmlspecialchars($s['application_id'] ?? '') ?>">
            </td>

            <!-- Remarks -->
            <td>
              <textarea class="form-control form-control-sm live-app-remarks" data-id="<?= $s['id'] ?>">
                <?= htmlspecialchars($s['application_remarks'] ?? '') ?>
              </textarea>
            </td>

            <!-- Actions: Record payment, Share access, Delete -->
            <td class="col-actions text-center">
              <div class="action-stack">
                <?php
                  $rowFullName = trim(ucfirst((string)($s['first_name'] ?? '')) . ' ' . ucfirst((string)($s['last_name'] ?? '')));
                  $rowTable = (string)($s['source'] ?? 'student_applications');
                ?>
                <button type="button"
                        class="btn-record-payment"
                        data-pay-id="<?= (int) $s['id'] ?>"
                        data-pay-table="<?= htmlspecialchars($rowTable, ENT_QUOTES, 'UTF-8') ?>"
                        data-pay-name="<?= htmlspecialchars($rowFullName, ENT_QUOTES, 'UTF-8') ?>"
                        data-pay-email="<?= htmlspecialchars(trim((string)($s['email'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                        title="Record application payment">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 12v-2m9-4a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                  Record payment
                </button>
                <?php $rowEmail = trim((string)($s['email'] ?? '')); ?>
                <?php if ($rowEmail !== '' && filter_var($rowEmail, FILTER_VALIDATE_EMAIL)): ?>
                  <button type="button"
                          class="btn-share-access"
                          data-share-email="<?= htmlspecialchars(strtolower($rowEmail), ENT_QUOTES, 'UTF-8') ?>"
                          data-share-name="<?= htmlspecialchars(trim((string)($s['first_name'] ?? '') . ' ' . (string)($s['last_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                          title="Send student portal access email">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4h16v16H4V4zm2 3l6 5 6-5"/></svg>
                    Share access
                  </button>
                <?php else: ?>
                  <span class="text-muted small">—</span>
                <?php endif; ?>

                <?php if ($canDeleteApplication && ($s['source'] ?? '') === 'student_applications'): ?>
                  <button type="button" class="btn-delete-app" data-delete-id="<?= (int) $s['id'] ?>" title="Delete this application permanently">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Delete
                  </button>
                <?php elseif ($canDeleteApplication): ?>
                  <span class="text-muted small" title="Delete is only available for main student applications">—</span>
                <?php else: ?>
                  <span class="text-muted small">—</span>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>
  </div>
</div>

<!-- Admission Letter Modal -->
<div class="modal fade" id="admissionModal" tabindex="-1" aria-labelledby="admissionModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="admissionForm" enctype="multipart/form-data">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="admissionModalLabel">Send Admission Letter</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="student_id" id="modal_student_id">
          <input type="hidden" name="table" id="modal_table">

          <div class="mb-3">
            <label>Email:</label>
            <input type="email" name="email" id="modal_email" class="form-control" required readonly>
          </div>

          <div class="mb-3">
            <label>Attach Admission Letter (PDF):</label>
            <input type="file" name="letter" class="form-control" accept=".pdf" required>
          </div>

          <!-- Progress Indicator -->
          <div id="sendingProgress" style="display:none;" class="text-info fw-bold mt-2">
            ⏳ Sending email... Please wait.
          </div>

          <!-- Result Message -->
          <div id="sendResult" class="mt-2 fw-semibold"></div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-success">📧 Send Letter</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <form id="paymentForm" action="javascript:void(0);" autocomplete="off" novalidate>
      <div class="modal-content shadow-lg rounded-4">
        <!-- Header -->
        <div class="modal-header">
          <h5 class="modal-title fw-bold">
            💰 Record Application Payment
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <!-- Body -->
        <div class="modal-body px-4 py-3">
          <!-- Hidden Fields -->
          <input type="hidden" id="pay_student_id" name="student_id">
          <input type="hidden" id="pay_table" name="table">
          <input type="hidden" id="pay_package_id" name="package_id">
          <input type="hidden" id="pay_request_id" name="request_id">

          <!-- Student Info -->
          <div class="row mb-3">
            <div class="col-md-6">
              <div class="small text-muted">Applicant</div>
              <div class="fw-semibold" id="pay_name">—</div>
            </div>
            <div class="col-md-6">
              <div class="small text-muted">Email</div>
              <div class="fw-semibold" id="pay_email">—</div>
            </div>
          </div>

          <hr class="my-3">

          <!-- Package Section -->
          <h6 class="fw-bold text-primary mb-3">📦 Package Details</h6>

          <!-- Package Select -->
          <div class="mb-3">
            <label class="form-label fw-semibold">Select Package</label>
            <select id="package_select" class="form-select" required>
              <option value="" disabled selected>Select Package</option>
              <!-- loaded dynamically -->
            </select>
          </div>

          <!-- Package Totals -->
          <div class="row g-3 mb-4">
            <div class="col-md-4">
              <label class="form-label small text-muted">Expected Total</label>
              <input type="text" id="expected_total" class="form-control fw-semibold" readonly>
            </div>
            <div class="col-md-4">
              <label class="form-label small text-muted">Paid So Far</label>
              <input type="text" id="paid_total" class="form-control" readonly>
            </div>
            <div class="col-md-4">
              <label class="form-label small text-muted">Remaining Balance</label>
              <input type="text" id="remaining_total" class="form-control fw-bold text-danger" readonly>
            </div>
          </div>

          <hr class="my-4">

          <!-- Fee Items -->
          <h6 class="fw-bold text-primary mb-3">🧾 Fee Items — Pay Per Item</h6>
          <div id="feeItemsWrapper" class="border rounded-3 bg-light p-3" style="min-height: 140px;">
            <div class="text-muted text-center py-4">Select a package to load fee items</div>
          </div>

          <!-- Payment Summary -->
          <div class="row mt-4 g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Total Payment (This Entry)</label>
              <input type="text" id="payment_grand_total" class="form-control fw-bold text-success" readonly value="0.00">
            </div>
          </div>

          <hr class="my-4">

          <!-- Payment Meta -->
          <h6 class="fw-bold text-primary mb-3">💳 Payment Details</h6>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Payment Method</label>
              <select name="payment_method" class="form-select" required>
                <option value="Cash">Cash</option>
                <option value="Bank Transfer">Bank Transfer</option>
                <option value="Mobile Money">Mobile Money</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Comment / Reference</label>
              <input type="text" name="comment" class="form-control" placeholder="Optional note or reference">
            </div>
          </div>

          <div id="paymentSuccessAlert" class="alert alert-success d-none mt-3 mb-0" role="alert">
            <strong>Payment saved.</strong>
            <div id="paymentSuccessDetail" class="small mt-1 mb-0"></div>
          </div>

          <div id="paymentErrorAlert" class="alert alert-danger d-none mt-3 mb-0" role="alert">
            <strong>Payment could not be saved.</strong>
            <div id="paymentErrorDetail" class="small mt-1 mb-0"></div>
          </div>

          <!-- Payment Progress -->
          <div id="paymentProgressWrapper" class="mt-4 d-none">
            <div class="small fw-semibold mb-1" id="paymentProgressText">Processing payment...</div>
            <div class="progress" style="height: 10px;">
              <div id="paymentProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%"></div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer bg-light rounded-bottom-4">
          <button type="submit" id="btnRecordPayment" class="btn btn-success px-4 fw-semibold">💾 Record Payment</button>
          <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Status update + optional channels (one tap) -->
<div class="modal fade" id="statusNotifyModal" tabindex="-1" aria-labelledby="statusNotifyModalLabel" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
      <div class="modal-header text-white py-3" style="background: linear-gradient(135deg, #012F6B 0%, #254D81 100%);">
        <div>
          <h5 class="modal-title fw-semibold mb-0" id="statusNotifyModalLabel">Save status</h5>
          <div class="small opacity-75 mt-1">Choose whether to notify the applicant</div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4 pt-3">
        <div class="text-center mb-4">
          <div class="d-inline-block px-3 py-1 rounded-pill small fw-semibold mb-2" style="background:#e8eef9;color:#012F6B;">New status</div>
          <div class="fs-4 fw-bold text-dark" id="notify_status_label_display">—</div>
        </div>
        <div id="statusRejectReasonWrap" class="d-none mb-3">
          <label for="statusRejectReason" class="form-label small fw-semibold text-danger mb-1">Reason for rejection</label>
          <textarea id="statusRejectReason" class="form-control" rows="3" maxlength="2000" placeholder="Required if you choose Email, WhatsApp, or both"></textarea>
          <div class="form-text small text-muted">This message is included when you notify the applicant. Optional if you save with &quot;Record only&quot;.</div>
        </div>
        <p class="text-center text-muted small mb-3">Tap one option — you can update the record without sending anything.</p>
        <div class="row g-2 g-md-3">
          <div class="col-6 col-lg-3">
            <button type="button" class="btn notify-channel-btn w-100 py-3 rounded-3 shadow-sm active" data-ne="0" data-nw="0" title="Save only">Record<br><span class="small fw-normal opacity-75">no alert</span></button>
          </div>
          <div class="col-6 col-lg-3">
            <button type="button" class="btn notify-channel-btn w-100 py-3 rounded-3 shadow-sm" data-ne="1" data-nw="0" title="Email">✉ Email</button>
          </div>
          <div class="col-6 col-lg-3">
            <button type="button" class="btn notify-channel-btn w-100 py-3 rounded-3 shadow-sm" data-ne="0" data-nw="1" title="WhatsApp">WhatsApp</button>
          </div>
          <div class="col-6 col-lg-3">
            <button type="button" class="btn notify-channel-btn w-100 py-3 rounded-3 shadow-sm" data-ne="1" data-nw="1" title="Both">Email +<br>WhatsApp</button>
          </div>
        </div>
      </div>
      <div class="modal-footer bg-light border-0 pt-2">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" id="statusNotifyCancel">Cancel</button>
        <button type="button" class="btn fw-semibold text-white px-4" style="background: linear-gradient(135deg, #012F6B, #254D81);" id="statusNotifyConfirm">Save</button>
      </div>
    </div>
  </div>
</div>

<!-- Success Toast -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
  <div id="successToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header">
      <strong class="me-auto">✅ Success</strong>
      <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
    </div>
    <div class="toast-body">Operation completed successfully.</div>
  </div>
  <div id="warningToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header bg-warning text-dark">
      <strong class="me-auto">⚠ Notice</strong>
      <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
    </div>
    <div class="toast-body">Something needs attention.</div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
  window.CAN_DELETE_STUDENT_APP = <?= json_encode($canDeleteApplication) ?>;
</script>

<script>
// Render application times in browser timezone (same as Student Application Report)
(function () {
  function timeAgo(dateStr) {
    if (!dateStr) return "-";
    const seconds = Math.floor((Date.now() - new Date(dateStr)) / 1000);
    const units = [
      [31536000, "y"],
      [2592000, "mo"],
      [86400, "d"],
      [3600, "h"],
      [60, "m"],
    ];
    for (const [s, l] of units) {
      const v = Math.floor(seconds / s);
      if (v >= 1) return `${v}${l} ago`;
    }
    return "just now";
  }

  function formatFullTime(dateStr) {
    if (!dateStr) return null;
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return null;
    const now = new Date();
    const diffDays = Math.floor((now - d) / (1000 * 60 * 60 * 24));

    let color = "#64748b";
    let icon = "⏱";
    if (diffDays <= 1) {
      color = "#16a34a";
      icon = "🆕";
    } else if (diffDays <= 5) {
      color = "#f59e0b";
      icon = "⏱";
    } else {
      color = "#dc2626";
      icon = "📅";
    }

    const date = d.toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
      year: "numeric",
    });
    const time = d.toLocaleTimeString("en-US", {
      hour: "2-digit",
      minute: "2-digit",
    });
    return { color, icon, date, time };
  }

  function renderAllTimes() {
    document.querySelectorAll(".js-app-time").forEach((el) => {
      const dt = (el.getAttribute("data-dt") || "").trim();
      const t = formatFullTime(dt);
      if (!t) return;
      el.style.display = "inline-flex";
      el.style.alignItems = "center";
      el.style.gap = "6px";
      el.style.flexWrap = "wrap";
      el.style.color = t.color;
      el.innerHTML = `
        <span class="time-icon">${t.icon}</span>
        <span class="time-days">${timeAgo(dt)}</span>
        <span class="time-separator">•</span>
        <span>${t.date}</span>
        <span class="time-separator">•</span>
        <span>${t.time}</span>
      `;
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", renderAllTimes);
  } else {
    renderAllTimes();
  }
})();
</script>

<script>
$(function() {
  // Global loading overlay (used by payment modal and other actions)
  window.showLoading = function () {
    $('#loadingOverlay').stop(true, true).fadeIn(150);
  };
  window.hideLoading = function () {
    $('#loadingOverlay').stop(true, true).fadeOut(150);
  };

  // SEARCH
  $('#searchInput').on('keyup', function(){
    const value = $(this).val().toLowerCase();
    $('#applicantTable tbody tr').filter(function(){
      $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
    });
  });

  // DELETE APPLICATION (Superadmin · api/applications.php?action=delete — main student_applications only)
  $(document).on('click', '.btn-delete-app', function (e) {
    e.preventDefault();
    e.stopPropagation();
    if (!window.CAN_DELETE_STUDENT_APP) {
      alert('Only Superadmin can delete applications.');
      return;
    }
    const id = $(this).data('delete-id');
    if (!id) return;
    if (!confirm('Permanently delete this application and related jobs? This cannot be undone.')) {
      return;
    }
    const fd = new FormData();
    fd.append('id', String(id));
    showLoading();
    fetch('api/applications.php?action=delete', {
      method: 'POST',
      body: fd,
      credentials: 'same-origin'
    })
      .then(function (r) { return r.text(); })
      .then(function (text) {
        hideLoading();
        var json;
        try {
          json = JSON.parse(text);
        } catch (err) {
          alert('Delete failed: invalid server response.');
          return;
        }
        if (!json.success) {
          alert(json.message || 'Delete failed.');
          return;
        }
        if (typeof window.showSuccessToast === 'function') {
          window.showSuccessToast('Application deleted');
        } else {
          alert('Application deleted.');
        }
        var $row = $('.btn-delete-app[data-delete-id="' + id + '"]').closest('tr');
        $row.fadeOut(280, function () { $(this).remove(); });
      })
      .catch(function (err) {
        hideLoading();
        console.error(err);
        alert('Delete failed. Check your connection and try again.');
      });
  });

  // SHARE PORTAL ACCESS (email login link + default password)
  $(document).on('click', '.btn-share-access', async function (e) {
    e.preventDefault();
    e.stopPropagation();
    const btn = e.currentTarget;
    const email = (btn.getAttribute('data-share-email') || '').trim();
    const name = (btn.getAttribute('data-share-name') || '').trim();
    if (!email) return;

    const oldHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'Sending...';
    try {
      const fd = new FormData();
      fd.append('email', email);
      fd.append('name', name);
      const res = await fetch('api/student-portal-share-access.php', {
        method: 'POST',
        body: fd,
        credentials: 'same-origin'
      });
      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success === false) {
        const msg = (json && (json.message || json.error)) ? (json.message || json.error) : 'Failed to send email.';
        alert(msg);
        btn.disabled = false;
        btn.innerHTML = oldHtml;
        return;
      }
      btn.innerHTML = 'Sent';
      if (typeof window.showSuccessToast === 'function') {
        window.showSuccessToast('Access email sent');
      }
      setTimeout(() => {
        btn.disabled = false;
        btn.innerHTML = oldHtml;
      }, 2000);
    } catch (err) {
      console.error(err);
      alert('Failed to send email. Check your connection and try again.');
      btn.disabled = false;
      btn.innerHTML = oldHtml;
    }
  });

  // STATUS DROPDOWN TOGGLE
  $(document).on('click', '.status-dropdown-toggle', function(e) {
    e.stopPropagation();
    const dropdown = $(this).closest('.status-dropdown');
    const menu = dropdown.find('.status-dropdown-menu');
    
    // Close other open dropdowns
    $('.status-dropdown-menu').not(menu).hide();
    
    // Toggle current dropdown
    menu.toggle();
  });

  // STATUS DROPDOWN ITEM SELECTION
  $(document).on('click', '.status-dropdown-item', function(e) {
    e.stopPropagation();
    const item = $(this);
    const flag = item.data('flag');
    const dropdown = item.closest('.status-dropdown');
    const id = dropdown.data('id');
    const table = dropdown.data('table');
    const toggle = dropdown.find('.status-dropdown-toggle');
    const statusText = toggle.find('.status-text');
    const originalStatus = statusText.text();
    const label = item.find('span:first').text();

    dropdown.find('.status-dropdown-menu').hide();

    // Special handling for Admit (letter flow — keep existing behavior)
    if (flag === 'admit') {
      statusText.text(label);
      dropdown.find('.status-check').removeClass('active');
      item.find('.status-check').addClass('active');
      showLoading();
      const row = dropdown.closest('tr');
      const email = row.find('td[data-field="email"]').text().trim();
      $('#modal_student_id').val(id);
      $('#modal_table').val(table);
      $('#modal_email').val(email);
      hideLoading();
      new bootstrap.Modal(
        document.getElementById('admissionModal'),
        { backdrop: 'static', keyboard: false }
      ).show();
      return;
    }

    // Normal statuses: choose Email / WhatsApp / both / none, then save
    window._statusNotifyPending = {
      dropdown: dropdown,
      id: id,
      table: table,
      flag: flag,
      label: label,
      originalStatus: originalStatus,
      statusText: statusText,
      item: item
    };
    $('#notify_status_label_display').text(label);
    $('.notify-channel-btn').removeClass('active');
    $('.notify-channel-btn[data-ne="0"][data-nw="0"]').addClass('active');
    if (flag === 'deny') {
      $('#statusRejectReasonWrap').removeClass('d-none');
    } else {
      $('#statusRejectReasonWrap').addClass('d-none');
      $('#statusRejectReason').val('');
    }
    new bootstrap.Modal(document.getElementById('statusNotifyModal')).show();
  });

  $(document).on('click', '.notify-channel-btn', function() {
    $('.notify-channel-btn').removeClass('active');
    $(this).addClass('active');
  });

  $('#statusNotifyConfirm').on('click', function() {
    const p = window._statusNotifyPending;
    if (!p) return;
    const $sel = $('.notify-channel-btn.active');
    const ne = $sel.length ? parseInt($sel.data('ne'), 10) || 0 : 0;
    const nw = $sel.length ? parseInt($sel.data('nw'), 10) || 0 : 0;
    const rejectReason = ($('#statusRejectReason').val() || '').trim();
    if (p.flag === 'deny' && (ne || nw) && rejectReason === '') {
      alert('Please enter a rejection reason before sending email or WhatsApp.');
      return;
    }
    window._statusNotifyPending = null;
    const modalEl = document.getElementById('statusNotifyModal');
    const inst = bootstrap.Modal.getInstance(modalEl);
    if (inst) inst.hide();

    const $btn = $(this).prop('disabled', true);
    showLoading();
    $.ajax({
      url: 'update-flag.php',
      method: 'POST',
      dataType: 'json',
      data: {
        id: p.id,
        flag: p.flag,
        table: p.table,
        notify_email: ne,
        notify_whatsapp: nw,
        rejection_reason: rejectReason,
        json: 1
      },
      success: function(data) {
        hideLoading();
        $btn.prop('disabled', false);
        if (!data || data.ok !== true) {
          var errMsg = data && data.error ? data.error : 'unknown';
          if (errMsg === 'rejection_reason_required') {
            errMsg = 'A rejection reason is required when sending email or WhatsApp.';
          }
          alert('Failed to update status: ' + errMsg);
          return;
        }
        p.statusText.text(p.label);
        p.dropdown.find('.status-check').removeClass('active');
        p.item.find('.status-check').addClass('active');

        const n = data.notify;
        const parts = ['Status saved'];
        let anyFail = false;

        if ((ne || nw) && !n) {
          anyFail = true;
          parts.push('Notifications failed (server error).');
        }

        if (n && n.email && n.email.requested) {
          if (n.email.sent) {
            parts.push('Email sent');
          } else {
            anyFail = true;
            parts.push('Email failed' + (n.email.error ? ': ' + n.email.error : ''));
          }
        }
        if (n && n.whatsapp && n.whatsapp.requested) {
          if (n.whatsapp.sent) {
            if (n.whatsapp.method === 'text') {
              parts.push('WhatsApp sent (session message)');
            } else {
              parts.push('WhatsApp sent');
            }
          } else {
            anyFail = true;
            parts.push('WhatsApp failed' + (n.whatsapp.error ? ': ' + n.whatsapp.error : ''));
          }
        }
        if (!ne && !nw) {
          parts.length = 1;
          parts[0] = 'Status saved (no notification)';
        }

        const msg = parts.join(' · ');
        if (anyFail && typeof window.showWarningToast === 'function') {
          window.showWarningToast(msg);
        } else {
          showSuccessToast(msg);
        }
      },
      error: function(xhr, status, error) {
        hideLoading();
        $btn.prop('disabled', false);
        let detail = error;
        try {
          const j = xhr.responseJSON;
          if (j && j.error) detail = j.error;
        } catch (e) { /* ignore */ }
        alert('Network error: ' + detail);
        console.error('AJAX Error:', status, error, xhr.responseText);
      }
    });
  });

  $('#statusNotifyModal').on('hidden.bs.modal', function() {
    if (window._statusNotifyPending) {
      window._statusNotifyPending = null;
    }
    $('#statusRejectReason').val('');
    $('#statusRejectReasonWrap').addClass('d-none');
  });

  // CLOSE DROPDOWNS WHEN CLICKING OUTSIDE
  $(document).on('click', function() {
    $('.status-dropdown-menu').hide();
  });

  // Application ID live update
  $('.live-app-id').on('input', function(){
    const id = $(this).data('id');
    const value = $(this).val();
    $.post("update-static.php", { id, application_id: value }, function(resp){
      console.log(resp);
    });
  });

  // Remarks live update
  $('.live-app-remarks').on('input', function(){
    const id = $(this).data('id');
    const value = $(this).val();
    $.post("update-static.php", { id, application_remarks: value }, function(resp){
      console.log(resp);
    });
  });

  // Editable fields update
  $(document).on('blur', '.editable-cell', function() {
    const cell = $(this);
    const id = cell.data('id');
    const field = cell.data('field');
    const value = cell.text().trim();

    showLoading();
    $.post('update-field.php', { id, field, value }, function(resp) {
      hideLoading();
      if (resp !== 'ok') {
        alert('Failed to save field');
      }
    }).fail(function() {
      hideLoading();
      alert('Network error while saving field');
    });
  });

  // DATE PICKER (if any datepickers exist)
  if ($('.datepicker').length) {
    flatpickr(".datepicker", {
      altInput: true,
      altFormat: "F j, Y",
      dateFormat: "Y-m-d",
      maxDate: "today"
    });
  }
});

// SEND ADMISSION LETTER
$('#admissionForm').on('submit', function(e){
  e.preventDefault();
  const formData = new FormData(this);

  showLoading();
  $('#sendingProgress').show();
  $('#sendResult').text('').removeClass('text-success text-danger');

  $.ajax({
    url: 'send_admission.php',
    method: 'POST',
    data: formData,
    contentType: false,
    processData: false,
    success: function(resp) {
      hideLoading();
      $('#sendingProgress').hide();
      if (resp.trim() === 'ok') {
        $('#sendResult').text('✅ Letter sent successfully!').addClass('text-success fw-bold');
        showSuccessToast('Admission letter sent successfully');
        
        // Hide modal after delay
        setTimeout(() => {
          const modal = bootstrap.Modal.getInstance(document.getElementById('admissionModal'));
          modal.hide();
          $('#admissionForm')[0].reset();
          $('#sendResult').text('');
        }, 2000);
      } else {
        $('#sendResult').text('❌ Failed to send: ' + resp).addClass('text-danger fw-bold');
      }
    },
    error: function(xhr, status, error) {
      hideLoading();
      $('#sendingProgress').hide();
      $('#sendResult').text('❌ Network error: ' + error).addClass('text-danger fw-bold');
      console.error('Send admission error:', status, error);
    }
  });
});

// Close admission modal
$('#admissionModal').on('hidden.bs.modal', function () {
  $('#admissionForm')[0].reset();
  $('#sendResult').text('');
});

/* =========================================================
   RECORD PAYMENT MODAL
========================================================= */
function openRecordPaymentModal(studentId, table, fullName, email) {
  const id = parseInt(String(studentId || ''), 10);
  if (!id) {
    alert('Invalid applicant id.');
    return;
  }

  $('#pay_student_id').val(id);
  $('#pay_table').val(table || 'student_applications');
  $('#pay_name').text(fullName || '—');
  $('#pay_email').text(email || '—');
  $('#pay_package_id').val('');
  $('#package_select').prop('disabled', false).html('<option disabled selected>Loading packages...</option>');
  $('#expected_total, #paid_total, #remaining_total').val('Loading...');
  $('#feeItemsWrapper').html('<div class="text-muted text-center py-4">Select a package to load fee items</div>');
  $('#payment_grand_total').val('0.00');
  $('select[name="payment_method"]').val('Bank Transfer');
  $('input[name="comment"]').val('');
  $('#paymentErrorAlert, #paymentSuccessAlert').addClass('d-none');
  $('#paymentErrorDetail, #paymentSuccessDetail').text('');
  $('#pay_request_id').val(
    (window.crypto && window.crypto.randomUUID)
      ? window.crypto.randomUUID()
      : ('pay-' + Date.now() + '-' + Math.random().toString(36).slice(2, 10))
  );

  const paymentModal = new bootstrap.Modal(
    document.getElementById('paymentModal'),
    { backdrop: 'static', keyboard: false }
  );
  paymentModal.show();

  $.getJSON('load-payment-info.php', { student_id: id })
    .done(function (data) {
      if (!data || typeof data !== 'object') {
        alert('Invalid payment response from server');
        return;
      }
      if (data.error) {
        alert(data.error);
        return;
      }
      if (!Array.isArray(data.packages) || !data.packages.length) {
        alert('No packages found');
        return;
      }
      let pkgOptions = '<option value="" disabled selected>Select Package</option>';
      data.packages.forEach(function (pkg) {
        pkgOptions += '<option value="' + pkg.id + '">' + pkg.name + ' (' + pkg.currency + ' ' + Number(pkg.total_amount).toFixed(2) + ')</option>';
      });
      $('#package_select').html(pkgOptions);
      $('#expected_total, #paid_total, #remaining_total').val('');
    })
    .fail(function (xhr) {
      console.error(xhr.responseText);
      alert('Failed to load payment packages');
    });
}

$(document).on('click', '.btn-record-payment', function (e) {
  e.preventDefault();
  e.stopPropagation();
  const btn = $(this);
  openRecordPaymentModal(
    btn.data('pay-id'),
    btn.data('pay-table'),
    btn.data('pay-name'),
    btn.data('pay-email')
  );
});

/* =========================================================
   PAYMENT MODAL — PER ITEM PAYMENT (FINAL / ERROR-PROOF)
========================================================= */
(() => {
  let paymentCurrency = '';
  let itemPayments = {};
  let isSubmitting = false;
  let paymentSubmitController = null;

  const modalEl = document.getElementById('paymentModal');

  function setPageLoading(on) {
    if (on) {
      if (typeof window.showLoading === 'function') window.showLoading();
    } else if (typeof window.hideLoading === 'function') {
      window.hideLoading();
    }
  }

  function hidePaymentError() {
    $('#paymentErrorAlert').addClass('d-none');
    $('#paymentErrorDetail').text('');
  }

  function hidePaymentSuccess() {
    $('#paymentSuccessAlert').addClass('d-none');
    $('#paymentSuccessDetail').text('');
  }

  function showPaymentError(message, detail) {
    hidePaymentSuccess();
    const parts = [message || 'Payment failed'];
    if (detail) parts.push(String(detail));
    $('#paymentErrorDetail').text(parts.join(' — '));
    $('#paymentErrorAlert').removeClass('d-none');
    const alertEl = document.getElementById('paymentErrorAlert');
    if (alertEl) alertEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function showPaymentSuccess(message, detail) {
    hidePaymentError();
    const parts = [message || 'Payment recorded successfully'];
    if (detail) parts.push(String(detail));
    $('#paymentSuccessDetail').text(parts.join(' — '));
    $('#paymentSuccessAlert').removeClass('d-none');
    const alertEl = document.getElementById('paymentSuccessAlert');
    if (alertEl) alertEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  /* =====================================================
     RESET MODAL
  ===================================================== */
  modalEl.addEventListener('hidden.bs.modal', () => {
    document.activeElement?.blur();

    $('#package_select').html('<option value="" disabled selected>Select Package</option>');
    $('#feeItemsWrapper').html('<div class="text-muted text-center py-4">Select a package to load fee items</div>');
    $('#expected_total, #paid_total, #remaining_total').val('');
    $('#payment_grand_total').val('0.00');
    $('#pay_package_id').val('');
    $('select[name="payment_method"]').val('Cash');
    $('input[name="comment"]').val('');
    itemPayments = {};
    isSubmitting = false;
    $('#pay_request_id').val('');
    if (paymentSubmitController) {
      paymentSubmitController.abort();
      paymentSubmitController = null;
    }
    hidePaymentError();
    hidePaymentSuccess();
  });

  /* =====================================================
     PACKAGE SELECT
  ===================================================== */
  $(document).on('change', '#package_select', function () {
    const packageId = this.value;
    const studentId = $('#pay_student_id').val();
    if (!packageId || !studentId) return;

    $('#pay_package_id').val(packageId);
    itemPayments = {};
    hidePaymentError();
    hidePaymentSuccess();

    $('#feeItemsWrapper').html('<div class="text-muted text-center py-4">Loading fee items…</div>');

    $.getJSON('load-package-details.php', {
      package_id: packageId,
      student_id: studentId
    })
    .done(data => {
      if (!data || typeof data !== 'object') return;

      paymentCurrency = data.currency || '';
      const total = Number(data.total || 0);
      const paid  = Number(data.paid || 0);
      const remaining = Math.max(0, total - paid);

      $('#expected_total').val(`${paymentCurrency} ${total.toFixed(2)}`);
      $('#paid_total').val(`${paymentCurrency} ${paid.toFixed(2)}`);
      $('#remaining_total').val(`${paymentCurrency} ${remaining.toFixed(2)}`);

      if (!Array.isArray(data.items)) return;

      let html = '<div class="list-group list-group-flush">';
      data.items.forEach(item => {
        const left = Math.max(0, Number(item.amount || 0) - Number(item.paid || 0));
        if (left <= 0) return;

        html += `
          <div class="list-group-item py-3">
            <div class="row align-items-center">
              <div class="col-md-5">
                <strong>${item.name}</strong><br>
                <small class="text-muted">Remaining: ${paymentCurrency} ${left.toFixed(2)}</small>
              </div>
              <div class="col-md-4">
                <input type="number" class="form-control form-control-sm item-payment-input"
                  min="0" max="${left}" step="0.01" data-item-id="${item.id}" data-max="${left}"
                  placeholder="0.00">
              </div>
              <div class="col-md-3 text-end">
                <span class="badge bg-light text-dark">${paymentCurrency}</span>
              </div>
            </div>
          </div>
        `;
      });
      html += '</div>';
      $('#feeItemsWrapper').html(html);
      updateGrandTotal();
    })
    .fail(function() {
      $('#feeItemsWrapper').html('<div class="text-danger text-center py-4">Failed to load fee items</div>');
    });
  });

  /* =====================================================
     ITEM INPUT
  ===================================================== */
  $(document).on('input', '.item-payment-input', function () {
    const id  = $(this).data('item-id');
    const max = Number($(this).data('max'));
    let val   = Number(this.value || 0);

    if (val > max) {
      val = max;
      this.value = max.toFixed(2);
    }

    if (val > 0) itemPayments[id] = val;
    else delete itemPayments[id];

    updateGrandTotal();
  });

  function updateGrandTotal() {
    const sum = Object.values(itemPayments).reduce((a, b) => a + b, 0);
    $('#payment_grand_total').val(`${paymentCurrency} ${sum.toFixed(2)}`);
  }

  /* =========================================================
   SUBMIT PAYMENT (fetch — reliable on iOS Safari)
  ========================================================= */
  function parsePaymentJson(raw) {
    if (!raw || !String(raw).trim()) {
      return null;
    }
    const text = String(raw).trim();
    try {
      return JSON.parse(text);
    } catch (e) {
      const start = text.indexOf('{');
      const end = text.lastIndexOf('}');
      if (start >= 0 && end > start) {
        return JSON.parse(text.slice(start, end + 1));
      }
      throw e;
    }
  }

  function handlePaymentSuccess(resp) {
    const msg = (resp && resp.message) ? resp.message : 'Payment recorded successfully';
    const receipt = (resp && resp.receipt_no) ? ('Receipt: ' + resp.receipt_no) : '';
    const total = (resp && resp.total_paid) ? ('Total paid: ' + resp.total_paid) : '';

    finishPaymentProgress(true);
    setPageLoading(false);
    showPaymentSuccess(msg, [receipt, total].filter(Boolean).join(' · '));

    const rowId = $('#pay_student_id').val();
    const $row = $('tr[data-row-id="' + rowId + '"]');
    if ($row.length) {
      $row.find('.status-dropdown-toggle .status-text').text('Paid');
    }

    if (resp && resp.receipt_no) {
      $(document).trigger('payment-recorded', [resp]);
    }

    if (typeof window.showSuccessToast === 'function') {
      window.showSuccessToast(msg);
    }

    setTimeout(function () {
      const modal = bootstrap.Modal.getInstance(modalEl);
      if (modal) modal.hide();
    }, 900);
  }

  async function submitRecordPayment() {
    if (isSubmitting) return;
    isSubmitting = true;

    const packageId = $('#pay_package_id').val();
    hidePaymentError();
    hidePaymentSuccess();

    if (!packageId) {
      isSubmitting = false;
      showPaymentError('Please select a package first.');
      return;
    }
    if (!Object.keys(itemPayments).length) {
      isSubmitting = false;
      showPaymentError('Please enter at least one item payment amount for a fee item.');
      return;
    }

    let requestId = $('#pay_request_id').val();
    if (!requestId) {
      requestId = (window.crypto && window.crypto.randomUUID)
        ? window.crypto.randomUUID()
        : ('pay-' + Date.now() + '-' + Math.random().toString(36).slice(2, 10));
      $('#pay_request_id').val(requestId);
    }

    const $btn = $('#btnRecordPayment').prop('disabled', true);
    setPageLoading(true);
    startPaymentProgress();

    if (paymentSubmitController) {
      paymentSubmitController.abort();
    }
    paymentSubmitController = new AbortController();

    const payload = {
      student_id: $('#pay_student_id').val(),
      table: $('#pay_table').val(),
      package_id: packageId,
      payment_method: $('select[name="payment_method"]').val(),
      comment: $('input[name="comment"]').val(),
      request_id: requestId,
      items: itemPayments
    };

    let succeeded = false;

    try {
      const res = await fetch('record-payment.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload),
        credentials: 'same-origin',
        signal: paymentSubmitController.signal
      });

      const raw = await res.text();
      let resp = null;
      try {
        resp = parsePaymentJson(raw);
      } catch (parseErr) {
        throw new Error(raw ? raw.slice(0, 280) : 'Invalid server response');
      }

      updatePaymentProgress(60, 'Saving receipt...');

      if (!res.ok || !resp || resp.success !== true) {
        const msg = (resp && resp.message) ? resp.message : ('Payment failed (HTTP ' + res.status + ')');
        const detail = (resp && resp.error) ? resp.error : (raw && !resp ? raw.slice(0, 400) : '');
        showPaymentError(msg, detail);
        finishPaymentProgress(false);
        if (typeof window.showWarningToast === 'function') {
          window.showWarningToast(msg);
        }
        return;
      }

      succeeded = true;
      handlePaymentSuccess(resp);
    } catch (err) {
      if (err && err.name === 'AbortError') {
        return;
      }
      finishPaymentProgress(false);
      setPageLoading(false);
      console.error('Payment error:', err);
      const msg = err && err.message ? err.message : 'Server error. Please try again.';
      if ($('#paymentErrorAlert').hasClass('d-none')) {
        showPaymentError(msg);
      }
      if (typeof window.showWarningToast === 'function') {
        window.showWarningToast(msg);
      }
    } finally {
      if (!succeeded) {
        isSubmitting = false;
        $btn.prop('disabled', false);
      }
      paymentSubmitController = null;
    }
  }

  $('#paymentForm').on('submit', function (e) {
    e.preventDefault();
    e.stopPropagation();
    submitRecordPayment();
  });

  /* =====================================================
     SUCCESS TOAST
  ===================================================== */
  window.showSuccessToast = function (msg) {
    const text = '✅ ' + (msg || 'Payment recorded successfully');
    const toast = document.getElementById('successToast');
    if (!toast) {
      alert(text);
      return;
    }
    toast.querySelector('.toast-body').innerText = text;
    bootstrap.Toast.getOrCreateInstance(toast, { delay: 6000 }).show();
  };

  window.showWarningToast = function (msg) {
    const text = '⚠ ' + (msg || 'Something went wrong');
    const toast = document.getElementById('warningToast');
    if (!toast) {
      alert(text);
      return;
    }
    toast.querySelector('.toast-body').innerText = text;
    bootstrap.Toast.getOrCreateInstance(toast, { delay: 8000 }).show();
  };
})();

/* =====================================================
   AUTO RECEIPT PRINT TRIGGER
===================================================== */
(function () {
  function openReceiptPrint(receiptNo) {
    if (!receiptNo) return;
    setTimeout(function () {
      const printUrl = 'printReceipt.php?receipt_no=' + encodeURIComponent(receiptNo);
      const win = window.open(printUrl, '_blank');
      if (!win) alert('⚠️ Please allow popups to print the receipt.');
    }, 300);
  }

  $(document).on('payment-recorded', function (_e, resp) {
    if (resp && resp.receipt_no) openReceiptPrint(resp.receipt_no);
  });

  $(document).ajaxSuccess(function (event, xhr, settings, response) {
    if (!settings.url || !settings.url.includes('record-payment.php')) return;
    if (!response || response.success !== true || !response.receipt_no) return;
    openReceiptPrint(response.receipt_no);
  });
})();

function startPaymentProgress() {
  const wrapper = $('#paymentProgressWrapper');
  const bar     = $('#paymentProgressBar');
  const text    = $('#paymentProgressText');

  if (!wrapper.length || !bar.length || !text.length) {
    console.warn('Payment progress elements not found');
    return;
  }

  bar.stop(true, true).removeClass('bg-danger')
     .addClass('bg-success progress-bar-striped progress-bar-animated')
     .css('width', '0%');
  text.text('Initializing payment...');
  wrapper.removeClass('d-none');

  setTimeout(() => {
    bar.css('width', '15%');
    text.text('Recording payment...');
  }, 120);
}

function updatePaymentProgress(percent, text) {
  $('#paymentProgressBar').css('width', percent + '%');
  $('#paymentProgressText').text(text);
}

function finishPaymentProgress(success = true) {
  updatePaymentProgress(100, success ? 'Completed successfully' : 'Failed');
  $('#paymentProgressBar').removeClass('bg-success').addClass(success ? 'bg-success' : 'bg-danger');

  setTimeout(() => {
    $('#paymentProgressWrapper').addClass('d-none');
    $('#paymentProgressBar').css('width', '0%').removeClass('bg-danger').addClass('bg-success');
  }, 2000);
}

// Duplicate search script for compatibility
document.getElementById("searchInput").addEventListener("keyup", function() {
  const value = this.value.toLowerCase();
  const rows = document.querySelectorAll("#applicantTable tbody tr");

  rows.forEach(row => {
    const rowText = row.textContent.toLowerCase();
    row.style.display = rowText.includes(value) ? "" : "none";
  });
});

// Handle escape key to close dropdowns
$(document).on('keydown', function(e) {
  if (e.key === 'Escape') {
    $('.status-dropdown-menu').hide();
  }
});
</script>

</body>
</html>