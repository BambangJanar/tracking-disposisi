<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

use Dompdf\Dompdf;
use Dompdf\Options;

requireLogin();
$user = getCurrentUser();
$tanggalDari = $_GET['tanggal_dari'] ?? date('Y-m-01');
$tanggalSampai = $_GET['tanggal_sampai'] ?? date('Y-m-d');
$settings = getAllSettings();

$logoBase64 = '';
if (!empty($settings['instansi_logo'])) { $path = SETTINGS_UPLOAD_DIR . $settings['instansi_logo']; if (file_exists($path)) { $logoBase64 = 'data:image/' . pathinfo($path, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($path)); } }
$ttdBase64 = '';
if (!empty($settings['ttd_image'])) { $pathTtd = SETTINGS_UPLOAD_DIR . $settings['ttd_image']; if (file_exists($pathTtd)) { $ttdBase64 = 'data:image/' . pathinfo($pathTtd, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($pathTtd)); } }

$query = "SELECT s.*, j.nama_jenis,
          (SELECT d.catatan FROM disposisi d WHERE d.id_surat = s.id AND d.status_disposisi = 'ditolak' ORDER BY d.created_at DESC LIMIT 1) as alasan_tolak
          FROM surat s JOIN jenis_surat j ON s.id_jenis = j.id
          WHERE s.status_surat = 'ditolak' AND DATE(s.tanggal_diterima) BETWEEN ? AND ?
          ORDER BY s.updated_at DESC";
$suratList = dbSelect($query, [$tanggalDari, $tanggalSampai], 'ss');

ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Laporan Surat Ditolak</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11pt; }
        .kop-surat { text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 20px; }
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
        table.data { width: 100%; border-collapse: collapse; font-size: 10pt; }
        table.data th, table.data td { border: 1px solid #000; padding: 5px; vertical-align: top; }
        table.data th { background-color: #eee; text-align: center; font-weight: bold; }
        .ttd-wrapper { width: 100%; margin-top: 40px; } .ttd-box { float: right; width: 40%; text-align: center; }
        .ttd-image { height: 80px; margin: 10px auto; display: block; } .ttd-spacer { height: 80px; }
        .ttd-nama { font-weight: bold; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="kop-surat"><table><tr>
        <td class="logo-cell"><?php if (!empty($logoBase64)): ?><img src="<?= $logoBase64 ?>" alt="Logo"><?php endif; ?></td>
        <td class="text-cell">
            <h2><?= nl2br(htmlspecialchars($settings['instansi_nama'])) ?></h2>
            <?php if (!empty($settings['instansi_alamat'])): ?><p><?= nl2br(htmlspecialchars($settings['instansi_alamat'])) ?></p><?php endif; ?>
            <?php if (!empty($settings['instansi_telepon']) || !empty($settings['instansi_email'])): ?><p><?php if (!empty($settings['instansi_telepon'])): ?>Telp: <?= htmlspecialchars($settings['instansi_telepon']) ?><?php endif; ?><?php if (!empty($settings['instansi_telepon']) && !empty($settings['instansi_email'])): ?> | <?php endif; ?><?php if (!empty($settings['instansi_email'])): ?>Email: <?= htmlspecialchars($settings['instansi_email']) ?><?php endif; ?></p><?php endif; ?>
        </td>
        <td class="logo-cell"></td>
    </tr></table></div>

    <div class="judul">
        <h3>LAPORAN SURAT DITOLAK</h3>
        <p>Periode: <?= date('d/m/Y', strtotime($tanggalDari)) ?> s/d <?= date('d/m/Y', strtotime($tanggalSampai)) ?></p>
    </div>

    <table class="data">
        <thead><tr>
            <th width="5%">No</th><th width="13%">No. Agenda</th><th width="10%">Jenis</th>
            <th width="25%">Perihal</th><th width="12%">Tgl Diterima</th><th width="25%">Alasan Penolakan</th>
        </tr></thead>
        <tbody>
            <?php if (!empty($suratList)): foreach($suratList as $no => $row): ?>
            <tr>
                <td style="text-align:center;"><?= $no + 1 ?></td>
                <td><b><?= htmlspecialchars($row['nomor_agenda']) ?></b></td>
                <td style="text-align:center;"><?= $row['nama_jenis'] ?></td>
                <td><?= htmlspecialchars(truncate($row['perihal'], 60)) ?></td>
                <td style="text-align:center;"><?= date('d/m/Y', strtotime($row['tanggal_diterima'])) ?></td>
                <td><em><?= htmlspecialchars($row['alasan_tolak'] ?? '-') ?></em></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="6" style="text-align:center;padding:20px;">Tidak ada data.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="ttd-wrapper"><div class="ttd-box">
        <p><?= htmlspecialchars($settings['ttd_kota']) ?>, <?= date('d F Y') ?></p>
        <p><?= htmlspecialchars($settings['ttd_jabatan']) ?></p>
        <?php if (!empty($ttdBase64)): ?><img src="<?= $ttdBase64 ?>" class="ttd-image" alt="TTD"><?php else: ?><div class="ttd-spacer"></div><?php endif; ?>
        <div class="ttd-nama"><?= htmlspecialchars($settings['ttd_nama_penandatangan']) ?></div>
        <?php if (!empty($settings['ttd_nip'])): ?><div>NIP. <?= htmlspecialchars($settings['ttd_nip']) ?></div><?php endif; ?>
    </div></div>
</body></html>
<?php
$html = ob_get_clean();
$options = new Options(); $options->set('isHtml5ParserEnabled', true); $options->set('isRemoteEnabled', true); $options->set('defaultFont', 'Arial');
$dompdf = new Dompdf($options); $dompdf->loadHtml($html); $dompdf->setPaper('A4', 'portrait'); $dompdf->render();
$dompdf->stream('Laporan_Surat_Ditolak_' . date('Ymd_His') . '.pdf', ["Attachment" => false]);
?>
