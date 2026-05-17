<?php
// job_applicants_dashboard.php
// Clean Dashboard for Xander Global Scholars Job Applicants

// Require database configuration
require_once 'db.php';

session_start();
require_once __DIR__ . '/helpers/role.php';
require_once __DIR__ . '/helpers/job_application_status.php';
require_once __DIR__ . '/helpers/job_application_delete.php';

xander_ensure_job_applications_process_status_column($conn);

$sessionRole = isset($_SESSION['role']) ? trim((string) $_SESSION['role']) : '';
$dbRole = '';
$adminPk = 0;
if (!empty($_SESSION['id'])) {
    $adminPk = (int) $_SESSION['id'];
} elseif (!empty($_SESSION['admin_id'])) {
    $adminPk = (int) $_SESSION['admin_id'];
}
if ($adminPk > 0) {
    $stAd = $conn->prepare('SELECT role FROM admins WHERE id = ? LIMIT 1');
    if ($stAd) {
        $stAd->bind_param('i', $adminPk);
        $stAd->execute();
        $rowAd = $stAd->get_result()->fetch_assoc();
        $stAd->close();
        if ($rowAd) {
            $dbRole = trim((string) ($rowAd['role'] ?? ''));
        }
    }
}
$canEditJobProcessStatus = xander_is_superadmin_role($dbRole) || xander_is_superadmin_role($sessionRole);

$JOB_PROCESS_STATUSES = xander_job_application_process_statuses();
$JOB_PROCESS_ORDER = xander_job_application_status_keys_in_order();

// Fetch all applicants (live search filters in the browser)
$sql = "SELECT ja.*, 
               GROUP_CONCAT(CONCAT_WS(':', jd.document_type, jd.file_path, jd.uploaded_at, jd.id) SEPARATOR '|') as documents
        FROM job_applications ja
        LEFT JOIN job_documents jd ON ja.user_id = jd.user_id
        GROUP BY ja.id
        ORDER BY ja.created_at DESC";

// Prepare and execute query
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $applicants = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    die("Error preparing query: " . $conn->error);
}

// Xander Color Codes
$appRoot = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

