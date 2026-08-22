## Rencana: Primary Key Dinamis (bisa ada / tanpa primary key)

### Masalah
Table `paramex` (dan table lain) tidak punya primary key, sehingga kolom Aksi di index.php menampilkan "Tidak ada primary key" dan Edit/Hapus tidak tersedia.

### Solusi: Identitas Baris Dinamis
Program otomatis menentukan identitas unik baris:
- **Table ber-PK** → memakai primary key (tetap akurat seperti sekarang).
- **Table tanpa PK** → memakai kombinasi **seluruh kolom** sebagai identitas, di-encode jadi satu string aman-URL (base64) yang dikirim lewat parameter `ident`.

Dengan ini, tombol Edit & Hapus **selalu muncul** untuk semua table, dengan PK atau tanpa PK.

### Perubahan per file

**1. `koneksi.php` — tambah fungsi bantu baru**
- `buildWhere($koloms, $data, $kecuali = [])` → bangun klausa `WHERE kolom = 'nilai' AND ...` dari kombinasi kolom (dipakai saat tanpa PK).
- `getRowByIdentitas($table, $identParams)` → ambil 1 baris berdasarkan identitas (PK **atau** kombinasi kolom).
- `encodeIdent($data)` → gabung `kolom=nilai` tiap kolom lalu `base64_encode`, agar aman di URL (nilai boleh mengandung `&`, `=`, spasi, dsb).
- `decodeIdent($string)` → kebalikan encode, kembalikan array key-value.

**2. `index.php`**
- Untuk tiap baris, bangun identitas: jika ada PK → `[pk]=nilai`; jika tidak → `ident=ENCODED`.
- Hapus teks "Tidak ada primary key"; tombol Edit & Hapus selalu tampil.
- Link jadi `edit.php?table=X&ident=...` dan `hapus.php?table=X&ident=...` (untuk tanpa PK), atau `edit.php?table=X&id=3` (untuk ber-PK).

**3. `edit.php`**
- Terima `ident` (string) ATAU primary key. Tentukan identitas dinamis, muat baris via `getRowByIdentitas`, isi form.
- Kirim identitas via hidden fields ke `update.php` (kolom + nilai lama setiap kolom identitas).

**4. `hapus.php`**
- Terima `ident`, decode, bangun DELETE dengan WHERE dinamis (`buildWhere`).

**5. `update.php`**
- Terima identitas (kolom + nilai lama) dari POST, bangun UPDATE dengan WHERE dinamis.

### Batasan yang perlu Anda ketahui
Untuk table **tanpa primary key**, identitas baris = seluruh nilai kolom saat itu. Jika ada **dua baris yang isinya sama persis** (duplikat penuh), Edit/Hapus akan memengaruhi keduanya — ini keterbatasan bawaan database tanpa PK, tidak ada cara membedakan baris identik. Untuk table **ber-PK**, perilaku tetap akurat 100%.

### Pengujian
Saya akan membuat table uji tanpa PK (dengan data berisi karakter khusus) dan table ber-PK, lalu menguji: tampil di index, Edit, Hapus, dan Update untuk kedua tipe. Setelah itu menghapus table uji.