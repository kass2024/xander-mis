<?php
require "marketing-openai.php"; // AI helper

// Country folder mappings
$COUNTRY_FOLDERS = [
    'rwanda'  => 30592893924,
    'burundi' => 31502860318,
    'goma'    => 31569571029, // Goma, DRC
    'kampala' => 31569568775, // Kampala, Uganda
    'nairobi' => 31569623770, // Nairobi, Kenya
];

// Display metadata for the country picker (label + flag emoji)
$COUNTRY_META = [
    'rwanda'  => ['label' => 'Rwanda',          'flag' => '🇷🇼'],
    'burundi' => ['label' => 'Burundi',         'flag' => '🇧🇮'],
    'goma'    => ['label' => 'Goma, DRC',       'flag' => '🇨🇩'],
    'kampala' => ['label' => 'Kampala, Uganda', 'flag' => '🇺🇬'],
    'nairobi' => ['label' => 'Nairobi, Kenya',  'flag' => '🇰🇪'],
];

// Initial country: ?country=xxx takes precedence, fallback to rwanda
$requestedCountry = strtolower(trim((string) ($_GET['country'] ?? '')));
$initialCountry = isset($COUNTRY_FOLDERS[$requestedCountry]) ? $requestedCountry : 'rwanda';

//-----------------------------------------------------------
// AJAX ENDPOINT - returns categorized + searchable JSON
//-----------------------------------------------------------
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    header("Content-Type: application/json");

    $token = "kqNT7Z8BpwhA0d4MFZVgju0kZbR12PpsX93VWhpTOL5i4jVefcDdX";

    // Get country from request (default to rwanda)
    $country = $_GET['country'] ?? 'rwanda';
    $country = in_array($country, array_keys($COUNTRY_FOLDERS)) ? $country : 'rwanda';

    // Use the folder ID for selected country
    $folderId = $COUNTRY_FOLDERS[$country];

    $listUrl = "https://api.pcloud.com/listfolder?folderid=$folderId&recursive=1&access_token=$token";
    $res = file_get_contents($listUrl);
    $json = json_decode($res, true);

    if (!$json || !isset($json['metadata'])) {
        echo json_encode(["error" => true]); exit;
    }

    function flat($items, &$out) {
        foreach ($items as $i) {
            if (!$i['isfolder']) $out[] = $i;
            if ($i['isfolder'] && isset($i['contents'])) flat($i['contents'], $out);
        }
    }

    $all = [];
    flat($json['metadata']['contents'], $all);

    $images = [];
    $videos = [];
    $others = [];

    foreach ($all as $f) {
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp','svg','bmp'])) $images[] = $f;
        elseif (in_array($ext, ['mp4','mov','avi','webm','mkv','wmv','flv','m4v'])) $videos[] = $f;
        else $others[] = $f;
    }

    echo json_encode([
        "images" => $images,
        "videos" => $videos,
        "others" => $others
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xander · Marketing Assets</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://vjs.zencdn.net/8.10.0/video-js.css" rel="stylesheet" />
    <link href="https://unpkg.com/@videojs/themes/dist/city/index.css" rel="stylesheet">
    <script src="https://vjs.zencdn.net/8.10.0/video.min.js"></script>

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
            letter-spacing: 0.5px;
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

        .brand-text {
            display: flex;
            flex-direction: column;
            line-height: 1.1;
        }

        .brand-text .name {
            font-weight: 800;
            font-size: 16px;
            color: var(--text);
            letter-spacing: 0.2px;
        }

        .brand-text .sub {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            letter-spacing: 1.4px;
            text-transform: uppercase;
        }

        .top-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

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

        /* ===== HERO ===== */
        .container-x {
            max-width: 1400px;
            margin: 0 auto;
            padding: 28px 24px 80px;
            position: relative;
            z-index: 1;
        }

        .hero {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 22px;
            margin-bottom: 26px;
        }

        @media (max-width: 980px) {
            .hero { grid-template-columns: 1fr; }
        }

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

        .quick-chip:hover {
            background: rgba(255, 255, 255, 0.18);
            transform: translateY(-1px);
        }

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
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .stat-card .icon {
            width: 38px; height: 38px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
            margin-bottom: 12px;
        }

        .stat-card.images .icon { background: rgba(2, 132, 199, 0.10); color: var(--info); }
        .stat-card.videos .icon { background: rgba(220, 38, 38, 0.10); color: var(--danger); }
        .stat-card.others .icon { background: rgba(234, 88, 12, 0.10); color: var(--warning); }
        .stat-card.total  .icon { background: rgba(1, 47, 107, 0.10); color: var(--deep-navy); }

        .stat-card .label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }

        .stat-card .value {
            font-size: 28px;
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
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 12px;
            align-items: center;
            margin-bottom: 22px;
        }

        @media (max-width: 820px) {
            .controls { grid-template-columns: 1fr; }
        }

        .search-wrap {
            position: relative;
        }

        .search-wrap i {
            position: absolute;
            left: 16px; top: 50%;
            transform: translateY(-50%);
            color: var(--text-soft);
            font-size: 16px;
        }

        #searchInput {
            width: 100%;
            padding: 12px 44px 12px 44px;
            border: 1.5px solid var(--border);
            background: var(--surface-2);
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            color: var(--text);
            transition: all 0.2s ease;
        }

        #searchInput::placeholder { color: var(--text-soft); }

        #searchInput:focus {
            outline: none;
            border-color: var(--secondary-blue);
            background: var(--surface);
            box-shadow: 0 0 0 4px rgba(37, 77, 129, 0.10);
        }

        .kbd {
            position: absolute;
            right: 12px; top: 50%;
            transform: translateY(-50%);
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 3px 7px;
            border-radius: 6px;
            background: var(--bg);
            border: 1px solid var(--border);
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 600;
        }

        .country-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
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

        /* ---------- Select2 country dropdown ---------- */
        .country-select-wrap { min-width: 240px; }
        .country-select-wrap .select2-container { width: 100% !important; }
        .country-select-wrap .select2-container--default .select2-selection--single {
            height: 42px;
            display: flex;
            align-items: center;
            padding: 0 12px;
            background: var(--surface);
            border: 1.5px solid var(--border);
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            transition: all .2s ease;
        }
        .country-select-wrap .select2-container--default .select2-selection--single:hover {
            border-color: var(--border-strong);
        }
        .country-select-wrap .select2-container--default.select2-container--focus .select2-selection--single,
        .country-select-wrap .select2-container--default.select2-container--open .select2-selection--single {
            border-color: var(--deep-navy);
            box-shadow: 0 0 0 3px rgba(0, 47, 107, 0.12);
        }
        .country-select-wrap .select2-selection__rendered {
            line-height: 1.2 !important;
            color: var(--deep-navy) !important;
            font-weight: 600;
            padding: 0 !important;
            display: flex !important;
            align-items: center;
            gap: 8px;
        }
        .country-select-wrap .select2-selection__arrow {
            height: 40px !important;
            right: 6px !important;
        }
        .country-option {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }
        .country-option .country-flag { font-size: 1.05rem; line-height: 1; }
        .select2-dropdown {
            border: 1.5px solid var(--border) !important;
            border-radius: 12px !important;
            overflow: hidden;
            box-shadow: 0 8px 28px rgba(0, 0, 0, .12) !important;
        }
        .select2-results__option--highlighted[aria-selected],
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background: var(--deep-navy) !important;
            color: #fff !important;
        }
        .select2-search--dropdown .select2-search__field {
            border: 1.5px solid var(--border) !important;
            border-radius: 8px !important;
            padding: 8px 10px !important;
        }

        .view-toggle {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 4px;
            background: var(--surface-2);
            border: 1.5px solid var(--border);
            border-radius: 12px;
        }

        .view-btn {
            width: 36px; height: 36px;
            background: transparent;
            border: none;
            border-radius: 9px;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .view-btn:hover { color: var(--text); }

        .view-btn.active {
            background: var(--surface);
            color: var(--deep-navy);
            box-shadow: var(--shadow-sm);
        }

        /* ===== TABS ===== */
        .tabs {
            display: flex;
            gap: 6px;
            padding: 6px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 24px;
            overflow-x: auto;
        }

        .tab {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 10px;
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 13.5px;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .tab:hover { color: var(--text); background: var(--surface-2); }

        .tab.active {
            color: var(--white);
            background: linear-gradient(135deg, var(--deep-navy), var(--secondary-blue));
            box-shadow: var(--shadow-md);
        }

        .tab .badge-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 22px;
            height: 20px;
            padding: 0 7px;
            border-radius: 999px;
            background: var(--bg);
            color: var(--text-muted);
            font-size: 11px;
            font-weight: 700;
        }

        .tab.active .badge-count {
            background: rgba(255, 255, 255, 0.18);
            color: var(--white);
        }

        /* ===== AI INSIGHTS ===== */
        .ai-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            padding: 18px 20px;
            margin-bottom: 28px;
            display: flex;
            gap: 16px;
            align-items: flex-start;
            position: relative;
            overflow: hidden;
        }

        .ai-card::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 4px;
            background: linear-gradient(180deg, var(--gold), var(--secondary-blue));
        }

        .ai-icon {
            flex-shrink: 0;
            width: 42px; height: 42px;
            border-radius: 11px;
            background: linear-gradient(135deg, rgba(242,166,90,0.15), rgba(1,47,107,0.10));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold);
            font-size: 20px;
        }

        .ai-content { flex: 1; min-width: 0; }

        .ai-title {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-muted);
            letter-spacing: 0.8px;
            text-transform: uppercase;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .ai-title .live {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 10px;
            color: var(--success);
        }

        .ai-title .live::before {
            content: '';
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--success);
            animation: pulse 1.6s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.8); }
        }

        #ai-box {
            color: var(--text);
            line-height: 1.55;
            font-size: 14px;
        }

        /* ===== SECTIONS ===== */
        .section { margin-bottom: 36px; scroll-margin-top: 90px; }
        .section.is-hidden { display: none; }

        .section-head {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .section-icon {
            width: 36px; height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .section-icon.images { background: rgba(2, 132, 199, 0.10); color: var(--info); }
        .section-icon.videos { background: rgba(220, 38, 38, 0.10); color: var(--danger); }
        .section-icon.others { background: rgba(234, 88, 12, 0.10); color: var(--warning); }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text);
            margin: 0;
        }

        .section-count {
            margin-left: 4px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            background: var(--bg);
            padding: 3px 10px;
            border-radius: 999px;
        }

        /* ===== GRID ===== */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 18px;
        }

        .grid.list-view {
            grid-template-columns: 1fr;
        }

        .file-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
            position: relative;
            animation: fadeInUp 0.4s ease both;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .file-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--border-strong);
        }

        .media-preview {
            position: relative;
            aspect-ratio: 16 / 11;
            background:
                linear-gradient(135deg, #EEF2F8 0%, #E2E8F0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .media-preview .skeleton {
            position: absolute; inset: 0;
            background: linear-gradient(90deg, #EEF2F8 0%, #F6F8FC 50%, #EEF2F8 100%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }

        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .media-preview .thumb {
            width: 100%; height: 100%;
            object-fit: cover;
            opacity: 0;
            transition: opacity 0.4s ease, transform 0.5s ease;
        }

        .file-card:hover .thumb { transform: scale(1.05); }
        .thumb.loaded { opacity: 1; }

        .file-icon-wrap {
            font-size: 2.6rem;
            color: var(--secondary-blue);
            opacity: 0.85;
        }

        .badge-type {
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

        .play-button {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 56px; height: 56px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 12px 26px rgba(0, 0, 0, 0.25);
        }

        .play-button:hover {
            background: var(--gold);
            transform: translate(-50%, -50%) scale(1.08);
        }

        .play-button i {
            color: var(--deep-navy);
            font-size: 22px;
            margin-left: 3px;
            transition: color 0.2s ease;
        }

        .play-button:hover i { color: var(--white); }

        .file-info { padding: 14px 16px 16px; }

        .filename {
            font-weight: 600;
            font-size: 14px;
            color: var(--text);
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

        .file-actions {
            display: flex;
            gap: 8px;
        }

        .btn-preview, .btn-download {
            flex: 1;
            padding: 9px 12px;
            border: none;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-family: inherit;
        }

        .btn-preview {
            background: var(--surface-2);
            color: var(--deep-navy);
            border: 1.5px solid var(--border);
        }

        .btn-preview:hover {
            background: var(--deep-navy);
            color: var(--white);
            border-color: var(--deep-navy);
        }

        .btn-download {
            background: linear-gradient(135deg, var(--deep-navy), var(--secondary-blue));
            color: var(--white);
            box-shadow: 0 6px 14px -6px rgba(1, 47, 107, 0.5);
        }

        .btn-download:hover {
            color: var(--white);
            transform: translateY(-1px);
            box-shadow: 0 10px 20px -8px rgba(1, 47, 107, 0.55);
        }

        /* List view tweaks */
        .grid.list-view .file-card {
            display: grid;
            grid-template-columns: 110px 1fr auto;
            align-items: center;
            gap: 16px;
            padding-right: 16px;
        }

        .grid.list-view .media-preview {
            aspect-ratio: 1;
            height: 90px;
            border-radius: 10px;
            margin: 12px 0 12px 12px;
        }

        .grid.list-view .file-info {
            padding: 12px 0;
        }

        .grid.list-view .file-actions {
            margin: 0;
            min-width: 220px;
        }

        @media (max-width: 640px) {
            .grid.list-view .file-card {
                grid-template-columns: 80px 1fr;
            }
            .grid.list-view .file-actions {
                grid-column: 1 / -1;
                padding: 0 12px 12px;
                min-width: auto;
            }
        }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 60px 24px;
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1.5px dashed var(--border-strong);
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

        /* ===== LOADING / SKELETONS ===== */
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

        .skeleton-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }
        .skeleton-card .sk-thumb {
            aspect-ratio: 16 / 11;
            background: linear-gradient(90deg, #EEF2F8 0%, #F6F8FC 50%, #EEF2F8 100%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }
        .skeleton-card .sk-body { padding: 14px; }
        .skeleton-card .sk-line {
            height: 12px;
            border-radius: 4px;
            background: linear-gradient(90deg, #EEF2F8 0%, #F6F8FC 50%, #EEF2F8 100%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            margin-bottom: 10px;
        }
        .skeleton-card .sk-line.w-60 { width: 60%; }
        .skeleton-card .sk-line.w-40 { width: 40%; }

        /* ===== TOAST ===== */
        .toast-container {
            position: fixed;
            bottom: 24px; right: 24px;
            z-index: 9999;
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

        /* ===== VIDEO MODAL ===== */
        .video-modal .modal-content {
            background: #0a0f1a;
            border: none;
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-xl);
        }
        .video-modal .modal-header {
            background: linear-gradient(135deg, var(--deep-navy), var(--secondary-blue));
            border-bottom: 1px solid rgba(242, 166, 90, 0.4);
            padding: 14px 20px;
        }
        .video-modal .modal-title {
            color: var(--white);
            font-weight: 700;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .video-modal .modal-title i { color: var(--gold); }
        .video-modal .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.85;
        }
        .video-modal .btn-close:hover { opacity: 1; }
        .video-modal .modal-body { padding: 0; background: #000; }

        #videoContainer { width: 100%; }

        .video-js { width: 100%; height: 60vh; }

        .video-js .vjs-big-play-button {
            background-color: rgba(242, 166, 90, 0.95);
            border: none;
            border-radius: 50%;
            width: 72px; height: 72px;
            line-height: 72px;
            left: 50%; top: 50%;
            transform: translate(-50%, -50%);
            box-shadow: 0 12px 30px rgba(0,0,0,0.4);
        }

        .video-js:hover .vjs-big-play-button {
            background-color: var(--gold-dark);
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
        }
    </style>
</head>
<body>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loader">
        <div class="spinner"></div>
        <p>Fetching latest assets…</p>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Top Nav -->
<div class="topbar">
    <div class="topbar-inner">
        <a href="#" class="brand">
            <div class="brand-mark">X</div>
            <div class="brand-text">
                <span class="name">Xander</span>
                <span class="sub">Marketing Assets</span>
            </div>
        </a>
        <div class="top-actions">
            <div class="pill">
                <span class="dot"></span>
                <span id="connectionLabel">Connected to pCloud</span>
            </div>
        </div>
    </div>
</div>

<main class="container-x">

    <!-- Hero + Stats -->
    <section class="hero">
        <div class="hero-card">
            <span class="hero-eyebrow">
                <i class="bi bi-stars"></i> Asset Library
            </span>
            <h1 class="hero-title">Your marketing materials, organized.</h1>
            <p class="hero-sub">Browse, preview, and download images, videos, and documents from your shared marketing folder. Filter by country or search across everything.</p>
            <div class="hero-quick">
                <button class="quick-chip" data-jump="images">
                    <i class="bi bi-images"></i> Jump to Images
                </button>
                <button class="quick-chip" data-jump="videos">
                    <i class="bi bi-camera-reels"></i> Jump to Videos
                </button>
                <button class="quick-chip" data-jump="others">
                    <i class="bi bi-file-earmark-text"></i> Documents
                </button>
            </div>
        </div>

        <div class="stats-panel">
            <div class="stat-card total">
                <div class="icon"><i class="bi bi-collection"></i></div>
                <div class="label">Total Assets</div>
                <div class="value" id="totalCount">0</div>
            </div>
            <div class="stat-card images">
                <div class="icon"><i class="bi bi-image"></i></div>
                <div class="label">Images</div>
                <div class="value" id="imagesStatCount">0</div>
            </div>
            <div class="stat-card videos">
                <div class="icon"><i class="bi bi-play-btn"></i></div>
                <div class="label">Videos</div>
                <div class="value" id="videosStatCount">0</div>
            </div>
            <div class="stat-card others">
                <div class="icon"><i class="bi bi-file-earmark"></i></div>
                <div class="label">Documents</div>
                <div class="value" id="othersStatCount">0</div>
            </div>
        </div>
    </section>

    <!-- Controls -->
    <div class="controls">
        <div class="search-wrap">
            <i class="bi bi-search"></i>
            <input type="text" id="searchInput" placeholder="Search files by name…" autocomplete="off">
            <span class="kbd">⌘F</span>
        </div>
        <div class="country-select-wrap" aria-label="Destination country">
            <select id="countrySelect" name="country" aria-label="Destination country">
                <?php foreach ($COUNTRY_FOLDERS as $cKey => $cFid):
                    $cMeta  = $COUNTRY_META[$cKey] ?? ['label' => ucfirst($cKey), 'flag' => ''];
                    $cFlag  = $cMeta['flag'];
                    $cLabel = $cMeta['label'];
                ?>
                    <option
                        value="<?= htmlspecialchars($cKey, ENT_QUOTES, 'UTF-8') ?>"
                        data-flag="<?= htmlspecialchars($cFlag, ENT_QUOTES, 'UTF-8') ?>"
                        data-label="<?= htmlspecialchars($cLabel, ENT_QUOTES, 'UTF-8') ?>"
                        <?= $cKey === $initialCountry ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cFlag . '  ' . $cLabel, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="view-toggle" role="tablist" aria-label="View">
            <button class="view-btn active" data-view="grid" type="button" title="Grid view">
                <i class="bi bi-grid-fill"></i>
            </button>
            <button class="view-btn" data-view="list" type="button" title="List view">
                <i class="bi bi-list-ul"></i>
            </button>
        </div>
    </div>

    <!-- AI Insights -->
    <div class="ai-card">
        <div class="ai-icon"><i class="bi bi-stars"></i></div>
        <div class="ai-content">
            <div class="ai-title">
                AI Insights
                <span class="live">Live</span>
            </div>
            <div id="ai-box">Analyzing your marketing materials…</div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="tabs" role="tablist" aria-label="Filter">
        <button class="tab active" data-filter="all" type="button">
            <i class="bi bi-grid"></i> All
            <span class="badge-count" id="tabAllCount">0</span>
        </button>
        <button class="tab" data-filter="images" type="button">
            <i class="bi bi-images"></i> Images
            <span class="badge-count" id="tabImagesCount">0</span>
        </button>
        <button class="tab" data-filter="videos" type="button">
            <i class="bi bi-camera-reels"></i> Videos
            <span class="badge-count" id="tabVideosCount">0</span>
        </button>
        <button class="tab" data-filter="others" type="button">
            <i class="bi bi-file-earmark-text"></i> Documents
            <span class="badge-count" id="tabOthersCount">0</span>
        </button>
    </div>

    <!-- Images Section -->
    <section class="section" id="section-images">
        <div class="section-head">
            <div class="section-icon images"><i class="bi bi-images"></i></div>
            <h2 class="section-title">Images</h2>
            <span class="section-count" id="imagesCount">0</span>
        </div>
        <div id="imagesGrid" class="grid"></div>
        <div id="noImages" class="empty-state d-none">
            <div class="empty-icon">🖼️</div>
            <div class="empty-title">No images yet</div>
            <div class="empty-text">Upload images to the marketing folder to see them appear here automatically.</div>
        </div>
    </section>

    <!-- Videos Section -->
    <section class="section" id="section-videos">
        <div class="section-head">
            <div class="section-icon videos"><i class="bi bi-camera-reels"></i></div>
            <h2 class="section-title">Videos</h2>
            <span class="section-count" id="videosCount">0</span>
        </div>
        <div id="videosGrid" class="grid"></div>
        <div id="noVideos" class="empty-state d-none">
            <div class="empty-icon">🎬</div>
            <div class="empty-title">No videos yet</div>
            <div class="empty-text">Upload videos to the marketing folder to see them appear here automatically.</div>
        </div>
    </section>

    <!-- Others Section -->
    <section class="section" id="section-others">
        <div class="section-head">
            <div class="section-icon others"><i class="bi bi-file-earmark-text"></i></div>
            <h2 class="section-title">Documents</h2>
            <span class="section-count" id="othersCount">0</span>
        </div>
        <div id="othersGrid" class="grid"></div>
        <div id="noOthers" class="empty-state d-none">
            <div class="empty-icon">📄</div>
            <div class="empty-title">No documents yet</div>
            <div class="empty-text">Upload PDFs, docs or any other files to the marketing folder to see them here.</div>
        </div>
    </section>
</main>

<!-- Video Preview Modal -->
<div class="modal fade video-modal" id="videoModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-play-circle-fill"></i>
                    <span id="videoModalTitle">Video Preview</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="videoContainer"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
// ===== CONFIGURATION =====
const COUNTRY_FOLDERS = <?= json_encode($COUNTRY_FOLDERS) ?>;
const COUNTRY_META    = <?= json_encode($COUNTRY_META) ?>;
let currentCountry = <?= json_encode($initialCountry) ?>;
let currentFilter = 'all';
let currentView = 'grid';
let allFilesData = { images: [], videos: [], others: [] };

// ===== COUNTRY SELECT2 DROPDOWN =====
function formatCountryOption(opt) {
    if (!opt.id) return opt.text;
    const meta = COUNTRY_META[opt.id] || {};
    const flag  = meta.flag  || '';
    const label = meta.label || opt.text;
    return $(
        '<span class="country-option"><span class="country-flag">' +
        flag + '</span><span>' + label + '</span></span>'
    );
}

jQuery(function ($) {
    const $sel = $('#countrySelect');
    $sel.select2({
        minimumResultsForSearch: 4,
        width: '100%',
        templateResult: formatCountryOption,
        templateSelection: formatCountryOption,
    });

    $sel.on('change', function () {
        const newKey = $(this).val();
        if (newKey === currentCountry) return;
        currentCountry = newKey;
        const meta = COUNTRY_META[currentCountry] || {};
        const label = meta.label || currentCountry;
        document.getElementById('searchInput').value = '';
        loadFiles();
        showToast(`Switched to ${label}`, 'info');
    });
});

// ===== VIEW TOGGLE =====
document.querySelectorAll('.view-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        if (btn.classList.contains('active')) return;
        document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentView = btn.dataset.view;
        ['imagesGrid','videosGrid','othersGrid'].forEach(id => {
            const g = document.getElementById(id);
            g.classList.toggle('list-view', currentView === 'list');
        });
    });
});

// ===== TAB FILTERS =====
document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        currentFilter = tab.dataset.filter;
        applyFilter();
    });
});

function applyFilter() {
    const map = {
        all: ['section-images','section-videos','section-others'],
        images: ['section-images'],
        videos: ['section-videos'],
        others: ['section-others']
    };
    const visible = map[currentFilter];
    ['section-images','section-videos','section-others'].forEach(id => {
        const el = document.getElementById(id);
        if (visible.includes(id)) el.classList.remove('is-hidden');
        else el.classList.add('is-hidden');
    });
}

// ===== QUICK JUMP CHIPS =====
document.querySelectorAll('.quick-chip').forEach(chip => {
    chip.addEventListener('click', () => {
        const target = chip.dataset.jump;
        const tab = document.querySelector(`.tab[data-filter="${target}"]`);
        if (tab) tab.click();
        document.getElementById(`section-${target}`)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
});

// ===== TOAST =====
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    const icon = type === 'success' ? 'check-circle-fill'
              : type === 'error'   ? 'exclamation-triangle-fill'
              : 'info-circle-fill';
    toast.innerHTML = `<i class="bi bi-${icon}"></i><span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.animation = 'slideIn 0.3s reverse';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ===== SKELETON LOADER =====
function showSkeletons() {
    ['imagesGrid','videosGrid','othersGrid'].forEach(id => {
        const grid = document.getElementById(id);
        grid.innerHTML = '';
        for (let i = 0; i < 4; i++) {
            grid.insertAdjacentHTML('beforeend', `
                <div class="skeleton-card">
                    <div class="sk-thumb"></div>
                    <div class="sk-body">
                        <div class="sk-line w-60"></div>
                        <div class="sk-line w-40"></div>
                    </div>
                </div>
            `);
        }
    });
}

// ===== LOAD FILES =====
async function loadFiles() {
    const loadingOverlay = document.getElementById('loadingOverlay');
    loadingOverlay.style.display = 'flex';
    showSkeletons();

    try {
        const response = await fetch(`?ajax=1&country=${currentCountry}`);
        const data = await response.json();

        if (data.error) {
            showToast('Failed to load files', 'error');
            loadingOverlay.style.display = 'none';
            return;
        }

        allFilesData = data;
        renderAll(data);
        updateCounts(data);
        runAISummary(data);

    } catch (error) {
        console.error('Error loading files:', error);
        showToast('Error connecting to server', 'error');
    }

    loadingOverlay.style.display = 'none';
}

// ===== COUNTS =====
function updateCounts(data) {
    const i = data.images.length, v = data.videos.length, o = data.others.length;
    const t = i + v + o;
    document.getElementById('imagesCount').textContent = i;
    document.getElementById('videosCount').textContent = v;
    document.getElementById('othersCount').textContent = o;
    document.getElementById('imagesStatCount').textContent = i;
    document.getElementById('videosStatCount').textContent = v;
    document.getElementById('othersStatCount').textContent = o;
    document.getElementById('totalCount').textContent = t;
    document.getElementById('tabAllCount').textContent = t;
    document.getElementById('tabImagesCount').textContent = i;
    document.getElementById('tabVideosCount').textContent = v;
    document.getElementById('tabOthersCount').textContent = o;
}

// ===== RENDER =====
function renderAll(data) {
    renderCategory('imagesGrid', data.images, 'image');
    renderCategory('videosGrid', data.videos, 'video');
    renderCategory('othersGrid', data.others, 'other');
}

function renderCategory(gridId, items, type) {
    const grid = document.getElementById(gridId);
    const noItemsElement = document.getElementById('no' + gridId.charAt(0).toUpperCase() + gridId.slice(1, -4));

    grid.innerHTML = '';
    grid.classList.toggle('list-view', currentView === 'list');

    if (items.length === 0) {
        if (noItemsElement) noItemsElement.classList.remove('d-none');
        return;
    }
    if (noItemsElement) noItemsElement.classList.add('d-none');

    items.forEach((file, idx) => {
        const ext = file.name.split('.').pop().toLowerCase();
        const fileSize = formatFileSize(file.size || 0);
        const thumbnail = type === 'image' ? getThumbUrl(file.fileid) : '';

        const card = document.createElement('div');
        card.className = 'file-card';
        card.dataset.name = file.name.toLowerCase();
        card.dataset.fileid = file.fileid;
        card.style.animationDelay = `${Math.min(idx * 30, 400)}ms`;

        let previewHtml = '';
        if (type === 'image') {
            previewHtml = `
                <div class="media-preview">
                    <span class="badge-type">${ext}</span>
                    <div class="skeleton"></div>
                    <img class="thumb" data-src="${thumbnail}" alt="${file.name}">
                </div>
            `;
        } else if (type === 'video') {
            previewHtml = `
                <div class="media-preview">
                    <span class="badge-type">${ext}</span>
                    <div class="file-icon-wrap">
                        <i class="bi bi-camera-reels"></i>
                    </div>
                    <div class="play-button" data-video-id="${file.fileid}" data-filename="${file.name}">
                        <i class="bi bi-play-fill"></i>
                    </div>
                </div>
            `;
        } else {
            previewHtml = `
                <div class="media-preview">
                    <span class="badge-type">${ext}</span>
                    <div class="file-icon-wrap">
                        ${getFileIcon(ext)}
                    </div>
                </div>
            `;
        }

        card.innerHTML = `
            ${previewHtml}
            <div class="file-info">
                <div class="filename" title="${file.name}">${file.name}</div>
                <div class="file-meta">
                    <span>${fileSize}</span>
                    <span class="dot-sep"></span>
                    <span class="ext-tag">${ext}</span>
                </div>
                <div class="file-actions">
                    ${type === 'video' ? `
                        <button class="btn-preview" data-video-id="${file.fileid}" data-filename="${file.name}">
                            <i class="bi bi-play-fill"></i> Preview
                        </button>
                    ` : ''}
                    <a href="download-pcloud.php?fileid=${file.fileid}&name=${encodeURIComponent(file.name)}" class="btn-download">
                        <i class="bi bi-download"></i> Download
                    </a>
                </div>
            </div>
        `;
        grid.appendChild(card);
    });

    if (type === 'image') lazyLoadImages();

    if (type === 'video') {
        document.querySelectorAll(`#${gridId} .play-button, #${gridId} .btn-preview`).forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const videoId = btn.getAttribute('data-video-id');
                const fileName = btn.getAttribute('data-filename');
                openVideoModal(videoId, fileName);
            });
        });
    }
}

