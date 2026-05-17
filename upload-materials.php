<?php
// Upload page — marketing folder; delete restricted to superadmin (see delete-marketing-pcloud.php)
session_start();
require_once __DIR__ . '/db.php';

$TEST_FOLDER_ID = 30592893924;

// Country folder mappings
$COUNTRY_FOLDERS = [
    'rwanda' => 30592893924,
    'burundi' => '31502860318'
];

$admin_id = $_SESSION['id'] ?? null;
if (!$admin_id || !isset($_SESSION['role'])) {
    header('Location: admin-login.php');
    exit;
}

$admin_id_safe = mysqli_real_escape_string($conn, (string) $admin_id);
$result = mysqli_query($conn, "SELECT role FROM admins WHERE id = '$admin_id_safe' LIMIT 1");
if (!$result || mysqli_num_rows($result) === 0) {
    header('Location: admin-login.php');
    exit;
}
$admin_row = mysqli_fetch_assoc($result);
$role = $admin_row['role'] ?? 'standard';
$canDeleteMarketing = ($role === 'superadmin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xander · File Manager</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        :root {
            --deep-navy: #012F6B;
            --secondary-blue: #254D81;
            --dark-blue: #002765;
            --gold: #F2A65A;
            --gold-dark: #e09540;
            --white: #FFFFFF;

            --bg: #F6F8FC;
            --surface: #FFFFFF;
            --surface-2: #FAFBFE;
            --border: #E8ECF3;
            --border-strong: #D9DFEA;

            --text: #0B1B36;
            --text-muted: #5B6A86;
            --text-soft: #8A98B3;

            --success: #16a34a;
            --success-dark: #15803d;
            --danger: #dc2626;
            --warning: #ea580c;
            --info: #0284c7;

            --shadow-sm: 0 1px 2px rgba(11, 27, 54, 0.04), 0 1px 3px rgba(11, 27, 54, 0.06);
            --shadow-md: 0 4px 12px rgba(11, 27, 54, 0.06), 0 2px 6px rgba(11, 27, 54, 0.04);
            --shadow-lg: 0 18px 40px -10px rgba(1, 47, 107, 0.18), 0 8px 16px -8px rgba(1, 47, 107, 0.10);
            --shadow-xl: 0 30px 60px -15px rgba(1, 47, 107, 0.25);

            --radius-sm: 8px;
            --radius: 12px;
            --radius-lg: 16px;
            --radius-xl: 22px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(1100px 600px at 110% -10%, rgba(242, 166, 90, 0.10), transparent 60%),
                radial-gradient(900px 500px at -10% 0%, rgba(1, 47, 107, 0.07), transparent 55%);
            pointer-events: none;
            z-index: 0;
        }

        /* ===== TOP NAV ===== */
        .topbar {
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: saturate(180%) blur(14px);
            -webkit-backdrop-filter: saturate(180%) blur(14px);
            background: rgba(255, 255, 255, 0.78);
            border-bottom: 1px solid var(--border);
        }

        .topbar-inner {
            max-width: 1400px;
            margin: 0 auto;
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .brand-mark {
            width: 38px;
            height: 38px;
            border-radius: 11px;
            background: linear-gradient(135deg, var(--deep-navy), var(--secondary-blue));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: 800;
            font-size: 18px;
            box-shadow: 0 6px 16px -4px rgba(1, 47, 107, 0.45);
            position: relative;
        }

        .brand-mark::after {
            content: '';
            position: absolute;
            inset: -2px;
            border-radius: 13px;
            background: linear-gradient(135deg, var(--gold), transparent 60%);
            opacity: 0.35;
            z-index: -1;
            filter: blur(6px);
        }

        .brand-text { display: flex; flex-direction: column; line-height: 1.1; }
        .brand-text .name { font-weight: 800; font-size: 16px; color: var(--text); }
        .brand-text .sub {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            letter-spacing: 1.4px;
            text-transform: uppercase;
        }

        .top-actions { display: flex; align-items: center; gap: 10px; }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            box-shadow: var(--shadow-sm);
        }

        .pill .dot {
            width: 7px; height: 7px; border-radius: 50%;
            background: var(--success);
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.18);
        }

        .pill.role-badge .dot { background: var(--gold); box-shadow: 0 0 0 3px rgba(242,166,90,0.18); }

        /* ===== CONTAINER ===== */
        .container-x {
            max-width: 1400px;
            margin: 0 auto;
            padding: 28px 24px 80px;
            position: relative;
            z-index: 1;
        }

        /* ===== HERO ===== */
        .hero {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 22px;
            margin-bottom: 26px;
        }
        @media (max-width: 980px) { .hero { grid-template-columns: 1fr; } }

        .hero-card {
            position: relative;
            overflow: hidden;
            border-radius: var(--radius-xl);
            background: linear-gradient(135deg, var(--deep-navy) 0%, var(--secondary-blue) 55%, #2a5a96 100%);
            color: var(--white);
            padding: 32px;
            box-shadow: var(--shadow-lg);
        }
        .hero-card::before {
            content: '';
            position: absolute;
            right: -80px; top: -80px;
            width: 300px; height: 300px;
            background: radial-gradient(closest-side, rgba(242,166,90,0.45), transparent 70%);
            filter: blur(2px);
        }
        .hero-card::after {
            content: '';
            position: absolute;
            left: -60px; bottom: -120px;
            width: 280px; height: 280px;
            background: radial-gradient(closest-side, rgba(255,255,255,0.10), transparent 70%);
        }

        .hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.18);
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--gold);
            backdrop-filter: blur(8px);
        }

        .hero-title {
            font-size: clamp(28px, 4vw, 40px);
            font-weight: 800;
            line-height: 1.1;
            margin: 14px 0 10px;
            letter-spacing: -0.5px;
            position: relative;
        }

        .hero-sub {
            color: rgba(255, 255, 255, 0.78);
            font-size: 15px;
            max-width: 520px;
            position: relative;
        }

        .hero-quick {
            margin-top: 22px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            position: relative;
        }

        .quick-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.10);
            border: 1px solid rgba(255, 255, 255, 0.18);
            color: var(--white);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            backdrop-filter: blur(10px);
        }
        .quick-chip:hover { background: rgba(255,255,255,0.18); transform: translateY(-1px); }
        .quick-chip i { color: var(--gold); }

        /* Stats panel */
        .stats-panel {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 18px;
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }

        .stat-card .icon {
            width: 38px; height: 38px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
            margin-bottom: 12px;
        }

        .stat-card.queue .icon  { background: rgba(242, 166, 90, 0.12); color: var(--gold); }
        .stat-card.total .icon  { background: rgba(1, 47, 107, 0.10); color: var(--deep-navy); }
        .stat-card.folder .icon { background: rgba(2, 132, 199, 0.10); color: var(--info); }
        .stat-card.role .icon   { background: rgba(22, 163, 74, 0.10); color: var(--success); }

        .stat-card .label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }

        .stat-card .value {
            font-size: 24px;
            font-weight: 800;
            color: var(--text);
            line-height: 1.1;
            margin-top: 4px;
        }

        /* ===== CONTROLS ===== */
        .controls {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 14px;
            box-shadow: var(--shadow-sm);
            display: flex;
            gap: 12px;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }

        .controls-left, .controls-right {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .country-wrap {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 4px;
            background: var(--surface-2);
            border: 1.5px solid var(--border);
            border-radius: 12px;
        }

        .country-btn {
            padding: 8px 14px;
            background: transparent;
            border: none;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .country-btn:hover { color: var(--text); }
        .country-btn.active {
            background: var(--surface);
            color: var(--deep-navy);
            box-shadow: var(--shadow-sm);
        }

        .btn-icon {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: var(--surface);
            border: 1.5px solid var(--border);
            color: var(--text);
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: inherit;
        }
        .btn-icon:hover {
            border-color: var(--border-strong);
            background: var(--surface-2);
        }
        .btn-icon i { color: var(--secondary-blue); }

        /* ===== UPLOAD CARD ===== */
        .upload-card {
            background: var(--surface);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .upload-header {
            background: var(--surface-2);
            padding: 16px 22px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .upload-header-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .upload-header-title .icon {
            width: 36px; height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, rgba(242, 166, 90, 0.20), rgba(1, 47, 107, 0.10));
            display: flex; align-items: center; justify-content: center;
            color: var(--gold);
            font-size: 18px;
        }

        .upload-header-title h5 {
            margin: 0;
            font-weight: 700;
            color: var(--text);
            font-size: 15px;
        }

        .upload-header-title p {
            margin: 0;
            font-size: 12px;
            color: var(--text-muted);
        }

        .upload-body { padding: 22px; }

        /* ===== DROP ZONE ===== */
        .drop-zone {
            border: 2px dashed var(--border-strong);
            border-radius: var(--radius-lg);
            padding: 48px 24px;
            text-align: center;
            background: linear-gradient(180deg, var(--surface-2) 0%, rgba(246, 248, 252, 0.6) 100%);
            cursor: pointer;
            transition: all 0.25s ease;
            position: relative;
            overflow: hidden;
        }

        .drop-zone::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(600px 200px at 50% 0%, rgba(242, 166, 90, 0.08), transparent 60%);
            opacity: 0;
            transition: opacity 0.25s ease;
            pointer-events: none;
        }

        .drop-zone:hover { border-color: var(--gold); }
        .drop-zone:hover::before { opacity: 1; }

        .drop-zone.dragover {
            border-color: var(--deep-navy);
            background: rgba(1, 47, 107, 0.03);
            transform: scale(1.005);
            box-shadow: var(--shadow-lg);
        }
        .drop-zone.dragover::before { opacity: 1; }

        .drop-icon {
            width: 76px;
            height: 76px;
            margin: 0 auto 18px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(1, 47, 107, 0.08), rgba(242, 166, 90, 0.12));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--deep-navy);
            font-size: 36px;
            position: relative;
            z-index: 1;
        }

        .drop-zone h4 {
            font-weight: 700;
            color: var(--text);
            margin-bottom: 6px;
            font-size: 18px;
            position: relative; z-index: 1;
        }

        .drop-zone p {
            color: var(--text-muted);
            margin-bottom: 20px;
            font-size: 14px;
            position: relative; z-index: 1;
        }

        .btn-browse {
            background: linear-gradient(135deg, var(--deep-navy), var(--secondary-blue));
            color: var(--white);
            padding: 11px 22px;
            border-radius: 10px;
            font-weight: 600;
            border: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: inherit;
            font-size: 14px;
            box-shadow: 0 8px 18px -8px rgba(1, 47, 107, 0.5);
            position: relative; z-index: 1;
        }

        .btn-browse:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 22px -8px rgba(1, 47, 107, 0.55);
            color: var(--white);
        }

        .browse-hint {
            margin-top: 12px;
            font-size: 12px;
            color: var(--text-soft);
            position: relative; z-index: 1;
        }

        .browse-hint kbd {
            background: var(--surface);
            border: 1px solid var(--border-strong);
            border-radius: 5px;
            padding: 2px 6px;
            font-size: 11px;
            font-weight: 600;
            color: var(--text);
            font-family: inherit;
        }

        /* ===== SELECTED FILES ===== */
        .selected-section { margin-top: 26px; }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .section-header h5 {
            font-weight: 700;
            color: var(--text);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 15px;
        }

        .badge-count {
            background: var(--gold);
            color: var(--white);
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            min-width: 26px;
            display: inline-flex;
            justify-content: center;
        }

        .btn-upload-now {
            background: linear-gradient(135deg, var(--success), var(--success-dark));
            color: var(--white);
            padding: 11px 22px;
            border-radius: 10px;
            font-weight: 600;
            border: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: inherit;
            font-size: 14px;
            box-shadow: 0 8px 18px -8px rgba(22, 163, 74, 0.5);
        }

        .btn-upload-now:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 12px 22px -8px rgba(22, 163, 74, 0.55);
            color: var(--white);
        }

        .btn-upload-now:disabled {
            opacity: 0.45;
            cursor: not-allowed;
            box-shadow: none;
        }

        .btn-clear {
            background: var(--surface);
            border: 1.5px solid var(--border);
            color: var(--text-muted);
            padding: 11px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-clear:hover {
            border-color: var(--danger);
            color: var(--danger);
        }

        /* ===== QUEUE GRID ===== */
        .queue-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 16px;
        }

        .queue-item {
            background: var(--surface);
            border-radius: var(--radius);
            overflow: hidden;
            border: 1px solid var(--border);
            position: relative;
            transition: all 0.25s ease;
            animation: fadeInUp 0.3s ease both;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .queue-item:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            border-color: var(--border-strong);
        }

        .queue-item .preview-container {
            height: 130px;
            overflow: hidden;
            position: relative;
            background: linear-gradient(135deg, #EEF2F8 0%, #E2E8F0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .queue-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .queue-item:hover img { transform: scale(1.04); }

        .queue-item .file-icon-fallback {
            font-size: 38px;
            color: var(--secondary-blue);
            opacity: 0.85;
        }

        .queue-item .file-info {
            padding: 10px 12px 12px;
            background: var(--surface);
        }

        .queue-item .file-name {
            font-weight: 600;
            font-size: 13px;
            color: var(--text);
            margin-bottom: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .queue-item .file-size { font-size: 11px; color: var(--text-muted); }

        .remove-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: rgba(11, 27, 54, 0.7);
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.2s ease;
            z-index: 10;
            opacity: 0;
            transform: scale(0.85);
            backdrop-filter: blur(6px);
        }

        .queue-item:hover .remove-btn { opacity: 1; transform: scale(1); }

        .remove-btn:hover {
            background: var(--danger);
            transform: scale(1.08) !important;
        }

        /* ===== PROGRESS ===== */
        .progress-container {
            margin-top: 26px;
            background: var(--surface-2);
            border-radius: var(--radius-lg);
            padding: 20px;
            border: 1px solid var(--border);
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
        }

        .progress-container h6 {
            font-weight: 700;
            color: var(--text);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        .progress-container h6 i {
            color: var(--gold);
            animation: spin 1.4s linear infinite;
        }

        .progress {
            height: 10px;
            border-radius: 999px;
            background: var(--border);
            overflow: hidden;
        }

        .progress-bar {
            background: linear-gradient(90deg, var(--deep-navy), var(--secondary-blue), var(--gold));
            background-size: 200% 100%;
            animation: progressShift 2s linear infinite;
            transition: width 0.3s ease;
            height: 100%;
        }

        @keyframes progressShift {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        #progressText {
            font-size: 13px;
            font-weight: 700;
            color: var(--deep-navy);
        }

        /* ===== FILES SECTION ===== */
        .files-section { margin-top: 36px; }

        .files-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .files-header h3 {
            font-weight: 700;
            color: var(--text);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 20px;
        }

        .files-header .section-icon {
            width: 36px; height: 36px;
            border-radius: 10px;
            background: rgba(1, 47, 107, 0.08);
            color: var(--deep-navy);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .refresh-btn {
            background: var(--surface);
            border: 1.5px solid var(--border);
            color: var(--text);
            padding: 9px 16px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-family: inherit;
        }

        .refresh-btn:hover {
            border-color: var(--border-strong);
            background: var(--surface-2);
        }

        .refresh-btn:hover i { transform: rotate(180deg); }
        .refresh-btn i { transition: transform 0.4s ease; color: var(--secondary-blue); }

        .search-wrap-files {
            position: relative;
            flex: 1;
            max-width: 360px;
            min-width: 220px;
        }

        .search-wrap-files i {
            position: absolute;
            left: 14px; top: 50%;
            transform: translateY(-50%);
            color: var(--text-soft);
            font-size: 15px;
        }

        #fileSearchInput {
            width: 100%;
            padding: 10px 14px 10px 40px;
            border: 1.5px solid var(--border);
            background: var(--surface-2);
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            color: var(--text);
            transition: all 0.2s ease;
            font-family: inherit;
        }

        #fileSearchInput:focus {
            outline: none;
            border-color: var(--secondary-blue);
            background: var(--surface);
            box-shadow: 0 0 0 4px rgba(37, 77, 129, 0.10);
        }

        /* ===== FILE GRID ===== */
        .file-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 18px;
        }

        .file-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            overflow: hidden;
            border: 1px solid var(--border);
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
            animation: fadeInUp 0.4s ease both;
        }

        .file-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--border-strong);
        }

        .file-preview {
            width: 100%;
            aspect-ratio: 16 / 11;
            background: linear-gradient(135deg, #EEF2F8 0%, #E2E8F0 100%);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .file-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.5s ease;
        }

        .file-card:hover .file-preview img { transform: scale(1.05); }

        .file-icon-large {
            font-size: 56px;
            color: var(--secondary-blue);
            opacity: 0.85;
        }

        .file-type-badge {
            position: absolute;
            top: 10px; left: 10px;
            padding: 4px 10px;
            border-radius: 999px;
            background: rgba(11, 27, 54, 0.78);
            color: var(--white);
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            backdrop-filter: blur(8px);
        }

        .file-info { padding: 14px 16px 16px; }

        .file-name {
            font-weight: 600;
            color: var(--text);
            font-size: 14px;
            margin-bottom: 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .file-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
            font-size: 12px;
            color: var(--text-muted);
        }

        .file-meta .dot-sep {
            width: 3px; height: 3px;
            border-radius: 50%;
            background: var(--text-soft);
        }

        .file-meta .ext-tag {
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .file-actions { display: flex; gap: 8px; }

        .btn-download {
            flex: 1;
            background: linear-gradient(135deg, var(--deep-navy), var(--secondary-blue));
            color: var(--white);
            padding: 9px 12px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            box-shadow: 0 6px 14px -6px rgba(1, 47, 107, 0.5);
        }

        .btn-download:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 20px -8px rgba(1, 47, 107, 0.55);
            color: var(--white);
        }

        .btn-delete {
            width: 38px;
            height: 38px;
            background: var(--surface);
            border: 1.5px solid var(--border);
            color: var(--danger);
            border-radius: 9px;
            font-size: 16px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .btn-delete:hover {
            background: var(--danger);
            color: var(--white);
            border-color: var(--danger);
            transform: translateY(-1px);
        }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 60px 24px;
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1.5px dashed var(--border-strong);
            grid-column: 1 / -1;
        }

        .empty-icon {
            font-size: 42px;
            margin-bottom: 14px;
            opacity: 0.7;
        }

        .empty-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 6px;
        }

        .empty-text {
            color: var(--text-muted);
            font-size: 14px;
            max-width: 360px;
            margin: 0 auto;
        }

        /* ===== LOADING ===== */
        .loading-overlay {
            position: fixed;
            inset: 0;
            background: rgba(246, 248, 252, 0.85);
            backdrop-filter: blur(6px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .loading-overlay .loader {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
        }

        .spinner {
            width: 46px; height: 46px;
            border: 3.5px solid var(--border);
            border-top-color: var(--deep-navy);
            border-right-color: var(--gold);
            border-radius: 50%;
            animation: spin 0.9s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        .loading-overlay .loader p {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
        }

        /* ===== SKELETON ===== */
        .skeleton-preview {
            width: 100%;
            aspect-ratio: 16 / 11;
            background: linear-gradient(90deg, #EEF2F8 0%, #F6F8FC 50%, #EEF2F8 100%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }

        .skeleton-line {
            height: 12px;
            background: linear-gradient(90deg, #EEF2F8 0%, #F6F8FC 50%, #EEF2F8 100%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 4px;
            margin: 8px 16px;
        }
        .skeleton-line.short { width: 60%; }
        .skeleton-line.medium { width: 80%; }

        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* ===== TOAST ===== */
        .toast-container {
            position: fixed;
            bottom: 24px; right: 24px;
            z-index: 9998;
            display: flex; flex-direction: column; gap: 10px;
        }

        .toast-notification {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px 16px;
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13.5px;
            font-weight: 500;
            color: var(--text);
            border-left: 4px solid;
            min-width: 250px;
            animation: slideIn 0.3s ease;
        }
        .toast-notification.success { border-left-color: var(--success); }
        .toast-notification.success i { color: var(--success); }
        .toast-notification.error { border-left-color: var(--danger); }
        .toast-notification.error i { color: var(--danger); }
        .toast-notification.info { border-left-color: var(--info); }
        .toast-notification.info i { color: var(--info); }

        @keyframes slideIn {
            from { transform: translateX(120%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* ===== CONFIRM MODAL ===== */
        .confirm-overlay {
            position: fixed;
            inset: 0;
            background: rgba(11, 27, 54, 0.5);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            padding: 20px;
        }

        .confirm-overlay.active { display: flex; }

        .confirm-modal {
            background: var(--surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
            max-width: 420px;
            width: 100%;
            overflow: hidden;
            animation: fadeInUp 0.25s ease;
        }

        .confirm-modal .head {
            padding: 22px 22px 0;
            display: flex;
            gap: 14px;
            align-items: flex-start;
        }

        .confirm-modal .head .icon {
            flex-shrink: 0;
            width: 44px; height: 44px;
            border-radius: 12px;
            background: rgba(220, 38, 38, 0.10);
            color: var(--danger);
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
        }

        .confirm-modal .head h4 {
            font-size: 16px;
            font-weight: 700;
            color: var(--text);
            margin: 0 0 4px;
        }

        .confirm-modal .head p {
            font-size: 13.5px;
            color: var(--text-muted);
            margin: 0;
            line-height: 1.5;
        }

        .confirm-modal .foot {
            padding: 22px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .confirm-modal button {
            padding: 9px 18px;
            border-radius: 9px;
            font-weight: 600;
            font-size: 13.5px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: inherit;
            border: 1.5px solid var(--border);
            background: var(--surface);
            color: var(--text);
        }
        .confirm-modal button:hover { background: var(--surface-2); }
        .confirm-modal button.danger {
            background: var(--danger);
            color: var(--white);
            border-color: var(--danger);
        }
        .confirm-modal button.danger:hover {
            background: #b91c1c;
            border-color: #b91c1c;
        }

        /* ===== SCROLLBAR ===== */
        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb {
            background: var(--border-strong);
            border-radius: 999px;
            border: 2px solid var(--bg);
        }
        ::-webkit-scrollbar-thumb:hover { background: var(--text-soft); }

        @media (max-width: 640px) {
            .container-x { padding: 18px 14px 60px; }
            .hero-card { padding: 24px; }
            .stats-panel { grid-template-columns: 1fr 1fr; }
            .upload-body { padding: 16px; }
            .drop-zone { padding: 36px 16px; }
        }
    </style>
</head>
<body>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loader">
        <div class="spinner"></div>
        <p id="loaderText">Working…</p>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Confirm Modal -->
<div class="confirm-overlay" id="confirmOverlay">
    <div class="confirm-modal">
        <div class="head">
            <div class="icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div>
                <h4 id="confirmTitle">Delete file?</h4>
                <p id="confirmText">This action cannot be undone.</p>
            </div>
        </div>
        <div class="foot">
            <button id="confirmCancel" type="button">Cancel</button>
            <button id="confirmOk" type="button" class="danger">Delete</button>
        </div>
    </div>
</div>

<!-- Top Nav -->
<div class="topbar">
    <div class="topbar-inner">
        <a href="#" class="brand">
            <div class="brand-mark">X</div>
            <div class="brand-text">
                <span class="name">Xander</span>
                <span class="sub">File Manager</span>
            </div>
        </a>
        <div class="top-actions">
            <?php if ($canDeleteMarketing): ?>
            <div class="pill role-badge">
                <span class="dot"></span>
                <span>Super Admin</span>
            </div>
            <?php endif; ?>
            <div class="pill">
                <span class="dot"></span>
                <span>Connected to pCloud</span>
            </div>
        </div>
    </div>
</div>

<main class="container-x">

    <!-- Hero + Stats -->
    <section class="hero">
        <div class="hero-card">
            <span class="hero-eyebrow">
                <i class="bi bi-cloud-upload-fill"></i> Upload
            </span>
            <h1 class="hero-title">Upload marketing materials.</h1>
            <p class="hero-sub">Drop images, videos, or documents into the marketing folder. Files are uploaded directly to pCloud and made available to the team.</p>
            <div class="hero-quick">
                <button class="quick-chip" id="heroChooseBtn">
                    <i class="bi bi-folder2-open"></i> Choose Files
                </button>
                <button class="quick-chip" onclick="document.getElementById('filesSection').scrollIntoView({behavior:'smooth'})">
                    <i class="bi bi-grid-3x3-gap"></i> View Uploaded Files
                </button>
            </div>
        </div>

        <div class="stats-panel">
            <div class="stat-card folder">
                <div class="icon"><i class="bi bi-folder-fill"></i></div>
                <div class="label">Current Folder</div>
                <div class="value" id="currentFolderLabel">🇷🇼 Rwanda</div>
            </div>
            <div class="stat-card total">
                <div class="icon"><i class="bi bi-collection"></i></div>
                <div class="label">Files in Folder</div>
                <div class="value" id="totalFilesCount">0</div>
            </div>
            <div class="stat-card queue">
                <div class="icon"><i class="bi bi-upload"></i></div>
                <div class="label">In Upload Queue</div>
                <div class="value" id="queueCount">0</div>
            </div>
            <div class="stat-card role">
                <div class="icon"><i class="bi bi-shield-check"></i></div>
                <div class="label">Your Role</div>
                <div class="value" style="font-size:16px;"><?= $canDeleteMarketing ? 'Super Admin' : 'Standard Admin' ?></div>
            </div>
        </div>
    </section>

    <!-- Controls -->
    <div class="controls">
        <div class="controls-left">
            <div class="country-wrap" role="tablist" aria-label="Country">
                <button class="country-btn active" data-country="rwanda" type="button">
                    <span>🇷🇼</span> Rwanda
                </button>
                <button class="country-btn" data-country="burundi" type="button">
                    <span>🇧🇮</span> Burundi
                </button>
            </div>
        </div>
        <div class="controls-right">
            <button class="btn-icon" type="button" id="topChooseBtn">
                <i class="bi bi-folder2-open"></i> Choose Files
            </button>
        </div>
    </div>

    <!-- Upload Card -->
    <div class="upload-card">
        <div class="upload-header">
            <div class="upload-header-title">
                <div class="icon"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                <div>
                    <h5>Upload New Files</h5>
                    <p>Drop here or click to browse. Supports images, videos, documents.</p>
                </div>
            </div>
        </div>

        <div class="upload-body">
            <!-- Drop Zone -->
            <div class="drop-zone" id="dropZone">
                <div class="drop-icon">
                    <i class="bi bi-cloud-arrow-up"></i>
                </div>
                <h4>Drag &amp; drop files here</h4>
                <p>or click anywhere in this area to browse</p>
                <input type="file" id="fileInput" hidden multiple>
                <button class="btn-browse" id="chooseBtn" type="button">
                    <i class="bi bi-folder2-open"></i>
                    Choose Files
                </button>
                <div class="browse-hint">
                    Tip: press <kbd>Ctrl</kbd> + <kbd>U</kbd> to open the file picker quickly.
                </div>
            </div>

            <!-- Selected Files -->
            <div class="selected-section" id="selectedSection" style="display: none;">
                <div class="section-header">
                    <h5>
                        <i class="bi bi-files"></i>
                        Files Selected
                        <span class="badge-count" id="selectedCount">0</span>
                    </h5>
                    <div style="display:flex; gap:10px; flex-wrap: wrap;">
                        <button class="btn-clear" id="clearQueueBtn" type="button">
                            <i class="bi bi-x-lg"></i> Clear all
                        </button>
                        <button class="btn-upload-now" id="uploadNowBtn" disabled>
                            <i class="bi bi-cloud-upload"></i>
                            Upload Selected Files
                        </button>
                    </div>
                </div>

                <div id="queueGrid" class="queue-grid"></div>
            </div>

            <!-- Progress -->
            <div class="progress-container" id="progressContainer" style="display: none;">
                <div class="progress-header">
                    <h6>
                        <i class="bi bi-arrow-repeat"></i>
                        Uploading…
                    </h6>
                    <span id="progressText">0%</span>
                </div>
                <div class="progress">
                    <div id="progressBar" class="progress-bar" style="width: 0%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Files in folder -->
    <div class="files-section" id="filesSection">
        <div class="files-header">
            <h3>
                <span class="section-icon"><i class="bi bi-folder2-open"></i></span>
                Files in folder
            </h3>
            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                <div class="search-wrap-files">
                    <i class="bi bi-search"></i>
                    <input type="text" id="fileSearchInput" placeholder="Search files…" autocomplete="off">
                </div>
                <button class="refresh-btn" onclick="loadFiles()" type="button">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
        </div>

        <div id="filesGrid" class="file-grid"></div>
    </div>
</main>

<script>
const COUNTRY_FOLDERS = <?= json_encode($COUNTRY_FOLDERS) ?>;
let currentCountry = 'rwanda';
let currentFolderId = COUNTRY_FOLDERS[currentCountry];
const ACCESS_TOKEN = "kqNT7Z8BpwhA0d4MFZVgju0kZbR12PpsX93VWhpTOL5i4jVefcDdX";
const CAN_DELETE_MARKETING = <?= $canDeleteMarketing ? 'true' : 'false' ?>;
let uploadQueue = [];

const fileInput = document.getElementById("fileInput");
const dropZone = document.getElementById("dropZone");
const queueGrid = document.getElementById("queueGrid");
const uploadNowBtn = document.getElementById("uploadNowBtn");
const selectedSection = document.getElementById("selectedSection");
const selectedCount = document.getElementById("selectedCount");
const loadingOverlay = document.getElementById("loadingOverlay");
const loaderText = document.getElementById("loaderText");
const toastContainer = document.getElementById("toastContainer");
const currentFolderLabel = document.getElementById("currentFolderLabel");
const totalFilesCount = document.getElementById("totalFilesCount");
const queueCount = document.getElementById("queueCount");
const fileSearchInput = document.getElementById("fileSearchInput");

// ===== COUNTRY BUTTONS =====
document.querySelectorAll('.country-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        if (btn.classList.contains('active')) return;
        document.querySelectorAll('.country-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentCountry = btn.dataset.country;
        currentFolderId = COUNTRY_FOLDERS[currentCountry];

        const countryName = currentCountry === 'rwanda' ? 'Rwanda' : 'Burundi';
        const countryFlag = currentCountry === 'rwanda' ? '🇷🇼' : '🇧🇮';
        currentFolderLabel.textContent = `${countryFlag} ${countryName}`;

        uploadQueue = [];
        renderQueue();
        loadFiles();
        showToast(`Switched to ${countryName} folder`, 'info');
    });
});

// ===== TOAST =====
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    const icon = type === 'success' ? 'check-circle-fill'
              : type === 'error'   ? 'exclamation-triangle-fill'
              : 'info-circle-fill';
    toast.innerHTML = `<i class="bi bi-${icon}"></i><span>${message}</span>`;
    toastContainer.appendChild(toast);
    setTimeout(() => {
        toast.style.animation = 'slideIn 0.3s reverse';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ===== CONFIRM MODAL =====
function showConfirm({ title, text, okText = 'Confirm', danger = true }) {
    return new Promise(resolve => {
        const overlay = document.getElementById('confirmOverlay');
        document.getElementById('confirmTitle').textContent = title;
        document.getElementById('confirmText').textContent = text;
        const ok = document.getElementById('confirmOk');
        const cancel = document.getElementById('confirmCancel');
        ok.textContent = okText;
        ok.className = danger ? 'danger' : '';
        overlay.classList.add('active');

        const cleanup = () => {
            overlay.classList.remove('active');
            ok.removeEventListener('click', onOk);
            cancel.removeEventListener('click', onCancel);
        };
        const onOk = () => { cleanup(); resolve(true); };
        const onCancel = () => { cleanup(); resolve(false); };
        ok.addEventListener('click', onOk);
        cancel.addEventListener('click', onCancel);
    });
}

// ===== UTILITIES =====
function formatFileSize(bytes) {
    if (!bytes || bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function getThumbnailUrl(fileId) {
    return `https://api.pcloud.com/getthumb?fileid=${fileId}&access_token=${ACCESS_TOKEN}&size=400x300&type=auto`;
}

function getFileTypeIcon(ext) {
    const map = {
        'pdf':  { icon: 'bi-file-earmark-pdf-fill', color: '#e74c3c' },
        'doc':  { icon: 'bi-file-earmark-word-fill', color: '#2b579a' },
        'docx': { icon: 'bi-file-earmark-word-fill', color: '#2b579a' },
        'xls':  { icon: 'bi-file-earmark-excel-fill', color: '#1d6f42' },
        'xlsx': { icon: 'bi-file-earmark-excel-fill', color: '#1d6f42' },
        'ppt':  { icon: 'bi-file-earmark-ppt-fill', color: '#d24726' },
        'pptx': { icon: 'bi-file-earmark-ppt-fill', color: '#d24726' },
        'txt':  { icon: 'bi-file-earmark-text-fill', color: '#7f8c8d' },
        'zip':  { icon: 'bi-file-earmark-zip-fill', color: '#f39c12' },
        'rar':  { icon: 'bi-file-earmark-zip-fill', color: '#f39c12' },
        'mp4':  { icon: 'bi-file-earmark-play-fill', color: '#0284c7' },
        'mov':  { icon: 'bi-file-earmark-play-fill', color: '#0284c7' },
        'mp3':  { icon: 'bi-file-earmark-music-fill', color: '#9b59b6' },
        'wav':  { icon: 'bi-file-earmark-music-fill', color: '#9b59b6' }
    };
    const t = map[ext] || { icon: 'bi-file-earmark-fill', color: '#5B6A86' };
    return `<i class="bi ${t.icon}" style="color: ${t.color};"></i>`;
}

// ===== LOAD FILES =====
async function loadFiles() {
    const grid = document.getElementById("filesGrid");

    grid.innerHTML = Array(4).fill(0).map(() => `
        <div class="file-card">
            <div class="skeleton-preview"></div>
            <div class="file-info">
                <div class="skeleton-line medium"></div>
                <div class="skeleton-line short"></div>
                <div style="display: flex; gap: 8px; padding-top: 6px;">
                    <div class="skeleton-line" style="flex: 1; height: 32px; margin: 0;"></div>
                    <div class="skeleton-line" style="width: 38px; height: 32px; margin: 0;"></div>
                </div>
            </div>
        </div>
    `).join('');

    try {
        const response = await fetch(`https://api.pcloud.com/listfolder?folderid=${currentFolderId}&access_token=${ACCESS_TOKEN}`);
        const data = await response.json();

        grid.innerHTML = "";

        if (!data || data.result !== 0) {
            totalFilesCount.textContent = 0;
            showToast("Failed to load files", "error");
            grid.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">📁</div>
                    <div class="empty-title">No Files Found</div>
                    <div class="empty-text">Upload your first file to get started.</div>
                </div>
            `;
            return;
        }

        const contents = data.metadata.contents || [];
        const files = contents.filter(item => !item.isfolder);
        totalFilesCount.textContent = files.length;

        if (files.length === 0) {
            grid.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">📁</div>
                    <div class="empty-title">No Files Found</div>
                    <div class="empty-text">Upload your first file to get started.</div>
                </div>
            `;
            return;
        }

        files.sort((a, b) => a.name.localeCompare(b.name));

        for (const file of files) {
            const isImage = file.name.match(/\.(jpg|jpeg|png|gif|webp|bmp|svg)$/i);
            const fileSize = formatFileSize(file.size);
            const fileExt = (file.name.split('.').pop() || 'file').toLowerCase();

            const fileCard = document.createElement('div');
            fileCard.className = 'file-card';
            fileCard.dataset.name = file.name.toLowerCase();

            let downloadUrl = '#';
            try {
                const linkResponse = await fetch(`https://api.pcloud.com/getfilelink?fileid=${file.fileid}&access_token=${ACCESS_TOKEN}`);
                const linkData = await linkResponse.json();
                if (linkData && linkData.hosts && linkData.hosts[0]) {
                    downloadUrl = `https://${linkData.hosts[0]}${linkData.path}`;
                }
            } catch (e) {
                console.error('Error getting download link:', e);
            }

            let previewHtml = '';
            if (isImage) {
                const thumbUrl = getThumbnailUrl(file.fileid);
                previewHtml = `
                    <div class="file-preview">
                        <span class="file-type-badge">${fileExt}</span>
                        <img src="${thumbUrl}" alt="${file.name}"
                             onerror="this.onerror=null; this.parentElement.innerHTML='<span class=\\'file-type-badge\\'>${fileExt}</span><div class=\\'file-icon-large\\'><i class=\\'bi bi-image\\'></i></div>'">
                    </div>
                `;
            } else {
                previewHtml = `
                    <div class="file-preview">
                        <span class="file-type-badge">${fileExt}</span>
                        <div class="file-icon-large">${getFileTypeIcon(fileExt)}</div>
                    </div>
                `;
            }

            fileCard.innerHTML = `
                ${previewHtml}
                <div class="file-info">
                    <div class="file-name" title="${file.name}">${file.name}</div>
                    <div class="file-meta">
                        <span>${fileSize}</span>
                        <span class="dot-sep"></span>
                        <span class="ext-tag">${fileExt}</span>
                    </div>
                    <div class="file-actions">
                        <a href="${downloadUrl}" class="btn-download" target="_blank" rel="noopener">
                            <i class="bi bi-download"></i> Download
                        </a>
                        ${CAN_DELETE_MARKETING ? `
                        <button type="button" class="btn-delete" onclick="deleteFile(${file.fileid}, '${file.name.replace(/'/g, "\\'")}')" title="Delete file">
                            <i class="bi bi-trash"></i>
                        </button>` : ''}
                    </div>
                </div>
            `;

            grid.appendChild(fileCard);
        }

        // Apply current search filter
        applyFileSearch();

    } catch (error) {
        console.error("Error loading files:", error);
        showToast("Error connecting to server", "error");
        grid.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">⚠️</div>
                <div class="empty-title">Connection Error</div>
                <div class="empty-text">Please try refreshing the page.</div>
            </div>
        `;
    }
}

// ===== FILE SEARCH =====
function applyFileSearch() {
    const term = (fileSearchInput?.value || '').toLowerCase().trim();
    document.querySelectorAll('#filesGrid .file-card').forEach(card => {
        const name = card.dataset.name || '';
        card.style.display = (term === '' || name.includes(term)) ? '' : 'none';
    });
}
fileSearchInput?.addEventListener('input', applyFileSearch);

// ===== ADD TO QUEUE =====
function addToQueue(files) {
    for (let file of files) {
        const isDuplicate = uploadQueue.some(f => f.name === file.name && f.size === file.size);
        if (!isDuplicate) uploadQueue.push(file);
    }
    renderQueue();
    if (uploadQueue.length > 0) selectedSection.style.display = 'block';
}

function renderQueue() {
    queueGrid.innerHTML = "";
    uploadNowBtn.disabled = uploadQueue.length === 0;
    selectedCount.textContent = uploadQueue.length;
    queueCount.textContent = uploadQueue.length;

    if (uploadQueue.length === 0) {
        selectedSection.style.display = 'none';
        return;
    }

    uploadQueue.forEach((file, index) => {
        const isImage = file.type.startsWith('image/');
        const fileSize = formatFileSize(file.size);
        const ext = (file.name.split('.').pop() || 'file').toLowerCase();

        const item = document.createElement('div');
        item.className = 'queue-item';

        const previewHtml = isImage
            ? `<img src="${URL.createObjectURL(file)}" alt="${file.name}">`
            : `<div class="file-icon-fallback">${getFileTypeIcon(ext)}</div>`;

        item.innerHTML = `
            <div class="preview-container">${previewHtml}</div>
            <div class="file-info">
                <div class="file-name" title="${file.name}">${file.name}</div>
                <div class="file-size">${fileSize}</div>
            </div>
            <button class="remove-btn" onclick="removeFromQueue(${index})" title="Remove">
                <i class="bi bi-x"></i>
            </button>
        `;
        queueGrid.appendChild(item);
    });
}

function removeFromQueue(index) {
    uploadQueue.splice(index, 1);
    renderQueue();
}

document.getElementById('clearQueueBtn').addEventListener('click', () => {
    if (uploadQueue.length === 0) return;
    uploadQueue = [];
    renderQueue();
    showToast('Queue cleared', 'info');
});

// ===== DRAG & DROP =====
dropZone.addEventListener("dragover", (e) => {
    e.preventDefault();
    dropZone.classList.add("dragover");
});

dropZone.addEventListener("dragleave", () => {
    dropZone.classList.remove("dragover");
});

dropZone.addEventListener("drop", (e) => {
    e.preventDefault();
    dropZone.classList.remove("dragover");
    addToQueue(e.dataTransfer.files);
});

// Click anywhere in the drop zone (except the button) to open picker
dropZone.addEventListener('click', (e) => {
    if (e.target.closest('button')) return;
    fileInput.click();
});

document.getElementById("chooseBtn").onclick = (e) => { e.stopPropagation(); fileInput.click(); };
document.getElementById("topChooseBtn").onclick = () => fileInput.click();
document.getElementById("heroChooseBtn").onclick = () => fileInput.click();

fileInput.onchange = () => {
    addToQueue(fileInput.files);
    fileInput.value = '';
};

// ===== UPLOAD =====
uploadNowBtn.onclick = async () => {
    if (uploadQueue.length === 0) return;

    loaderText.textContent = 'Preparing upload…';
    loadingOverlay.style.display = 'flex';

    const formData = new FormData();
    formData.append("folderid", currentFolderId);
    uploadQueue.forEach(file => formData.append("files[]", file));

    try {
        const response = await fetch("upload-pcloud.php", { method: "POST", body: formData });
        const result = await response.json();

        if (!result.success) {
            showToast("Upload failed: " + (result.error || "Unknown error"), "error");
            loadingOverlay.style.display = 'none';
            return;
        }

        const hash = result.progresshash;
        const progressContainer = document.getElementById("progressContainer");
        const progressBar = document.getElementById("progressBar");
        const progressText = document.getElementById("progressText");

        progressContainer.style.display = "block";
        loadingOverlay.style.display = 'none';

        let finished = false;
        while (!finished) {
            await new Promise(resolve => setTimeout(resolve, 800));
            const progressResponse = await fetch("pcloud-progress.php?hash=" + hash);
            const progressData = await progressResponse.json();
            if (!progressData.success) continue;

            const percent = progressData.total > 0
                ? Math.floor((progressData.uploaded / progressData.total) * 100)
                : 0;
            progressBar.style.width = percent + "%";
            progressText.innerText = percent + "%";
            finished = progressData.finished;
        }

        progressBar.style.width = "100%";
        progressText.innerText = "100%";
        showToast("Files uploaded successfully", "success");

        uploadQueue = [];
        renderQueue();
        loadFiles();

        setTimeout(() => {
            progressContainer.style.display = "none";
            progressBar.style.width = "0%";
            progressText.innerText = "0%";
        }, 2000);

    } catch (error) {
        console.error("Upload error:", error);
        showToast("Upload failed: Connection error", "error");
        loadingOverlay.style.display = 'none';
    }
};

// ===== DELETE (superadmin only) =====
async function deleteFile(fileId, fileName) {
    if (!CAN_DELETE_MARKETING) {
        showToast("Only Super Admin can delete marketing files", "error");
        return;
    }
    const confirmed = await showConfirm({
        title: 'Delete this file?',
        text: fileName ? `“${fileName}” will be permanently removed from the marketing folder. This cannot be undone.` : 'This action cannot be undone.',
        okText: 'Delete',
        danger: true
    });
    if (!confirmed) return;

    loaderText.textContent = 'Deleting file…';
    loadingOverlay.style.display = 'flex';

    try {
        const response = await fetch("delete-marketing-pcloud.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "fileid=" + encodeURIComponent(fileId),
            credentials: "same-origin"
        });
        const result = await response.json();

        if (result && result.success) {
            showToast("File deleted successfully", "success");
            loadFiles();
        } else {
            showToast("Delete failed: " + (result.error || "Unknown error"), "error");
        }
    } catch (error) {
        console.error("Delete error:", error);
        showToast("Delete failed: Connection error", "error");
    }
    loadingOverlay.style.display = 'none';
}

// ===== KEYBOARD SHORTCUTS =====
document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'u') {
        e.preventDefault();
        fileInput.click();
    }
    if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key.toLowerCase() === 'r') {
        e.preventDefault();
        loadFiles();
    }
    if (e.key === 'Escape') {
        const overlay = document.getElementById('confirmOverlay');
        if (overlay.classList.contains('active')) {
            overlay.classList.remove('active');
        }
    }
});

// ===== INIT =====
document.addEventListener('DOMContentLoaded', () => {
    loadFiles();
});
</script>

</body>
</html>
