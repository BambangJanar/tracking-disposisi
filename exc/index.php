<?php

/**
 * index.php  -  CRUD DINAMIS (DAFTAR TABLE DITENTUKAN MANUAL)
 *
 * Halaman utama CRUD. Nama-nama table yang ingin ditampilkan ditulis
 * manual di array $tables di bawah. Anda tinggal menambah/mengganti
 * nama table di sana — program otomatis membaca struktur (kolom &
 * primary key) langsung dari database, jadi tidak perlu mengubah kode lain.
 *
 * Kolom form tambah/edit, tabel data, dan aksi Edit/Hapus/Print semuanya
 * dinamis mengikuti struktur table yang ada di database.
 */
require_once __DIR__ . '/koneksi.php';

/* ------------------------------------------------------------------
 * DAFTAR TABLE YANG DITAMPILKAN (TULIS MANUAL)
 * Tambahkan/ganti nama table di dalam array ini sesuai kebutuhan.
 * Contoh: $tables = ['surat', 'jenis_surat', 'bagian'];
 * ------------------------------------------------------------------ */
$tables = [
    'anggota', /* <- Ganti dengan nama tabel Anda */
];

/* Table yang sedang dipilih (dari URL), default table pertama. */
$table = isset($_GET['table']) ? $_GET['table'] : '';

/* Jika table tidak dikirim atau tidak termasuk daftar manual, gunakan yang pertama. */
if ($table === '' || !in_array($table, $tables, true)) {
    $table = !empty($tables) ? $tables[0] : '';
}

/* Pastikan table benar-benar ada di database. */
if ($table !== '' && !tableExists($table)) {
    $table = '';
}

/* Data table terpilih. */
$koloms = ($table !== '') ? getColumns($table) : [];
$pk     = ($table !== '') ? getPrimaryKey($table) : null;
$rows   = ($table !== '') ? getRows($table, $pk) : [];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD Dinamis</title>
</head>

<body>

    <?php if ($table === ''): ?>

        <!-- Tidak ada table yang bisa ditampilkan -->
        <p>Belum ada table yang didefinisikan. Tambahkan nama table pada array <b>$tables</b> di file <b>index.php</b>.</p>

    <?php else: ?>

        <!-- Tombol aksi utama -->
        <p id="tombol_aksi">
            <a href="tambah.php?table=<?php echo urlencode($table); ?>">
                <button type="button">Tambah Data</button>
            </a>
            <button type="button" onclick="window.open('print.php?table=<?php echo urlencode($table); ?>', '_blank');">Cetak / Print</button>
        </p>

        <?php if (empty($rows)): ?>

            <p>Belum ada data pada table ini.</p>

        <?php else: ?>

            <table border="1" cellpadding="6" cellspacing="0">
                <thead>
                    <tr>
                        <?php foreach ($koloms as $kol): ?>
                            <th><?php echo htmlspecialchars(label($kol['nama'])); ?></th>
                        <?php endforeach; ?>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $baris): ?>
                        <tr>
                            <?php foreach ($koloms as $kol): ?>
                                <td><?php echo htmlspecialchars((string)$baris[$kol['nama']]); ?></td>
                            <?php endforeach; ?>
                            <td>
                                <?php
                                /* Bangun identitas baris: primary key jika ada,
                                       atau kombinasi seluruh kolom jika tidak ada. */
                                if ($pk) {
                                    $urlEdit = 'edit.php?table=' . urlencode($table)
                                        . '&' . urlencode($pk) . '=' . urlencode($baris[$pk]);
                                    $urlHapus = 'hapus.php?table=' . urlencode($table)
                                        . '&' . urlencode($pk) . '=' . urlencode($baris[$pk]);
                                } else {
                                    $ident = encodeIdent($baris);
                                    $urlEdit = 'edit.php?table=' . urlencode($table)
                                        . '&ident=' . urlencode($ident);
                                    $urlHapus = 'hapus.php?table=' . urlencode($table)
                                        . '&ident=' . urlencode($ident);
                                }
                                ?>
                                <a href="<?php echo $urlEdit; ?>">
                                    <button type="button">Edit</button>
                                </a>
                                <a href="<?php echo $urlHapus; ?>"
                                    onclick="return confirm('Yakin ingin menghapus baris ini?');">
                                    <button type="button">Hapus</button>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php endif; ?>

    <?php endif; ?>

</body>

</html>