<?php
// includes/watermark_pdf.php
// Helper untuk menambahkan watermark logo Bank Kalsel pada laporan PDF (dompdf)
// Logo ditampilkan transparan, tidak miring, berulang di seluruh halaman + 1 logo besar di tengah

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
 * Generate CSS watermark untuk dompdf
 * - .watermark-tiled: logo kecil berulang di seluruh halaman (opacity rendah)
 * - .watermark-center: satu logo besar di tengah halaman (opacity sedikit lebih tinggi)
 * Keduanya menggunakan position:fixed agar muncul di SETIAP halaman PDF
 */
function getWatermarkCss() {
    $base64 = getWatermarkLogoBase64();
    if (empty($base64)) return '';
    
    return '
        /* === WATERMARK LOGO BANK KALSEL === */
        .watermark-tiled {
            position: fixed;
            top: -30px;
            left: -30px;
            width: 850px;
            height: 1250px;
            z-index: -1000;
            background-image: url("' . $base64 . '");
            background-repeat: repeat;
            background-size: 120px auto;
            opacity: 0.065;
        }
        .watermark-center {
            position: fixed;
            top: 300px;
            left: 130px;
            width: 350px;
            z-index: -999;
            opacity: 0.085;
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
        .watermark-tiled {
            position: fixed;
            top: -30px;
            left: -30px;
            width: 1250px;
            height: 850px;
            z-index: -1000;
            background-image: url("' . $base64 . '");
            background-repeat: repeat;
            background-size: 120px auto;
            opacity: 0.065;
        }
        .watermark-center {
            position: fixed;
            top: 200px;
            left: 280px;
            width: 350px;
            z-index: -999;
            opacity: 0.085;
        }
    ';
}

/**
 * Generate HTML elemen watermark untuk disisipkan setelah <body>
 */
function getWatermarkHtml() {
    $base64 = getWatermarkLogoBase64();
    if (empty($base64)) return '';
    
    return '<div class="watermark-tiled"></div>' . "\n" .
           '    <img src="' . $base64 . '" class="watermark-center" alt="">';
}
