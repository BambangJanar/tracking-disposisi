# Alur Deskripsi — Flowchart Sistem Usulan
## Aplikasi Tracking Disposisi Surat

Berikut alur deskripsi Flowchart Sistem Usulan berdasarkan kode aktual aplikasi `tracking-disposisi` (sesuai diagram `01_flowchart_sistem_usulan.png`).

---

**1. Mulai (Start)**

Proses dimulai ketika pengguna (staf, admin, Kepala Pimpinan, atau Anak Magang) mengakses aplikasi.

**2. Login / Registrasi Akun**

Sistem menampilkan halaman login. Pengguna yang belum memiliki akun dapat melakukan registrasi (`modules/auth/register_handler.php`). Akun baru dibuat dengan role `user` (Anak Magang), berstatus `pending` dan `status_aktif = 0`, sehingga tidak bisa login sampai disetujui Kepala Pimpinan.

**3. Keputusan: Akun Aktif?** (`includes/auth.php:69`)

- **Tidak aktif** → bila pengguna baru mendaftar, sistem menampilkan pesan menunggu persetujuan Kepala Pimpinan, proses berhenti. Bila akun tidak terdaftar/nonaktif, tampil pesan *"Email tidak ditemukan atau akun tidak aktif"*, proses berhenti.
- **Aktif (Ya)** → sistem membuat session (`user_id`, nama, `role`, `id_bagian`) dan mencatat `log_aktivitas` "login", lalu menampilkan **Dashboard** berisi statistik surat masuk/keluar, jumlah disposisi pending, dan notifikasi.

**4. Input Surat Baru**

Pengguna memilih menu Surat → Tambah Surat, mengisi jenis surat, nomor surat, tanggal diterima, dari/ke instansi, perihal, tingkat surat (`biasa/sedang/penting/mendesak`), dan lampiran opsional (`surat_handler.php:27-38`).

**5. Keputusan: Data Wajib Lengkap?** (`surat_handler.php:40-43`)

- **Tidak** → tampil pesan *"Mohon lengkapi data wajib"* (jenis surat, tanggal diterima, perihal), proses berhenti.
- **Ya** → lanjut ke validasi file.

**6. Keputusan: Lampiran Valid?** (`config.php:74-75`)

- **Tidak** → tampil error (format harus `pdf/jpg/jpeg/png`, maks 5 MB), proses berhenti.
- **Ya** → file disimpan ke `uploads/surat/` dengan nama unik (`uniqid()` + timestamp).

**7. Simpan Surat** (`SuratService::create`)

Sistem membuat **nomor agenda otomatis** (`SM/001/01/2025` — prefix sesuai jenis surat), menyimpan data surat dengan status **`baru`**, mencatat stakeholder `pembuat`, mengirim **notifikasi `surat_masuk`** ke semua user kecuali pembuat, dan mencatat `log_aktivitas` "tambah_surat".

**8. Buat Disposisi**

Pengguna membuka detail surat, lalu memilih penerima dan mengisi catatan disposisi.

**9. Keputusan: Validasi Disposisi Lolos?** (`disposisi_handler.php:35-63`)

Validasi berlapis: surat valid, penerima wajib diisi, **tidak boleh disposisi ke diri sendiri**, dan **tidak boleh ada disposisi aktif duplikat** ke penerima yang sama (status selain `selesai`/`ditolak`).

- **Tidak lolos** → tampil pesan error sesuai kasus, proses berhenti.
- **Lolos** → simpan disposisi berstatus **`dikirim`**.

**10. Simpan Disposisi** (`DisposisiService::createDisposisi`)

Sistem menentukan stakeholder penerima: **`penerima_utama`** jika pengirim belum jadi stakeholder, atau **`penerima_delegasi`** jika pengirim adalah stakeholder berperan lain (rantai delegasi). Bila surat masih `baru`, status surat diubah menjadi **`proses`**. Sistem mengirim **notifikasi `disposisi_baru`** ke penerima dan mencatat log "disposisi_surat".

**11. Penerima Membuka Detail Surat** (`surat_detail.php:28`)

Saat penerima membuka surat, sistem melakukan **auto-accept**: status disposisi `dikirim` → `diterima` dan `tanggal_respon` diisi otomatis.

**12. Penerima Memperbarui Status Disposisi**

Penerima mengubah status menjadi `diproses`, `selesai` (UI: "disetujui"), atau `ditolak`, plus catatan respon.

**13. Keputusan: Transisi Status Valid?** (`disposisi_handler.php:137-155`)

Aturan transisi: `dikirim → diterima`, `diterima → diproses/selesai/ditolak`, `diproses → selesai/ditolak`. **Kepala Pimpinan (headadmin) dapat bypass** alur ini. Hanya penerima (atau Kepala Pimpinan) yang boleh mengubah status.

- **Tidak valid** → tampil pesan *"status tidak valid"*, proses berhenti.
- **Valid** → simpan status + catatan + `tanggal_respon`, kirim **notifikasi `surat_update`** ke pengirim, catat log "update_disposisi".

**14. Keputusan: Status = Selesai atau Ditolak?**

- **Belum** → disposisi berlanjut pada status baru (misal `diproses`), alur kembali ke proses kerja penerima.
- **Ya** → sistem mengecek apakah **semua disposisi surat sudah tuntas**.

**15. Keputusan: Semua Disposisi Tuntas?** (`DisposisiService::checkAllDisposisiCompleted`)

- **Belum** → status surat tetap `proses`, menunggu disposisi lain selesai.
- **Ya** → status surat menjadi **`disetujui`** (bila semua `selesai`) atau **`ditolak`** + `alasan_penolakan` (bila ada yang ditolak). Sistem lalu **membersihkan notifikasi & menonaktifkan stakeholder** (`clearBySurat` + `deactivateStakeholders`) dan mengirim **notifikasi `surat_selesai`** ke seluruh stakeholder.

**16. Kelola Arsip Surat**

Surat berstatus terminal dapat diarsipkan (**status sebelum arsip disimpan** di `status_sebelum_arsip`), di-unarsip (status dikembalikan), atau dihapus permanen (beserta notifikasi/stakeholder terkait) (`surat_handler.php:119-181`).

**17. Lihat Laporan**

Pengguna memilih jenis laporan (rekap surat, disposisi, kinerja disposisi, aktivitas, log download, statistik pengguna) dengan filter `tanggal_dari`–`tanggal_sampai`.

**18. Keputusan: Cetak PDF?**

- **Tidak** → kembali ke menu.
- **Ya** → sistem generate PDF via **Dompdf** dengan logo, identitas instansi, dan TTD dari tabel `settings`, plus watermark, lalu ditampilkan inline untuk diunduh/dicetak.

**19. Logout** (`auth.php:99-106`)

Sistem mencatat `log_aktivitas` "logout", menghapus session, dan mengarahkan ke halaman login.

**20. Selesai (Stop)**

Proses berakhir.

---

## Catatan Tambahan untuk Dokumentasi

- **Role yang terlibat**: `superadmin` (Kepala Bagian), `admin` (Karyawan), `user` (Anak Magang — akses terbatas), `headadmin` (Kepala Pimpinan Divisi — akses penuh + bypass).
- **Status surat**: `baru → proses → disetujui | ditolak → arsip` (arsip dapat dikembalikan).
- **Status disposisi**: `dikirim → diterima → diproses → selesai | ditolak`.
