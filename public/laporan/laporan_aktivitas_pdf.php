<?php
// public/laporan/laporan_aktivitas_pdf.php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

use Dompdf\Dompdf;
use Dompdf\Options;

requireLogin();
requireRole('superadmin');

$user = getCurrentUser();
$tanggalDari = $_GET['tanggal_dari'] ?? date('Y-m-d');
$tanggalSampai = $_GET['tanggal_sampai'] ?? date('Y-m-d');
$userId = $_GET['user_id'] ?? '';

// Load settings dinamis
$settings = getAllSettings();

// ============================================================================
// 1. LOGIKA LOGO INSTANSI (Base64)
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
// 2. LOGIKA GAMBAR TTD (Base64)
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
// ============================================================================

$query = "SELECT l.*, u.nama_lengkap, u.email
          FROM log_aktivitas l
          JOIN users u ON l.user_id = u.id
          WHERE DATE(l.created_at) BETWEEN ? AND ?";

$params = [$tanggalDari, $tanggalSampai];
$types = 'ss';

if (!empty($userId)) {
    $query .= " AND l.user_id = ?";
    $params[] = $userId;
    $types .= 'i';
}

$query .= " ORDER BY l.created_at DESC LIMIT 500";

$logList = dbSelect($query, $params, $types);

ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Log Aktivitas Sistem</title>
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
            font-size: 9pt;
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
            height: 80px; /* Tinggi gambar TTD */
            margin: 10px auto;
        }
        .ttd-spacer {
            height: 80px; /* Jarak jika tidak ada gambar */
        }
        .ttd-nama {
            font-weight: bold;
            text-decoration: underline;
        }
    </style>
</head>
<body>

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
        <h3>LOG AKTIVITAS SISTEM</h3>
        <p>Periode: <?= date('d/m/Y', strtotime($tanggalDari)) ?> s/d <?= date('d/m/Y', strtotime($tanggalSampai)) ?></p>
        <p style="font-size: 9pt;">Total: <?= count($logList) ?> aktivitas (Max 500 terbaru)</p>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th width="5%">NO</th>
                <th width="15%">WAKTU</th>
                <th width="18%">USER</th>
                <th width="13%">AKTIVITAS</th>
                <th width="49%">KETERANGAN</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logList)): ?>
            <tr><td colspan="5" style="text-align: center; padding: 20px;">Tidak ada log aktivitas untuk periode ini</td></tr>
            <?php else: ?>
                <?php foreach ($logList as $index => $log): ?>
                <tr>
                    <td style="text-align: center;"><?= $index + 1 ?></td>
                    <td style="text-align: center; font-size: 8pt;"><?= formatDateTime($log['created_at']) ?></td>
                    <td>
                        <b><?= htmlspecialchars($log['nama_lengkap']) ?></b><br>
                        <span style="font-size: 7pt; color: #666;"><?= htmlspecialchars($log['email']) ?></span>
                    </td>
                    <td style="text-align: center; font-size: 8pt;">
                        <?= ucfirst(str_replace('_', ' ', $log['aktivitas'])) ?>
                    </td>
                    <td style="font-size: 8pt;"><?= htmlspecialchars($log['keterangan'] ?? '-') ?></td>
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
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = 'Log_Aktivitas_' . date('Ymd_His') . '.pdf';
$dompdf->stream($filename, ["Attachment" => false]);
?>