<?php
// public/file_viewer.php
// Viewer file lampiran surat dengan proteksi download/print
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../modules/surat/surat_service.php';

requireLogin();

$user = getCurrentUser();
$suratId = (int)($_GET['surat_id'] ?? 0);

if (!$suratId) {
    redirect('index.php');
    exit;
}

$surat = SuratService::getById($suratId);

if (!$surat || empty($surat['lampiran_file'])) {
    redirect('index.php?error=file_not_found');
    exit;
}

$pageTitle = 'Viewer - ' . $surat['nomor_agenda'];

// URL handler untuk streaming file (view inline)
$downloadHandlerUrl = dirname(BASE_URL) . '/modules/download/download_handler.php';
$viewUrl = $downloadHandlerUrl . '?action=view&surat_id=' . $suratId;
$downloadUrl = $downloadHandlerUrl . '?action=download&surat_id=' . $suratId;

// Deteksi tipe file
$ext = strtolower(pathinfo($surat['lampiran_file'], PATHINFO_EXTENSION));
$isImage = in_array($ext, ['jpg', 'jpeg', 'png']);
$isPdf = ($ext === 'pdf');

// Load settings
$settings = getAllSettings();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - <?= htmlspecialchars($settings['app_name'] ?? 'Tracking Disposisi') ?></title>
    
    <?php if (!empty($settings['app_favicon'])): ?>
        <link rel="icon" href="<?= SETTINGS_UPLOAD_URL . $settings['app_favicon'] ?>">
    <?php endif; ?>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', sans-serif; 
            background: #1a1a2e; 
            color: #fff;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        /* === TOOLBAR === */
        .viewer-toolbar {
            background: #16213e;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
            z-index: 10;
        }
        .toolbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }
        .toolbar-left .back-btn {
            background: rgba(255,255,255,0.1);
            color: #fff;
            border: none;
            padding: 8px 14px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            transition: background 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .toolbar-left .back-btn:hover { background: rgba(255,255,255,0.2); }
        .file-info {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }
        .file-info .file-title {
            font-size: 14px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .file-info .file-meta {
            font-size: 11px;
            color: rgba(255,255,255,0.5);
        }
        .toolbar-right {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }
        .toolbar-right .btn {
            background: rgba(255,255,255,0.1);
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .toolbar-right .btn:hover { background: rgba(255,255,255,0.2); }
        .toolbar-right .btn-download { background: rgba(34,197,94,0.2); color: #4ade80; }
        .toolbar-right .btn-download:hover { background: rgba(34,197,94,0.35); }
        .toolbar-right .btn-print { background: rgba(59,130,246,0.2); color: #60a5fa; }
        .toolbar-right .btn-print:hover { background: rgba(59,130,246,0.35); }

        /* === VIEWER AREA === */
        .viewer-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
            background: #0f0f23;
        }
        .viewer-container iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        .viewer-container img {
            max-width: 95%;
            max-height: 95%;
            object-fit: contain;
            border-radius: 4px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
        }

        /* === PROTEKSI PRINT === */
        @media print {
            body * { display: none !important; }
            body::after {
                display: block !important;
                content: "⚠️ Pencetakan halaman ini tidak diizinkan. Silakan gunakan tombol Cetak yang tersedia di aplikasi.";
                position: fixed;
                top: 40%;
                left: 50%;
                transform: translate(-50%, -50%);
                font-size: 18pt;
                color: #333;
                text-align: center;
                padding: 40px;
                font-family: Arial, sans-serif;
            }
        }

        /* === WATERMARK OVERLAY === */
        .watermark-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            pointer-events: none;
            z-index: 5;
            background: repeating-linear-gradient(
                -45deg,
                transparent,
                transparent 200px,
                rgba(255,255,255,0.015) 200px,
                rgba(255,255,255,0.015) 201px
            );
        }

        /* === RESPONSIVE === */
        @media (max-width: 640px) {
            .viewer-toolbar { padding: 8px 12px; }
            .file-info .file-title { font-size: 12px; max-width: 120px; }
            .file-info .file-meta { font-size: 10px; }
            .toolbar-right .btn { padding: 6px 10px; font-size: 12px; }
            .toolbar-right .btn span { display: none; }
        }
    </style>
</head>
<body>
    <!-- Toolbar -->
    <div class="viewer-toolbar">
        <div class="toolbar-left">
            <a href="<?= 'surat_detail.php?id=' . $suratId ?>" onclick="goBack(event)" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                <span>Kembali</span>
            </a>
            <div class="file-info">
                <div class="file-title" title="<?= htmlspecialchars($surat['perihal']) ?>">
                    <?= htmlspecialchars($surat['nomor_agenda']) ?> — <?= htmlspecialchars(mb_substr($surat['perihal'], 0, 50)) ?><?= mb_strlen($surat['perihal']) > 50 ? '...' : '' ?>
                </div>
                <div class="file-meta">
                    <i class="fas fa-file<?= $isPdf ? '-pdf' : '-image' ?> mr-1"></i>
                    <?= htmlspecialchars($surat['lampiran_file']) ?>
                </div>
            </div>
        </div>

        <div class="toolbar-right">
            <a href="<?= $downloadUrl ?>" class="btn btn-download" title="Unduh File">
                <i class="fas fa-download"></i>
                <span>Unduh</span>
            </a>
            <button onclick="printFile()" class="btn btn-print" title="Cetak File">
                <i class="fas fa-print"></i>
                <span>Cetak</span>
            </button>
        </div>
    </div>

    <!-- Viewer -->
    <div class="viewer-container" oncontextmenu="return false;">
        <?php if ($isPdf): ?>
            <iframe 
                src="<?= $viewUrl ?>#toolbar=0&navpanes=0&scrollbar=1" 
                title="PDF Viewer"
                sandbox="allow-same-origin allow-scripts"
            ></iframe>
        <?php elseif ($isImage): ?>
            <img 
                src="<?= $viewUrl ?>" 
                alt="<?= htmlspecialchars($surat['lampiran_file']) ?>"
                draggable="false"
            >
        <?php else: ?>
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-file text-5xl mb-4" style="color: rgba(255,255,255,0.3);"></i>
                <p>Preview tidak tersedia untuk tipe file ini.</p>
                <a href="<?= $downloadUrl ?>" class="btn btn-download" style="margin-top: 16px; display: inline-flex;">
                    <i class="fas fa-download"></i> Unduh File
                </a>
            </div>
        <?php endif; ?>
        
        <div class="watermark-overlay"></div>
    </div>

    <script>
        // === PROTEKSI KEYBOARD ===
        document.addEventListener('keydown', function(e) {
            // Blokir Ctrl+P (Print)
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                showNotification('Gunakan tombol Cetak di toolbar untuk mencetak file.');
                return false;
            }
            // Blokir Ctrl+S (Save)
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                showNotification('Gunakan tombol Unduh di toolbar untuk mengunduh file.');
                return false;
            }
        });

        // === PROTEKSI DRAG ===
        document.addEventListener('dragstart', function(e) {
            e.preventDefault();
        });

        // === FUNGSI CETAK ===
        function printFile() {
            // Buka file di tab baru untuk dicetak (agar tercatat di log)
            const printWindow = window.open('<?= $viewUrl ?>', '_blank');
            if (printWindow) {
                printWindow.addEventListener('load', function() {
                    printWindow.print();
                });
            }
        }

        // === NOTIFIKASI ===
        function showNotification(message) {
            // Hapus notifikasi sebelumnya
            const existing = document.querySelector('.viewer-notification');
            if (existing) existing.remove();

            const notif = document.createElement('div');
            notif.className = 'viewer-notification';
            notif.style.cssText = `
                position: fixed;
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%);
                background: rgba(0,0,0,0.85);
                color: #fff;
                padding: 12px 24px;
                border-radius: 10px;
                font-size: 13px;
                z-index: 100;
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255,255,255,0.1);
                animation: slideUp 0.3s ease;
            `;
            notif.innerHTML = '<i class="fas fa-info-circle" style="margin-right: 8px; color: #60a5fa;"></i>' + message;
            document.body.appendChild(notif);

            setTimeout(() => {
                notif.style.opacity = '0';
                notif.style.transition = 'opacity 0.3s';
                setTimeout(() => notif.remove(), 300);
            }, 3000);
        }

        // === FUNGSI KEMBALI ===
        function goBack(e) {
            // Jika ada history sebelumnya, gunakan history.back()
            if (window.history.length > 1 && document.referrer) {
                e.preventDefault();
                window.history.back();
            }
            // Jika tidak ada history, biarkan href default (surat_detail.php) bekerja
        }

        // CSS Animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideUp {
                from { opacity: 0; transform: translateX(-50%) translateY(10px); }
                to { opacity: 1; transform: translateX(-50%) translateY(0); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
