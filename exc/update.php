<?php
/**
 * update.php  -  PROSES UPDATE DATA (UPDATE DINAMIS)
 *
 * Menerima data dari form edit.php, lalu menyusun query UPDATE
 * secara dinamis. Identitas baris bisa berupa primary key ATAU
 * kombinasi seluruh kolom (untuk table tanpa primary key).
 */
require_once __DIR__ . '/koneksi.php';

$table = isset($_POST['table']) ? $_POST['table'] : '';

/* Validasi table. */
if ($table === '' || !tableExists($table)) {
    hentikan('Table tidak ditemukan.');
}

/* Tentukan identitas baris:
   - Jika form mengirim 'pk' (primary key), pakai itu.
   - Jika form mengirim 'ident[...]' (kombinasi kolom), pakai itu. */
$identParams = [];
if (isset($_POST['pk']) && $_POST['pk'] !== '') {
    $pk = $_POST['pk'];
    $nilaiPk = isset($_POST['pk_value']) ? $_POST['pk_value'] : '';
    if ($nilaiPk === '') {
        hentikan('Primary key tidak valid. Data tidak dapat diubah.');
    }
    $identParams = [$pk => $nilaiPk];
} elseif (isset($_POST['ident']) && is_array($_POST['ident'])) {
    $identParams = $_POST['ident'];
}

/* Bangun WHERE dinamis. Jika table punya PK, WHERE hanya memakai PK. */
$where = buildWhere($table, $identParams);
if ($where === '') {
    hentikan('Identitas baris tidak valid. Data tidak dapat diubah.');
}

$koloms = getColumns($table);

/* Susun pasangan "kolom = nilai" untuk SET. */
$setBagian = [];
foreach ($koloms as $kol) {
    /* Lewati kolom auto-increment (tidak boleh diubah). */
    if ($kol['auto']) {
        continue;
    }
    /* Hanya ubah kolom yang benar-benar dikirim dari form. */
    if (isset($_POST[$kol['nama']])) {
        $setBagian[] = '`' . $conn->real_escape_string($kol['nama']) . "` = '"
                     . bersih($_POST[$kol['nama']]) . "'";
    }
}

/* Jika tidak ada kolom yang diubah, beri peringatan. */
if (empty($setBagian)) {
    hentikan('Tidak ada data yang dikirim untuk diubah.');
}

$sql = "UPDATE `" . $conn->real_escape_string($table) . "` SET "
     . implode(', ', $setBagian)
     . " WHERE {$where}";


/* Jalankan query dan beri umpan balik. */
if ($conn->query($sql)) {
    header('Location: index.php?table=' . urlencode($table));
    exit;
} else {
    if (DB_DEBUG) {
        die('Gagal memperbarui: ' . htmlspecialchars($conn->error));
    }
    hentikan('Gagal memperbarui data. Periksa kembali isian Anda.');
}
