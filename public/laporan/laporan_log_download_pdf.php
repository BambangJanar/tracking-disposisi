<?php
// public/laporan/laporan_log_download_pdf.php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/watermark_pdf.php';
require_once __DIR__ . '/../../modules/download/download_service.php';

use Dompdf\Dompdf;
use Dompdf\Options;

requireLogin();

$user = getCurrentUser();
$userRole = $user['id_role'] ?? 3;

// Hanya superadmin (1) dan admin/karyawan (2)
if ($userRole == 3) {
    redirect('../index.php?error=unauthorized');
    exit;
}

// Filter
$filters = [
    'search'         => $_GET['search'] ?? '',
    'user_id'        => $_GET['user_id'] ?? '',
    'aksi'           => $_GET['aksi'] ?? '',
    'tanggal_dari'   => $_GET['tanggal_dari'] ?? '',
    'tanggal_sampai' => $_GET['tanggal_sampai'] ?? '',
];

// Load settings dinamis
$settings = getAllSettings();

// ============================================================================
// LOGIKA LOGO INSTANSI (Base64)
// ============================================================================
$logoBase64 = '';
if (!empty($settings['instansi_logo'])) {
    $path = SETTINGS_UPLOAD_DIR . $settings['instansi_logo'];
    if (file_exists($path)) {
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
}

// ============================================================================
// LOGIKA GAMBAR TTD (Base64)
// ============================================================================
$ttdBase64 = '';
if (!empty($settings['ttd_image'])) {
    $pathTtd = SETTINGS_UPLOAD_DIR . $settings['ttd_image'];
    if (file_exists($pathTtd)) {
        $typeTtd = pathinfo($pathTtd, PATHINFO_EXTENSION);
        $dataTtd = file_get_contents($pathTtd);
        $ttdBase64 = 'data:image/' . $typeTtd . ';base64,' . base64_encode($dataTtd);
    }
}

// Ambil data log (max 500 terbaru)
$logList = DownloadService::getAll($filters, 500, 0);

// Informasi periode
$periodeText = 'Semua Data';
if (!empty($filters['tanggal_dari']) && !empty($filters['tanggal_sampai'])) {
    $periodeText = date('d/m/Y', strtotime($filters['tanggal_dari'])) . ' s/d ' . date('d/m/Y', strtotime($filters['tanggal_sampai']));
} elseif (!empty($filters['tanggal_dari'])) {
    $periodeText = 'Mulai ' . date('d/m/Y', strtotime($filters['tanggal_dari']));
} elseif (!empty($filters['tanggal_sampai'])) {
    $periodeText = 'Sampai ' . date('d/m/Y', strtotime($filters['tanggal_sampai']));
}

ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Log Akses File</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11pt; }
        
        .kop-surat {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .kop-surat table { width: 100%; border-collapse: collapse; border: none; }
        .kop-surat td { border: none; }
        .kop-surat .logo-cell { width: 100px; text-align: center; vertical-align: middle; }
        .kop-surat .text-cell { text-align: center; vertical-align: middle; }
        .kop-surat img { max-height: 80px; max-width: 80px; }
        .kop-surat h2 { margin: 0; font-size: 16pt; text-transform: uppercase; font-weight: bold; }
        .kop-surat p { margin: 2px 0; font-size: 10pt; }

        .judul { text-align: center; margin-bottom: 20px; }
        .judul h3 { margin: 0; text-decoration: underline; font-size: 12pt; font-weight: bold; }
        .judul p { margin: 5px 0; font-size: 10pt; }

        table.data {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }
        table.data th, table.data td {
            border: 1px solid #000;
            padding: 4px;
            vertical-align: top;
        }
        table.data th {
            background-color: #eee;
            text-align: center;
            font-weight: bold;
        }

        .badge-download { background-color: #d1fae5; color: #065f46; padding: 2px 6px; border-radius: 3px; font-size: 7pt; }
        .badge-view { background-color: #dbeafe; color: #1e40af; padding: 2px 6px; border-radius: 3px; font-size: 7pt; }

        .ttd-wrapper {
            width: 100%;
            margin-top: 40px;
            display: table;
        }
        .ttd-box {
            float: right;
            width: 40%;
            text-align: center;
        }
        .ttd-image {
            height: 80px;
            margin: 10px auto;
        }
        .ttd-spacer {
            height: 80px;
        }
        .ttd-nama {
            font-weight: bold;
            text-decoration: underline;
        }
        <?= getWatermarkCss() ?>
    </style>
</head>
<body>
    <?= getWatermarkHtml() ?>


    <div class="kop-surat">
        <table>
            <tr>
                <td class="logo-cell">
                    <?php if (!empty($logoBase64)): ?>
                        <img src="<?= $logoBase64 ?>" alt="Logo">
                    <?php endif; ?>
                </td>
                <td class="text-cell">
                    <h2><?= nl2br(htmlspecialchars($settings['instansi_nama'])) ?></h2>
                    <?php if (!empty($settings['instansi_alamat'])): ?>
                    <p><?= nl2br(htmlspecialchars($settings['instansi_alamat'])) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($settings['instansi_telepon']) || !empty($settings['instansi_email'])): ?>
                    <p>
                        <?php if (!empty($settings['instansi_telepon'])): ?>
                        Telp: <?= htmlspecialchars($settings['instansi_telepon']) ?>
                        <?php endif; ?>
                        <?php if (!empty($settings['instansi_telepon']) && !empty($settings['instansi_email'])): ?> | <?php endif; ?>
                        <?php if (!empty($settings['instansi_email'])): ?>
                        Email: <?= htmlspecialchars($settings['instansi_email']) ?>
                        <?php endif; ?>
                    </p>
                    <?php endif; ?>
                </td>
                <td class="logo-cell"></td>
            </tr>
        </table>
    </div>

    <div class="judul">
        <h3>LOG AKSES FILE LAMPIRAN SURAT</h3>
        <p>Periode: <?= $periodeText ?></p>
        <p style="font-size: 9pt;">Total: <?= count($logList) ?> aktivitas (Max 500 terbaru)</p>
        <p style="font-size: 8pt; color: #666;">Dicetak oleh: <?= htmlspecialchars($user['nama_lengkap']) ?> pada <?= date('d/m/Y H:i:s') ?></p>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th width="4%">NO</th>
                <th width="13%">WAKTU</th>
                <th width="16%">PENGGUNA</th>
                <th width="15%">NO. AGENDA</th>
                <th width="22%">PERIHAL</th>
                <th width="8%">AKSI</th>
                <th width="12%">IP ADDRESS</th>
                <th width="10%">FILE</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logList)): ?>
            <tr><td colspan="8" style="text-align: center; padding: 20px;">Tidak ada log akses file untuk filter ini</td></tr>
            <?php else: ?>
                <?php foreach ($logList as $index => $log): ?>
                <tr>
                    <td style="text-align: center;"><?= $index + 1 ?></td>
                    <td style="text-align: center; font-size: 7pt;"><?= formatDateTime($log['created_at']) ?></td>
                    <td>
                        <b><?= htmlspecialchars($log['nama_lengkap']) ?></b><br>
                        <span style="font-size: 7pt; color: #666;"><?= getRoleLabel($log['nama_role']) ?></span>
                    </td>
                    <td style="font-size: 8pt;"><?= htmlspecialchars($log['nomor_agenda']) ?></td>
                    <td style="font-size: 8pt;"><?= htmlspecialchars(mb_substr($log['perihal'], 0, 40)) ?><?= mb_strlen($log['perihal']) > 40 ? '...' : '' ?></td>
                    <td style="text-align: center;">
                        <span class="<?= $log['aksi'] === 'download' ? 'badge-download' : 'badge-view' ?>">
                            <?= $log['aksi'] === 'download' ? 'UNDUH' : 'LIHAT' ?>
                        </span>
                    </td>
                    <td style="text-align: center; font-size: 7pt; font-family: monospace;"><?= htmlspecialchars($log['ip_address'] ?? '-') ?></td>
                    <td style="font-size: 7pt;"><?= htmlspecialchars(mb_substr($log['nama_file'], 0, 20)) ?>...</td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="ttd-wrapper">
        <div class="ttd-box">
            <p>
                <?= htmlspecialchars($settings['ttd_kota']) ?>, 
                <?= date('d F Y') ?>
            </p>
            <p><?= htmlspecialchars($settings['ttd_jabatan']) ?></p> 
            
            <?php if (!empty($ttdBase64)): ?>
                <img src="<?= $ttdBase64 ?>" class="ttd-image" alt="TTD">
            <?php else: ?>
                <div class="ttd-spacer"></div>
            <?php endif; ?>
            
            <div class="ttd-nama">
                <?= htmlspecialchars($settings['ttd_nama_penandatangan']) ?>
            </div>
            <?php if (!empty($settings['ttd_nip'])): ?>
            <div>NIP. <?= htmlspecialchars($settings['ttd_nip']) ?></div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$filename = 'Log_Akses_File_' . date('Ymd_His') . '.pdf';
$dompdf->stream($filename, ["Attachment" => false]);
?>
