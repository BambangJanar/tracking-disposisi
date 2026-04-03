<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireLogin();
$user = getCurrentUser();
$pageTitle = 'Laporan Per Bagian';

$tanggalDari = $_GET['tanggal_dari'] ?? date('Y-m-01');
$tanggalSampai = $_GET['tanggal_sampai'] ?? date('Y-m-d');

// Statistik per bagian
$query = "SELECT 
            b.id as bagian_id,
            b.nama_bagian,
            COUNT(DISTINCT CASE WHEN s.id_jenis = 1 THEN s.id END) as surat_masuk,
            COUNT(DISTINCT CASE WHEN s.id_jenis = 2 THEN s.id END) as surat_keluar,
            COUNT(DISTINCT s.id) as total_surat,
            (SELECT COUNT(*) FROM disposisi d2 
             JOIN users u2 ON d2.ke_user_id = u2.id 
             WHERE u2.id_bagian = b.id 
             AND DATE(d2.tanggal_disposisi) BETWEEN ? AND ?) as disposisi_masuk,
            (SELECT COUNT(*) FROM disposisi d3 
             JOIN users u3 ON d3.dari_user_id = u3.id 
             WHERE u3.id_bagian = b.id 
             AND DATE(d3.tanggal_disposisi) BETWEEN ? AND ?) as disposisi_keluar
          FROM bagian b
          LEFT JOIN users u ON u.id_bagian = b.id
          LEFT JOIN surat s ON s.dibuat_oleh = u.id AND DATE(s.tanggal_diterima) BETWEEN ? AND ?
          GROUP BY b.id, b.nama_bagian
          ORDER BY total_surat DESC";

$bagianList = dbSelect($query, [$tanggalDari, $tanggalSampai, $tanggalDari, $tanggalSampai, $tanggalDari, $tanggalSampai], 'ssssss');

// Global stats
$totalSuratAll = array_sum(array_column($bagianList, 'total_surat'));
$totalDispMasuk = array_sum(array_column($bagianList, 'disposisi_masuk'));
$totalDispKeluar = array_sum(array_column($bagianList, 'disposisi_keluar'));
$totalBagian = count($bagianList);
?>

<?php include '../partials/header.php'; ?>

<div class="flex min-h-screen">
    <?php include '../partials/sidebar.php'; ?>
    
    <div class="flex-1 lg:ml-64">
        <main class="p-4 sm:p-6 lg:p-8">
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-2">Laporan Per Bagian</h1>
                    <p class="text-sm text-gray-600">Periode: <span class="font-medium"><?= formatTanggal($tanggalDari) ?></span> s/d <span class="font-medium"><?= formatTanggal($tanggalSampai) ?></span></p>
                </div>
                <a href="laporan_per_bagian_pdf.php?tanggal_dari=<?= $tanggalDari ?>&tanggal_sampai=<?= $tanggalSampai ?>" target="_blank" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg inline-flex items-center justify-center transition-colors text-sm whitespace-nowrap">
                    <i class="fas fa-file-pdf mr-2"></i>Cetak PDF
                </a>
            </div>
            
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <form method="GET">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div><label class="block text-xs font-medium text-gray-700 mb-1">Dari Tanggal</label><input type="date" name="tanggal_dari" value="<?= $tanggalDari ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500"></div>
                        <div><label class="block text-xs font-medium text-gray-700 mb-1">Sampai Tanggal</label><input type="date" name="tanggal_sampai" value="<?= $tanggalSampai ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500"></div>
                        <div class="sm:col-span-2 lg:col-span-1"><label class="block text-xs font-medium text-gray-700 mb-1">&nbsp;</label><button type="submit" class="w-full bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-filter mr-2"></i>Filter</button></div>
                    </div>
                </form>
            </div>
            
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-3 sm:p-4 border-l-4 border-indigo-600">
                    <p class="text-xs sm:text-sm text-gray-600 mb-1">Jumlah Bagian</p>
                    <p class="text-xl sm:text-2xl font-bold text-indigo-600"><?= $totalBagian ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-3 sm:p-4 border-l-4 border-blue-500">
                    <p class="text-xs sm:text-sm text-gray-600 mb-1">Total Surat</p>
                    <p class="text-xl sm:text-2xl font-bold text-blue-600"><?= $totalSuratAll ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-3 sm:p-4 border-l-4 border-green-500">
                    <p class="text-xs sm:text-sm text-gray-600 mb-1">Disposisi Masuk</p>
                    <p class="text-xl sm:text-2xl font-bold text-green-600"><?= $totalDispMasuk ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-3 sm:p-4 border-l-4 border-orange-500">
                    <p class="text-xs sm:text-sm text-gray-600 mb-1">Disposisi Keluar</p>
                    <p class="text-xl sm:text-2xl font-bold text-orange-600"><?= $totalDispKeluar ?></p>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="hidden lg:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50"><tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Bagian</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Surat Masuk</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Surat Keluar</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Total Surat</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Disp. Masuk</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Disp. Keluar</th>
                        </tr></thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($bagianList)): ?>
                            <tr><td colspan="7" class="px-6 py-10 text-center text-gray-500"><i class="fas fa-building text-4xl mb-3 text-gray-300"></i><p>Tidak ada data bagian</p></td></tr>
                            <?php else: foreach ($bagianList as $i => $b): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm"><?= $i + 1 ?></td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900"><?= htmlspecialchars($b['nama_bagian']) ?></td>
                                <td class="px-6 py-4 text-sm text-center font-medium text-blue-600"><?= $b['surat_masuk'] ?></td>
                                <td class="px-6 py-4 text-sm text-center font-medium text-orange-600"><?= $b['surat_keluar'] ?></td>
                                <td class="px-6 py-4 text-sm text-center font-bold"><?= $b['total_surat'] ?></td>
                                <td class="px-6 py-4 text-sm text-center font-medium text-green-600"><?= $b['disposisi_masuk'] ?></td>
                                <td class="px-6 py-4 text-sm text-center font-medium text-purple-600"><?= $b['disposisi_keluar'] ?></td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="lg:hidden">
                    <?php if (empty($bagianList)): ?>
                    <div class="p-8 text-center text-gray-500"><p>Tidak ada data</p></div>
                    <?php else: foreach ($bagianList as $i => $b): ?>
                    <div class="border-b border-gray-200 p-4">
                        <div class="font-semibold text-gray-900 text-sm mb-3"><?= htmlspecialchars($b['nama_bagian']) ?></div>
                        <div class="grid grid-cols-2 gap-2 text-center text-xs">
                            <div class="bg-blue-50 p-2 rounded"><div class="font-bold text-lg text-blue-600"><?= $b['surat_masuk'] ?></div><div class="text-gray-500">Surat Masuk</div></div>
                            <div class="bg-orange-50 p-2 rounded"><div class="font-bold text-lg text-orange-600"><?= $b['surat_keluar'] ?></div><div class="text-gray-500">Surat Keluar</div></div>
                            <div class="bg-green-50 p-2 rounded"><div class="font-bold text-lg text-green-600"><?= $b['disposisi_masuk'] ?></div><div class="text-gray-500">Disp. Masuk</div></div>
                            <div class="bg-purple-50 p-2 rounded"><div class="font-bold text-lg text-purple-600"><?= $b['disposisi_keluar'] ?></div><div class="text-gray-500">Disp. Keluar</div></div>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </main>
        <?php include '../partials/footer.php'; ?>
    </div>
</div>