$colors = [
    'navy' => '#012F6B',
    'secondary_blue' => '#254D81',
    'dark_blue' => '#002765',
    'gold' => '#F2A65A',
    'white' => '#FFFFFF',
    'light_gray' => '#F8F9FA',
    'border_gray' => '#E0E0E0'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Applicants - Xander Global Scholars</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        body {
            background: linear-gradient(165deg, #eef2f7 0%, <?= $colors['light_gray'] ?> 45%, #f1f5f9 100%);
            color: <?= $colors['navy'] ?>;
            min-height: 100vh;
        }

        .dashboard-container {
            padding: 0;
            max-width: 1520px;
            margin: 0 auto;
        }

        .ja-sticky-toolbar {
            position: sticky;
            top: 0;
            z-index: 300;
            padding: 18px 24px 14px;
            background: rgba(248, 249, 250, 0.9);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(1, 47, 107, 0.07);
            box-shadow: 0 8px 32px rgba(1, 39, 101, 0.06);
        }

        .ja-main {
            padding: 12px 24px 48px;
        }

        /* Dashboard Header */
        .dashboard-header {
            background: <?= $colors['white'] ?>;
            padding: 22px 24px;
            border-radius: 16px;
            margin-bottom: 14px;
            box-shadow: 0 2px 8px rgba(1, 47, 107, 0.06), 0 12px 40px rgba(1, 47, 107, 0.06);
            border: 1px solid rgba(1, 47, 107, 0.06);
            border-left: 4px solid <?= $colors['gold'] ?>;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header-title h1 {
            color: <?= $colors['navy'] ?>;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-title h1 i {
            color: <?= $colors['gold'] ?>;
        }

        .header-title p {
            color: #666;
            font-size: 0.9rem;
            margin-top: 5px;
        }

        .header-stats {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .applicant-count {
            background: <?= $colors['navy'] ?>;
            color: <?= $colors['white'] ?>;
            padding: 8px 15px;
            border-radius: 6px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .export-btn {
            background: <?= $colors['gold'] ?>;
            color: <?= $colors['navy'] ?>;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .export-btn:hover {
            background: #e69542;
            transform: translateY(-2px);
        }

        /* Search Container */
        .search-container {
            background: <?= $colors['white'] ?>;
            padding: 16px 20px;
            border-radius: 14px;
            margin-bottom: 0;
            box-shadow: 0 2px 8px rgba(1, 47, 107, 0.05);
            border: 1px solid rgba(1, 47, 107, 0.06);
        }

        .search-form {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 12px 20px;
            border: 2px solid <?= $colors['border_gray'] ?>;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .search-input:focus {
            outline: none;
            border-color: <?= $colors['gold'] ?>;
            box-shadow: 0 0 0 3px rgba(242, 166, 90, 0.2);
        }

        .search-btn {
            background: <?= $colors['navy'] ?>;
            color: <?= $colors['white'] ?>;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .search-btn:hover {
            background: <?= $colors['dark_blue'] ?>;
        }

        .clear-btn {
            background: <?= $colors['secondary_blue'] ?>;
            color: <?= $colors['white'] ?>;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .clear-btn:hover {
            background: <?= $colors['navy'] ?>;
        }

        /* Main Content - Cards Grid */
        .applicants-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .applicants-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Applicant Card */
        .applicant-card {
            background: <?= $colors['white'] ?>;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(1, 47, 107, 0.06), 0 16px 48px rgba(1, 47, 107, 0.08);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            border: 1px solid rgba(1, 47, 107, 0.08);
            display: flex;
            flex-direction: column;
        }

        .applicant-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(1, 47, 107, 0.08), 0 24px 56px rgba(1, 47, 107, 0.12);
        }

        .applicant-card.is-removing {
            opacity: 0;
            transform: scale(0.96);
            pointer-events: none;
            transition: opacity 0.35s ease, transform 0.35s ease;
        }

        .card-header {
            background: linear-gradient(135deg, <?= $colors['navy'] ?> 0%, <?= $colors['secondary_blue'] ?> 55%, <?= $colors['dark_blue'] ?> 100%);
            color: <?= $colors['white'] ?>;
            padding: 18px 18px 16px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .card-header-main {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
            flex: 1;
        }

        .applicant-avatar {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.15);
            border: 2px solid rgba(242, 166, 90, 0.45);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.95rem;
            letter-spacing: 0.02em;
            flex-shrink: 0;
        }

        .card-header-text {
            min-width: 0;
        }

        .applicant-name {
            font-size: 1.15rem;
            font-weight: 700;
            margin-bottom: 6px;
            line-height: 1.25;
            letter-spacing: -0.02em;
        }

        .applicant-id {
            font-size: 0.72rem;
            opacity: 0.88;
            font-family: ui-monospace, 'Cascadia Code', monospace;
            background: rgba(0, 0, 0, 0.2);
            padding: 4px 10px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .card-delete-btn {
            flex-shrink: 0;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            background: rgba(220, 38, 38, 0.2);
            color: #fecaca;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s, transform 0.2s, color 0.2s;
        }

        .card-delete-btn:hover {
            background: #dc2626;
            color: #fff;
            transform: scale(1.05);
        }

        .card-delete-btn:disabled {
            opacity: 0.55;
            cursor: not-allowed;
            transform: none;
        }

        .card-body {
            padding: 18px;
            flex: 1;
        }

        .card-section {
            margin-bottom: 14px;
        }

        .card-section:last-child {
            margin-bottom: 0;
        }

        .info-block {
            background: #f8fafc;
            border: 1px solid #e8eef5;
            border-radius: 12px;
            padding: 12px 14px;
        }

        .info-block-title {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: <?= $colors['secondary_blue'] ?>;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-block-title i {
            color: <?= $colors['gold'] ?>;
            font-size: 0.85rem;
        }

        .info-line {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 0.88rem;
            color: <?= $colors['navy'] ?>;
            margin-bottom: 6px;
            word-break: break-word;
        }

        .info-line:last-child {
            margin-bottom: 0;
        }

        .info-line i {
            color: <?= $colors['gold'] ?>;
            width: 18px;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .info-line-muted {
            font-size: 0.82rem;
            color: #64748b;
            padding-left: 28px;
        }

        /* Documents panel */
        .docs-panel {
            background: linear-gradient(180deg, #fffbf7 0%, #fff 100%);
            border: 1px solid rgba(242, 166, 90, 0.35);
            border-radius: 14px;
            padding: 14px;
        }

        .docs-panel-head {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            font-weight: 700;
            font-size: 0.8rem;
            color: <?= $colors['navy'] ?>;
        }

        .docs-panel-head i {
            color: <?= $colors['gold'] ?>;
        }

        .docs-count {
            margin-left: auto;
            background: <?= $colors['navy'] ?>;
            color: #fff;
            font-size: 0.7rem;
            padding: 3px 10px;
            border-radius: 999px;
            font-weight: 600;
        }

        .docs-grid {
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-height: 220px;
            overflow-y: auto;
            padding-right: 4px;
            scrollbar-width: thin;
        }

        .doc-tile {
            display: grid;
            grid-template-columns: 44px 1fr auto;
            gap: 12px;
            align-items: center;
            padding: 10px 12px;
            background: #fff;
            border: 1px solid #e8eef5;
            border-radius: 12px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .doc-tile:hover {
            border-color: rgba(242, 166, 90, 0.6);
            box-shadow: 0 4px 12px rgba(1, 47, 107, 0.06);
        }

        .doc-tile-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            background: #eff6ff;
            color: <?= $colors['secondary_blue'] ?>;
        }

        .doc-tile-icon.pdf {
            background: #fef2f2;
            color: #dc2626;
        }

        .doc-tile-icon.image {
            background: #f0fdf4;
            color: #16a34a;
        }

        .doc-tile-type {
            display: block;
            font-weight: 700;
            font-size: 0.82rem;
            color: <?= $colors['navy'] ?>;
            margin-bottom: 2px;
        }

        .doc-tile-file {
            display: block;
            font-size: 0.72rem;
            color: #64748b;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 100%;
        }

        .doc-tile-actions {
            display: flex;
            gap: 6px;
        }

        .doc-btn {
            background: <?= $colors['navy'] ?>;
            color: <?= $colors['white'] ?>;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            text-decoration: none;
            transition: transform 0.2s, background 0.2s;
            font-size: 0.85rem;
        }

        .doc-btn:hover {
            background: <?= $colors['dark_blue'] ?>;
            transform: translateY(-1px);
        }

        .doc-btn.download {
            background: <?= $colors['gold'] ?>;
            color: <?= $colors['navy'] ?>;
        }

        .doc-btn.download:hover {
            background: #e69542;
        }

        .docs-empty {
            font-size: 0.85rem;
            color: #94a3b8;
            font-style: italic;
            padding: 8px 0;
        }

        /* Card Footer */
        .card-footer {
            padding: 14px 18px;
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #e8eef5;
            gap: 10px;
            flex-wrap: wrap;
        }

        .applied-date {
            font-size: 0.85rem;
            color: #666;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .view-btn {
            background: <?= $colors['secondary_blue'] ?>;
            color: <?= $colors['white'] ?>;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }

        .view-btn:hover {
            background: <?= $colors['navy'] ?>;
        }

        .contact-btn {
            background: <?= $colors['gold'] ?>;
            color: <?= $colors['navy'] ?>;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }

        .contact-btn:hover {
            background: #e69542;
        }

        /* ----- Application process (workflow) ----- */
        .process-panel {
            background: linear-gradient(180deg, #f8fafc 0%, #fff 100%);
            border-bottom: 1px solid <?= $colors['border_gray'] ?>;
            padding: 14px 15px 16px;
        }

        .process-panel-title {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: <?= $colors['secondary_blue'] ?>;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .process-panel-title i {
            color: <?= $colors['gold'] ?>;
        }

        .process-tracker {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 6px;
            margin-bottom: 14px;
            overflow-x: auto;
            padding-bottom: 6px;
            scrollbar-width: thin;
        }

        .process-step {
            flex: 1;
            min-width: 72px;
            text-align: center;
            position: relative;
        }

        .process-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 11px;
            left: calc(50% + 14px);
            right: calc(-50% + 14px);
            height: 3px;
            background: #e2e8f0;
            z-index: 0;
        }

        .process-step.done:not(:last-child)::after {
            background: linear-gradient(90deg, <?= $colors['gold'] ?>, #e2e8f0);
        }

        .process-step-dot {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            margin: 0 auto 6px;
            position: relative;
            z-index: 1;
            background: #e2e8f0;
            border: 3px solid #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }

        .process-step.done .process-step-dot {
            background: <?= $colors['gold'] ?>;
            border-color: #fff7ed;
        }

        .process-step.current .process-step-dot {
            background: <?= $colors['navy'] ?>;
            border-color: <?= $colors['gold'] ?>;
            box-shadow: 0 0 0 3px rgba(242, 166, 90, 0.35);
        }

        .process-step.pending .process-step-dot {
            background: #e2e8f0;
        }

        .process-step-label {
            display: block;
            font-size: 0.65rem;
            line-height: 1.25;
            color: #64748b;
            font-weight: 600;
        }

        .process-step.done .process-step-label,
        .process-step.current .process-step-label {
            color: <?= $colors['navy'] ?>;
        }

        .process-status-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            justify-content: space-between;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 700;
            background: #e0e7ff;
            color: <?= $colors['navy'] ?>;
        }

        .status-pill--submitted { background: #f1f5f9; color: #475569; }
        .status-pill--under_review { background: #fef3c7; color: #92400e; }
        .status-pill--waiting_decision { background: #dbeafe; color: #1e40af; }
        .status-pill--final_decision { background: #d1fae5; color: #065f46; }
        .status-pill--closed { background: #e2e8f0; color: #334155; }
        .status-pill--rejected { background: #fee2e2; color: #991b1b; }

        .job-process-select {
            flex: 1;
            min-width: 200px;
            max-width: 100%;
            padding: 8px 12px;
            border-radius: 8px;
            border: 2px solid <?= $colors['border_gray'] ?>;
            font-size: 0.85rem;
            font-weight: 600;
            color: <?= $colors['navy'] ?>;
            background: #fff;
            cursor: pointer;
        }

        .job-process-select:focus {
            outline: none;
            border-color: <?= $colors['gold'] ?>;
            box-shadow: 0 0 0 3px rgba(242, 166, 90, 0.25);
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0,0,0,0);
            border: 0;
        }

        .process-toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 2000;
            background: <?= $colors['navy'] ?>;
            color: #fff;
            padding: 12px 18px;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            display: none;
            align-items: center;
            gap: 10px;
        }

        .process-toast.show { display: flex; }

        /* Empty State */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 50px 20px;
            background: <?= $colors['white'] ?>;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .empty-state i {
            font-size: 3rem;
            color: <?= $colors['border_gray'] ?>;
            margin-bottom: 15px;
        }

        .empty-state h3 {
            color: #666;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .empty-state p {
            color: #777;
            font-size: 0.95rem;
            margin-bottom: 20px;
        }

        .search-live-meta {
            font-size: 0.85rem;
            color: #64748b;
            margin-bottom: 12px;
            min-height: 1.25em;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .search-live-meta.is-filtering {
            color: <?= $colors['navy'] ?>;
            font-weight: 600;
        }

        .search-live-meta i {
            color: <?= $colors['gold'] ?>;
        }

        .search-input-wrap {
            position: relative;
            flex: 1;
            min-width: 250px;
        }

        .search-input-wrap .search-input {
            width: 100%;
            padding-right: 42px;
        }

        .search-input-clear {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: #94a3b8;
            cursor: pointer;
            padding: 6px;
            border-radius: 8px;
            display: none;
            line-height: 1;
        }

        .search-input-clear:hover {
            color: <?= $colors['navy'] ?>;
            background: #f1f5f9;
        }

        .search-input-clear.is-visible {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .applicant-card.ja-search-hidden {
            display: none !important;
        }

        .ja-search-empty {
            grid-column: 1 / -1;
            display: none;
        }

        .ja-search-empty.is-visible {
            display: block;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 15px;
            }
            
            .dashboard-header {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }
            
            .header-stats {
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .search-form {
                flex-direction: column;
            }
            
            .search-input {
                width: 100%;
            }
            
            .search-btn, .clear-btn {
                width: 100%;
                justify-content: center;
            }
            
            .info-row {
                flex-direction: column;
            }
            
            .info-label {
                width: 100%;
                margin-bottom: 5px;
            }
            
            .card-footer {
                flex-direction: column;
                gap: 10px;
                align-items: stretch;
            }
            
            .action-buttons {
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .dashboard-container {
                padding: 10px;
            }
            
            .applicants-grid {
                gap: 15px;
            }
            
            .card-header, .card-body, .card-footer {
                padding: 12px;
            }
        }

        /* Status + notify modal (same flow as students-manage) */
        #jobStatusNotifyModal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        #jobStatusNotifyModal.show-flex { display: flex !important; }
        .job-notify-dialog {
            background: #fff;
            border-radius: 16px;
            max-width: 640px;
            width: 100%;
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .job-notify-header {
            background: linear-gradient(135deg, #012F6B 0%, #254D81 100%);
            color: #fff;
            padding: 18px 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
        }
        .job-notify-header h3 { margin: 0; font-size: 1.15rem; font-weight: 600; }
        .job-notify-header p { margin: 6px 0 0; font-size: 0.85rem; opacity: 0.9; }
        .job-notify-close {
            background: none;
            border: none;
            color: #fff;
            font-size: 1.5rem;
            cursor: pointer;
            line-height: 1;
        }
        .job-notify-body { padding: 20px; }
        .job-notify-preview {
            text-align: center;
            margin-bottom: 16px;
        }
        .job-notify-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            background: #e8eef9;
            color: #012F6B;
            margin-bottom: 8px;
        }
        .job-notify-label-text {
            font-size: 1.35rem;
            font-weight: 700;
            color: #0f172a;
        }
        .job-notify-hint {
            text-align: center;
            font-size: 0.85rem;
            color: #64748b;
            margin-bottom: 14px;
        }
        .job-notify-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        @media (min-width: 768px) {
            .job-notify-grid { grid-template-columns: repeat(4, 1fr); }
        }
        .job-notify-channel {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 10px;
            background: #fff;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            color: #334155;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .job-notify-channel:hover { border-color: #94a3b8; }
        .job-notify-channel.active {
            border-color: #012F6B;
            color: #012F6B;
            box-shadow: 0 0 0 1px #012F6B;
        }
        .job-notify-channel span { display: block; font-size: 0.75rem; font-weight: 400; opacity: 0.85; margin-top: 4px; }
        .job-notify-footer {
            padding: 14px 20px 18px;
            background: #f8fafc;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .job-notify-footer button {
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            border: none;
        }
        .job-notify-cancel { background: #fff; border: 1px solid #cbd5e1 !important; color: #475569; }
        .job-notify-confirm {
            background: linear-gradient(135deg, #012F6B, #254D81);
            color: #fff;
        }
        .process-toast.warn { background: linear-gradient(135deg, #b45309, #d97706); }
        .process-toast.error { background: linear-gradient(135deg, #b91c1c, #dc2626); }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="ja-sticky-toolbar">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="header-title">
                <h1><i class="fas fa-user-tie"></i> Job Applicants Dashboard</h1>
                <p>Review and manage all job applications and documents</p>
            </div>
            <div class="header-stats">
                <div class="applicant-count" id="jaApplicantCount">
                    <i class="fas fa-users"></i>
                    <?php 
                        $total = count($applicants);
                        echo $total . ' Applicant' . ($total != 1 ? 's' : '');
                    ?>
                </div>
                <a href="export_applicants.php" class="export-btn">
                    <i class="fas fa-download"></i> Export CSV
                </a>
            </div>
        </div>

        <!-- Search Section -->
        <div class="search-container">
            <div class="search-live-meta" id="jaSearchMeta" aria-live="polite">
                <i class="fas fa-bolt"></i>
                <span>Type to search by name, email, phone, or user ID</span>
            </div>
            <div class="search-form">
                <div class="search-input-wrap">
                    <input type="search"
                           id="jaSearchInput"
                           class="search-input"
                           placeholder="Search by name, email, phone, or user ID…"
                           autocomplete="off"
                           spellcheck="false"
                           title="Live search — results update as you type">
                    <button type="button" class="search-input-clear" id="jaSearchClear" aria-label="Clear search" title="Clear">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
        </div>

        <div class="ja-main">
        <!-- Applicants Grid -->
        <div class="applicants-grid" id="jaApplicantsGrid">
            <div class="empty-state ja-search-empty" id="jaSearchEmpty" aria-hidden="true">
                <i class="fas fa-search"></i>
                <h3>No matches</h3>
                <p>Try a different name, email, phone, or user ID.</p>
            </div>
            <?php if(empty($applicants)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No applicants found</h3>
                    <p>No job applications have been submitted yet.</p>
                </div>
            <?php else: ?>
                <?php foreach($applicants as $applicant): 
                    // Parse documents
                    $documents = [];
                    if(!empty($applicant['documents'])) {
                        $docItems = explode('|', $applicant['documents']);
                        foreach($docItems as $item) {
                            if(!empty($item)) {
                                list($type, $path, $time, $docId) = explode(':', $item, 4);
                                $documents[] = [
                                    'type' => $type,
                                    'path' => $path,
                                    'time' => $time,
                                    'docId' => $docId
                                ];
                            }
                        }
                    }
                    
                    // Format date
                    $appliedDate = date('M d, Y h:i A', strtotime($applicant['created_at']));
                    
                    // Get full address
                    $address = $applicant['province_state'] . ', ' . $applicant['district'];
                    $detailedArea = $applicant['sector'] . ' / ' . $applicant['cell_ward'] . ' / ' . $applicant['village'];
                    
                    // Emergency contact info
                    $emergencyContact = $applicant['emergency_full_name'] . ' (' . $applicant['emergency_relationship'] . ')';
                    $emergencyPhone = $applicant['emergency_area_code'] . ' ' . $applicant['emergency_phone_number'];
                    
                    // Full name
                    $fullName = $applicant['first_name'] . ' ' . $applicant['last_name'];
                    $phone = $applicant['phone_area_code'] . ' ' . $applicant['phone_number'];

                    $procKey = xander_normalize_job_process_status($applicant['process_status'] ?? null);
                    $procLabel = $JOB_PROCESS_STATUSES[$procKey];
                    $curIdx = array_search($procKey, $JOB_PROCESS_ORDER, true);
                    if ($curIdx === false) {
                        $curIdx = 0;
                    }
                    $nameParts = preg_split('/\s+/u', trim($fullName), -1, PREG_SPLIT_NO_EMPTY);
                    $initials = '';
                    if (!empty($nameParts[0])) {
                        $initials .= mb_strtoupper(mb_substr($nameParts[0], 0, 1));
                    }
                    if (!empty($nameParts[1])) {
                        $initials .= mb_strtoupper(mb_substr($nameParts[1], 0, 1));
                    }
                    if ($initials === '') {
                        $initials = '?';
                    }
                    $jaSearchBlob = mb_strtolower(trim(implode(' ', [
                        $fullName,
                        (string) ($applicant['email'] ?? ''),
                        $phone,
                        (string) ($applicant['user_id'] ?? ''),
                        $address,
                        $detailedArea,
                        (string) ($applicant['first_name'] ?? ''),
                        (string) ($applicant['last_name'] ?? ''),
                        $emergencyContact,
                        $emergencyPhone,
                    ])));
                ?>
                <div class="applicant-card"
                     data-application-id="<?= (int) $applicant['id'] ?>"
                     data-search="<?= htmlspecialchars($jaSearchBlob, ENT_QUOTES, 'UTF-8') ?>">
                    <!-- Card Header -->
                    <div class="card-header">
                        <div class="card-header-main">
                            <div class="applicant-avatar" aria-hidden="true"><?= htmlspecialchars($initials) ?></div>
                            <div class="card-header-text">
                                <div class="applicant-name"><?= htmlspecialchars($fullName) ?></div>
                                <div class="applicant-id"><i class="fas fa-fingerprint"></i> <?= htmlspecialchars($applicant['user_id']) ?></div>
                            </div>
                        </div>
                        <?php if ($canEditJobProcessStatus): ?>
                        <button type="button"
                                class="card-delete-btn job-delete-btn"
                                data-application-id="<?= (int) $applicant['id'] ?>"
                                data-applicant-name="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>"
                                title="Delete application (Superadmin only)"
                                aria-label="Delete application for <?= htmlspecialchars($fullName) ?>">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                        <?php endif; ?>
                    </div>

                    <!-- Application process (view: all · edit status: Superadmin only) -->
                    <div class="process-panel">
                        <div class="process-panel-title">
                            <i class="fas fa-route"></i> Application process
                        </div>
                        <div class="process-tracker" role="list" aria-label="Application status stages">
                            <?php foreach ($JOB_PROCESS_ORDER as $i => $stepKey):
                                $st = $i < $curIdx ? 'done' : ($i === $curIdx ? 'current' : 'pending');
                                $stepLabel = $JOB_PROCESS_STATUSES[$stepKey];
                            ?>
                            <div class="process-step <?= $st ?>" role="listitem" title="<?= htmlspecialchars($stepLabel) ?>">
                                <span class="process-step-dot" aria-hidden="true"></span>
                                <span class="process-step-label"><?= htmlspecialchars($stepLabel) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="process-status-row">
                            <span class="status-pill status-pill--<?= htmlspecialchars($procKey) ?>"><?= htmlspecialchars($procLabel) ?></span>
                            <?php if ($canEditJobProcessStatus): ?>
                            <label class="sr-only" for="job-status-<?= (int) $applicant['id'] ?>">Set application status</label>
                            <select id="job-status-<?= (int) $applicant['id'] ?>"
                                    class="job-process-select"
                                    data-application-id="<?= (int) $applicant['id'] ?>"
                                    title="Superadmin only — updates application stage">
                                <?php foreach ($JOB_PROCESS_STATUSES as $k => $lab): ?>
                                <option value="<?= htmlspecialchars($k) ?>" <?= $k === $procKey ? 'selected' : '' ?>><?= htmlspecialchars($lab) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Card Body -->
                    <div class="card-body">
                        <div class="card-section">
                            <div class="info-block">
                                <div class="info-block-title"><i class="fas fa-address-card"></i> Contact</div>
                                <div class="info-line"><i class="fas fa-envelope"></i><span><?= htmlspecialchars($applicant['email']) ?></span></div>
                                <div class="info-line"><i class="fas fa-phone"></i><span><?= htmlspecialchars($phone) ?></span></div>
                            </div>
                        </div>
                        <div class="card-section">
                            <div class="info-block">
                                <div class="info-block-title"><i class="fas fa-location-dot"></i> Location</div>
                                <div class="info-line"><i class="fas fa-map-marker-alt"></i><span><?= htmlspecialchars($address) ?></span></div>
                                <div class="info-line-muted"><?= htmlspecialchars($detailedArea) ?></div>
                            </div>
                        </div>
                        <div class="card-section">
                            <div class="info-block">
                                <div class="info-block-title"><i class="fas fa-user-shield"></i> Emergency</div>
                                <div class="info-line"><i class="fas fa-user"></i><span><?= htmlspecialchars($emergencyContact) ?></span></div>
                                <div class="info-line"><i class="fas fa-phone-alt"></i><span><?= htmlspecialchars($emergencyPhone) ?></span></div>
                            </div>
                        </div>
                        <div class="card-section">
                            <div class="docs-panel">
                                <div class="docs-panel-head">
                                    <i class="fas fa-folder-open"></i>
                                    <span>Documents</span>
                                    <span class="docs-count"><?= count($documents) ?></span>
                                </div>
                                <?php if (!empty($documents)): ?>
                                <div class="docs-grid">
                                    <?php foreach ($documents as $doc):
                                        $fileName = basename($doc['path']);
                                        $fileExtension = strtolower(pathinfo($doc['path'], PATHINFO_EXTENSION));
                                        $docLabel = xander_job_document_display_label((string) $doc['type']);
                                        $iconClass = 'fa-file';
                                        $tileClass = '';
                                        if ($fileExtension === 'pdf') {
                                            $iconClass = 'fa-file-pdf';
                                            $tileClass = 'pdf';
                                        } elseif (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                                            $iconClass = 'fa-file-image';
                                            $tileClass = 'image';
                                        }
                                    ?>
                                    <article class="doc-tile">
                                        <div class="doc-tile-icon <?= htmlspecialchars($tileClass) ?>">
                                            <i class="fas <?= $iconClass ?>"></i>
                                        </div>
                                        <div>
                                            <span class="doc-tile-type"><?= htmlspecialchars($docLabel) ?></span>
                                            <span class="doc-tile-file" title="<?= htmlspecialchars($fileName) ?>"><?= htmlspecialchars($fileName) ?></span>
                                        </div>
                                        <div class="doc-tile-actions">
                                            <a href="<?= htmlspecialchars($doc['path']) ?>" class="doc-btn download" download title="Download <?= htmlspecialchars($fileName) ?>">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <a href="<?= htmlspecialchars($doc['path']) ?>" class="doc-btn" target="_blank" rel="noopener" title="View <?= htmlspecialchars($fileName) ?>">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </article>
                                    <?php endforeach; ?>
                                </div>
                                <?php else: ?>
                                <p class="docs-empty">No documents uploaded</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    
                    <!-- Card Footer -->
                    <div class="card-footer">
                        <div class="applied-date">
                            <i class="fas fa-calendar-check"></i> <?= $appliedDate ?>
                        </div>
                        <div class="action-buttons">
                            <a href="mailto:<?= htmlspecialchars($applicant['email']) ?>" class="contact-btn" title="Send Email">
                                <i class="fas fa-envelope"></i> Contact
                            </a>
                            <button class="view-btn view-details-btn" 
                                    data-applicant='<?= htmlspecialchars(json_encode([
                                        'name' => $fullName,
                                        'email' => $applicant['email'],
                                        'phone' => $phone,
                                        'address' => $address,
                                        'emergency' => $emergencyContact,
                                        'emergencyPhone' => $emergencyPhone,
                                        'applied' => $appliedDate,
                                        'documents' => $documents,
                                        'processStatus' => $procKey,
                                        'processLabel' => $procLabel,
                                    ])) ?>'>
                                <i class="fas fa-eye"></i> Details
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div id="detailsModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
        <div style="background: white; border-radius: 10px; max-width: 600px; width: 100%; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
            <div style="background: <?= $colors['navy'] ?>; color: white; padding: 20px; border-radius: 10px 10px 0 0; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 1.2rem;"><i class="fas fa-user-circle"></i> Applicant Details</h3>
                <button id="closeModal" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <div style="padding: 20px;" id="modalContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Save status + optional email / WhatsApp -->
    <div id="jobStatusNotifyModal" aria-hidden="true">
        <div class="job-notify-dialog">
            <div class="job-notify-header">
                <div>
                    <h3><i class="fas fa-bell"></i> Save status</h3>
                    <p>Notify the applicant about this update (optional)</p>
                </div>
                <button type="button" class="job-notify-close" id="jobNotifyCloseX" aria-label="Close">&times;</button>
            </div>
            <div class="job-notify-body">
                <div class="job-notify-preview">
                    <div class="job-notify-badge">New status</div>
                    <div class="job-notify-label-text" id="jobNotifyStatusLabel">—</div>
                </div>
                <div id="jobRejectReasonWrap" class="job-reject-reason-wrap" style="display:none;">
                    <label for="jobRejectReason" style="display:block;font-weight:600;font-size:0.85rem;color:#b91c1c;margin-bottom:6px;">Reason for rejection</label>
                    <textarea id="jobRejectReason" rows="3" maxlength="2000" style="width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:10px;font-size:0.9rem;resize:vertical;" placeholder="Required if you send email or WhatsApp"></textarea>
                    <p style="margin:6px 0 0;font-size:0.78rem;color:#64748b;">Included in the message to the applicant when you notify.</p>
                </div>
                <p class="job-notify-hint">Choose one — you can save without sending anything.</p>
                <div class="job-notify-grid">
                    <button type="button" class="job-notify-channel active" data-ne="0" data-nw="0">Record only<span>No notification</span></button>
                    <button type="button" class="job-notify-channel" data-ne="1" data-nw="0">Email<span>Send email</span></button>
                    <button type="button" class="job-notify-channel" data-ne="0" data-nw="1">WhatsApp<span>Send WhatsApp</span></button>
                    <button type="button" class="job-notify-channel" data-ne="1" data-nw="1">Both<span>Email + WhatsApp</span></button>
                </div>
            </div>
            <div class="job-notify-footer">
                <button type="button" class="job-notify-cancel" id="jobNotifyCancel">Cancel</button>
                <button type="button" class="job-notify-confirm" id="jobNotifyConfirm">Save</button>
            </div>
        </div>
    </div>

    <div id="processToast" class="process-toast" role="status" aria-live="polite">
        <i class="fas fa-check-circle"></i>
        <span id="processToastMsg">Saved</span>
    </div>

    <script>
        window.APP_ROOT = <?= json_encode($appRoot, JSON_UNESCAPED_SLASHES) ?>;
        window.JOB_PROCESS_ORDER = <?= json_encode($JOB_PROCESS_ORDER, JSON_UNESCAPED_UNICODE) ?>;
        window.JOB_PROCESS_LABELS = <?= json_encode($JOB_PROCESS_STATUSES, JSON_UNESCAPED_UNICODE) ?>;

        function jaApi(path) {
            var rel = String(path || '').replace(/^\//, '');
            var base = (typeof window.APP_ROOT === 'string' && window.APP_ROOT) ? window.APP_ROOT.replace(/\/$/, '') : '';
            return base ? (base + '/' + rel) : rel;
        }

        function jaParseJsonResponse(r) {
            return r.text().then(function(text) {
                var json = null;
                try {
                    json = text ? JSON.parse(text) : null;
                } catch (e) {
                    var hint = (text && text.indexOf('<') !== -1) ? 'Server returned HTML instead of JSON (check PHP error log).' : (text || ('HTTP ' + r.status));
                    throw new Error(hint);
                }
                if (!json || typeof json.success === 'undefined') {
                    throw new Error('Invalid server response');
                }
                return json;
            });
        }

        function jaNorm(s) {
            return String(s || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }

        function jaEscapeHtml(s) {
            return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
        }

        function jaUpdateApplicantCount() {
            var countEl = document.getElementById('jaApplicantCount');
            if (!countEl) return;
            var total = document.querySelectorAll('.applicant-card').length;
            var visible = document.querySelectorAll('.applicant-card:not(.ja-search-hidden)').length;
            var input = document.getElementById('jaSearchInput');
            var q = input ? input.value.trim() : '';
            if (q) {
                countEl.innerHTML = '<i class="fas fa-users"></i> ' + visible + ' of ' + total;
            } else {
                countEl.innerHTML = '<i class="fas fa-users"></i> ' + total + ' Applicant' + (total !== 1 ? 's' : '');
            }
        }

        function jaFilterApplicants() {
            var input = document.getElementById('jaSearchInput');
            var clearBtn = document.getElementById('jaSearchClear');
            var meta = document.getElementById('jaSearchMeta');
            var empty = document.getElementById('jaSearchEmpty');
            if (!input) return;
            var raw = input.value.trim();
            var tokens = jaNorm(raw).split(/\s+/).filter(Boolean);
            var cards = document.querySelectorAll('.applicant-card');
            var visible = 0;
            cards.forEach(function(card) {
                var hay = jaNorm(card.getAttribute('data-search') || '');
                var match = tokens.length === 0 || tokens.every(function(t) { return hay.indexOf(t) !== -1; });
                card.classList.toggle('ja-search-hidden', !match);
                if (match) visible++;
            });
            if (clearBtn) clearBtn.classList.toggle('is-visible', raw.length > 0);
            if (meta) {
                meta.classList.toggle('is-filtering', tokens.length > 0);
                if (tokens.length === 0) {
                    meta.innerHTML = '<i class="fas fa-bolt"></i><span>Type to search by name, email, phone, or user ID</span>';
                } else if (visible === 0) {
                    meta.innerHTML = '<i class="fas fa-search"></i><span>No applicants match “' + jaEscapeHtml(raw) + '”</span>';
                } else {
                    meta.innerHTML = '<i class="fas fa-filter"></i><span>Showing ' + visible + ' of ' + cards.length + ' applicants</span>';
                }
            }
            if (empty) {
                var showEmpty = tokens.length > 0 && visible === 0 && cards.length > 0;
                empty.classList.toggle('is-visible', showEmpty);
                empty.setAttribute('aria-hidden', showEmpty ? 'false' : 'true');
            }
            jaUpdateApplicantCount();
        }

        var jaSearchDebounce;
        function jaScheduleFilter() {
            clearTimeout(jaSearchDebounce);
            jaSearchDebounce = setTimeout(jaFilterApplicants, 90);
        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var searchInput = document.getElementById('jaSearchInput');
            var searchClear = document.getElementById('jaSearchClear');
            if (searchInput) {
                searchInput.addEventListener('input', jaScheduleFilter);
                searchInput.addEventListener('search', jaFilterApplicants);
                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        searchInput.value = '';
                        jaFilterApplicants();
                    }
                });
                try {
                    var qParam = new URL(window.location.href).searchParams.get('search');
                    if (qParam) {
                        searchInput.value = qParam;
                        jaFilterApplicants();
                    }
                } catch (eUrl) {}
            }
            if (searchClear && searchInput) {
                searchClear.addEventListener('click', function() {
                    searchInput.value = '';
                    jaFilterApplicants();
                    searchInput.focus();
                });
            }

            document.querySelectorAll('.job-process-select').forEach(function(sel) {
                sel.setAttribute('data-prev-status', sel.value);
            });

            const modal = document.getElementById('detailsModal');
            const modalContent = document.getElementById('modalContent');
            const closeModal = document.getElementById('closeModal');
            
            // View details button click
            document.querySelectorAll('.view-details-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const applicantData = JSON.parse(this.getAttribute('data-applicant'));
                    showApplicantDetails(applicantData);
                });
            });
            
            // Show applicant details in modal
            function showApplicantDetails(data) {
                let documentsHtml = '';
                if(data.documents && data.documents.length > 0) {
                    documentsHtml = data.documents.map(doc => {
                        const label = (doc.type || 'Document').replace(/[_-]+/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
                        const file = doc.path.split('/').pop();
                        return `
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 14px; background: #fff; border-radius: 12px; margin-bottom: 8px; border: 1px solid #e8eef5;">
                            <div>
                                <strong style="color: <?= $colors['navy'] ?>;">${label}</strong><br>
                                <small style="color:#64748b;">${file}</small>
                            </div>
                            <div>
                                <a href="${doc.path}" class="doc-btn download" download style="display: inline-flex; margin-right: 5px;">
                                    <i class="fas fa-download"></i>
                                </a>
                                <a href="${doc.path}" class="doc-btn" target="_blank" style="display: inline-flex;">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </div>
                    `;
                    }).join('');
                } else {
                    documentsHtml = '<p style="color: #666; font-style: italic;">No documents uploaded</p>';
                }
                
                const procLabel = data.processLabel || (window.JOB_PROCESS_LABELS && data.processStatus ? window.JOB_PROCESS_LABELS[data.processStatus] : '') || '—';
                modalContent.innerHTML = `
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                        <div style="grid-column: 1 / -1; background: linear-gradient(135deg, #f8f9fa, #e9ecef); padding: 15px; border-radius: 8px; text-align: center;">
                            <h4 style="margin: 0; color: <?= $colors['navy'] ?>;">${data.name}</h4>
                            <p style="margin: 5px 0 0 0; color: #666;">Applied on ${data.applied}</p>
                        </div>
                        <div style="grid-column: 1 / -1; background: rgba(37, 77, 129, 0.08); padding: 12px 14px; border-radius: 8px; border-left: 4px solid <?= $colors['gold'] ?>;">
                            <strong style="color: <?= $colors['secondary_blue'] ?>; font-size: 0.85rem;">Application process</strong>
                            <div style="margin-top: 6px; font-weight: 700; color: <?= $colors['navy'] ?>;">${procLabel}</div>
                        </div>
                        
                        <div style="background: #f8f9fa; padding: 12px; border-radius: 6px;">
                            <strong style="color: <?= $colors['secondary_blue'] ?>;">Contact Info</strong>
                            <div style="margin-top: 8px;">
                                <div><i class="fas fa-envelope" style="color: <?= $colors['gold'] ?>;"></i> ${data.email}</div>
                                <div><i class="fas fa-phone" style="color: <?= $colors['gold'] ?>;"></i> ${data.phone}</div>
                            </div>
                        </div>
                        
                        <div style="background: #f8f9fa; padding: 12px; border-radius: 6px;">
                            <strong style="color: <?= $colors['secondary_blue'] ?>;">Location</strong>
                            <div style="margin-top: 8px;">
                                <div><i class="fas fa-map-marker-alt" style="color: <?= $colors['gold'] ?>;"></i> ${data.address}</div>
                            </div>
                        </div>
                        
                        <div style="grid-column: 1 / -1; background: rgba(242, 166, 90, 0.1); padding: 12px; border-radius: 6px; border-left: 4px solid <?= $colors['gold'] ?>;">
                            <strong style="color: <?= $colors['secondary_blue'] ?>;">Emergency Contact</strong>
                            <div style="margin-top: 8px;">
                                <div><i class="fas fa-user-shield" style="color: <?= $colors['gold'] ?>;"></i> ${data.emergency}</div>
                                <div><i class="fas fa-phone-alt" style="color: <?= $colors['gold'] ?>;"></i> ${data.emergencyPhone}</div>
                            </div>
                        </div>
                        
                        <div style="grid-column: 1 / -1;">
                            <strong style="color: <?= $colors['secondary_blue'] ?>; display: block; margin-bottom: 10px;">Documents</strong>
                            ${documentsHtml}
                        </div>
                    </div>
                    
                    <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 15px; border-top: 1px solid #dee2e6;">
                        <a href="mailto:${data.email}" class="contact-btn" style="display: inline-flex; text-decoration: none;">
                            <i class="fas fa-envelope"></i> Send Email
                        </a>
                        <button id="closeDetails" class="search-btn" style="background: #666;">
                            <i class="fas fa-times"></i> Close
                        </button>
                    </div>
                `;
                
                modal.style.display = 'flex';
                
                // Close modal events
                closeModal.addEventListener('click', hideModal);
                document.getElementById('closeDetails').addEventListener('click', hideModal);
                
                // Close on outside click
                modal.addEventListener('click', function(e) {
                    if(e.target === modal) {
                        hideModal();
                    }
                });
            }
            
            function hideModal() {
                modal.style.display = 'none';
            }
            
            // Close modal with Escape key
            document.addEventListener('keydown', function(e) {
                if(e.key === 'Escape' && modal.style.display === 'flex') {
                    hideModal();
                }
            });

            function showProcessToast(msg) {
                const t = document.getElementById('processToast');
                const m = document.getElementById('processToastMsg');
                if (!t || !m) return;
                m.textContent = msg;
                t.classList.add('show');
                clearTimeout(window._processToastTimer);
                window._processToastTimer = setTimeout(function() { t.classList.remove('show'); }, 2800);
            }

            function updateCardProcessUI(card, statusKey) {
                if (!card || !window.JOB_PROCESS_ORDER) return;
                const order = window.JOB_PROCESS_ORDER;
                const labels = window.JOB_PROCESS_LABELS || {};
                const curIdx = order.indexOf(statusKey);
                const idx = curIdx === -1 ? 0 : curIdx;
                const steps = card.querySelectorAll('.process-step');
                steps.forEach(function(step, i) {
                    step.classList.remove('done', 'current', 'pending');
                    var st = i < idx ? 'done' : (i === idx ? 'current' : 'pending');
                    step.classList.add(st);
                });
                var pill = card.querySelector('.status-pill');
                if (pill) {
                    pill.className = 'status-pill status-pill--' + statusKey;
                    pill.textContent = labels[statusKey] || statusKey;
                }
                var sel = card.querySelector('.job-process-select');
                if (sel && sel.querySelector('option[value="' + statusKey + '"]')) {
                    sel.value = statusKey;
                }
            }

            var jobNotifyModal = document.getElementById('jobStatusNotifyModal');
            var jobNotifyPending = null;

            document.querySelectorAll('.job-notify-channel').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.job-notify-channel').forEach(function(b) { b.classList.remove('active'); });
                    btn.classList.add('active');
                });
            });

            function openJobNotifyModal() {
                if (!jobNotifyModal) return;
                jobNotifyModal.classList.add('show-flex');
                jobNotifyModal.style.display = 'flex';
                jobNotifyModal.setAttribute('aria-hidden', 'false');
            }
            function closeJobNotifyModal() {
                if (!jobNotifyModal) return;
                jobNotifyModal.classList.remove('show-flex');
                jobNotifyModal.style.display = 'none';
                jobNotifyModal.setAttribute('aria-hidden', 'true');
                jobNotifyPending = null;
                var jrr = document.getElementById('jobRejectReason');
                var jrw = document.getElementById('jobRejectReasonWrap');
                if (jrr) jrr.value = '';
                if (jrw) jrw.style.display = 'none';
            }

            document.getElementById('jobNotifyCancel').addEventListener('click', closeJobNotifyModal);
            document.getElementById('jobNotifyCloseX').addEventListener('click', closeJobNotifyModal);
            jobNotifyModal.addEventListener('click', function(ev) {
                if (ev.target === jobNotifyModal) closeJobNotifyModal();
            });

            document.getElementById('jobNotifyConfirm').addEventListener('click', function() {
                var p = jobNotifyPending;
                if (!p || !p.sel) return;
                var active = document.querySelector('.job-notify-channel.active');
                var ne = active ? parseInt(active.getAttribute('data-ne'), 10) || 0 : 0;
                var nw = active ? parseInt(active.getAttribute('data-nw'), 10) || 0 : 0;
                var sel = p.sel;
                var statusKey = p.newKey;
                var id = p.id;
                var reasonEl = document.getElementById('jobRejectReason');
                var rejectReason = reasonEl ? (reasonEl.value || '').trim() : '';
                if (statusKey === 'rejected' && (ne || nw) && rejectReason === '') {
                    alert('Please enter a rejection reason before sending email or WhatsApp.');
                    return;
                }
                closeJobNotifyModal();
                sel.disabled = true;
                var fd = new FormData();
                fd.append('application_id', id);
                fd.append('process_status', statusKey);
                fd.append('notify_email', ne ? '1' : '0');
                fd.append('notify_whatsapp', nw ? '1' : '0');
                fd.append('rejection_reason', rejectReason);
                fetch(jaApi('api/job-application-status.php'), {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin'
                })
                .then(jaParseJsonResponse)
                .then(function(json) {
                    sel.disabled = false;
                    if (!json.success) {
                        alert(json.message || 'Could not update status');
                        sel.value = p.prevKey;
                        return;
                    }
                    sel.value = statusKey;
                    sel.setAttribute('data-prev-status', statusKey);
                    var card = sel.closest('.applicant-card');
                    updateCardProcessUI(card, statusKey);
                    var label = (json.data && json.data.label) ? json.data.label : ((window.JOB_PROCESS_LABELS && window.JOB_PROCESS_LABELS[statusKey]) || statusKey);
                    var n = json.data && json.data.notify;
                    var parts = ['Status saved'];
                    var anyFail = false;
                    if ((ne || nw) && !n) {
                        anyFail = true;
                        parts.push('Notifications failed (server error).');
                    }
                    if (n && n.email && n.email.requested) {
                        if (n.email.sent) parts.push('Email sent');
                        else { anyFail = true; parts.push('Email failed' + (n.email.error ? ': ' + n.email.error : '')); }
                    }
                    if (n && n.whatsapp && n.whatsapp.requested) {
                        if (n.whatsapp.sent) {
                            parts.push(n.whatsapp.method === 'text' ? 'WhatsApp sent (session)' : 'WhatsApp sent');
                        } else {
                            anyFail = true;
                            parts.push('WhatsApp failed' + (n.whatsapp.error ? ': ' + n.whatsapp.error : ''));
                        }
                    }
                    if (!ne && !nw) {
                        parts.length = 1;
                        parts[0] = 'Status saved (no notification)';
                    }
                    var msg = parts.join(' · ');
                    var toast = document.getElementById('processToast');
                    var toastMsg = document.getElementById('processToastMsg');
                    if (toast && toastMsg) {
                        toast.classList.toggle('warn', anyFail);
                        toastMsg.textContent = msg;
                        toast.classList.add('show');
                        clearTimeout(window._jobToastTimer);
                        window._jobToastTimer = setTimeout(function() { toast.classList.remove('show'); toast.classList.remove('warn'); }, 3800);
                    } else {
                        alert(msg);
                    }
                    var viewBtn = card ? card.querySelector('.view-details-btn') : null;
                    if (viewBtn) {
                        try {
                            var d = JSON.parse(viewBtn.getAttribute('data-applicant'));
                            d.processStatus = statusKey;
                            d.processLabel = label;
                            viewBtn.setAttribute('data-applicant', JSON.stringify(d));
                        } catch (err2) {}
                    }
                })
                .catch(function(err) {
                    sel.disabled = false;
                    sel.value = p.prevKey;
                    console.error(err);
                    alert('Network error while saving status.');
                });
            });

            document.addEventListener('focusin', function(e) {
                var t = e.target;
                if (t.classList && t.classList.contains('job-process-select')) {
                    t.setAttribute('data-prev-status', t.value);
                }
            });

            document.addEventListener('change', function(e) {
                var sel = e.target;
                if (!sel.classList || !sel.classList.contains('job-process-select')) return;
                var id = sel.getAttribute('data-application-id');
                var newKey = sel.value;
                var prevKey = sel.getAttribute('data-prev-status');
                if (prevKey === null || prevKey === '') return;
                if (!id || newKey === prevKey) return;
                sel.value = prevKey;
                var labels = window.JOB_PROCESS_LABELS || {};
                document.getElementById('jobNotifyStatusLabel').textContent = labels[newKey] || newKey;
                document.querySelectorAll('.job-notify-channel').forEach(function(b, i) {
                    b.classList.toggle('active', i === 0);
                });
                var rrWrap = document.getElementById('jobRejectReasonWrap');
                var rrInput = document.getElementById('jobRejectReason');
                if (rrWrap && rrInput) {
                    rrWrap.style.display = newKey === 'rejected' ? 'block' : 'none';
                    rrInput.value = '';
                }
                jobNotifyPending = { sel: sel, id: id, newKey: newKey, prevKey: prevKey };
                openJobNotifyModal();
            });

            document.querySelectorAll('.job-delete-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var id = btn.getAttribute('data-application-id');
                    var name = btn.getAttribute('data-applicant-name') || 'this applicant';
                    if (!id) return;
                    if (!confirm('Permanently delete the application for ' + name + '?\n\nThis removes all uploaded documents and cannot be undone.')) {
                        return;
                    }
                    btn.disabled = true;
                    var fd = new FormData();
                    fd.append('application_id', id);
                    fetch(jaApi('api/delete-job-application.php'), {
                        method: 'POST',
                        body: fd,
                        credentials: 'same-origin'
                    })
                    .then(jaParseJsonResponse)
                    .then(function(json) {
                        if (!json.success) {
                            btn.disabled = false;
                            alert(json.message || 'Could not delete application');
                            return;
                        }
                        var card = btn.closest('.applicant-card');
                        if (card) {
                            card.classList.add('is-removing');
                            setTimeout(function() {
                                card.remove();
                                jaFilterApplicants();
                            }, 320);
                        }
                        var toast = document.getElementById('processToast');
                        var toastMsg = document.getElementById('processToastMsg');
                        if (toast && toastMsg) {
                            toast.classList.remove('warn', 'error');
                            toastMsg.textContent = 'Application deleted';
                            toast.classList.add('show');
                            clearTimeout(window._jobToastTimer);
                            window._jobToastTimer = setTimeout(function() {
                                toast.classList.remove('show');
                                toast.classList.remove('error');
                            }, 2800);
                        }
                    })
                    .catch(function(err) {
                        btn.disabled = false;
                        alert(err && err.message ? err.message : 'Network error while deleting.');
                    });
                });
            });
        });
    </script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>