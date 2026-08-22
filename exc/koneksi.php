<?php

/**
 * ============================================================
 *  koneksi.php  -  INTI SEMUA FILE
 * ============================================================
 *  Tempat mengatur konfigurasi database dan pustaka fungsi
 *  dinamis yang dipakai seluruh file lain (index, tambah,
 *  edit, simpan, update, hapus, print).
 *
 *  ATURAN DINAMIS (anti-error):
 *  1. Ganti nama database  -> cukup ubah konstanta DB di bawah.
 *  2. Ganti nama folder    -> tidak berpengaruh (hanya path relatif).
 *  3. Ganti nama table     -> tidak perlu ubah kode, otomatis terdeteksi.
 *  4. Ganti nama kolom     -> tidak perlu ubah kode, otomatis terdeteksi.
 *  5. Tambah table/kolom   -> tidak perlu ubah kode.
 * ============================================================
 */

/* ------------------------------------------------------------------
 * 1. KONFIGURASI DATABASE
 *    Ubah 4 nilai di bawah ini sesuai kondisi di lapangan.
 *    Cukup satu tempat, file lain tidak perlu diubah.
 * ------------------------------------------------------------------ */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'perpustakaan_uji_sidang'); /* <- Ganti dengan nama database Anda */

/* Aktifkan true saat ingin melihat pesan error teknis (untuk debugging). */
define('DB_DEBUG', false);

/* ------------------------------------------------------------------
 * 2. KONEKSI KE DATABASE
 *    Menggunakan mysqli dengan penanganan error yang jelas.
 *    Jika database tidak ditemukan, tampilkan pesan ramah,
 *    bukan error fatal mentah.
 * ------------------------------------------------------------------ */
/* Matikan mode eksepsi mysqli (aktif default di PHP 8.1+).
   Ini penting agar koneksi yang gagal mengembalikan $conn->connect_error
   alih-alih melempar exception yang membuat error fatal. */
mysqli_report(MYSQLI_REPORT_OFF);

$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

/* Periksa apakah koneksi berhasil. */
if ($conn->connect_error) {
    $kode = $conn->connect_errno;

    /* Kode 1049 = database tidak ditemukan. */
    if ($kode === 1049) {
        $pesan = 'Database tidak ditemukan. Periksa nilai konstanta DB_NAME di file <b>koneksi.php</b>.';
    }
    /* Kode 1045 = username/password salah. */ elseif ($kode === 1045) {
        $pesan = 'Username atau password database salah. Periksa DB_USER dan DB_PASS di file <b>koneksi.php</b>.';
    }
    /* Kode 2002 = server tidak ditemukan / tidak berjalan. */ elseif ($kode === 2002) {
        $pesan = 'Server database tidak dapat dijangkau. Pastikan MySQL/Laragon sedang berjalan dan periksa DB_HOST.';
    }
    /* Pesan umum untuk kasus lain. */ else {
        $pesan = 'Tidak dapat terhubung ke database. Periksa konfigurasi di file <b>koneksi.php</b>.';
    }

    /* Jika mode debug aktif, tampilkan detail teknis. */
    if (DB_DEBUG) {
        $pesan .= '<br><small>Detail teknis: ' . htmlspecialchars($conn->connect_error) . '</small>';
    }

    /* Hentikan program dengan pesan yang mudah dipahami. */
    die('Koneksi gagal: ' . $pesan);
}

/* Set karakter UTF-8 agar huruf/karakter khusus terbaca dengan benar. */
$conn->set_charset('utf8mb4');

/* ------------------------------------------------------------------
 * 3. FUNGSI BANTU DINAMIS
 *    Semua fungsi di bawah membaca struktur langsung dari database.
 *    Tidak ada nama table/kolom yang ditulis kaku di kode.
 * ------------------------------------------------------------------ */

/**
 * Ambil daftar semua table yang ada di database.
 * @return array  Daftar nama table, atau array kosong jika tidak ada.
 */
function getTables()
{
    global $conn;
    $hasil = [];
    $query = $conn->query("SHOW TABLES");
    if ($query && $query->num_rows > 0) {
        while ($baris = $query->fetch_row()) {
            $hasil[] = $baris[0];
        }
    }
    return $hasil;
}

/**
 * Ambil daftar kolom sebuah table beserta informasinya.
 * @param string $table  Nama table.
 * @return array  Daftar kolom, tiap elemen berisi nama, tipe, auto-increment.
 * @return false  Jika table tidak ditemukan.
 */
