<?php

/**
 * simpan.php  -  PROSES SIMPAN DATA BARU (INSERT DINAMIS)
 *
 * Menerima data dari form tambah.php, lalu menyusun query INSERT
 * secara dinamis. Tidak peduli berapa kolom atau nama apa saja.
 */
require_once __DIR__ . '/koneksi.php';

$table = isset($_POST['table']) ? $_POST['table'] : '';

/* Validasi table. */
if ($table === '' || !tableExists($table)) {
    hentikan('Table tidak ditemukan.');
}

$koloms = getColumns($table);

/* Susun daftar kolom yang akan disimpan (lewati auto-increment). */
$daftarKolom = [];
$daftarNilai = [];
foreach ($koloms as $kol) {
    if ($kol['auto']) {
        continue;
    }
    /* Hanya simpan kolom yang benar-benar dikirim dari form. */
    if (isset($_POST[$kol['nama']])) {
        $daftarKolom[] = '`' . $conn->real_escape_string($kol['nama']) . '`';
        $daftarNilai[] = "'" . bersih($_POST[$kol['nama']]) . "'";
    }
}

/* Jika tidak ada kolom yang bisa disimpan, beri peringatan. */
if (empty($daftarKolom)) {
    hentikan('Tidak ada data yang dikirim untuk disimpan.');
}

$sql = "INSERT INTO `" . $conn->real_escape_string($table) . "` ("
    . implode(', ', $daftarKolom) . ") VALUES ("
    . implode(', ', $daftarNilai) . ")";

/* Jalankan query dan beri umpan balik. */
if ($conn->query($sql)) {
    header('Location: index.php?table=' . urlencode($table));
    exit;
} else {
    if (DB_DEBUG) {
        die('Gagal menyimpan: ' . htmlspecialchars($conn->error));
    }
    hentikan('Gagal menyimpan data. Periksa kembali isian Anda.');
}
