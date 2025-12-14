<?php
// public/laporan/laporan_disposisi_pdf.php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

use Dompdf\Dompdf;
use Dompdf\Options;

requireLogin();

$user = getCurrentUser();
$conn = getConnection(); 
$tanggalDari = $_GET['tanggal_dari'] ?? date('Y-m-01');
$tanggalSampai = $_GET['tanggal_sampai'] ?? date('Y-m-d');

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

$query = "SELECT d.*, 
                 s.nomor_agenda, s.nomor_surat, s.perihal,
                 js.nama_jenis,
                 u1.nama_lengkap as dari_user_nama,
                 u2.nama_lengkap as ke_user_nama
          FROM disposisi d
          JOIN surat s ON d.id_surat = s.id
          JOIN jenis_surat js ON s.id_jenis = js.id
          JOIN users u1 ON d.dari_user_id = u1.id
          JOIN users u2 ON d.ke_user_id = u2.id
          WHERE DATE(d.tanggal_disposisi) BETWEEN ? AND ?
          ORDER BY d.tanggal_disposisi ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $tanggalDari, $tanggalSampai);
$stmt->execute();
$result = $stmt->get_result();

ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Disposisi</title>
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

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10pt;
        }
        table th, table td {
            border: 1px solid #000;
            padding: 5px;
            vertical-align: top;
        }
        table th {
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
        /* Style Tambahan untuk Gambar TTD */
        .ttd-image {
            height: 80px;
            margin: 10px auto;
            display: block;
        }
        .ttd-spacer {
            height: 80px;
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
        <h3>LAPORAN DISPOSISI SURAT</h3>
        <p>Periode: <?= date('d/m/Y', strtotime($tanggalDari)) ?> s/d <?= date('d/m/Y', strtotime($tanggalSampai)) ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">Tgl Disposisi</th>
                <th width="30%">Surat / Perihal</th>
                <th width="25%">Dari / Kepada</th>
                <th width="25%">Isi Disposisi</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if ($result->num_rows > 0): 
                $no = 1;
                while($row = $result->fetch_assoc()):
            ?>
                <tr>
                    <td style="text-align: center;"><?= $no++ ?></td>
                    <td style="text-align: center;"><?= date('d/m/Y', strtotime($row['tanggal_disposisi'])) ?></td>
                    <td>
                        <b>No:</b> <?= htmlspecialchars($row['nomor_surat']) ?><br>
                        <b>Hal:</b> <?= htmlspecialchars($row['perihal']) ?>
                    </td>
                    <td>
                        <b>Dari:</b> <?= htmlspecialchars($row['dari_user_nama']) ?><br>
                        <b>Ke:</b> <?= htmlspecialchars($row['ke_user_nama']) ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($row['catatan']) ?>
                        <br>
                        <small><i>(Status: <?= $row['status_disposisi'] ?>)</i></small>
                    </td>
                </tr>
            <?php 
                endwhile; 
            else: 
            ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 20px;">Tidak ada data disposisi pada periode ini.</td>
                </tr>
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
$dompdf->stream("Laporan_Disposisi_" . date('Ymd_His') . ".pdf", array("Attachment" => false));
?>