<?php
// staff-management.php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers/role.php';

/* ============================================================
   SECURITY CHECK 
============================================================ */
if (!isset($_SESSION['id']) || !isset($_SESSION['role'])) {
    http_response_code(403);
    exit('Access denied. Please log in.');
}

$isSuperAdmin = pcvc_is_superadmin_role($_SESSION['role'] ?? '');
$currentUserId = $_SESSION['id'];

/* ============================================================
   STATUS MANAGEMENT SYSTEM
============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status']) && $isSuperAdmin) {
    $id = intval($_POST['status_user_id']);
    
    // Fetch current status
    $stmt = $conn->prepare("SELECT status FROM admins WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user) {
        // Cycle status: pending -> active -> deactive -> pending
        $currentStatus = $user['status'];
        $newStatus = '';
        
        switch($currentStatus) {
            case 'pending':
                $newStatus = 'active';
                break;
            case 'active':
                $newStatus = 'deactive';
                break;
            case 'deactive':
                $newStatus = 'pending';
                break;
            default:
                $newStatus = 'pending';
        }
        
        // Update status
        $updateStmt = $conn->prepare("UPDATE admins SET status = ? WHERE id = ?");
        $updateStmt->bind_param("si", $newStatus, $id);
        
        if ($updateStmt->execute()) {
            $_SESSION['success'] = "Status updated successfully to " . ucfirst($newStatus);
        } else {
            $_SESSION['error'] = "Failed to update status";
        }
        $updateStmt->close();
    }
    $stmt->close();
    
    header("Location: staff-management.php");
    exit;
}

/* ============================================================
   DELETE STAFF (SUPERADMIN ONLY)
============================================================ */
if (isset($_GET['delete']) && $isSuperAdmin) {
    $id = intval($_GET['delete']);
    
    // Prevent self-deletion
    if ($id == $currentUserId) {
        $_SESSION['error'] = "You cannot delete your own account";
    } else {
        $stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['success'] = "Staff member deleted successfully";
    }
    header("Location: staff-management.php");
    exit;
}

