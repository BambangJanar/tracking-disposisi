<?php
/**
 * tambah.php  -  FORM TAMBAH DATA (DINAMIS)
 *
 * Membuat form isian otomatis dari kolom table yang dipilih.
 * Kolom auto-increment otomatis dilewati (dibiarkan diisi database).
 * Tidak ada nama kolom yang ditulis kaku di kode.
 */
require_once __DIR__ . '/koneksi.php';

$table = isset($_GET['table']) ? $_GET['table'] : '';

/* Validasi: table harus ada. */
if ($table === '' || !tableExists($table)) {
    hentikan('Table tidak ditemukan. Kembali dan pilih table yang benar.');
}

$koloms = getColumns($table);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Data - <?php echo htmlspecialchars($table); ?></title>
</head>
<body>

    <h3>Tambah Data - <?php echo htmlspecialchars($table); ?></h3>

    <form method="post" action="simpan.php">
        <!-- Simpan nama table untuk diproses simpan.php -->
        <input type="hidden" name="table" value="<?php echo htmlspecialchars($table); ?>">

        <?php foreach ($koloms as $kol): ?>
            <?php
                /* Lewati kolom auto-increment (dibiarkan diisi otomatis). */
                if ($kol['auto']) {
                    continue;
                }
                /* Tentukan tipe input sederhana: textarea untuk teks panjang, text untuk lainnya. */
                $tipeInput = (strpos($kol['tipe'], 'text') !== false || strpos($kol['tipe'], 'blob') !== false) ? 'textarea' : 'text';
            ?>
            <p>
                <label for="f_<?php echo htmlspecialchars($kol['nama']); ?>">
                    <?php echo htmlspecialchars(label($kol['nama'])); ?>:
                </label><br>
                <?php if ($tipeInput === 'textarea'): ?>
                    <textarea id="f_<?php echo htmlspecialchars($kol['nama']); ?>"
                              name="<?php echo htmlspecialchars($kol['nama']); ?>"
                              rows="3" cols="40"></textarea>
                <?php else: ?>
                    <input type="text" id="f_<?php echo htmlspecialchars($kol['nama']); ?>"
                           name="<?php echo htmlspecialchars($kol['nama']); ?>">
                <?php endif; ?>
            </p>
        <?php endforeach; ?>

        <p>
            <button type="submit">Simpan Data</button>
            <button type="button" onclick="window.location.href='index.php?table=<?php echo urlencode($table); ?>'">Kembali</button>
        </p>
    </form>

</body>
</html>
