<?php
// public/laporan/laporan_surat_masuk_pdf.php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../modules/surat/surat_service.php';

use Dompdf\Dompdf;
use Dompdf\Options;

requireLogin();

$user = getCurrentUser();
$tanggalDari = $_GET['tanggal_dari'] ?? date('Y-m-01');
$tanggalSampai = $_GET['tanggal_sampai'] ?? date('Y-m-d');

// Load settings dinamis
$settings = getAllSettings();

$filters = [
    'id_jenis' => 1,
    'tanggal_dari' => $tanggalDari,
    'tanggal_sampai' => $tanggalSampai,
    'include_arsip' => true
];

$suratList = SuratService::getAll($filters, 1000, 0);
$totalSurat = count($suratList);

$byStatus = [];
foreach ($suratList as $surat) {
    $status = $surat['status_surat'];
    if (!isset($byStatus[$status])) {
        $byStatus[$status] = 0;
    }
    $byStatus[$status]++;
}

ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Surat Masuk</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11pt; }
        
        .kop-surat {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .kop-surat table { width: 100%; }
        .kop-surat .logo-cell { width: 80px; text-align: center; vertical-align: middle; }
        .kop-surat .text-cell { text-align: center; vertical-align: middle; }
        .kop-surat img { max-height: 70px; max-width: 70px; }
        .kop-surat h2 { margin: 0; font-size: 14pt; text-transform: uppercase; }
        .kop-surat p { margin: 2px 0; font-size: 10pt; }

        .judul { text-align: center; margin-bottom: 20px; }
        .judul h3 { margin: 0; text-decoration: underline; font-size: 12pt; }
        .judul p { margin: 5px 0; font-size: 10pt; }

        table.data {
            width: 100%;
            border-collapse: collapse;
            font-size: 10pt;
        }
        table.data th, table.data td {
            border: 1px solid #000;
            padding: 5px;
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
        .ttd-nama {
            margin-top: 70px;
            font-weight: bold;
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="kop-surat">
        <table>
            <tr>
                <?php if (!empty($settings['instansi_logo'])): ?>
                <td class="logo-cell">
                    <img src="<?= SETTINGS_UPLOAD_DIR . $settings['instansi_logo'] ?>" alt="Logo">
                </td>
                <?php endif; ?>
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
                        <?php if (!empty($settings['instansi_email'])): ?>
                        | Email: <?= htmlspecialchars($settings['instansi_email']) ?>
                        <?php endif; ?>
                    </p>
                    <?php endif; ?>
                </td>
                <?php if (!empty($settings['instansi_logo'])): ?>
                <td class="logo-cell"></td>
                <?php endif; ?>
            </tr>
        </table>
    </div>

    <div class="judul">
        <h3>LAPORAN SURAT MASUK</h3>
        <p>Periode: <?= date('d-m-Y', strtotime($tanggalDari)) ?> s/d <?= date('d-m-Y', strtotime($tanggalSampai)) ?></p>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">No. Agenda</th>
                <th width="20%">Dari Instansi</th>
                <th width="30%">Perihal</th>
                <th width="15%">Tgl Surat</th>
                <th width="15%">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if (!empty($suratList)): 
                foreach($suratList as $no => $row):
            ?>
                <tr>
                    <td style="text-align: center;"><?= $no + 1 ?></td>
                    <td><b><?= htmlspecialchars($row['nomor_agenda']) ?></b></td>
                    <td><?= htmlspecialchars($row['dari_instansi'] ?? '-') ?></td>
                    <td><?= htmlspecialchars(truncate($row['perihal'], 80)) ?></td>
                    <td style="text-align: center;"><?= date('d/m/Y', strtotime($row['tanggal_surat'])) ?></td>
                    <td style="text-align: center;"><?= ucfirst($row['status_surat']) ?></td>
                </tr>
            <?php 
                endforeach; 
            else: 
            ?>
                <tr>
                    <td colspan="6" style="text-align: center;">Tidak ada data surat masuk pada periode ini.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="ttd-wrapper">
        <div class="ttd-box">
            <p><?= htmlspecialchars($settings['ttd_kota']) ?>, <?= date('d F Y') ?></p>
            <p><?= htmlspecialchars($settings['ttd_jabatan']) ?></p> 
            
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

$filename = 'Laporan_Surat_Masuk_' . date('Ymd_His') . '.pdf';
$dompdf->stream($filename, ["Attachment" => false]);