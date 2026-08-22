<?php
/**
 * hapus.php  -  PROSES HAPUS DATA (DELETE DINAMIS)
 *
 * Menerima table dan identitas baris dari URL, lalu menghapus baris
 * terkait. Identitas bisa berupa primary key ATAU kombinasi seluruh
 * kolom (untuk table tanpa primary key).
 */
require_once __DIR__ . '/koneksi.php';

$table = isset($_GET['table']) ? $_GET['table'] : '';
$pk    = getPrimaryKey($table);

/* Validasi table. */
if ($table === '' || !tableExists($table)) {
    hentikan('Table tidak ditemukan.');
}

/* Tentukan identitas baris: primary key atau kombinasi kolom. */
$identParams = [];
if ($pk) {
    $nilaiPk = isset($_GET[$pk]) ? $_GET[$pk] : '';
    if ($nilaiPk === '') {
        hentikan('Data tidak ditemukan. Tidak ada identitas baris yang dikirim.');
    }
    $identParams = [$pk => $nilaiPk];
} else {
    $identString = isset($_GET['ident']) ? $_GET['ident'] : '';
    $identParams = decodeIdent($identString);
    if (empty($identParams)) {
        hentikan('Data tidak ditemukan. Tidak ada identitas baris yang dikirim.');
    }
}

/* Bangun WHERE dinamis. Jika table punya PK, WHERE hanya memakai PK. */
$where = buildWhere($table, $identParams);
if ($where === '') {
    hentikan('Identitas baris tidak valid. Data tidak dapat dihapus.');
}

$sql = "DELETE FROM `" . $conn->real_escape_string($table) . "` WHERE {$where}";

if ($conn->query($sql)) {
    header('Location: index.php?table=' . urlencode($table));
    exit;
} else {
    if (DB_DEBUG) {
        die('Gagal menghapus: ' . htmlspecialchars($conn->error));
    }
    hentikan('Gagal menghapus data.');
}