function getThumbUrl(fileId) {
    return `https://api.pcloud.com/getthumb?fileid=${fileId}&access_token=kqNT7Z8BpwhA0d4MFZVgju0kZbR12PpsX93VWhpTOL5i4jVefcDdX&size=400x300&type=auto`;
}

function getFileIcon(ext) {
    const iconMap = {
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
        'js':   { icon: 'bi-filetype-js', color: '#f1c40f' },
        'html': { icon: 'bi-filetype-html', color: '#e67e22' },
        'css':  { icon: 'bi-filetype-css', color: '#3498db' },
        'php':  { icon: 'bi-filetype-php', color: '#6c5ce7' },
        'json': { icon: 'bi-filetype-json', color: '#2ecc71' },
        'mp3':  { icon: 'bi-file-earmark-music-fill', color: '#9b59b6' },
        'wav':  { icon: 'bi-file-earmark-music-fill', color: '#8e44ad' }
    };
    const t = iconMap[ext] || { icon: 'bi-file-earmark-fill', color: '#7f8c8d' };
    return `<i class="bi ${t.icon}" style="color: ${t.color}; font-size: 2.6rem;"></i>`;
}

// ===== LAZY LOAD =====
function lazyLoadImages() {
    const obs = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                const skeleton = img.previousElementSibling;
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                    img.onload = () => {
                        img.classList.add('loaded');
                        if (skeleton && skeleton.classList.contains('skeleton')) skeleton.style.display = 'none';
                    };
                    img.onerror = () => {
                        if (skeleton) skeleton.style.display = 'none';
                        const fallback = document.createElement('div');
                        fallback.className = 'file-icon-wrap';
                        fallback.innerHTML = '<i class="bi bi-image" style="color: var(--secondary-blue); font-size: 2.6rem;"></i>';
                        img.parentNode.insertBefore(fallback, img);
                        img.remove();
                    };
                }
                observer.unobserve(img);
            }
        });
    }, { rootMargin: '80px' });
    document.querySelectorAll('img.thumb:not(.loaded)').forEach(img => obs.observe(img));
}

