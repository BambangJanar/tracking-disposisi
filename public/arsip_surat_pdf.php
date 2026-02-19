<?php
// public/arsip_surat_pdf.php - Cetak PDF Arsip Digital
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

use Dompdf\Dompdf;
use Dompdf\Options;

requireLogin();

$user = getCurrentUser();
$conn = getConnection();

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

// ============================================================================
// Ambil filter dari GET
// ============================================================================
$search = $_GET['search'] ?? '';

$params = [];
$types = '';

$query = "SELECT s.*, 
          js.nama_jenis,
          u.nama_lengkap as dibuat_oleh_nama
          FROM surat s
          LEFT JOIN jenis_surat js ON s.id_jenis = js.id
          LEFT JOIN users u ON s.dibuat_oleh = u.id
          WHERE s.status_surat = 'arsip'";

if (!empty($search)) {
    $searchWild = '%' . $search . '%';
    $query .= " AND (s.nomor_agenda LIKE ? OR s.perihal LIKE ? OR s.nomor_surat LIKE ?)";
    $params[] = $searchWild;
    $params[] = $searchWild;
    $params[] = $searchWild;
    $types .= 'sss';
}

$query .= " ORDER BY s.updated_at DESC";

$arsipList = dbSelect($query, $params, $types);

ob_start();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Arsip Surat Digital</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
        }

        .kop-surat {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .kop-surat table {
            width: 100%;
            border-collapse: collapse;
            border: none;
        }

        .kop-surat td {
            border: none;
        }

        .kop-surat .logo-cell {
            width: 100px;
            text-align: center;
            vertical-align: middle;
        }

        .kop-surat .text-cell {
            text-align: center;
            vertical-align: middle;
        }

        .kop-surat img {
            max-height: 80px;
            max-width: 80px;
        }

        .kop-surat h2 {
            margin: 0;
            font-size: 16pt;
            text-transform: uppercase;
            font-weight: bold;
        }

        .kop-surat p {
            margin: 2px 0;
            font-size: 10pt;
        }

        .judul {
            text-align: center;
            margin-bottom: 20px;
        }

        .judul h3 {
            margin: 0;
            text-decoration: underline;
            font-size: 12pt;
            font-weight: bold;
        }

        .judul p {
            margin: 5px 0;
            font-size: 10pt;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }

        table th,
        table td {
            border: 1px solid #000;
            padding: 5px;
            vertical-align: top;
        }

        table th {
            background-color: #eee;
            text-align: center;
            font-weight: bold;
        }

        .status-badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
        }

        .status-disetujui {
            background-color: #D1FAE5;
            color: #065F46;
        }

        .status-ditolak {
            background-color: #FEE2E2;
            color: #991B1B;
        }

        .status-proses {
            background-color: #FEF3C7;
            color: #92400E;
        }

        .status-baru {
            background-color: #DBEAFE;
            color: #1E40AF;
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
        <h3>DAFTAR ARSIP SURAT DIGITAL</h3>
        <p>Dicetak pada: <?= date('d/m/Y H:i') ?> WIB</p>
        <?php if (!empty($search)): ?>
            <p>Pencarian: "<?= htmlspecialchars($search) ?>"</p>
        <?php endif; ?>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="12%">No. Agenda</th>
                <th width="12%">No. Surat</th>
                <th width="8%">Jenis</th>
                <th width="23%">Perihal</th>
                <th width="15%">Dari / Ke Instansi</th>
                <th width="10%">Tgl Surat</th>
                <th width="8%">Status Sblm</th>
                <th width="10%">Tgl Arsip</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (!empty($arsipList)):
                $no = 1;
                foreach ($arsipList as $surat):
                    $statusSebelum = $surat['status_sebelum_arsip'] ?? 'baru';
            ?>
                    <tr>
                        <td style="text-align: center;"><?= $no++ ?></td>
                        <td><?= htmlspecialchars($surat['nomor_agenda']) ?></td>
                        <td><?= htmlspecialchars($surat['nomor_surat']) ?></td>
                        <td style="text-align: center;"><?= htmlspecialchars($surat['nama_jenis']) ?></td>
                        <td><?= htmlspecialchars($surat['perihal']) ?></td>
                        <td>
                            <?php if ($surat['dari_instansi']): ?>
                                <b>Dari:</b> <?= htmlspecialchars($surat['dari_instansi']) ?><br>
                            <?php endif; ?>
                            <?php if ($surat['ke_instansi']): ?>
                                <b>Ke:</b> <?= htmlspecialchars($surat['ke_instansi']) ?>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;"><?= date('d/m/Y', strtotime($surat['tanggal_surat'])) ?></td>
                        <td style="text-align: center;">
                            <span class="status-badge status-<?= $statusSebelum ?>">
                                <?= ucfirst($statusSebelum) ?>
                            </span>
                        </td>
                        <td style="text-align: center;"><?= date('d/m/Y', strtotime($surat['updated_at'])) ?></td>
                    </tr>
                <?php
                endforeach;
            else:
                ?>
                <tr>
                    <td colspan="9" style="text-align: center; padding: 20px;">Tidak ada data arsip surat.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <p style="font-size: 9pt; margin-top: 10px; color: #666;">
        Total: <?= count($arsipList) ?> surat diarsipkan
    </p>

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
$dompdf->stream("Arsip_Surat_" . date('Ymd_His') . ".pdf", array("Attachment" => false));
?>