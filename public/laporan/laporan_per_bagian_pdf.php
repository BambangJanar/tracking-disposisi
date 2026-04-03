<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

use Dompdf\Dompdf; use Dompdf\Options;
requireLogin();
$tanggalDari = $_GET['tanggal_dari'] ?? date('Y-m-01');
$tanggalSampai = $_GET['tanggal_sampai'] ?? date('Y-m-d');
$settings = getAllSettings();

$logoBase64 = '';
if (!empty($settings['instansi_logo'])) { $p = SETTINGS_UPLOAD_DIR . $settings['instansi_logo']; if (file_exists($p)) { $logoBase64 = 'data:image/' . pathinfo($p, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($p)); } }
$ttdBase64 = '';
if (!empty($settings['ttd_image'])) { $p = SETTINGS_UPLOAD_DIR . $settings['ttd_image']; if (file_exists($p)) { $ttdBase64 = 'data:image/' . pathinfo($p, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($p)); } }

$query = "SELECT b.nama_bagian,
            COUNT(DISTINCT CASE WHEN s.id_jenis = 1 THEN s.id END) as surat_masuk,
            COUNT(DISTINCT CASE WHEN s.id_jenis = 2 THEN s.id END) as surat_keluar,
            COUNT(DISTINCT s.id) as total_surat,
            (SELECT COUNT(*) FROM disposisi d2 JOIN users u2 ON d2.ke_user_id = u2.id WHERE u2.id_bagian = b.id AND DATE(d2.tanggal_disposisi) BETWEEN ? AND ?) as disposisi_masuk,
            (SELECT COUNT(*) FROM disposisi d3 JOIN users u3 ON d3.dari_user_id = u3.id WHERE u3.id_bagian = b.id AND DATE(d3.tanggal_disposisi) BETWEEN ? AND ?) as disposisi_keluar
          FROM bagian b LEFT JOIN users u ON u.id_bagian = b.id LEFT JOIN surat s ON s.dibuat_oleh = u.id AND DATE(s.tanggal_diterima) BETWEEN ? AND ?
          GROUP BY b.id, b.nama_bagian ORDER BY total_surat DESC";
$list = dbSelect($query, [$tanggalDari, $tanggalSampai, $tanggalDari, $tanggalSampai, $tanggalDari, $tanggalSampai], 'ssssss');

ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>Laporan Per Bagian</title>
<style>
    body { font-family: Arial, sans-serif; font-size: 11pt; }
    .kop-surat { text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 20px; }
    .kop-surat table { width: 100%; border-collapse: collapse; border: none; } .kop-surat td { border: none; }
    .kop-surat .logo-cell { width: 100px; text-align: center; vertical-align: middle; } .kop-surat .text-cell { text-align: center; vertical-align: middle; }
    .kop-surat img { max-height: 80px; max-width: 80px; } .kop-surat h2 { margin: 0; font-size: 16pt; text-transform: uppercase; font-weight: bold; } .kop-surat p { margin: 2px 0; font-size: 10pt; }
    .judul { text-align: center; margin-bottom: 20px; } .judul h3 { margin: 0; text-decoration: underline; font-size: 12pt; } .judul p { margin: 5px 0; font-size: 10pt; }
    table.data { width: 100%; border-collapse: collapse; font-size: 10pt; }
    table.data th, table.data td { border: 1px solid #000; padding: 5px; vertical-align: top; }
    table.data th { background-color: #eee; text-align: center; font-weight: bold; }
    .ttd-wrapper { width: 100%; margin-top: 40px; } .ttd-box { float: right; width: 40%; text-align: center; }
    .ttd-image { height: 80px; margin: 10px auto; display: block; } .ttd-spacer { height: 80px; } .ttd-nama { font-weight: bold; text-decoration: underline; }
</style></head>
<body>
    <div class="kop-surat"><table><tr>
        <td class="logo-cell"><?php if (!empty($logoBase64)): ?><img src="<?= $logoBase64 ?>" alt="Logo"><?php endif; ?></td>
        <td class="text-cell"><h2><?= nl2br(htmlspecialchars($settings['instansi_nama'])) ?></h2>
            <?php if (!empty($settings['instansi_alamat'])): ?><p><?= nl2br(htmlspecialchars($settings['instansi_alamat'])) ?></p><?php endif; ?>
            <?php if (!empty($settings['instansi_telepon']) || !empty($settings['instansi_email'])): ?><p><?php if (!empty($settings['instansi_telepon'])): ?>Telp: <?= htmlspecialchars($settings['instansi_telepon']) ?><?php endif; ?><?php if (!empty($settings['instansi_telepon']) && !empty($settings['instansi_email'])): ?> | <?php endif; ?><?php if (!empty($settings['instansi_email'])): ?>Email: <?= htmlspecialchars($settings['instansi_email']) ?><?php endif; ?></p><?php endif; ?>
        </td><td class="logo-cell"></td>
    </tr></table></div>
    <div class="judul"><h3>LAPORAN PER BAGIAN</h3><p>Periode: <?= date('d/m/Y', strtotime($tanggalDari)) ?> s/d <?= date('d/m/Y', strtotime($tanggalSampai)) ?></p></div>
    <table class="data"><thead><tr>
        <th width="5%">No</th><th width="25%">Nama Bagian</th><th width="14%">Surat Masuk</th>
        <th width="14%">Surat Keluar</th><th width="14%">Total Surat</th><th width="14%">Disp. Masuk</th><th width="14%">Disp. Keluar</th>
    </tr></thead><tbody>
        <?php if (!empty($list)): foreach($list as $no => $r): ?>
        <tr>
            <td style="text-align:center;"><?= $no + 1 ?></td>
            <td><b><?= htmlspecialchars($r['nama_bagian']) ?></b></td>
            <td style="text-align:center;"><?= $r['surat_masuk'] ?></td>
            <td style="text-align:center;"><?= $r['surat_keluar'] ?></td>
            <td style="text-align:center;font-weight:bold;"><?= $r['total_surat'] ?></td>
            <td style="text-align:center;"><?= $r['disposisi_masuk'] ?></td>
            <td style="text-align:center;"><?= $r['disposisi_keluar'] ?></td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="7" style="text-align:center;padding:20px;">Tidak ada data.</td></tr>
        <?php endif; ?>
    </tbody></table>
    <div class="ttd-wrapper"><div class="ttd-box">
        <p><?= htmlspecialchars($settings['ttd_kota']) ?>, <?= date('d F Y') ?></p><p><?= htmlspecialchars($settings['ttd_jabatan']) ?></p>
        <?php if (!empty($ttdBase64)): ?><img src="<?= $ttdBase64 ?>" class="ttd-image" alt="TTD"><?php else: ?><div class="ttd-spacer"></div><?php endif; ?>
        <div class="ttd-nama"><?= htmlspecialchars($settings['ttd_nama_penandatangan']) ?></div>
        <?php if (!empty($settings['ttd_nip'])): ?><div>NIP. <?= htmlspecialchars($settings['ttd_nip']) ?></div><?php endif; ?>
    </div></div>
</body></html>
<?php
$html = ob_get_clean();
$options = new Options(); $options->set('isHtml5ParserEnabled', true); $options->set('isRemoteEnabled', true); $options->set('defaultFont', 'Arial');
$dompdf = new Dompdf($options); $dompdf->loadHtml($html); $dompdf->setPaper('A4', 'portrait'); $dompdf->render();
$dompdf->stream('Laporan_Per_Bagian_' . date('Ymd_His') . '.pdf', ["Attachment" => false]);
?>
