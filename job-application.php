<?php
/**
 * job-application.php
 * Job Application Form for Xander Global Scholars
 * Compatible with save_job_application.php v3.0.0
 */

// ============================================
// SECURITY & SESSION INITIALIZATION
// ============================================

// Start session with simple configuration for localhost
session_name('XGS_JOB_FORM'); // Must match save_job_application.php
session_start([
    'cookie_lifetime' => 7200, // 2 hours
    'cookie_secure' => false, // Set to false for localhost/XAMPP
    'cookie_httponly' => true,
    'use_strict_mode' => true
]);

// Check if session ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Invalid access. Please use the provided application link.');
}

// Validate and sanitize user ID
$user_id = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $_GET['id']);

// Store in session (must match save_job_application.php)
$_SESSION['user_id'] = $user_id;
$_SESSION['session_start'] = time();

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Include database connection
require_once 'db.php';

// Check if already applied
$already_applied = false;
$check_sql = "SELECT id FROM job_applications WHERE user_id = ?";
$check_stmt = $conn->prepare($check_sql);
if ($check_stmt) {
    $check_stmt->bind_param("s", $user_id);
    $check_stmt->execute();
    $check_stmt->store_result();
    if ($check_stmt->num_rows > 0) {
        $already_applied = true;
    }
    $check_stmt->close();
}

// If already applied, show message
if ($already_applied) {
    $redirect_url = "already-applied.php?id=" . urlencode($user_id);
    header("Location: $redirect_url");
    exit;
}