function getColumns($table)
{
    global $conn;
    if (!tableExists($table)) {
        return false;
    }
    $hasil = [];
    $query = $conn->query("SHOW COLUMNS FROM `" . $conn->real_escape_string($table) . "`");
    if ($query) {
        while ($baris = $query->fetch_assoc()) {
            $hasil[] = [
                'nama'    => $baris['Field'],
                'tipe'    => $baris['Type'],
                'auto'    => ($baris['Extra'] === 'auto_increment'),
                'primary' => ($baris['Key'] === 'PRI'),
                'boleh_null' => ($baris['Null'] === 'YES'),
            ];
        }
    }
    return $hasil;
}

/**
 * Periksa apakah sebuah table benar-benar ada di database.
 * @param string $table  Nama table.
 * @return bool
 */
function tableExists($table)
{
    global $conn;
    $query = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($table) . "'");
    return ($query && $query->num_rows > 0);
}

/**
 * Deteksi otomatis nama PRIMARY KEY sebuah table.
 * Dinamis: tidak peduli kolomnya bernama id, kode, nomor, dll.
 * @param string $table  Nama table.
 * @return string|null   Nama kolom primary key, atau null jika tidak ada.
 */
function getPrimaryKey($table)
{
    global $conn;
    $query = $conn->query("SHOW KEYS FROM `" . $conn->real_escape_string($table) . "` WHERE Key_name = 'PRIMARY'");
    if ($query && $query->num_rows > 0) {
        $baris = $query->fetch_assoc();
        return $baris['Column_name'];
    }
    return null;
}

/**
 * Ambil semua baris data dari sebuah table.
 * @param string $table  Nama table.
 * @param string|null $urutan  Nama kolom untuk pengurutan (opsional).
 * @return array  Data table (array of associative array), atau kosong.
 */
function getRows($table, $urutan = null)
{
    global $conn;
    if (!tableExists($table)) {
        return [];
    }
    $table_aman = $conn->real_escape_string($table);
    $sql = "SELECT * FROM `{$table_aman}`";

    /* Jika ada kolom urutan dan benar-benar ada di table, gunakan untuk mengurutkan. */
    if ($urutan && columnExists($table, $urutan)) {
        $sql .= " ORDER BY `" . $conn->real_escape_string($urutan) . "` ASC";
    }

    $hasil = [];
    $query = $conn->query($sql);
    if ($query) {
        while ($baris = $query->fetch_assoc()) {
            $hasil[] = $baris;
        }
    }
    return $hasil;
}

/**
 * Ambil satu baris data berdasarkan primary key.
 * @param string $table  Nama table.
 * @param mixed $nilaiPk  Nilai primary key.
 * @return array|null     Data baris, atau null jika tidak ditemukan.
 */
function getRow($table, $nilaiPk)
{
    global $conn;
    $pk = getPrimaryKey($table);
    if (!$pk) {
        return null;
    }
    $table_aman = $conn->real_escape_string($table);
    $sql = "SELECT * FROM `{$table_aman}` WHERE `" . $conn->real_escape_string($pk) . "` = '"
        . $conn->real_escape_string($nilaiPk) . "' LIMIT 1";
    $query = $conn->query($sql);
    if ($query && $query->num_rows > 0) {
        return $query->fetch_assoc();
    }
    return null;
}

/**
 * Ambil satu baris berdasarkan identitas dinamis.
 * Identitas bisa berupa primary key ATAU kombinasi seluruh kolom
 * (untuk table yang tidak memiliki primary key).
 *
 * @param string $table       Nama table.
 * @param array  $identParams Asosiatif kolom => nilai (identitas baris).
 * @return array|null         Data baris, atau null jika tidak ditemukan.
 */
function getRowByIdentitas($table, $identParams)
{
    global $conn;
    if (!tableExists($table) || empty($identParams)) {
        return null;
    }
    $table_aman = $conn->real_escape_string($table);
    $where = buildWhere($table, $identParams);
    if ($where === '') {
        return null;
    }
    $sql = "SELECT * FROM `{$table_aman}` WHERE {$where} LIMIT 1";
    $query = $conn->query($sql);
    if ($query && $query->num_rows > 0) {
        return $query->fetch_assoc();
    }
    return null;
}

