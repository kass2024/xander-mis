<?php
require "marketing-openai.php"; // AI helper

//-----------------------------------------------------------
// AJAX ENDPOINT - returns categorized + searchable JSON
//-----------------------------------------------------------
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    header("Content-Type: application/json");

    $token = "kqNT7Z8BpwhA0d4MFZVgju0kZbR12PpsX93VWhpTOL5i4jVefcDdX";
    
    // 👇 Use the folder ID from configuration
    $folderId = 30592893924;  // Marketing Materials folder

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
    <title>Xander - Marketing Materials</title>
    
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
    </style>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Video.js CSS -->
    <link href="https://vjs.zencdn.net/8.10.0/video-js.css" rel="stylesheet" />
    <link href="https://unpkg.com/@videojs/themes/dist/city/index.css" rel="stylesheet">
    <!-- Video.js Script -->
    <script src="https://vjs.zencdn.net/8.10.0/video.min.js"></script>
    
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
            max-width: 1400px;
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
            content: '📊';
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
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 24px;
        }

        /* ===== PAGE HEADER ===== */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--deep-navy);
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0;
        }

        .page-title i {
            color: var(--gold);
            font-size: 32px;
        }

        .search-container {
            position: relative;
            min-width: 320px;
        }

        .search-container i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 16px;
        }

        #searchInput {
            width: 100%;
            padding: 12px 16px 12px 45px;
            border: 2px solid var(--border-light);
            border-radius: 30px;
            font-size: 14px;
            background: var(--white);
            transition: all 0.3s ease;
        }

        #searchInput:focus {
            outline: none;
            border-color: var(--gold);
            box-shadow: 0 0 0 4px rgba(242, 166, 90, 0.1);
        }

        /* ===== AI INSIGHTS CARD ===== */
        .ai-card {
            background: var(--white);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(1, 47, 107, 0.08);
            border: 1px solid var(--border-light);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .ai-header {
            background: linear-gradient(135deg, #f8fafc 0%, #eef2f6 100%);
            padding: 16px 20px;
            border-bottom: 2px solid var(--gold);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .ai-header i {
            color: var(--gold);
            font-size: 24px;
        }

        .ai-header h5 {
            margin: 0;
            font-weight: 700;
            color: var(--deep-navy);
            font-size: 18px;
        }

        .ai-body {
            padding: 20px;
            background: linear-gradient(135deg, rgba(242, 166, 90, 0.02) 0%, rgba(1, 47, 107, 0.02) 100%);
        }

        #ai-box {
            font-size: 0.95rem;
            line-height: 1.6;
            color: var(--text-dark);
            white-space: pre-wrap;
        }

        /* ===== SECTION HEADERS ===== */
        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 40px 0 20px 0;
        }

        .section-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--deep-navy) 0%, var(--secondary-blue) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 24px;
        }

        .section-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--deep-navy);
            margin: 0;
        }

        .section-count {
            background: var(--gold);
            color: var(--deep-navy);
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            margin-left: 12px;
        }

        /* ===== GRID LAYOUT ===== */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 25px;
        }

        /* ===== FILE CARD ===== */
        .file-card {
            background: var(--white);
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--border-light);
            box-shadow: 0 5px 15px rgba(1, 47, 107, 0.08);
            transition: all 0.3s ease;
            position: relative;
        }

        .file-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(1, 47, 107, 0.15);
            border-color: var(--gold);
        }

        .media-preview {
            position: relative;
            width: 100%;
            height: 180px;
            background: linear-gradient(135deg, #f8fafc 0%, #eef2f6 100%);
            overflow: hidden;
        }

        .skeleton {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 50%, #e2e8f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }

        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .thumb {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 2;
        }

        .thumb.loaded {
            opacity: 1;
        }

        .file-icon {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f8fafc 0%, #eef2f6 100%);
            font-size: 48px;
            transition: transform 0.3s ease;
        }

        .file-card:hover .file-icon {
            transform: scale(1.05);
        }

        .play-button {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 60px;
            height: 60px;
            background: rgba(1, 47, 107, 0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 10;
            border: 2px solid var(--gold);
        }

        .play-button:hover {
            transform: translate(-50%, -50%) scale(1.1);
            background: var(--deep-navy);
        }

        .play-button i {
            color: var(--white);
            font-size: 24px;
            margin-left: 4px;
        }

        .file-info {
            padding: 16px;
        }

        .filename {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 15px;
        }

        .file-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .file-size {
            font-size: 12px;
            color: var(--text-muted);
            background: var(--light-bg);
            padding: 4px 8px;
            border-radius: 4px;
        }

        .file-ext {
            background: linear-gradient(135deg, var(--deep-navy) 0%, var(--secondary-blue) 100%);
            color: var(--white);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .file-actions {
            display: flex;
            gap: 8px;
        }

        .btn-preview {
            flex: 1;
            background: var(--light-bg);
            border: 1px solid var(--border-light);
            color: var(--deep-navy);
            padding: 8px 0;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-preview:hover {
            background: var(--gold);
            border-color: var(--gold);
            color: var(--deep-navy);
        }

        .btn-download {
            flex: 1;
            background: linear-gradient(135deg, var(--deep-navy) 0%, var(--secondary-blue) 100%);
            color: var(--white);
            padding: 8px 0;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-download:hover {
            background: linear-gradient(135deg, var(--dark-blue) 0%, var(--deep-navy) 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(1, 47, 107, 0.3);
            color: var(--white);
        }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--light-bg);
            border-radius: 16px;
            border: 2px dashed var(--border-light);
            grid-column: 1 / -1;
        }

        .empty-icon {
            font-size: 64px;
            margin-bottom: 20px;
            color: var(--deep-navy);
        }

        .empty-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .empty-text {
            color: var(--text-muted);
            font-size: 14px;
        }

        /* ===== VIDEO MODAL ===== */
        .video-modal .modal-dialog {
            max-width: 90%;
            height: 85vh;
            margin: 2rem auto;
        }

        .video-modal .modal-content {
            background: var(--deep-navy);
            border: 2px solid var(--gold);
            border-radius: 16px;
            overflow: hidden;
            height: 100%;
        }

        .video-modal .modal-header {
            background: linear-gradient(135deg, var(--deep-navy) 0%, var(--secondary-blue) 100%);
            border-bottom: 2px solid var(--gold);
            padding: 12px 20px;
        }

        .video-modal .modal-title {
            color: var(--white);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .video-modal .modal-title i {
            color: var(--gold);
        }

        .video-modal .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.8;
        }

        .video-modal .btn-close:hover {
            opacity: 1;
        }

        .video-modal .modal-body {
            padding: 0;
            background: #000;
            height: calc(100% - 60px);
        }

        #videoContainer {
            width: 100%;
            height: 100%;
        }

        .video-js {
            width: 100%;
            height: 100%;
        }

        .video-js .vjs-big-play-button {
            background-color: rgba(1, 47, 107, 0.8);
            border: 2px solid var(--gold);
            border-radius: 50%;
            width: 70px;
            height: 70px;
            line-height: 70px;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
        }

        .video-js:hover .vjs-big-play-button {
            background-color: var(--deep-navy);
        }

        /* ===== TOAST NOTIFICATIONS ===== */
        .toast-container {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 9999;
        }

        .toast-notification {
            background: var(--white);
            border-radius: 8px;
            padding: 12px 20px;
            margin-top: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            border-left: 4px solid;
            animation: slideIn 0.3s ease;
        }

        .toast-notification.success {
            border-left-color: var(--success);
        }

        .toast-notification.error {
            border-left-color: var(--danger);
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* ===== LOADING OVERLAY ===== */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid var(--border-light);
            border-radius: 50%;
            border-top-color: var(--deep-navy);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .header-container {
                padding: 0 16px;
            }
            
            .logo-main {
                font-size: 1.5rem;
            }
            
            .logo-main::after {
                right: -25px;
                font-size: 1.3rem;
            }
            
            .logo-subtitle {
                font-size: 1rem;
                padding-left: 12px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-container {
                min-width: 100%;
            }
            
            .grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 15px;
            }
            
            .section-header {
                flex-wrap: wrap;
            }
            
            .file-actions {
                flex-direction: column;
            }
            
            .video-modal .modal-dialog {
                max-width: 95%;
                height: 60vh;
            }
        }

        @media (max-width: 480px) {
            .logo-container {
                gap: 10px;
            }
            
            .logo-main {
                font-size: 1.3rem;
            }
            
            .logo-subtitle {
                font-size: 0.8rem;
                padding-left: 8px;
            }
            
            .grid {
                grid-template-columns: 1fr;
            }
            
            .section-icon {
                width: 36px;
                height: 36px;
                font-size: 18px;
            }
            
            .section-title {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="spinner"></div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Xander Header -->
<div class="xander-header">
    <div class="header-container">
        <div class="logo-container">
            <div class="logo-main">XANDER</div>
            <div class="logo-subtitle">MARKETING ASSETS</div>
        </div>
    </div>
</div>

<main class="main-container">
    
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title">
            <i class="bi bi-megaphone"></i>
            Marketing Materials
        </h1>
        
        <div class="search-container">
            <i class="bi bi-search"></i>
            <input type="text" id="searchInput" class="form-control" 
                   placeholder="Search files by name...">
        </div>
    </div>
    
    <!-- AI Insights Card -->
    <div class="ai-card">
       
        <div class="ai-body">
            <div id="ai-box">Analyzing your marketing materials...</div>
        </div>
    </div>
    
    <!-- Images Section -->
    <div class="section-header">
        <div class="section-icon">
            <i class="bi bi-images"></i>
        </div>
        <h2 class="section-title">
            Images
            <span class="section-count" id="imagesCount">0</span>
        </h2>
    </div>
    <div id="imagesGrid" class="grid"></div>
    <div id="noImages" class="empty-state d-none">
        <div class="empty-icon">🖼️</div>
        <div class="empty-title">No Images Found</div>
        <div class="empty-text">Upload images to the marketing folder to see them here</div>
    </div>
    
    <!-- Videos Section -->
    <div class="section-header">
        <div class="section-icon">
            <i class="bi bi-camera-reels"></i>
        </div>
        <h2 class="section-title">
            Videos
            <span class="section-count" id="videosCount">0</span>
        </h2>
    </div>
    <div id="videosGrid" class="grid"></div>
    <div id="noVideos" class="empty-state d-none">
        <div class="empty-icon">🎬</div>
        <div class="empty-title">No Videos Found</div>
        <div class="empty-text">Upload videos to the marketing folder to see them here</div>
    </div>
    
    <!-- Other Files Section -->
    <div class="section-header">
        <div class="section-icon">
            <i class="bi bi-files"></i>
        </div>
        <h2 class="section-title">
            Other Files
            <span class="section-count" id="othersCount">0</span>
        </h2>
    </div>
    <div id="othersGrid" class="grid"></div>
    <div id="noOthers" class="empty-state d-none">
        <div class="empty-icon">📄</div>
        <div class="empty-title">No Other Files Found</div>
        <div class="empty-text">Upload documents to the marketing folder to see them here</div>
    </div>
</main>

<!-- Video Preview Modal -->
<div class="modal fade video-modal" id="videoModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-play-circle"></i>
                    Video Preview
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
<script>
// ===== CONFIGURATION =====
const FOLDER_ID = 30592893924;
let allFilesData = { images: [], videos: [], others: [] };

// ===== TOAST NOTIFICATION SYSTEM =====
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    toast.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    toastContainer.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideIn 0.3s reverse';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ===== LOAD FILES =====
async function loadFiles() {
    const loadingOverlay = document.getElementById('loadingOverlay');
    loadingOverlay.style.display = 'flex';
    
    try {
        const response = await fetch('?ajax=1');
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

// ===== UPDATE SECTION COUNTS =====
function updateCounts(data) {
    document.getElementById('imagesCount').textContent = data.images.length;
    document.getElementById('videosCount').textContent = data.videos.length;
    document.getElementById('othersCount').textContent = data.others.length;
}

// ===== RENDER ALL CATEGORIES =====
function renderAll(data) {
    renderCategory('imagesGrid', data.images, 'image');
    renderCategory('videosGrid', data.videos, 'video');
    renderCategory('othersGrid', data.others, 'other');
}

// ===== RENDER CATEGORY =====
function renderCategory(gridId, items, type) {
    const grid = document.getElementById(gridId);
    const noItemsElement = document.getElementById('no' + gridId.charAt(0).toUpperCase() + gridId.slice(1, -4));
    
    grid.innerHTML = '';
    
    if (items.length === 0) {
        if (noItemsElement) noItemsElement.classList.remove('d-none');
        return;
    }
    
    if (noItemsElement) noItemsElement.classList.add('d-none');
    
    items.forEach(file => {
        const ext = file.name.split('.').pop().toLowerCase();
        const fileSize = formatFileSize(file.size || 0);
        const thumbnail = type === 'image' ? getThumbUrl(file.fileid) : '';
        
        const card = document.createElement('div');
        card.className = 'file-card';
        card.dataset.name = file.name.toLowerCase();
        card.dataset.fileid = file.fileid;
        
        // Build card HTML
        let previewHtml = '';
        
        if (type === 'image') {
            previewHtml = `
                <div class="media-preview">
                    <div class="skeleton"></div>
                    <img class="thumb" data-src="${thumbnail}" alt="${file.name}">
                </div>
            `;
        } else if (type === 'video') {
            previewHtml = `
                <div class="media-preview">
                    <div class="file-icon">
                        <i class="bi bi-camera-reels" style="color: var(--deep-navy);"></i>
                    </div>
                    <div class="play-button" data-video-id="${file.fileid}" data-filename="${file.name}">
                        <i class="bi bi-play-fill"></i>
                    </div>
                </div>
            `;
        } else {
            previewHtml = `
                <div class="media-preview">
                    <div class="file-icon">
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
                    <span class="file-size">${fileSize}</span>
                    <span class="file-ext">${ext}</span>
                </div>
                <div class="file-actions">
                    ${type === 'video' ? `
                        <button class="btn-preview" data-video-id="${file.fileid}" data-filename="${file.name}">
                            <i class="bi bi-play-fill"></i> Preview
                        </button>
                    ` : ''}
                    <a href="download-pcloud.php?fileid=${file.fileid}&name=${encodeURIComponent(file.name)}" 
                       class="btn-download">
                        <i class="bi bi-download"></i> Download
                    </a>
                </div>
            </div>
        `;
        
        grid.appendChild(card);
    });
    
    // Add event listeners for images (lazy loading)
    if (type === 'image') {
        lazyLoadImages();
    }
    
    // Add event listeners for video previews
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

// ===== GET THUMBNAIL URL =====
function getThumbUrl(fileId) {
    return `https://api.pcloud.com/getthumb?fileid=${fileId}&access_token=kqNT7Z8BpwhA0d4MFZVgju0kZbR12PpsX93VWhpTOL5i4jVefcDdX&size=256x256&type=auto`;
}

// ===== GET FILE ICON =====
function getFileIcon(ext) {
    const iconMap = {
        'pdf': { icon: 'bi-file-pdf', color: '#e74c3c' },
        'doc': { icon: 'bi-file-word', color: '#2b579a' },
        'docx': { icon: 'bi-file-word', color: '#2b579a' },
        'xls': { icon: 'bi-file-excel', color: '#1d6f42' },
        'xlsx': { icon: 'bi-file-excel', color: '#1d6f42' },
        'ppt': { icon: 'bi-file-ppt', color: '#d24726' },
        'pptx': { icon: 'bi-file-ppt', color: '#d24726' },
        'txt': { icon: 'bi-file-text', color: '#7f8c8d' },
        'zip': { icon: 'bi-file-zip', color: '#f39c12' },
        'rar': { icon: 'bi-file-zip', color: '#f39c12' },
        'js': { icon: 'bi-file-code', color: '#f1c40f' },
        'html': { icon: 'bi-file-code', color: '#e67e22' },
        'css': { icon: 'bi-file-code', color: '#3498db' },
        'php': { icon: 'bi-file-code', color: '#6c5ce7' },
        'json': { icon: 'bi-file-code', color: '#2ecc71' },
        'mp3': { icon: 'bi-file-music', color: '#9b59b6' },
        'wav': { icon: 'bi-file-music', color: '#8e44ad' }
    };
    
    const fileType = iconMap[ext] || { icon: 'bi-file-earmark', color: '#7f8c8d' };
    return `<i class="bi ${fileType.icon}" style="color: ${fileType.color}; font-size: 3rem;"></i>`;
}

// ===== LAZY LOAD IMAGES =====
function lazyLoadImages() {
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                const skeleton = img.previousElementSibling;
                
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                    img.onload = () => {
                        img.classList.add('loaded');
                        if (skeleton && skeleton.classList.contains('skeleton')) {
                            skeleton.style.display = 'none';
                        }
                    };
                    img.onerror = () => {
                        console.error('Failed to load image:', img.dataset.src);
                        if (skeleton) skeleton.style.display = 'none';
                        // Show fallback icon
                        const fallback = document.createElement('div');
                        fallback.className = 'file-icon';
                        fallback.innerHTML = '<i class="bi bi-image" style="color: var(--deep-navy); font-size: 3rem;"></i>';
                        img.parentNode.insertBefore(fallback, img);
                        img.remove();
                    };
                }
                
                observer.unobserve(img);
            }
        });
    }, {
        rootMargin: '50px'
    });
    
    document.querySelectorAll('img.thumb:not(.loaded)').forEach(img => {
        imageObserver.observe(img);
    });
}

// ===== FORMAT FILE SIZE =====
function formatFileSize(bytes) {
    if (!bytes || bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// ===== OPEN VIDEO MODAL =====
function openVideoModal(videoId, fileName) {
    const modalElement = document.getElementById('videoModal');
    const videoContainer = document.getElementById('videoContainer');
    
    videoContainer.innerHTML = `
        <video id="videoPlayer" class="video-js vjs-big-play-centered" controls preload="auto" data-setup='{"fluid": true}'>
            <source src="get-video.php?fileid=${videoId}" type="video/mp4">
            <p class="vjs-no-js">Your browser does not support video playback.</p>
        </video>
    `;
    
    // Initialize video player
    if (typeof videojs !== 'undefined') {
        const player = videojs('videoPlayer', {
            autoplay: true,
            controls: true,
            responsive: true,
            fluid: true
        });
    }
    
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
    
    // Clean up on modal hide
    modalElement.addEventListener('hidden.bs.modal', function() {
        if (videojs.getPlayer('videoPlayer')) {
            videojs.getPlayer('videoPlayer').dispose();
        }
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
        
        const response = await fetch('marketing-openai.php', { 
            method: 'POST', 
            body: formData 
        });
        
        const text = await response.text();
        aiBox.textContent = text;
        
    } catch (error) {
        console.error('AI summary error:', error);
        aiBox.textContent = 'AI analysis temporarily unavailable. Please try again later.';
    }
}

// ===== SEARCH FUNCTIONALITY =====
let searchTimeout;
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    
    const searchTerm = this.value.toLowerCase().trim();
    
    searchTimeout = setTimeout(() => {
        const cards = document.querySelectorAll('.file-card');
        let visibleCount = 0;
        
        cards.forEach(card => {
            const fileName = card.dataset.name || '';
            const shouldShow = searchTerm === '' || fileName.includes(searchTerm);
            card.style.display = shouldShow ? '' : 'none';
            if (shouldShow) visibleCount++;
        });
        
        // Show empty states if no items visible in a section
        if (searchTerm !== '') {
            const sections = ['images', 'videos', 'others'];
            sections.forEach(section => {
                const grid = document.getElementById(`${section}Grid`);
                const noElement = document.getElementById(`no${section.charAt(0).toUpperCase() + section.slice(1)}`);
                const visibleInSection = Array.from(grid.children).some(
                    child => child.style.display !== 'none' && child.classList.contains('file-card')
                );
                
                if (noElement) {
                    if (!visibleInSection && allFilesData[section]?.length > 0) {
                        noElement.classList.remove('d-none');
                        noElement.querySelector('.empty-text').textContent = 
                            `No ${section} match your search "${searchTerm}"`;
                    } else {
                        noElement.classList.add('d-none');
                    }
                }
            });
        } else {
            // Reset empty states
            ['images', 'videos', 'others'].forEach(section => {
                const noElement = document.getElementById(`no${section.charAt(0).toUpperCase() + section.slice(1)}`);
                if (noElement && allFilesData[section]?.length > 0) {
                    noElement.classList.add('d-none');
                } else if (noElement && allFilesData[section]?.length === 0) {
                    noElement.classList.remove('d-none');
                    noElement.querySelector('.empty-text').textContent = 
                        `No ${section} found in the marketing folder`;
                }
            });
        }
    }, 300);
});

// ===== KEYBOARD SHORTCUTS =====
document.addEventListener('keydown', (e) => {
    // Ctrl/Cmd + F to focus search
    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
        e.preventDefault();
        document.getElementById('searchInput').focus();
    }
    
    // Escape to clear search
    if (e.key === 'Escape' && document.activeElement === document.getElementById('searchInput')) {
        document.getElementById('searchInput').value = '';
        document.getElementById('searchInput').blur();
        // Trigger search clear
        document.getElementById('searchInput').dispatchEvent(new Event('input'));
    }
});

// ===== INITIALIZE =====
document.addEventListener('DOMContentLoaded', () => {
    loadFiles();
    
    // Add animation styles
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .file-card {
            animation: fadeIn 0.5s ease forwards;
        }
    `;
    document.head.appendChild(style);
});
</script>
</body>
</html>