/** Pre-screening → job application handoff (superadmin Apply now, work abroad). */
$prescreenHandoffForJs = null;
if (!empty($_GET['from_prescreen']) && !empty($_SESSION['xander_prescreen_handoff'])) {
    $handoff = $_SESSION['xander_prescreen_handoff'];
    $reqId = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) ($_GET['id'] ?? ''));
    if ($reqId !== '' && ($handoff['user_id'] ?? '') === $user_id && ($handoff['service_type'] ?? '') === 'work_abroad') {
        $_SESSION['user_id'] = $reqId;
        $prescreenHandoffForJs = [
            'docs' => $handoff['docs'] ?? [],
            'prefill' => $handoff['prefill'] ?? [],
            'hints' => $handoff['hints'] ?? [],
            'auto_run' => !empty($handoff['auto_run']),
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Application | Xander Global Scholars</title>
    
    <!-- CSS Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/css/intlTelInput.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-700: #374151;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background-color: #f8fafc;
            color: var(--gray-700);
            line-height: 1.6;
            min-height: 100vh;
        }
        
        .application-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .header-section {
            text-align: center;
            margin-bottom: 2rem;
            padding: 2rem 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .header-section h1 {
            font-size: clamp(1.8rem, 4vw, 2.5rem);
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .header-section .subtitle {
            font-size: clamp(0.9rem, 2vw, 1.1rem);
            opacity: 0.9;
            padding: 0 1rem;
        }
        
        .form-section {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--gray-200);
        }
        
        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--gray-100);
        }
        
        .section-icon {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: white;
            flex-shrink: 0;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-700);
            margin: 0;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .form-label.required::after {
            content: " *";
            color: var(--danger-color);
        }
        
        .form-control, .form-select {
            padding: 0.75rem 1rem;
            border: 2px solid var(--gray-300);
            border-radius: 8px;
            transition: all 0.3s ease;
            width: 100%;
            font-size: 1rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            outline: none;
        }
        
        .form-control.is-invalid, .form-select.is-invalid {
            border-color: var(--danger-color);
        }
        
        .invalid-feedback {
            font-size: 0.875rem;
            color: var(--danger-color);
            margin-top: 0.25rem;
        }
        
        .file-upload-area {
            border: 2px dashed var(--gray-300);
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            background: var(--gray-100);
            cursor: pointer;
            transition: all 0.3s ease;
            min-height: 140px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .file-upload-area:hover {
            border-color: var(--primary-color);
            background: rgba(37, 99, 235, 0.05);
        }
        
        .file-upload-area.dragover {
            border-color: var(--primary-color);
            background: rgba(37, 99, 235, 0.1);
        }
        
        .file-upload-icon {
            font-size: 1.75rem;
            color: var(--gray-400);
            margin-bottom: 0.75rem;
        }
        
        .file-preview-container {
            margin-top: 0.5rem;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .file-preview {
            background: var(--gray-100);
            border-radius: 6px;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid var(--gray-200);
        }
        
        .file-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex: 1;
            min-width: 0;
        }
        
        .file-icon {
            font-size: 1.25rem;
            color: var(--gray-500);
            flex-shrink: 0;
        }
        
        .file-name {
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex: 1;
            min-width: 0;
        }
        
        .file-size {
            font-size: 0.875rem;
            color: var(--gray-500);
            margin-top: 0.125rem;
        }
        
        .file-remove {
            background: none;
            border: none;
            color: var(--danger-color);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 4px;
            transition: background-color 0.2s;
            flex-shrink: 0;
        }
        
        .file-remove:hover {
            background: rgba(239, 68, 68, 0.1);
        }
        
        .btn-primary {
            background: var(--primary-color);
            border: none;
            padding: 0.875rem 2rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
            color: white;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }
        
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .alert {
            border-radius: 8px;
            border: none;
            padding: 1rem 1.25rem;
        }
        
        .session-timer {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            z-index: 1000;
            display: flex;
            align-items: center;
        }
        
        .job-submit-overlay {
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(3px);
            display: none;
            align-items: center;
            justify-content: center;
        }
        
        .job-submit-overlay.is-visible {
            display: flex;
        }
        
        .job-submit-box {
            background: #fff;
            border-radius: 14px;
            padding: 1.5rem 1.75rem;
            max-width: 320px;
            width: 90%;
            text-align: center;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.15);
        }
        
        .job-submit-spin {
            width: 40px;
            height: 40px;
            margin: 0 auto 12px;
            border: 3px solid #e2e8f0;
            border-top-color: var(--primary-color);
            border-radius: 50%;
            animation: jobSpin 0.75s linear infinite;
        }
        
        @keyframes jobSpin {
            to { transform: rotate(360deg); }
        }
        
        .validation-summary {
            display: none;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        /* Success Message Styles */
        .success-container {
            display: none;
            max-width: 800px;
            margin: 2rem auto;
            text-align: center;
        }
        
        .success-card {
            background: white;
            border-radius: 14px;
            padding: 2rem 1.5rem;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--gray-200);
        }
        
        .success-icon {
            width: 56px;
            height: 56px;
            background: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 1.5rem;
        }
        
        .success-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }
        
        .success-message {
            font-size: 1rem;
            color: var(--gray-500);
            margin-bottom: 1.25rem;
        }
        
        .reference-id {
            font-family: ui-monospace, 'Courier New', monospace;
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--primary-color);
            letter-spacing: 0.02em;
            margin: 0.25rem 0 1rem;
            word-break: break-all;
        }
        
        @media (max-width: 768px) {
            .application-container {
                padding: 0.5rem;
            }
            
            .header-section {
                padding: 1.5rem 1rem;
                margin-bottom: 1.5rem;
            }
            
            .form-section {
                padding: 1.25rem;
                margin-bottom: 1rem;
            }
            
            .file-upload-area {
                padding: 1.25rem;
                min-height: 130px;
            }
            
            .section-icon {
                width: 36px;
                height: 36px;
                margin-right: 0.75rem;
            }
            
            .section-title {
                font-size: 1.125rem;
            }
            
            .success-card {
                padding: 2rem 1rem;
            }
            
            .success-title {
                font-size: 1.5rem;
            }
            
            .reference-id {
                font-size: 1.25rem;
                padding: 0.5rem 1rem;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .btn-primary {
                width: 100%;
                padding: 0.875rem 1.5rem;
            }
            
            .session-timer {
                bottom: 10px;
                right: 10px;
                font-size: 0.75rem;
                padding: 0.375rem 0.75rem;
            }
        }
        
        @media (max-width: 576px) {
            .row.g-3, .row.g-4 {
                --bs-gutter-x: 1rem;
                --bs-gutter-y: 1rem;
            }
            
            .col-md-6, .col-md-6.col-lg-4 {
                padding-right: calc(var(--bs-gutter-x) * 0.5);
                padding-left: calc(var(--bs-gutter-x) * 0.5);
                margin-bottom: var(--bs-gutter-y);
            }
            
            .header-section h1 {
                font-size: 1.6rem;
            }
            
            .header-section .subtitle {
                font-size: 0.9rem;
            }
            
            .file-upload-icon {
                font-size: 1.5rem;
                margin-bottom: 0.5rem;
            }
            
            .file-upload-area h6 {
                font-size: 0.9rem;
                margin-bottom: 0.25rem;
            }
            
            .file-upload-area p {
                font-size: 0.8rem;
                margin-bottom: 0.5rem;
            }
            
            .file-upload-stats {
                font-size: 0.75rem;
            }
            
            .form-control, .form-select {
                padding: 0.625rem 0.875rem;
                font-size: 0.9375rem;
            }
        }
        
        /* Select2 Mobile Responsiveness */
        .select2-container--bootstrap-5 .select2-selection {
            min-height: calc(1.5em + 1.5rem + 4px);
            padding: 0.75rem 1rem;
            border: 2px solid var(--gray-300);
            border-radius: 8px;
        }
        
        .select2-container--bootstrap-5 .select2-selection:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        /* Intl Tel Input Mobile Responsiveness */
        .iti {
            width: 100%;
        }
        
        .iti__flag-container {
            padding: 0 8px;
        }
        
        .iti__selected-flag {
            padding: 0 12px;
        }
        
        /* Hide leave alert - removed the beforeunload event handler */
    </style>
</head>
<body>
    <!-- Session Timer -->
    <div class="session-timer" id="sessionTimer">
        <i class="fas fa-clock me-2"></i>Session: <span id="timer">59:59</span>
    </div>
    
    <!-- Submit overlay -->
    <div class="job-submit-overlay" id="jobSubmitOverlay" aria-live="polite">
        <div class="job-submit-box">
            <div class="job-submit-spin" aria-hidden="true"></div>
            <p class="fw-semibold mb-1" style="margin:0">Saving application</p>
            <p class="small text-muted mb-0">One moment…</p>
        </div>
    </div>

    <!-- Success (hidden until submit) -->
    <div class="success-container" id="successContainer">
        <div class="success-card">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h1 class="success-title">Thank you</h1>
            <p class="success-message">Your application was received. We will be in touch soon.</p>
            <p class="small text-muted mb-1">Reference</p>
            <div class="reference-id" id="referenceId">—</div>
            <p class="small text-muted mb-3">A confirmation email is on its way.</p>
            <button type="button" class="btn btn-primary px-4" id="submitAnotherBtn">Close</button>
        </div>
    </div>

    <!-- Database Warning -->
    <?php if ($conn->connect_error): ?>
    <div class="container mt-3">
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Note:</strong> System check unavailable. You can still submit your application.
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Main Application Container (Visible Initially) -->
    <div class="application-container" id="applicationContainer">
        <!-- Header -->
        <div class="header-section">
            <h1>Job Application</h1>
            <p class="subtitle">Apply for international jobs with guidance from <strong>Xander Global Scholars</strong>.</p>
            <div class="mt-3">
                <span class="badge bg-light text-dark me-2">
                    <i class="fas fa-shield-alt me-1"></i> Secure
                </span>
                <span class="badge bg-light text-dark">
                    <i class="fas fa-lock me-1"></i> Encrypted
                </span>
            </div>
        </div>
        
        <!-- Validation Summary -->
        <div class="validation-summary" id="validationSummary">
            <div class="d-flex align-items-start">
                <i class="fas fa-exclamation-circle text-danger me-2 mt-1"></i>
                <div>
                    <h6 class="mb-2">Please correct the following errors:</h6>
                    <ul id="validationList" class="mb-0 ps-3"></ul>
                </div>
            </div>
        </div>
        
        <?php require __DIR__ . '/includes/job_smart_autofill.php'; ?>

        <!-- Main Form -->
        <form id="jobForm" enctype="multipart/form-data" novalidate>
            <!-- CSRF Protection -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">
            
            <!-- Personal Information Section -->
            <section class="form-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <h2 class="section-title">Personal Information</h2>
                </div>
                
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" 
                               placeholder="Enter your first name" maxlength="100">
                        <div class="invalid-feedback">Please enter your first name</div>
                    </div>
                    
                    <div class="col-12 col-md-6">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" 
                               placeholder="Enter your last name" maxlength="100">
                        <div class="invalid-feedback">Please enter your last name</div>
                    </div>
                    
                    <div class="col-12 col-md-6">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="example@email.com" maxlength="150">
                        <div class="invalid-feedback">Please enter a valid email address</div>
                    </div>
                    
                    <div class="col-12 col-md-6">
                        <label for="phone" class="form-label">Phone Number</label>
                        <div class="form-text mb-2" style="font-size: 0.85rem;">Use the country flag dropdown, then your number. We store <strong>digits only</strong> (country code + number, no +) so WhatsApp can message you.</div>
                        <input type="tel" class="form-control" id="phone">
                        <input type="hidden" name="phone_area_code" id="phone_area_code">
                        <input type="hidden" name="phone_number" id="phone_number">
                        <div class="invalid-feedback">Please enter a valid phone number</div>
                        <div class="form-text">Include country code</div>
                    </div>
                </div>
            </section>
            
            <!-- Work Preference Section -->
            <section class="form-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-globe"></i>
                    </div>
                    <h2 class="section-title">Work Preference</h2>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <label for="work_country_id" class="form-label">Desired Work Country</label>
                        <select class="form-select country-select" id="work_country_id" name="work_country_id">
                            <option value="">Select a country...</option>
                            <!-- Countries loaded via JavaScript -->
                        </select>
                        <div id="prescreenWorkCountriesHint" class="alert alert-info mt-2 py-2 small d-none" role="status"></div>
                        <div class="invalid-feedback">Please select a work country</div>
                        <div class="form-text">We'll match you with opportunities in this country</div>
                    </div>
                </div>
            </section>
            
            <!-- Home Address Section -->
            <section class="form-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <h2 class="section-title">Home Address</h2>
                </div>
                
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label for="address_country_id" class="form-label">Country</label>
                        <select class="form-select country-select" id="address_country_id" name="address_country_id">
                            <option value="">Select your country...</option>
                            <!-- Countries loaded via JavaScript -->
                        </select>
                        <div class="invalid-feedback">Please select your country</div>
                    </div>
                    
                    <div class="col-12 col-md-6">
                        <label for="province_state" class="form-label">Province / State</label>
                        <input type="text" class="form-control" id="province_state" name="province_state" 
                               placeholder="Enter province or state" maxlength="120">
                        <div class="invalid-feedback">Please enter your province/state</div>
                    </div>
                    
                    <div class="col-12 col-md-6">
                        <label for="district" class="form-label">District</label>
                        <input type="text" class="form-control" id="district" name="district" 
                               placeholder="Enter district" maxlength="120">
                        <div class="invalid-feedback">Please enter your district</div>
                    </div>
                    
                    <div class="col-12 col-md-6">
                        <label for="sector" class="form-label">Sector</label>
                        <input type="text" class="form-control" id="sector" name="sector" 
                               placeholder="Enter sector" maxlength="120">
                        <div class="invalid-feedback">Please enter your sector</div>
                    </div>
                    
                    <div class="col-12 col-md-6">
                        <label for="cell_ward" class="form-label">Cell / Ward</label>
                        <input type="text" class="form-control" id="cell_ward" name="cell_ward" 
                               placeholder="Enter cell or ward" maxlength="120">
                        <div class="invalid-feedback">Please enter your cell/ward</div>
                    </div>
                    
                    <div class="col-12 col-md-6">
                        <label for="village" class="form-label">Village</label>
                        <input type="text" class="form-control" id="village" name="village" 
                               placeholder="Enter village" maxlength="120">
                        <div class="invalid-feedback">Please enter your village</div>
                    </div>
                </div>
            </section>
            
            <!-- Emergency Contact Section -->
            <section class="form-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <h2 class="section-title">Emergency Contact</h2>
                </div>
                
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label for="emergency_full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="emergency_full_name" name="emergency_full_name" 
                               placeholder="Enter full name" maxlength="150">
                        <div class="invalid-feedback">Please enter emergency contact name</div>
                    </div>
                    
                    <div class="col-12 col-md-6">
                        <label for="emergency_relationship" class="form-label">Relationship</label>
                        <input type="text" class="form-control" id="emergency_relationship" name="emergency_relationship" 
                               placeholder="e.g., Father, Sister, Spouse" maxlength="100">
                        <div class="invalid-feedback">Please enter relationship</div>
                    </div>
                    
                    <div class="col-12 col-md-6">
                        <label for="emergency_phone" class="form-label">Phone Number</label>
                        <div class="form-text mb-2" style="font-size: 0.85rem;">Same as above: country code + number, saved as digits only (no +) for WhatsApp.</div>
                        <input type="tel" class="form-control" id="emergency_phone">
                        <input type="hidden" name="emergency_area_code" id="emergency_area_code">
                        <input type="hidden" name="emergency_phone_number" id="emergency_phone_number">
                        <div class="invalid-feedback">Please enter a valid emergency phone number</div>
                    </div>
                    
                    <div class="col-12 col-md-6">
                        <label for="emergency_email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="emergency_email" name="emergency_email" 
                               placeholder="emergency@email.com" maxlength="150">
                        <div class="invalid-feedback">Please enter a valid emergency email</div>
                    </div>
                </div>
            </section>
            
            <!-- Documents Upload Section -->
            <section class="form-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-file-upload"></i>
                    </div>
                    <h2 class="section-title">Documents Upload</h2>
                </div>
                
                <div class="row g-3">
                    <!-- Passport -->
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="file-upload-area" onclick="document.getElementById('passport').click()">
                            <div class="file-upload-icon">
                                <i class="fas fa-passport"></i>
                            </div>
                            <h6>Passport <span class="text-danger">*</span></h6>
                            <p class="small mb-2">Clear scan of passport page</p>
                            <div class="file-upload-stats small">
                                <span class="me-3"><i class="fas fa-file"></i> PDF, JPG, PNG</span>
                                <span><i class="fas fa-weight-hanging"></i> 15MB max</span>
                            </div>
                            <input type="file" class="d-none" id="passport" name="passport" 
                                   accept=".pdf,.jpg,.jpeg,.png" data-max-size="15728640">
                        </div>
                        <div class="file-preview-container" id="passport-preview"></div>
                        <div class="invalid-feedback">Passport is required</div>
                    </div>
                    
                    <!-- Photo -->
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="file-upload-area" onclick="document.getElementById('photo').click()">
                            <div class="file-upload-icon">
                                <i class="fas fa-camera"></i>
                            </div>
                            <h6>Passport Photo <span class="text-danger">*</span></h6>
                            <p class="small mb-2">Recent passport-sized photo</p>
                            <div class="file-upload-stats small">
                                <span class="me-3"><i class="fas fa-file-image"></i> JPG, PNG</span>
                                <span><i class="fas fa-weight-hanging"></i> 15MB max</span>
                            </div>
                            <input type="file" class="d-none" id="photo" name="photo" 
                                   accept=".jpg,.jpeg,.png" data-max-size="15728640">
                        </div>
                        <div class="file-preview-container" id="photo-preview"></div>
                        <div class="invalid-feedback">Passport photo is required</div>
                    </div>
                    
                    <!-- National ID -->
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="file-upload-area" onclick="document.getElementById('national_id').click()">
                            <div class="file-upload-icon">
                                <i class="fas fa-id-card"></i>
                            </div>
                            <h6>National ID</h6>
                            <p class="small mb-2">Front and back if applicable</p>
                            <div class="file-upload-stats small">
                                <span class="me-3"><i class="fas fa-file"></i> PDF, JPG, PNG</span>
                                <span><i class="fas fa-weight-hanging"></i> 15MB max</span>
                            </div>
                            <input type="file" class="d-none" id="national_id" name="national_id" 
                                   accept=".pdf,.jpg,.jpeg,.png" data-max-size="15728640">
                        </div>
                        <div class="file-preview-container" id="national_id-preview"></div>
                    </div>
                    
                    <!-- CV -->
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="file-upload-area" onclick="document.getElementById('cv').click()">
                            <div class="file-upload-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <h6>Resume / CV</h6>
                            <p class="small mb-2">Your professional resume</p>
                            <div class="file-upload-stats small">
                                <span class="me-3"><i class="fas fa-file"></i> PDF, DOC, DOCX</span>
                                <span><i class="fas fa-weight-hanging"></i> 15MB max</span>
                            </div>
                            <input type="file" class="d-none" id="cv" name="cv" 
                                   accept=".pdf,.doc,.docx" data-max-size="15728640">
                        </div>
                        <div class="file-preview-container" id="cv-preview"></div>
                    </div>
                    
                    <!-- Academic Certificates -->
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="file-upload-area" onclick="document.getElementById('academic_certificates').click()">
                            <div class="file-upload-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <h6>Academic Certificates</h6>
                            <p class="small mb-2">Upload one certificate file</p>
                            <div class="file-upload-stats small">
                                <span class="me-3"><i class="fas fa-file"></i> PDF, JPG, PNG</span>
                                <span><i class="fas fa-weight-hanging"></i> 15MB max</span>
                            </div>
                            <input type="file" class="d-none" id="academic_certificates" name="academic_certificates" 
                                   accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" data-max-size="15728640">
                        </div>
                        <div class="file-preview-container" id="academic_certificates-preview"></div>
                    </div>
                    
                    <!-- Experience Letters -->
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="file-upload-area" onclick="document.getElementById('experience_letters').click()">
                            <div class="file-upload-icon">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            <h6>Experience Letters</h6>
                            <p class="small mb-2">Upload one experience letter</p>
                            <div class="file-upload-stats small">
                                <span class="me-3"><i class="fas fa-file"></i> PDF, DOC, JPG, PNG</span>
                                <span><i class="fas fa-weight-hanging"></i> 15MB max</span>
                            </div>
                            <input type="file" class="d-none" id="experience_letters" name="experience_letters" 
                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" data-max-size="15728640">
                        </div>
                        <div class="file-preview-container" id="experience_letters-preview"></div>
                    </div>
                    
                    <!-- Bank Statement -->
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="file-upload-area" onclick="document.getElementById('bank_statement').click()">
                            <div class="file-upload-icon">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                            <h6>Bank Statement</h6>
                            <p class="small mb-2">Last 3 months statement</p>
                            <div class="file-upload-stats small">
                                <span class="me-3"><i class="fas fa-file"></i> PDF, JPG, PNG</span>
                                <span><i class="fas fa-weight-hanging"></i> 15MB max</span>
                            </div>
                            <input type="file" class="d-none" id="bank_statement" name="bank_statement" 
                                   accept=".pdf,.jpg,.jpeg,.png" data-max-size="15728640">
                        </div>
                        <div class="file-preview-container" id="bank_statement-preview"></div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <div class="alert alert-info">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-info-circle me-3 mt-1"></i>
                            <div>
                                <strong>File Requirements:</strong>
                                <ul class="mb-0 mt-1">
                                    <li>Maximum file size: <strong>15MB per file</strong></li>
                                    <li>Required documents: <strong>Passport & Photo</strong></li>
                                    <li>All files must be single files (no zipped/combined files)</li>
                                    <li>Accepted formats: PDF, JPG, JPEG, PNG, DOC, DOCX</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Submit Button -->
            <div class="d-flex justify-content-between align-items-center mt-4 py-3">
                <div class="form-text">
                    <i class="fas fa-shield-alt me-1"></i> Your data is protected with SSL encryption
                </div>
                <button type="submit" class="btn btn-primary btn-lg px-4" id="submitBtn">
                    <i class="fas fa-paper-plane me-2"></i> Submit Application
                </button>
            </div>
        </form>
    </div>
    
    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/intlTelInput.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
    // Application configuration
    const CONFIG = {
        userId: '<?php echo $user_id; ?>',
        csrfToken: '<?php echo $_SESSION['csrf_token']; ?>',
        endpoints: {
            countries: 'getCountries.php',
            save: 'save_job_application.php',
            upload: 'upload_temp_file.php',
            autofill: 'job_ai_autofill.php'
        },
        maxFileSize: 15728640, // 15MB in bytes
        sessionTimeout: 7200, // 2 hours in seconds
        allowedExtensions: ['.pdf', '.jpg', '.jpeg', '.png', '.doc', '.docx'],
        fileIcons: {
            'pdf': 'fas fa-file-pdf text-danger',
            'jpg': 'fas fa-file-image text-success',
            'jpeg': 'fas fa-file-image text-success',
            'png': 'fas fa-file-image text-success',
            'doc': 'fas fa-file-word text-primary',
            'docx': 'fas fa-file-word text-primary'
        }
    };
    
    // Application state
    let state = {
        phoneInput: null,
        emergencyPhoneInput: null,
        countries: [],
        uploadedFiles: {},
        isSubmitting: false,
        sessionTimer: null
    };
    
    // Initialize application
    $(document).ready(function() {
        console.log('Initializing job application form...');
        
        // Initialize phone inputs
        initPhoneInputs();
        
        // Load countries
        loadCountries();
        
        // Initialize file upload handlers
        initFileUploads();
        
        // Initialize form validation
        initFormValidation();
        
        // Initialize session timer
        initSessionTimer();
        
        // Initialize Select2 for country selects
        $('.country-select').select2({
            theme: 'bootstrap-5',
            placeholder: 'Select a country...',
            allowClear: false,
            width: '100%'
        });
        
        // Initialize success page buttons
        $('#submitAnotherBtn').on('click', function() {
            location.reload();
        });
        
        // Show welcome message
        showToast('Fill in what you can — all fields are optional. You may submit with missing details.', 'info', 6000);
    });
    
    // Initialize phone inputs
    function initPhoneInputs() {
        const phoneInput = document.querySelector("#phone");
        const emergencyPhoneInput = document.querySelector("#emergency_phone");
        
        if (phoneInput) {
            state.phoneInput = window.intlTelInput(phoneInput, {
                initialCountry: "auto",
                geoIpLookup: function(callback) {
                    fetch('https://ipapi.co/json/')
                        .then(res => res.json())
                        .then(data => {
                            const countryCode = data.country_code ? data.country_code : "us";
                            callback(countryCode);
                        })
                        .catch(() => callback("us"));
                },
                utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/utils.js",
                separateDialCode: true,
                preferredCountries: ['us', 'gb', 'ca', 'au']
            });
        }
        
        if (emergencyPhoneInput) {
            state.emergencyPhoneInput = window.intlTelInput(emergencyPhoneInput, {
                initialCountry: "auto",
                geoIpLookup: function(callback) {
                    fetch('https://ipapi.co/json/')
                        .then(res => res.json())
                        .then(data => {
                            const countryCode = data.country_code ? data.country_code : "us";
                            callback(countryCode);
                        })
                        .catch(() => callback("us"));
                },
                utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/utils.js",
                separateDialCode: true,
                preferredCountries: ['us', 'gb', 'ca', 'au']
            });
        }
    }
    
    // Load countries from API
    async function loadCountries() {
        try {
            const response = await fetch(CONFIG.endpoints.countries);
            if (response.ok) {
                const countries = await response.json();
                state.countries = countries;
                
                // Populate country selects
                populateCountrySelect('work_country_id', countries);
                populateCountrySelect('address_country_id', countries);
                
                console.log(`Loaded ${countries.length} countries`);
            } else {
                throw new Error('Failed to load countries');
            }
        } catch (error) {
            console.error('Error loading countries:', error);
            // Fallback to default countries
            const defaultCountries = [
                {id: 73, name: 'Spain'},
                {id: 117, name: 'Zambia'},
                {id: 163, name: 'Rwanda'},
                {id: 176, name: 'South Africa'}
            ];
            populateCountrySelect('work_country_id', defaultCountries);
            populateCountrySelect('address_country_id', defaultCountries);
        }
    }
    
    // Populate country select
    function populateCountrySelect(selectId, countries) {
        const select = $(`#${selectId}`);
        if (select.length) {
            // Clear existing options
            select.empty();
            select.append('<option value="">Select a country...</option>');
            
            // Sort countries alphabetically
            countries.sort((a, b) => a.name.localeCompare(b.name));
            
            // Add countries
            countries.forEach(country => {
                select.append(`<option value="${country.id}">${country.name}</option>`);
            });
            
            // Refresh Select2
            select.trigger('change.select2');
        }
    }
    
    // Initialize file uploads
    function initFileUploads() {
        // Add change event to all file inputs
        $('input[type="file"]').on('change', function(e) {
            handleFileUpload(this);
        });
        
        // Setup drag and drop
        $('.file-upload-area').each(function() {
            const area = $(this);
            const input = area.siblings('input[type="file"]');
            
            area.on('dragover', function(e) {
                e.preventDefault();
                area.addClass('dragover');
            });
            
            area.on('dragleave', function(e) {
                e.preventDefault();
                area.removeClass('dragover');
            });
            
            area.on('drop', function(e) {
                e.preventDefault();
                area.removeClass('dragover');
                
                if (e.originalEvent.dataTransfer.files.length) {
                    const file = e.originalEvent.dataTransfer.files[0];
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    input[0].files = dataTransfer.files;
                    
                    // Trigger change event
                    input.trigger('change');
                }
            });
        });
    }
    
async function handleFileUpload(input) {
    const file = input.files[0];
    if (!file) return;

    const validation = validateFile(file);
    if (!validation.valid) {
        showToast(validation.message, 'error');
        input.value = '';
        return;
    }

    createFilePreview(file, input.id);

    const formData = new FormData();
    formData.append('file', file);
    formData.append('field', input.id);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', CONFIG.endpoints.upload, true);

    xhr.upload.onprogress = function () {
        /* progress UI optional — upload is usually quick */
    };

    xhr.onload = function () {
        try {
            const res = JSON.parse(xhr.responseText);
            if (res.status === 'success') {
                state.uploadedFiles[input.id] = res.path;
                showToast(`${input.id} uploaded`, 'success', 2000);
            } else {
                throw new Error(res.error || 'Upload failed');
            }
        } catch (err) {
            showToast(err.message, 'error');
            input.value = '';
        }
    };

    xhr.onerror = function () {
        showToast('Upload failed. Check connection.', 'error');
        input.value = '';
    };

    xhr.send(formData);
}

    
    // Validate file
    function validateFile(file) {
        // Check file size
        if (file.size > CONFIG.maxFileSize) {
            return {
                valid: false,
                message: `File "${file.name}" exceeds 15MB limit`
            };
        }
        
        // Check file extension
        const extension = '.' + file.name.split('.').pop().toLowerCase();
        if (!CONFIG.allowedExtensions.includes(extension)) {
            return {
                valid: false,
                message: `File type not supported for "${file.name}". Allowed: ${CONFIG.allowedExtensions.join(', ')}`
            };
        }
        
        return { valid: true, message: 'File is valid' };
    }
    
    // Create file preview
    function createFilePreview(file, inputId) {
        const extension = file.name.split('.').pop().toLowerCase();
        const iconClass = CONFIG.fileIcons[extension] || 'fas fa-file text-secondary';
        const previewId = `${inputId}-preview`;
        
        // Format file size
        const fileSize = formatFileSize(file.size);
        
        // Create preview HTML
        const previewHtml = `
            <div class="file-preview">
                <div class="file-info">
                    <i class="${iconClass} file-icon"></i>
                    <div class="flex-grow-1">
                        <div class="file-name">${file.name}</div>
                        <div class="file-size">${fileSize}</div>
                    </div>
                </div>
                <button type="button" class="file-remove" onclick="removeFile('${inputId}')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        $(`#${previewId}`).html(previewHtml);
    }
    
    // Remove file
    function removeFile(inputId) {
        const input = document.getElementById(inputId);
        if (input) {
            input.value = '';
            $(`#${inputId}-preview`).empty();
            delete state.uploadedFiles[inputId];
            showToast('File removed', 'info', 2000);
        }
    }
    
    // Format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Initialize form validation
    function initFormValidation() {
        const form = $('#jobForm');
        
        // Real-time validation
        form.find('input, select').on('input change', function() {
            validateField($(this));
        });
        
        // Form submission
        form.on('submit', function(e) {
            e.preventDefault();
            
            if (state.isSubmitting) {
                showToast('Please wait, submission in progress...', 'warning');
                return;
            }
            
            submitForm({ lenient: true });
        });
    }
    
    // Validate field
    function validateField(field) {
        const value = field.val() ? field.val().trim() : '';
        const isRequired = field.prop('required');
        const type = field.attr('type');
        const name = field.attr('name');
        
        // Clear previous validation
        field.removeClass('is-invalid is-valid');
        
        // field validation
        if (isRequired && !value) {
            field.addClass('is-invalid');
            return false;
        }
        
        // Email validation
        if (type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                field.addClass('is-invalid');
                return false;
            }
        }
        
      // Phone validation (intl-tel-input based)
const fieldId = field.attr('id');

if ((fieldId === 'phone' || fieldId === 'emergency_phone') && value) {
    const itiInstance =
        fieldId === 'phone'
            ? state.phoneInput
            : state.emergencyPhoneInput;

    // intl-tel-input instance must exist and be valid
    if (!itiInstance || !itiInstance.isValidNumber()) {
        field.addClass('is-invalid');
        return false;
    }
}

        
        // File validation
        if (type === 'file' && isRequired) {
            const fileInput = document.getElementById(field.attr('id'));
            if (!fileInput.files || fileInput.files.length === 0) {
                field.addClass('is-invalid');
                return false;
            }
        }
        
        // If we get here, field is valid
        if (value) {
            field.addClass('is-valid');
        }
        
        return true;
    }
    
    // Validate only fields that have values (nothing is required).
    function validateForm() {
        let isValid = true;
        const errors = [];
        
        $('#jobForm').find('input, select').each(function() {
            const $f = $(this);
            const val = $f.val() ? String($f.val()).trim() : '';
            if (val === '') {
                return;
            }
            if (!validateField($f)) {
                isValid = false;
                const label = $f.closest('.col-md-6, .col-12, .mb-3').find('.form-label').first().text().replace(/\*/g, '').trim()
                    || $f.attr('name') || $f.attr('id') || 'Field';
                errors.push('Please check ' + label);
            }
        });
        
        const phoneVal = $('#phone').val() ? String($('#phone').val()).trim() : '';
        if (phoneVal && state.phoneInput && !state.phoneInput.isValidNumber()) {
            isValid = false;
            errors.push('Please enter a valid phone number');
        }
        
        const emVal = $('#emergency_phone').val() ? String($('#emergency_phone').val()).trim() : '';
        if (emVal && state.emergencyPhoneInput && !state.emergencyPhoneInput.isValidNumber()) {
            isValid = false;
            errors.push('Please enter a valid emergency phone number');
        }
        
        if (!isValid) {
            showValidationErrors(errors);
            showToast('Please correct the highlighted fields', 'error');
        }
        
        return isValid;
    }
    
    // Show validation errors
    function showValidationErrors(errors) {
        const validationList = $('#validationList');
        validationList.empty();
        
        errors.forEach(error => {
            validationList.append(`<li>${error}</li>`);
        });
        
        $('#validationSummary').show();
        
        // Scroll to first error
        const firstError = $('.is-invalid').first();
        if (firstError.length) {
            $('html, body').animate({
                scrollTop: firstError.offset().top - 100
            }, 500);
            firstError.focus();
        }
    }
    
    // Show success message
    function showSuccessMessage(referenceNumber) {
        // Hide form container
        $('#applicationContainer').hide();
        $('#sessionTimer').hide();
        
        // Update reference ID
        $('#referenceId').text(referenceNumber);
        
        // Show success container
        $('#successContainer').show();
        
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    
    // Submit form
    async function submitForm(opts) {
        opts = opts || {};
        const lenient = opts.lenient !== false;
        if (!lenient && typeof validateForm === 'function' && !validateForm()) {
            return false;
        }
        if (lenient && typeof validateForm === 'function' && !validateForm()) {
            return false;
        }

        // Update UI
        state.isSubmitting = true;
        $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Submitting...');
        document.getElementById('jobSubmitOverlay')?.classList.add('is-visible');
try {
            // Prepare form data
           const formData = new FormData();

// Append normal form fields ONLY
$('#jobForm')
  .serializeArray()
  .forEach(item => formData.append(item.name, item.value));

// Append uploaded file paths
Object.keys(state.uploadedFiles).forEach(field => {
    formData.append(field, state.uploadedFiles[field]);
});

            
            // WhatsApp-ready: digits only, no + (country code in phone_area_code, national in phone_number)
            function jobPhoneParts(iti) {
                if (!iti || !iti.isValidNumber()) return null;
                const fullDigits = iti.getNumber().replace(/\D/g, '');
                const dial = String(iti.getSelectedCountryData().dialCode || '');
                let national = (dial && fullDigits.startsWith(dial)) ? fullDigits.slice(dial.length) : fullDigits;
                national = national.replace(/^0+/, '');
                return { dial, national };
            }
            const mainP = jobPhoneParts(state.phoneInput);
            if (mainP && mainP.dial) {
                formData.set('phone_area_code', mainP.dial);
                formData.set('phone_number', mainP.national);
            } else if (lenient) {
                formData.set('phone_area_code', formData.get('phone_area_code') || '');
                formData.set('phone_number', formData.get('phone_number') || '');
            }
            const emP = jobPhoneParts(state.emergencyPhoneInput);
            if (emP && emP.dial) {
                formData.set('emergency_area_code', emP.dial);
                formData.set('emergency_phone_number', emP.national);
            } else if (lenient) {
                formData.set('emergency_area_code', formData.get('emergency_area_code') || '');
                formData.set('emergency_phone_number', formData.get('emergency_phone_number') || '');
            }

            if (lenient) {
                formData.append('lenient_submit', '1');
            }
            
            // Add debug flag
            if (new URLSearchParams(window.location.search).has('debug')) {
                formData.append('debug', 'true');
            }
// Submit to server
            const response = await fetch(CONFIG.endpoints.save, {
                method: 'POST',
                body: formData
            });
let result;
try {
    result = await response.json();
} catch (e) {
    throw new Error('Invalid server response');
}

            
            if (response.ok && result.status === 'success') {
                document.getElementById('jobSubmitOverlay')?.classList.remove('is-visible');
                const referenceNumber = result.data?.reference_number || generateReferenceNumber();
                showSuccessMessage(referenceNumber);
                return true;
                
            } else {
                // Error
                document.getElementById('jobSubmitOverlay')?.classList.remove('is-visible');
                
                if (response.status === 409) {
                    // Duplicate submission
                    showToast(result.message || 'You have already submitted an application', 'error');
                    setTimeout(() => {
                        window.location.href = `already-applied.php?id=${CONFIG.userId}`;
                    }, 3000);
                } else if (response.status === 422) {
                    // Validation errors
                    showValidationErrors(result.data?.errors || [result.message]);
                    showToast('Please correct the errors below', 'error');
                } else {
                    // Other errors
                    showToast(result.message || 'Submission failed. Please try again.', 'error');
                }
                return false;
            }
            
        } catch (error) {
            // Network error
            document.getElementById('jobSubmitOverlay')?.classList.remove('is-visible');
            showToast('Network error. Please check your connection and try again.', 'error');
            console.error('Submission error:', error);
            return false;
            
        } finally {
            state.isSubmitting = false;
            $('#submitBtn')
                .prop('disabled', false)
                .html('<i class="fas fa-paper-plane me-2"></i> Submit Application');
        }
    }
    
    window.submitForm = submitForm;
    
    // Generate reference number
    function generateReferenceNumber() {
        const timestamp = Date.now();
        const random = Math.floor(Math.random() * 10000);
        return `XGS-${timestamp}-${random}`;
    }
    
    // Initialize session timer
    function initSessionTimer() {
        let seconds = CONFIG.sessionTimeout;
        
        function updateTimer() {
            if (seconds <= 0) {
                clearInterval(state.sessionTimer);
                $('#sessionTimer').html('<i class="fas fa-clock me-2"></i>Session Expired');
                showToast('Your session has expired. Please refresh the page.', 'error', 10000);
                return;
            }
            
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = seconds % 60;
            const timeString = `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
            
            $('#timer').text(timeString);
            seconds--;
            
            // Warn at 5 minutes
            if (seconds === 300) {
                showToast('Your session will expire in 5 minutes', 'warning', 5000);
            }
        }
        
        updateTimer();
        state.sessionTimer = setInterval(updateTimer, 1000);
    }
    
    // Show toast message
    function showToast(message, type = 'info', duration = 3000) {
        // Remove existing toasts
        $('.toast').remove();
        
        // Create toast
        const toastId = 'toast-' + Date.now();
        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-bg-${type} border-0 position-fixed bottom-0 end-0 m-3" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        $('body').append(toastHtml);
        
        // Show toast
        const toast = new bootstrap.Toast(document.getElementById(toastId), {
            autohide: duration > 0,
            delay: duration
        });
        toast.show();
        
        // Remove after hiding
        $(`#${toastId}`).on('hidden.bs.toast', function() {
            $(this).remove();
        });
    }
    </script>
</body>
</html>