/* ============================================================
   UPDATE STAFF (SUPERADMIN ONLY)
============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_staff']) && $isSuperAdmin) {

    $id         = (int) ($_POST['id'] ?? 0);
    $fullName   = trim((string) ($_POST['full_name'] ?? ''));
    $emailIn    = trim((string) ($_POST['email'] ?? ''));
    $phoneIn    = trim((string) ($_POST['phone_number'] ?? ''));
    $usernameIn = trim((string) ($_POST['username'] ?? ''));

    $salary    = (float) ($_POST['salary_per_minute'] ?? 0);
    $monthly   = ($_POST['monthly_salary'] !== '' ? (float) $_POST['monthly_salary'] : null);
    $currency  = $_POST['salary_currency'] ?? 'USD';
    $break     = (int) ($_POST['allowed_break_minutes'] ?? 0);
    $days      = (int) ($_POST['work_days_per_week'] ?? 0);
    $sheet     = trim((string) ($_POST['sheet_id'] ?? ''));
    $link      = trim((string) ($_POST['sheet_link'] ?? ''));
    $officeRaw = trim((string) ($_POST['office_id'] ?? ''));
    $officeId  = ($officeRaw !== '' ? (int) $officeRaw : null);

    $role = (string) ($_POST['role'] ?? 'staff');

    $position     = trim((string) ($_POST['position'] ?? ''));
    $empType      = trim((string) ($_POST['employment_type'] ?? ''));
    $startDate    = !empty($_POST['employment_start_date']) ? (string) $_POST['employment_start_date'] : null;
    $nid          = trim((string) ($_POST['national_id'] ?? ''));
    $address      = trim((string) ($_POST['address'] ?? ''));
    $dob          = !empty($_POST['date_of_birth']) ? (string) $_POST['date_of_birth'] : null;
    $marital      = !empty($_POST['marital_status']) ? (string) $_POST['marital_status'] : null;
    $nationality  = trim((string) ($_POST['nationality'] ?? ''));
    $birthPlace   = trim((string) ($_POST['place_of_birth'] ?? ''));

    if ($id <= 0) {
        $_SESSION['error'] = 'Invalid staff record.';
        header('Location: staff-management.php');
        exit;
    }

    if ($fullName === '' || $emailIn === '' || $usernameIn === '') {
        $_SESSION['error'] = 'Full name, email, and username are required.';
        header('Location: staff-management.php');
        exit;
    }
    if (!filter_var($emailIn, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Please enter a valid email address.';
        header('Location: staff-management.php');
        exit;
    }

    $nameParts = preg_split('/\s+/u', $fullName, 2, PREG_SPLIT_NO_EMPTY);
    $firstName = $nameParts[0] ?? '';
    $lastName  = isset($nameParts[1]) ? trim((string) $nameParts[1]) : '';

    $chkUser = $conn->prepare('SELECT id FROM admins WHERE username = ? AND id != ? LIMIT 1');
    if ($chkUser) {
        $chkUser->bind_param('si', $usernameIn, $id);
        $chkUser->execute();
        $dupUser = $chkUser->get_result()->fetch_assoc();
        $chkUser->close();
        if ($dupUser) {
            $_SESSION['error'] = 'That username is already taken by another account.';
            header('Location: staff-management.php');
            exit;
        }
    }

    $chkMail = $conn->prepare('SELECT id FROM admins WHERE email = ? AND id != ? LIMIT 1');
    if ($chkMail) {
        $chkMail->bind_param('si', $emailIn, $id);
        $chkMail->execute();
        $dupMail = $chkMail->get_result()->fetch_assoc();
        $chkMail->close();
        if ($dupMail) {
            $_SESSION['error'] = 'That email is already used by another account.';
            header('Location: staff-management.php');
            exit;
        }
    }

    $stmt = $conn->prepare("
        UPDATE admins SET
            username = ?,
            first_name = ?,
            last_name = ?,
            full_name = ?,
            email = ?,
            phone_number = ?,
            role = ?,
            position = ?,
            employment_type = ?,
            employment_start_date = ?,
            national_id = ?,
            date_of_birth = ?,
            marital_status = ?,
            nationality = ?,
            place_of_birth = ?,
            address = ?,
            salary_per_minute = ?,
            monthly_salary = ?,
            salary_currency = ?,
            allowed_break_minutes = ?,
            work_days_per_week = ?,
            sheet_id = ?,
            sheet_link = ?,
            office_id = ?
        WHERE id = ?
    ");

    if (!$stmt) {
        $_SESSION['error'] = 'Update failed: ' . $conn->error;
        header('Location: staff-management.php');
        exit;
    }

    $stmt->bind_param(
        'ssssssssssssssssddsiissii',
        $usernameIn,
        $firstName,
        $lastName,
        $fullName,
        $emailIn,
        $phoneIn,
        $role,
        $position,
        $empType,
        $startDate,
        $nid,
        $dob,
        $marital,
        $nationality,
        $birthPlace,
        $address,
        $salary,
        $monthly,
        $currency,
        $break,
        $days,
        $sheet,
        $link,
        $officeId,
        $id
    );

    if ($stmt->execute()) {
        $_SESSION['success'] = 'Staff information updated successfully';
    } else {
        $_SESSION['error'] = 'Update failed: ' . $stmt->error;
    }
    $stmt->close();

    header('Location: staff-management.php');
    exit;
}

/* ============================================================
   FETCH ADMINS + OFFICES
============================================================ */
$admins  = $conn->query("SELECT * FROM admins ORDER BY 
    CASE 
        WHEN role = 'superadmin' THEN 1
        WHEN role = 'admin' THEN 2
        ELSE 3
    END, created_at DESC");
    
$offices = $conn->query("SELECT id, office_name FROM offices ORDER BY office_name ASC");

// Get success/error messages
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xander - Staff Management</title>
    
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
            --border-light: #e2e8f0;
        }
        
        /* Status button animations */
        @keyframes statusPulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .status-btn {
            transition: all 0.3s ease;
            cursor: pointer;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .status-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        
        .status-btn:active {
            animation: statusPulse 0.3s ease;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            min-width: 90px;
        }
        
        /* Tooltip styling */
        [data-tooltip] {
            position: relative;
            cursor: pointer;
        }
        
        [data-tooltip]:before {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            padding: 5px 10px;
            background: rgba(0,0,0,0.8);
            color: white;
            font-size: 12px;
            border-radius: 4px;
            white-space: nowrap;
            display: none;
            z-index: 1000;
            margin-bottom: 5px;
        }
        
        [data-tooltip]:hover:before {
            display: block;
        }
    </style>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(180deg, var(--white) 0%, #f0f4f8 100%);
            color: var(--text-dark);
            min-height: 100vh;
            overflow: hidden;
        }

        /* ===== XANDER HEADER ===== */
        .xander-header {
            background: linear-gradient(135deg, var(--deep-navy) 0%, var(--secondary-blue) 100%);
            padding: 12px 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 3px solid var(--gold);
        }

        .header-container {
            max-width: 100%;
            margin: 0 auto;
            padding: 0 24px;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo-main {
            font-size: 2rem;
            font-weight: 800;
            color: var(--white);
            letter-spacing: 1px;
            position: relative;
            display: inline-block;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .logo-main::after {
            content: '🎓';
            position: absolute;
            top: -8px;
            right: -35px;
            font-size: 1.8rem;
            filter: drop-shadow(2px 2px 2px rgba(0,0,0,0.3));
        }

        .logo-subtitle {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--gold);
            letter-spacing: 1px;
            border-left: 3px solid var(--gold);
            padding-left: 20px;
            text-transform: uppercase;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }

        /* ===== MAIN CONTAINER ===== */
        .main-container {
            height: calc(100vh - 80px);
            padding: 20px 24px 0 24px;
            display: flex;
            flex-direction: column;
        }

        /* ===== PAGE HEADER ===== */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-shrink: 0;
        }

        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--deep-navy);
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }

        .page-title i {
            color: var(--gold);
            font-size: 28px;
        }

        .stats-container {
            display: flex;
            gap: 15px;
        }

        .stat-card {
            background: var(--white);
            padding: 10px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(1, 47, 107, 0.1);
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid var(--border-light);
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--deep-navy) 0%, var(--secondary-blue) 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 20px;
        }

        .stat-info h3 {
            font-size: 20px;
            font-weight: 700;
            margin: 0;
            color: var(--deep-navy);
            line-height: 1.2;
        }

        .stat-info p {
            margin: 0;
            color: var(--text-muted);
            font-size: 12px;
        }

        /* ===== ALERTS ===== */
        .alert {
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 16px;
            border: none;
            font-weight: 500;
            flex-shrink: 0;
        }

        /* ===== SEARCH & FILTERS ===== */
        .filters-section {
            background: var(--white);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(1, 47, 107, 0.08);
            border: 1px solid var(--border-light);
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            align-items: center;
            flex-shrink: 0;
        }

        .search-wrapper {
            flex: 1;
            min-width: 300px;
            position: relative;
        }

        .search-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 16px;
        }

        #searchBox {
            width: 100%;
            padding: 10px 16px 10px 42px;
            border: 2px solid var(--border-light);
            border-radius: 8px;
            font-size: 14px;
        }

        #searchBox:focus {
            outline: none;
            border-color: var(--gold);
        }

        .filter-badge {
            background: var(--light-bg);
            padding: 6px 12px;
            border-radius: 6px;
            color: var(--text-muted);
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .filter-badge:hover {
            background: var(--border-light);
        }

        .filter-badge.active {
            background: var(--deep-navy);
            color: var(--white);
        }

        /* ===== TABLE CARD ===== */
        .table-card {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(1, 47, 107, 0.1);
            border: 1px solid var(--border-light);
            overflow: hidden;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        /* ===== TABLE RESPONSIVE ===== */
        .table-responsive {
            flex: 1;
            overflow: auto;
            position: relative;
        }

        th {
            background: linear-gradient(135deg, #f8fafc 0%, #eef2f6 100%);
            color: var(--deep-navy);
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 14px 8px;
            position: sticky;
            top: 0;
            z-index: 10;
            white-space: nowrap;
            border-bottom: 2px solid var(--gold);
            border-right: 1px solid #dee2e6;
            text-align: center;
            vertical-align: middle;
        }

        th:last-child {
            border-right: none;
        }

        th i {
            margin-left: 4px;
            color: var(--gold);
        }

        td {
            padding: 10px 8px;
            border-bottom: 1px solid var(--border-light);
            border-right: 1px solid #f1f3f5;
            vertical-align: middle;
            background: var(--white);
            white-space: nowrap;
            min-width: 100px;
        }

        td:last-child {
            border-right: none;
        }

        .form-control-sm, .form-select-sm {
            padding: 6px 8px;
            font-size: 12px;
            height: 32px;
            width: 100%;
            min-width: 100px;
        }

        .profile-img-container {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid var(--gold);
            margin: 0 auto;
        }

        .profile-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-placeholder {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--secondary-blue) 0%, var(--deep-navy) 100%);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            margin: 0 auto;
        }

        .role-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            white-space: nowrap;
            width: 100%;
            text-align: center;
        }

        .role-superadmin {
            background: linear-gradient(135deg, var(--gold) 0%, #e6953e 100%);
            color: var(--deep-navy);
        }

        .role-admin {
            background: linear-gradient(135deg, var(--deep-navy) 0%, var(--secondary-blue) 100%);
            color: var(--white);
        }

        .role-staff {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: var(--white);
        }

        .role-agent {
            background: linear-gradient(135deg, var(--info) 0%, #026aa7 100%);
            color: var(--white);
        }

        .action-group {
            display: flex;
            gap: 4px;
            justify-content: center;
            min-width: 90px;
        }

        .btn-sm {
            padding: 4px 8px;
            font-size: 11px;
            border-radius: 4px;
            white-space: nowrap;
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success) 0%, #1b5e20 100%);
            border: none;
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger) 0%, #8b1e1e 100%);
            border: none;
            color: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .empty-state {
            padding: 40px 20px;
            text-align: center;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            display: none;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid var(--border-light);
            border-radius: 50%;
            border-top-color: var(--deep-navy);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button { 
            opacity: 0.3;
            height: 20px;
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
    <div class="header-container">
        <div class="logo-container">
            <div class="logo-main">XANDER</div>
            <div class="logo-subtitle">STAFF MANAGEMENT</div>
        </div>
    </div>
</div>

<div class="main-container">
    
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title">
            <i class="bi bi-people-fill"></i>
            Staff Management
        </h1>
        
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-people"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $admins->num_rows ?></h3>
                    <p>Total</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-star-fill"></i>
                </div>
                <div class="stat-info">
                    <h3>
                        <?php 
                        $admins->data_seek(0);
                        $superadminCount = 0;
                        while($row = $admins->fetch_assoc()) {
                            if($row['role'] === 'superadmin') $superadminCount++;
                        }
                        $admins->data_seek(0);
                        echo $superadminCount;
                        ?>
                    </h3>
                    <p>Super</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Alert Messages -->
    <?php if ($success): ?>
    <div class="alert alert-success" id="successAlert">
        <i class="bi bi-check-circle-fill me-2"></i>
        <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-danger" id="errorAlert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>
    
    <!-- Search and Filters -->
    <div class="filters-section">
        <div class="search-wrapper">
            <i class="bi bi-search"></i>
            <input type="text" id="searchBox" class="form-control" placeholder="Search...">
        </div>
        
        <div class="filter-badge active" onclick="filterByRole('all')">
            <i class="bi bi-people"></i> All
        </div>
        <div class="filter-badge" onclick="filterByRole('superadmin')">
            <i class="bi bi-star-fill"></i> Super
        </div>
        <div class="filter-badge" onclick="filterByRole('admin')">
            <i class="bi bi-shield"></i> Admin
        </div>
        <div class="filter-badge" onclick="filterByRole('staff')">
            <i class="bi bi-person-badge"></i> Staff
        </div>
        <div class="filter-badge" onclick="filterByRole('agent')">
            <i class="bi bi-person-workspace"></i> Agent
        </div>
    </div>
    
    <!-- Staff Table Card -->
    <div class="table-card">
        <div class="table-responsive">
            <table class="table" id="staffTable">
                <thead>
                    <tr>
                        <th style="min-width: 40px;">#</th>
                        <th style="min-width: 60px;">Profile</th>
                        <th onclick="sortTable(2)" style="cursor: pointer; min-width: 150px;">
                            Name <i class="bi bi-arrow-down-up"></i>
                        </th>
                        <th style="min-width: 180px;">Email</th>
                        <th style="min-width: 120px;">Phone</th>
                        <th style="min-width: 120px;">Username</th>
                        <th style="min-width: 100px;">Role</th>
                        <th style="min-width: 100px;">Status</th>
                        <th style="min-width: 120px;">Position</th>
                        <th style="min-width: 100px;">Emp Type</th>
                        <th style="min-width: 100px;">Start Date</th>
                        <th style="min-width: 100px;">DOB</th>
                        <th style="min-width: 80px;">Marital</th>
                        <th style="min-width: 120px;">Nationality</th>
                        <th style="min-width: 120px;">Birth Place</th>
                        <th style="min-width: 150px;">National ID</th>
                        <th style="min-width: 150px;">Address</th>
                        <th style="min-width: 120px;">Office</th>
                        <th style="min-width: 90px;">Salary/Min</th>
                        <th style="min-width: 100px;">Monthly</th>
                        <th style="min-width: 60px;">Curr</th>
                        <th style="min-width: 70px;">Break</th>
                        <th style="min-width: 70px;">Days</th>
                        <th style="min-width: 120px;">Sheet ID</th>
                        <th style="min-width: 150px;">Sheet Link</th>
                        <th style="min-width: 100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $counter = 1;
                    $admins->data_seek(0);
                    while($row = $admins->fetch_assoc()): 
                        $isOwnProfile = ($row['id'] == $currentUserId);
                        $status = $row['status'] ?? 'pending';
                        $statusIcon = '';
                        $statusColor = '';
                        
                        switch($status) {
                            case 'active':
                                $statusIcon = '✅';
                                $statusColor = 'btn-success';
                                break;
                            case 'deactive':
                                $statusIcon = '❌';
                                $statusColor = 'btn-danger';
                                break;
                            case 'pending':
                            default:
                                $statusIcon = '⏳';
                                $statusColor = 'btn-warning';
                                break;
                        }
                    ?>
                    <tr data-role="<?= htmlspecialchars($row['role'] ?? 'staff') ?>" data-status="<?= $status ?>">
                        <form method="post" class="staff-form" data-id="<?= $row['id'] ?>">
                            <td class="text-center"><?= $counter++ ?></td>
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            
                            <td class="text-center">
                                <?php if (!empty($row['profile_photo'])): ?>
                                <div class="profile-img-container">
                                    <img src="uploads/<?= htmlspecialchars($row['profile_photo'], ENT_QUOTES, 'UTF-8') ?>" 
                                         alt="Profile" class="profile-img">
                                </div>
                                <?php else: ?>
                                <div class="profile-placeholder">
                                    <?= strtoupper(substr($row['first_name'] ?? $row['username'] ?? 'U', 0, 1)) ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <?php
                                $displayName = trim((string) ($row['full_name'] ?? ''));
                                if ($displayName === '') {
                                    $displayName = trim((string) (($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')));
                                }
                                if ($displayName === '') {
                                    $displayName = (string) ($row['username'] ?? '');
                                }
                                ?>
                                <?php if ($isSuperAdmin): ?>
                                <input type="text" name="full_name" class="form-control form-control-sm" required
                                       value="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="Full name" autocomplete="name">
                                <?php else: ?>
                                <?= htmlspecialchars($displayName !== '' ? $displayName : 'N/A', ENT_QUOTES, 'UTF-8') ?>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($isSuperAdmin): ?>
                                <input type="email" name="email" class="form-control form-control-sm" required
                                       value="<?= htmlspecialchars($row['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="email@…" autocomplete="email">
                                <?php else: ?>
                                <?= htmlspecialchars($row['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($isSuperAdmin): ?>
                                <input type="text" name="phone_number" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($row['phone_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="Phone" inputmode="tel" autocomplete="tel">
                                <?php else: ?>
                                <?= htmlspecialchars($row['phone_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($isSuperAdmin): ?>
                                <input type="text" name="username" class="form-control form-control-sm" required
                                       value="<?= htmlspecialchars($row['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="Username" autocomplete="username">
                                <?php else: ?>
                                <?= htmlspecialchars($row['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Role Selector -->
                            <td>
                                <?php if ($isSuperAdmin): ?>
                                <?php if (($row['role'] ?? '') === 'superadmin'): ?>
                                <input type="hidden" name="role" value="superadmin">
                                <select class="form-select form-select-sm" disabled aria-readonly="true">
                                    <option value="superadmin" selected>Super</option>
                                </select>
                                <?php else: ?>
                                <select name="role" class="form-select form-select-sm">
                                    <option value="superadmin">Super</option>
                                    <option value="admin" <?= ($row['role'] ?? '') == 'admin' ? 'selected' : '' ?>>Admin</option>
                                    <option value="staff" <?= ($row['role'] ?? '') == 'staff' ? 'selected' : '' ?>>Staff</option>
                                    <option value="agent" <?= ($row['role'] ?? '') == 'agent' ? 'selected' : '' ?>>Agent</option>
                                </select>
                                <?php endif; ?>
                                <?php else: ?>
                                <span class="role-badge role-<?= $row['role'] ?? 'staff' ?>">
                                    <?= ucfirst($row['role'] ?? 'Staff') ?>
                                </span>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Status Column (same form as save — no nested forms) -->
                            <td>
                                <?php if ($isSuperAdmin): ?>
                                    <input type="hidden" name="status_user_id" value="<?= $row['id'] ?>">
                                    <button type="submit" name="toggle_status" value="1"
                                            class="btn <?= $statusColor ?> status-btn"
                                            data-tooltip="Click to change status"
                                            onclick="return confirm('Change this account status?')"
                                            style="min-width: 90px; display: inline-flex; align-items: center; gap: 5px; justify-content: center;">
                                        <?= $statusIcon ?> <?= ucfirst($status) ?>
                                    </button>
                                <?php else: ?>
                                <span class="status-badge <?= $statusColor ?>" style="background: <?= $status == 'active' ? '#d4edda' : ($status == 'deactive' ? '#f8d7da' : '#fff3cd') ?>; color: <?= $status == 'active' ? '#155724' : ($status == 'deactive' ? '#721c24' : '#856404') ?>">
                                    <?= $statusIcon ?> <?= ucfirst($status) ?>
                                </span>
                                <?php endif; ?>
                            </td>
                            
                            <td><input name="position" value="<?= htmlspecialchars($row['position'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                       class="form-control form-control-sm" <?= !$isSuperAdmin ? 'readonly' : '' ?>></td>
                            
                            <td>
                                <select name="employment_type" class="form-select form-select-sm" <?= !$isSuperAdmin ? 'disabled' : '' ?>>
                                    <option value="">--</option>
                                    <option <?= ($row['employment_type'] ?? '') == 'Full-time' ? 'selected' : '' ?>>Full-time</option>
                                    <option <?= ($row['employment_type'] ?? '') == 'Part-time' ? 'selected' : '' ?>>Part-time</option>
                                    <option <?= ($row['employment_type'] ?? '') == 'Contract' ? 'selected' : '' ?>>Contract</option>
                                </select>
                            </td>
                            
                            <td><input type="date" name="employment_start_date" 
                                       value="<?= htmlspecialchars($row['employment_start_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                       class="form-control form-control-sm" <?= !$isSuperAdmin ? 'readonly' : '' ?>></td>
                            
                            <td><input type="date" name="date_of_birth" 
                                       value="<?= htmlspecialchars($row['date_of_birth'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                       class="form-control form-control-sm" <?= !$isSuperAdmin ? 'readonly' : '' ?>></td>
                            
                            <td>
                                <select name="marital_status" class="form-select form-select-sm" <?= !$isSuperAdmin ? 'disabled' : '' ?>>
                                    <option value="">--</option>
                                    <?php foreach(['Single','Married','Divorced','Widowed'] as $m): ?>
                                    <option <?= ($row['marital_status'] ?? '') == $m ? 'selected' : '' ?>><?= $m ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            
                            <td><input name="nationality" value="<?= htmlspecialchars($row['nationality'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                       class="form-control form-control-sm" <?= !$isSuperAdmin ? 'readonly' : '' ?>></td>
                            
                            <td><input name="place_of_birth" value="<?= htmlspecialchars($row['place_of_birth'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                       class="form-control form-control-sm" <?= !$isSuperAdmin ? 'readonly' : '' ?>></td>
                            
                            <td><input name="national_id" value="<?= htmlspecialchars($row['national_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                       class="form-control form-control-sm" <?= !$isSuperAdmin ? 'readonly' : '' ?>></td>
                            
                            <td><input name="address" value="<?= htmlspecialchars($row['address'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                       class="form-control form-control-sm" <?= !$isSuperAdmin ? 'readonly' : '' ?>></td>
                            
                            <td>
                                <select name="office_id" class="form-select form-select-sm" <?= !$isSuperAdmin ? 'disabled' : '' ?>>
                                    <option value="">--</option>
                                    <?php 
                                    $offices->data_seek(0); 
                                    while($o = $offices->fetch_assoc()): 
                                    ?>
                                    <option value="<?= $o['id'] ?>" <?= ($row['office_id'] ?? '') == $o['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($o['office_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </td>
                            
                            <td><input type="number" step="0.01" name="salary_per_minute" 
                                       value="<?= htmlspecialchars($row['salary_per_minute'] ?? 8.33) ?>" 
                                       class="form-control form-control-sm" <?= !$isSuperAdmin ? 'readonly' : '' ?>></td>
                            
                            <td><input type="number" step="0.01" name="monthly_salary" 
                                       value="<?= htmlspecialchars($row['monthly_salary'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                       class="form-control form-control-sm" <?= !$isSuperAdmin ? 'readonly' : '' ?>></td>
                            
                            <td>
                                <select name="salary_currency" class="form-select form-select-sm" <?= !$isSuperAdmin ? 'disabled' : '' ?>>
                                    <option <?= ($row['salary_currency'] ?? 'USD') == 'KES' ? 'selected' : '' ?>>KES</option>
                                    <option <?= ($row['salary_currency'] ?? 'USD') == 'RWF' ? 'selected' : '' ?>>RWF</option>
                                    <option <?= ($row['salary_currency'] ?? 'USD') == 'USD' ? 'selected' : '' ?>>USD</option>
                                </select>
                            </td>
                            
                            <td><input type="number" name="allowed_break_minutes" 
                                       value="<?= htmlspecialchars($row['allowed_break_minutes'] ?? 60) ?>" 
                                       class="form-control form-control-sm" <?= !$isSuperAdmin ? 'readonly' : '' ?>></td>
                            
                            <td><input type="number" name="work_days_per_week" 
                                       value="<?= htmlspecialchars($row['work_days_per_week'] ?? 6) ?>" 
                                       class="form-control form-control-sm" <?= !$isSuperAdmin ? 'readonly' : '' ?>></td>
                            
                            <td><input name="sheet_id" value="<?= htmlspecialchars($row['sheet_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                       class="form-control form-control-sm" <?= !$isSuperAdmin ? 'readonly' : '' ?>></td>
                            
                            <td><input name="sheet_link" value="<?= htmlspecialchars($row['sheet_link'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                       class="form-control form-control-sm" <?= !$isSuperAdmin ? 'readonly' : '' ?>></td>
                            
                            <td>
                                <div class="action-group">
                                    <?php if ($isSuperAdmin): ?>
                                    <button type="submit" name="update_staff" class="btn btn-success btn-sm" 
                                            onclick="return confirmUpdate()">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                    <?php if (!$isOwnProfile): ?>
                                    <a href="?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm" 
                                       onclick="return confirmDelete()">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php else: ?>
                                    <span class="text-muted" data-tooltip="View only">👁️</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </form>
                    </tr>
                    <?php endwhile; ?>
                    
                    <?php if ($admins->num_rows == 0): ?>
                    <tr>
                        <td colspan="26" class="text-center py-5">
                            <div class="empty-state">
                                <div class="empty-icon">👥</div>
                                <div class="empty-title">No Staff Members Found</div>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(function() {
    // Auto-hide alerts after 3 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 3000);
    
    // Show loading on form submit; ensure disabled selects post with the row
    $('.staff-form').on('submit', function() {
        $(this).find(':input:disabled').prop('disabled', false);
        $('#loadingOverlay').fadeIn();
        return true;
    });

    // Prevent double-click spam on status buttons
    $('.status-btn').on('click', function(e) {
        var $btn = $(this);
        if ($btn.data('clicked')) {
            e.preventDefault();
            return false;
        }
        $btn.data('clicked', true);
        var $form = $btn.closest('form.staff-form');
        if ($form.length) {
            $form.find(':input:disabled').prop('disabled', false);
        }
        setTimeout(function() {
            $btn.data('clicked', false);
        }, 1000);
    });
});

// Search functionality
document.getElementById('searchBox').addEventListener('keyup', function() {
    let value = this.value.toLowerCase();
    let rows = document.querySelectorAll('#staffTable tbody tr');
    
    rows.forEach(row => {
        let text = row.textContent.toLowerCase();
        row.style.display = text.includes(value) ? '' : 'none';
    });
});

// Filter by role
function filterByRole(role) {
    let rows = document.querySelectorAll('#staffTable tbody tr');
    
    rows.forEach(row => {
        if (role === 'all') {
            row.style.display = '';
        } else {
            let rowRole = row.getAttribute('data-role');
            row.style.display = rowRole === role ? '' : 'none';
        }
    });
}

// Sort table by column
function sortTable(columnIndex) {
    let table = document.getElementById('staffTable');
    let tbody = table.querySelector('tbody');
    let rows = Array.from(tbody.querySelectorAll('tr'));
    
    // Toggle sort order
    if (!table.sortDirection) {
        table.sortDirection = {};
    }
    table.sortDirection[columnIndex] = !table.sortDirection[columnIndex];
    
    rows.sort((a, b) => {
        let aText = a.cells[columnIndex].textContent.trim().toLowerCase();
        let bText = b.cells[columnIndex].textContent.trim().toLowerCase();
        
        if (table.sortDirection[columnIndex]) {
            return aText.localeCompare(bText);
        } else {
            return bText.localeCompare(aText);
        }
    });
    
    // Reorder rows
    rows.forEach(row => tbody.appendChild(row));
}

// Confirm delete
function confirmDelete() {
    return confirm('⚠️ Delete this staff member?');
}

// Confirm update
function confirmUpdate() {
    return confirm('Save changes?');
}

// Keyboard shortcuts
$(document).on('keydown', function(e) {
    // Ctrl+F - Focus search
    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
        e.preventDefault();
        document.getElementById('searchBox').focus();
    }
});
</script>

</body>
</html>