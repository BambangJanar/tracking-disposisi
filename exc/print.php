<?php
/**
 * print.php  -  CETAK / PRINT DATA TABLE (DINAMIS)
 *
 * Menampilkan data sebuah table dalam bentuk tabel HTML, lalu secara
 * otomatis memunculkan dialog cetak (Ctrl + P) saat halaman dimuat.
 * Kolom dibaca otomatis dari database, tidak perlu ditulis manual.
 *
 * Dipanggil dari index.php lewat: print.php?table=nama_table
 */
require_once __DIR__ . '/koneksi.php';

$table = isset($_GET['table']) ? $_GET['table'] : '';

if ($table === '' || !tableExists($table)) {
    hentikan('Table tidak ditemukan.');
}

$koloms = getColumns($table);
$pk     = getPrimaryKey($table);
$rows   = ($pk) ? getRows($table, $pk) : getRows($table);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak - <?php echo htmlspecialchars($table); ?></title>
</head>
<body>

    <h2>Data Table: <?php echo htmlspecialchars($table); ?></h2>

    <?php if (empty($rows)): ?>

        <p>Belum ada data pada table ini.</p>

    <?php else: ?>

        <table border="1" cellpadding="6" cellspacing="0" style="width:100%; border-collapse:collapse;">
            <thead>
                <tr>
                    <th>No</th>
                    <?php foreach ($koloms as $kol): ?>
                        <th><?php echo htmlspecialchars(label($kol['nama'])); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; ?>
                <?php foreach ($rows as $baris): ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <?php foreach ($koloms as $kol): ?>
                            <td><?php echo htmlspecialchars((string)$baris[$kol['nama']]); ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php endif; ?>

    <!-- Tombol cetak & tutup. Tombol ini otomatis disembunyikan saat dialog print. -->
    <p id="tombol_cetak">
        <button type="button" onclick="window.print();">Cetak / Print</button>
        <button type="button" onclick="window.close();">Tutup</button>
    </p>

    <script>
    /* Munculkan dialog cetak (Ctrl + P) otomatis saat halaman selesai dimuat. */
    window.onload = function () {
        window.print();
    };
    </script>

</body>
</html>
