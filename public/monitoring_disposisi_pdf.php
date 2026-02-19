<?php
// public/monitoring_disposisi_pdf.php - Cetak PDF Monitoring Disposisi
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../modules/disposisi/disposisi_service.php';

use Dompdf\Dompdf;
use Dompdf\Options;

requireLogin();

$user = getCurrentUser();
$userId = $user['id'];
$userRole = $user['id_role'] ?? 3;
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
$filters = [
    'status_disposisi' => $_GET['status'] ?? '',
    'search' => $_GET['search'] ?? ''
];

// Ambil SEMUA data (tanpa pagination) untuk PDF
$disposisiList = DisposisiService::getForMonitoring($userId, $userRole, $filters, 9999, 0);

ob_start();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Monitoring Disposisi</title>
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

        .status-dikirim {
            background-color: #DBEAFE;
            color: #1E40AF;
        }

        .status-diterima {
            background-color: #FEF3C7;
            color: #92400E;
        }

        .status-diproses {
            background-color: #E0E7FF;
            color: #3730A3;
        }

        .status-selesai {
            background-color: #D1FAE5;
            color: #065F46;
        }

        .status-ditolak {
            background-color: #FEE2E2;
            color: #991B1B;
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
        <h3>MONITORING DISPOSISI SURAT</h3>
        <p>Dicetak pada: <?= date('d/m/Y H:i') ?> WIB</p>
        <?php if (!empty($filters['status_disposisi'])): ?>
            <p>Filter Status: <?= ucfirst($filters['status_disposisi']) ?></p>
        <?php endif; ?>
        <?php if (!empty($filters['search'])): ?>
            <p>Pencarian: "<?= htmlspecialchars($filters['search']) ?>"</p>
        <?php endif; ?>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="20%">Alur Disposisi</th>
                <th width="30%">Surat / Perihal</th>
                <th width="20%">Catatan</th>
                <th width="13%">Tanggal</th>
                <th width="12%">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (!empty($disposisiList)):
                $no = 1;
                foreach ($disposisiList as $disp):
            ?>
                    <tr>
                        <td style="text-align: center;"><?= $no++ ?></td>
                        <td>
                            <b>Dari:</b> <?= htmlspecialchars($disp['dari_user_nama']) ?><br>
                            <b>Ke:</b> <?= htmlspecialchars($disp['ke_user_nama']) ?>
                        </td>
                        <td>
                            <b>No:</b> <?= htmlspecialchars($disp['nomor_agenda']) ?><br>
                            <b>Hal:</b> <?= htmlspecialchars($disp['perihal']) ?>
                        </td>
                        <td><?= htmlspecialchars($disp['catatan'] ?? '-') ?></td>
                        <td style="text-align: center;"><?= date('d/m/Y', strtotime($disp['tanggal_disposisi'])) ?></td>
                        <td style="text-align: center;">
                            <span class="status-badge status-<?= $disp['status_disposisi'] ?>">
                                <?= ucfirst($disp['status_disposisi']) ?>
                            </span>
                        </td>
                    </tr>
                <?php
                endforeach;
            else:
                ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px;">Tidak ada data disposisi.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <p style="font-size: 9pt; margin-top: 10px; color: #666;">
        Total: <?= count($disposisiList) ?> data disposisi
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
$dompdf->stream("Monitoring_Disposisi_" . date('Ymd_His') . ".pdf", array("Attachment" => false));
?>