function formatFileSize(bytes) {
    if (!bytes || bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B','KB','MB','GB','TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// ===== VIDEO MODAL =====
function openVideoModal(videoId, fileName) {
    const modalElement = document.getElementById('videoModal');
    const videoContainer = document.getElementById('videoContainer');
    document.getElementById('videoModalTitle').textContent = fileName || 'Video Preview';

    videoContainer.innerHTML = `
        <video id="videoPlayer" class="video-js vjs-big-play-centered" controls preload="auto" data-setup='{"fluid": true}'>
            <source src="get-video.php?fileid=${videoId}" type="video/mp4">
            <p class="vjs-no-js">Your browser does not support video playback.</p>
        </video>
    `;
    if (typeof videojs !== 'undefined') {
        videojs('videoPlayer', { autoplay: true, controls: true, responsive: true, fluid: true });
    }
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
    modalElement.addEventListener('hidden.bs.modal', function() {
        if (videojs.getPlayer('videoPlayer')) videojs.getPlayer('videoPlayer').dispose();
        videoContainer.innerHTML = '';
    }, { once: true });
}

// ===== AI SUMMARY =====
async function runAISummary(data) {
    const aiBox = document.getElementById('ai-box');
    try {
        const allNames = [...data.images, ...data.videos, ...data.others].map(f => f.name);
        if (allNames.length === 0) {
            aiBox.textContent = 'No files available for analysis. Upload files to get AI-powered insights.';
            return;
        }
        const formData = new FormData();
        formData.append('names', JSON.stringify(allNames));
        const response = await fetch('marketing-openai.php', { method: 'POST', body: formData });
        const text = await response.text();
        aiBox.textContent = text;
    } catch (error) {
        console.error('AI summary error:', error);
        aiBox.textContent = 'AI analysis temporarily unavailable. Please try again later.';
    }
}

// ===== SEARCH =====
let searchTimeout;
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const term = this.value.toLowerCase().trim();
    searchTimeout = setTimeout(() => {
        document.querySelectorAll('.file-card').forEach(card => {
            const name = card.dataset.name || '';
            card.style.display = (term === '' || name.includes(term)) ? '' : 'none';
        });
        if (term !== '') {
            ['images','videos','others'].forEach(section => {
                const grid = document.getElementById(`${section}Grid`);
                const noEl = document.getElementById(`no${section.charAt(0).toUpperCase() + section.slice(1)}`);
                const visible = Array.from(grid.children).some(c => c.style.display !== 'none' && c.classList.contains('file-card'));
                if (noEl) {
                    if (!visible && allFilesData[section]?.length > 0) {
                        noEl.classList.remove('d-none');
                        noEl.querySelector('.empty-text').textContent = `No ${section} match “${term}”.`;
                    } else {
                        noEl.classList.add('d-none');
                    }
                }
            });
        } else {
            ['images','videos','others'].forEach(section => {
                const noEl = document.getElementById(`no${section.charAt(0).toUpperCase() + section.slice(1)}`);
                if (noEl && allFilesData[section]?.length > 0) {
                    noEl.classList.add('d-none');
                } else if (noEl) {
                    noEl.classList.remove('d-none');
                    noEl.querySelector('.empty-text').textContent = `Upload files to the marketing folder to see them here.`;
                }
            });
        }
    }, 250);
});

// ===== KEYBOARD =====
document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
        e.preventDefault();
        document.getElementById('searchInput').focus();
    }
    if (e.key === 'Escape' && document.activeElement === document.getElementById('searchInput')) {
        const input = document.getElementById('searchInput');
        input.value = '';
        input.blur();
        input.dispatchEvent(new Event('input'));
    }
});

// ===== INIT =====
document.addEventListener('DOMContentLoaded', () => {
    loadFiles();
});
</script>
</body>
</html>
