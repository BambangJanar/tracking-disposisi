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

// --- PERBAIKAN ERROR DATABASE DI SINI ---
// Kita harus memanggil fungsi getConnection() untuk mendapatkan objek koneksi
$conn = getConnection(); 

// Ambil filter tanggal
$tanggalDari = $_GET['tanggal_dari'] ?? date('Y-m-01');
$tanggalSampai = $_GET['tanggal_sampai'] ?? date('Y-m-d');

// Query Data Disposisi
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

// Menggunakan Prepared Statement agar lebih aman
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $tanggalDari, $tanggalSampai);
$stmt->execute();
$result = $stmt->get_result();

// Mulai buffering HTML untuk PDF
ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Disposisi</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11pt; }
        
        /* Layout Kop Surat */
        .kop-surat {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .kop-surat h2 { margin: 0; font-size: 16pt; text-transform: uppercase; }
        .kop-surat p { margin: 2px 0; font-size: 10pt; }

        /* Judul Laporan */
        .judul { text-align: center; margin-bottom: 20px; }
        .judul h3 { margin: 0; text-decoration: underline; font-size: 12pt; }
        .judul p { margin: 5px 0; font-size: 10pt; }

        /* Tabel Sederhana */
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
            background-color: #eee; /* Sedikit abu-abu biar header jelas, tapi tetap BW */
            text-align: center;
            font-weight: bold;
        }

        /* Tanda Tangan */
        .ttd-wrapper {
            width: 100%;
            margin-top: 40px;
            display: table; /* Hack layout untuk dompdf */
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
        <h2>PEMERINTAH KABUPATEN CONTOH</h2>
        <h2>DINAS CONTOH APLIKASI SURAT</h2>
        <p>Jl. Contoh Alamat No. 123, Kota Contoh, Provinsi Contoh</p>
        <p>Telp: (021) 555-5555 | Email: admin@instansi.gov.id</p>
    </div>

    <div class="judul">
        <h3>LAPORAN DISPOSISI SURAT</h3>
        <p>Periode: <?= date('d-m-Y', strtotime($tanggalDari)) ?> s/d <?= date('d-m-Y', strtotime($tanggalSampai)) ?></p>
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
                    <td colspan="5" style="text-align: center;">Tidak ada data disposisi pada periode ini.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="ttd-wrapper">
        <div class="ttd-box">
            <p>Kota Contoh, <?= date('d F Y') ?></p>
            <p>Mengetahui,</p>
            <p>Kepala Dinas / Pimpinan</p> 
            
            <div class="ttd-nama">
                <?= htmlspecialchars($user['nama_lengkap']) ?>
            </div>
            <div>NIP. 19xxxxxxxxxxxxxx</div>
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

// Set Kertas Portrait A4
$dompdf->setPaper('A4', 'portrait');

$dompdf->render();
$dompdf->stream("Laporan_Disposisi_Simple.pdf", array("Attachment" => false));
?>