/**
 * Bangun klausa WHERE dari kombinasi kolom (dipakai untuk identitas
 * baris pada table tanpa primary key).
 * Hanya menyertakan kolom yang benar-benar ada di table.
 *
 * @param string $table   Nama table.
 * @param array  $data    Asosiatif kolom => nilai.
 * @param array  $kecuali Nama kolom yang dikecualikan (opsional).
 * @return string         Klausa WHERE, atau string kosong bila tidak valid.
 */
function buildWhere($table, $data, $kecuali = [])
{
    global $conn;
    $bagian = [];
    $kolomTable = getColumns($table);
    if (!$kolomTable) {
        return '';
    }
    /* Kumpulkan nama kolom yang valid. */
    $daftarNama = array_column($kolomTable, 'nama');

    foreach ($data as $kolom => $nilai) {
        /* Abaikan kolom yang tidak ada di table atau yang dikecualikan. */
        if (!in_array($kolom, $daftarNama, true)) {
            continue;
        }
        if (in_array($kolom, $kecuali, true)) {
            continue;
        }
        $bagian[] = '`' . $conn->real_escape_string($kolom) . "` = '"
            . $conn->real_escape_string((string)$nilai) . "'";
    }
    return implode(' AND ', $bagian);
}

/**
 * Encode identitas baris (kombinasi kolom => nilai) menjadi satu
 * string aman untuk dipakai di URL. Nilai boleh mengandung karakter
 * khusus seperti &, =, spasi, kutip, dsb.
 *
 * @param array $data  Asosiatif kolom => nilai.
 * @return string      String ter-encode (base64).
 */
function encodeIdent($data)
{
    return base64_encode(http_build_query($data));
}

/**
 * Decode string identitas hasil encodeIdent() kembali menjadi array.
 * @param string $string  String ter-encode.
 * @return array          Asosiatif kolom => nilai, atau kosong jika gagal.
 */
function decodeIdent($string)
{
    $hasil = [];
    if ($string === '' || $string === null) {
        return $hasil;
    }
    $decoded = base64_decode($string, true);
    if ($decoded === false) {
        return $hasil;
    }
    parse_str($decoded, $hasil);
    return $hasil;
}

/**
 * Periksa apakah sebuah kolom ada di dalam sebuah table.
 * @param string $table   Nama table.
 * @param string $kolom   Nama kolom.
 * @return bool
 */
function columnExists($table, $kolom)
{
    $kolomTable = getColumns($table);
    if (!$kolomTable) {
        return false;
    }
    foreach ($kolomTable as $k) {
        if ($k['nama'] === $kolom) {
            return true;
        }
    }
    return false;
}

/**
 * Bersihkan nilai sebelum dipakai di query (anti-injection).
 * @param mixed $nilai  Nilai mentah dari user.
 * @return mixed        Nilai yang sudah dibersihkan.
 */
function bersih($nilai)
{
    global $conn;
    if (is_array($nilai)) {
        foreach ($nilai as $k => $v) {
            $nilai[$k] = bersih($v);
        }
        return $nilai;
    }
    return $conn->real_escape_string(trim((string)$nilai));
}

/**
 * Ambil nilai dari array (GET atau POST) dengan aman.
 * Tidak akan error walau kunci tidak ada.
 * @param array  $sumber  $_GET atau $_POST.
 * @param string $kunci   Nama parameter.
 * @param mixed  $default Nilai pengganti jika tidak ada.
 * @return mixed
 */
function ambil($sumber, $kunci, $default = '')
{
    return isset($sumber[$kunci]) ? $sumber[$kunci] : $default;
}

/**
 * Tampilkan halaman peringatan sederhana lalu hentikan program.
 * Dipakai bila table/parameter tidak ditemukan.
 * @param string $pesan  Pesan yang ditampilkan.
 */
function hentikan($pesan)
{
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Perhatian</title></head><body>';
    echo '<h3>' . htmlspecialchars($pesan) . '</h3>';
    echo '<p><a href="index.php">Kembali ke daftar table</a></p>';
    echo '</body></html>';
    exit;
}

/**
 * Buat nama yang aman untuk ditampilkan (mengganti underscore dengan spasi).
 * @param string $nama  Nama asli (mis. "nama_buku").
 * @return string       Label tampilan (mis. "Nama Buku").
 */
function label($nama)
{
    return ucwords(str_replace('_', ' ', $nama));
}
