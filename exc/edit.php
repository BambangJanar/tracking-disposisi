<?php
/**
 * edit.php  -  FORM EDIT DATA (DINAMIS)
 *
 * Membuat form isian otomatis dari kolom table dan mengisi nilai
 * data lama berdasarkan identitas baris yang dikirim via URL.
 * Identitas bisa berupa primary key ATAU kombinasi seluruh kolom
 * (untuk table tanpa primary key).
 */
require_once __DIR__ . '/koneksi.php';

$table = isset($_GET['table']) ? $_GET['table'] : '';
$pk    = getPrimaryKey($table);

/* Validasi table. */
if ($table === '' || !tableExists($table)) {
    hentikan('Table tidak ditemukan. Kembali dan pilih table yang benar.');
}

$koloms = getColumns($table);

/* Tentukan identitas baris:
   - Jika ada primary key, gunakan nilainya dari URL.
   - Jika tidak, decode parameter 'ident' (kombinasi seluruh kolom). */
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

$data = getRowByIdentitas($table, $identParams);

/* Jika data lama tidak ditemukan, tampilkan pesan. */
if (!$data) {
    hentikan('Data tidak ditemukan. Baris mungkin sudah dihapus.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data - <?php echo htmlspecialchars($table); ?></title>
</head>
<body>

    <h3>Edit Data - <?php echo htmlspecialchars($table); ?></h3>

    <form method="post" action="update.php">
        <!-- Simpan nama table untuk diproses update.php -->
        <input type="hidden" name="table" value="<?php echo htmlspecialchars($table); ?>">

        <?php if ($pk): ?>
            <!-- Identitas via primary key -->
            <input type="hidden" name="pk" value="<?php echo htmlspecialchars($pk); ?>">
            <input type="hidden" name="pk_value" value="<?php echo htmlspecialchars($identParams[$pk]); ?>">
        <?php else: ?>
            <!-- Identitas via kombinasi seluruh kolom (table tanpa primary key) -->
            <?php foreach ($identParams as $kolIdent => $nilaiIdent): ?>
                <input type="hidden" name="ident[<?php echo htmlspecialchars($kolIdent); ?>]"
                       value="<?php echo htmlspecialchars($nilaiIdent); ?>">
            <?php endforeach; ?>
        <?php endif; ?>

        <?php foreach ($koloms as $kol): ?>
            <?php
                /* Nilai lama yang akan diisi ke input. */
                $nilaiLama = isset($data[$kol['nama']]) ? $data[$kol['nama']] : '';
                $tipeInput = (strpos($kol['tipe'], 'text') !== false || strpos($kol['tipe'], 'blob') !== false) ? 'textarea' : 'text';
            ?>
            <p>
                <label for="f_<?php echo htmlspecialchars($kol['nama']); ?>">
                    <?php echo htmlspecialchars(label($kol['nama'])); ?>:
                </label><br>
                <?php if ($kol['auto']): ?>
                    <!-- Kolom auto-increment hanya ditampilkan, tidak bisa diubah. -->
                    <input type="text" value="<?php echo htmlspecialchars((string)$nilaiLama); ?>" disabled>
                <?php elseif ($tipeInput === 'textarea'): ?>
                    <textarea id="f_<?php echo htmlspecialchars($kol['nama']); ?>"
                              name="<?php echo htmlspecialchars($kol['nama']); ?>"
                              rows="3" cols="40"><?php echo htmlspecialchars((string)$nilaiLama); ?></textarea>
                <?php else: ?>
                    <input type="text" id="f_<?php echo htmlspecialchars($kol['nama']); ?>"
                           name="<?php echo htmlspecialchars($kol['nama']); ?>"
                           value="<?php echo htmlspecialchars((string)$nilaiLama); ?>">
                <?php endif; ?>
            </p>
        <?php endforeach; ?>

        <p>
            <button type="submit">Simpan Perubahan</button>
            <button type="button" onclick="window.location.href='index.php?table=<?php echo urlencode($table); ?>'">Kembali</button>
        </p>
    </form>

</body>
</html>
