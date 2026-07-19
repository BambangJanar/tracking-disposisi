<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/watermark_pdf.php';

use Dompdf\Dompdf; use Dompdf\Options;
requireLogin();
$tanggalDari = $_GET['tanggal_dari'] ?? date('Y-m-01');
$tanggalSampai = $_GET['tanggal_sampai'] ?? date('Y-m-d');
$settings = getAllSettings();

$logoBase64 = '';
if (!empty($settings['instansi_logo'])) { $p = SETTINGS_UPLOAD_DIR . $settings['instansi_logo']; if (file_exists($p)) { $logoBase64 = 'data:image/' . pathinfo($p, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($p)); } }
$ttdBase64 = '';
if (!empty($settings['ttd_image'])) { $p = SETTINGS_UPLOAD_DIR . $settings['ttd_image']; if (file_exists($p)) { $ttdBase64 = 'data:image/' . pathinfo($p, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($p)); } }

$query = "SELECT u.nama_lengkap, r.nama_role, COALESCE(b.nama_bagian, u.nama_bagian_custom, '-') as nama_bagian,
            (SELECT COUNT(*) FROM surat s WHERE s.dibuat_oleh = u.id AND DATE(s.created_at) BETWEEN ? AND ?) as surat_dibuat,
            (SELECT COUNT(*) FROM disposisi d1 WHERE d1.dari_user_id = u.id AND DATE(d1.tanggal_disposisi) BETWEEN ? AND ?) as disposisi_dikirim,
            (SELECT COUNT(*) FROM disposisi d2 WHERE d2.ke_user_id = u.id AND DATE(d2.tanggal_disposisi) BETWEEN ? AND ?) as disposisi_diterima,
            (SELECT COUNT(*) FROM log_aktivitas la WHERE la.user_id = u.id AND la.aktivitas = 'login' AND DATE(la.created_at) BETWEEN ? AND ?) as total_login
          FROM users u JOIN roles r ON u.id_role = r.id LEFT JOIN bagian b ON u.id_bagian = b.id
          WHERE u.status_aktif = 1 ORDER BY surat_dibuat DESC";
$list = dbSelect($query, [$tanggalDari, $tanggalSampai, $tanggalDari, $tanggalSampai, $tanggalDari, $tanggalSampai, $tanggalDari, $tanggalSampai], 'ssssssss');

ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>Laporan Statistik Pengguna</title>
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
        <?= getWatermarkCss() ?>
</style></head>
<body>
    <?= getWatermarkHtml() ?>

    <div class="kop-surat"><table><tr>
        <td class="logo-cell"><?php if (!empty($logoBase64)): ?><img src="<?= $logoBase64 ?>" alt="Logo"><?php endif; ?></td>
        <td class="text-cell"><h2><?= nl2br(htmlspecialchars($settings['instansi_nama'])) ?></h2>
            <?php if (!empty($settings['instansi_alamat'])): ?><p><?= nl2br(htmlspecialchars($settings['instansi_alamat'])) ?></p><?php endif; ?>
            <?php if (!empty($settings['instansi_telepon']) || !empty($settings['instansi_email'])): ?><p><?php if (!empty($settings['instansi_telepon'])): ?>Telp: <?= htmlspecialchars($settings['instansi_telepon']) ?><?php endif; ?><?php if (!empty($settings['instansi_telepon']) && !empty($settings['instansi_email'])): ?> | <?php endif; ?><?php if (!empty($settings['instansi_email'])): ?>Email: <?= htmlspecialchars($settings['instansi_email']) ?><?php endif; ?></p><?php endif; ?>
        </td><td class="logo-cell"></td>
    </tr></table></div>
    <div class="judul"><h3>LAPORAN STATISTIK PENGGUNA</h3><p>Periode: <?= date('d/m/Y', strtotime($tanggalDari)) ?> s/d <?= date('d/m/Y', strtotime($tanggalSampai)) ?></p></div>
    <table class="data"><thead><tr>
        <th width="5%">No</th><th width="20%">Nama</th><th width="12%">Role</th><th width="15%">Bagian</th>
        <th width="12%">Surat Dibuat</th><th width="12%">Disp. Kirim</th><th width="12%">Disp. Terima</th><th width="12%">Login</th>
    </tr></thead><tbody>
        <?php if (!empty($list)): foreach($list as $no => $r): ?>
        <tr>
            <td style="text-align:center;"><?= $no + 1 ?></td>
            <td><b><?= htmlspecialchars($r['nama_lengkap']) ?></b></td>
            <td style="text-align:center;"><?= ucfirst($r['nama_role']) ?></td>
            <td><?= htmlspecialchars($r['nama_bagian']) ?></td>
            <td style="text-align:center;"><?= $r['surat_dibuat'] ?></td>
            <td style="text-align:center;"><?= $r['disposisi_dikirim'] ?></td>
            <td style="text-align:center;"><?= $r['disposisi_diterima'] ?></td>
            <td style="text-align:center;"><?= $r['total_login'] ?></td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="8" style="text-align:center;padding:20px;">Tidak ada data.</td></tr>
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
$dompdf = new Dompdf($options); $dompdf->loadHtml($html); $dompdf->setPaper('A4', 'landscape'); $dompdf->render();
$dompdf->stream('Laporan_Statistik_Pengguna_' . date('Ymd_His') . '.pdf', ["Attachment" => false]);
?>
