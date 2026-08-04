<?php
// includes/watermark_pdf.php
// Helper untuk menambahkan watermark logo Bank Kalsel pada laporan PDF (dompdf)
// Logo ditampilkan sebagai satu logo besar transparan di tengah halaman

/**
 * Mengambil watermark logo Bank Kalsel dalam format Base64
 * Menggunakan static cache agar file hanya dibaca sekali per request
 */
function getWatermarkLogoBase64() {
    static $cache = null;
    if ($cache !== null) return $cache;
    
    $logoPath = __DIR__ . '/../assets/images/watermark_logo.png';
    if (file_exists($logoPath)) {
        $data = file_get_contents($logoPath);
        $cache = 'data:image/png;base64,' . base64_encode($data);
    } else {
        $cache = '';
    }
    return $cache;
}

/**
 * Generate CSS watermark untuk dompdf (Portrait)
 * Satu logo besar di tengah halaman
 */
function getWatermarkCss() {
    $base64 = getWatermarkLogoBase64();
    if (empty($base64)) return '';
    
    return '
        /* === WATERMARK LOGO BANK KALSEL === */
        .watermark-center {
            position: fixed;
            top: 50%;
            left: 50%;
            width: 450px;
            margin-top: -225px;
            margin-left: -225px;
            z-index: -999;
            opacity: 0.08;
        }
    ';
}

/**
 * Generate CSS watermark untuk layout landscape
 */
function getWatermarkCssLandscape() {
    $base64 = getWatermarkLogoBase64();
    if (empty($base64)) return '';
    
    return '
        /* === WATERMARK LOGO BANK KALSEL (LANDSCAPE) === */
        .watermark-center {
            position: fixed;
            top: 50%;
            left: 50%;
            width: 450px;
            margin-top: -225px;
            margin-left: -225px;
            z-index: -999;
            opacity: 0.08;
        }
    ';
}

/**
 * Generate HTML elemen watermark untuk disisipkan setelah <body>
 * Hanya satu logo besar di tengah (tanpa tiled/berulang)
 */
function getWatermarkHtml() {
    $base64 = getWatermarkLogoBase64();
    if (empty($base64)) return '';
    
    return '<img src="' . $base64 . '" class="watermark-center" alt="">';
}
