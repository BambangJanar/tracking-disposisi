<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireLogin();
$user = getCurrentUser();
$pageTitle = 'Laporan Kinerja Disposisi';

$tanggalDari = $_GET['tanggal_dari'] ?? date('Y-m-01');
$tanggalSampai = $_GET['tanggal_sampai'] ?? date('Y-m-d');

// Kinerja per user (penerima disposisi)
$query = "SELECT 
            u.id as user_id,
            u.nama_lengkap,
            r.nama_role,
            COUNT(d.id) as total_disposisi,
            SUM(CASE WHEN d.tanggal_respon IS NOT NULL THEN 1 ELSE 0 END) as sudah_respon,
            SUM(CASE WHEN d.tanggal_respon IS NULL AND d.status_disposisi IN ('dikirim','diterima') THEN 1 ELSE 0 END) as belum_respon,
            SUM(CASE WHEN d.status_disposisi = 'selesai' THEN 1 ELSE 0 END) as selesai,
            SUM(CASE WHEN d.status_disposisi = 'ditolak' THEN 1 ELSE 0 END) as ditolak_count,
            ROUND(AVG(CASE WHEN d.tanggal_respon IS NOT NULL THEN TIMESTAMPDIFF(HOUR, d.tanggal_disposisi, d.tanggal_respon) END), 1) as avg_respon_jam
          FROM disposisi d
          JOIN users u ON d.ke_user_id = u.id
          JOIN roles r ON u.id_role = r.id
          WHERE DATE(d.tanggal_disposisi) BETWEEN ? AND ?
          GROUP BY u.id, u.nama_lengkap, r.nama_role
          ORDER BY total_disposisi DESC";

$kinerjalist = dbSelect($query, [$tanggalDari, $tanggalSampai], 'ss');

// Global stats
$totalDisposisi = array_sum(array_column($kinerjalist, 'total_disposisi'));
$totalRespon = array_sum(array_column($kinerjalist, 'sudah_respon'));
$totalBelum = array_sum(array_column($kinerjalist, 'belum_respon'));
$totalSelesai = array_sum(array_column($kinerjalist, 'selesai'));

// Avg response time (global)
$avgGlobal = 0;
$validAvg = array_filter(array_column($kinerjalist, 'avg_respon_jam'), fn($v) => $v !== null);
if (count($validAvg) > 0) $avgGlobal = round(array_sum($validAvg) / count($validAvg), 1);
?>

<?php include '../partials/header.php'; ?>

<div class="flex min-h-screen">
    <?php include '../partials/sidebar.php'; ?>
    
    <div class="flex-1 lg:ml-64">
        <main class="p-4 sm:p-6 lg:p-8">
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-2">Laporan Kinerja Disposisi</h1>
                    <p class="text-sm text-gray-600">Periode: <span class="font-medium"><?= formatTanggal($tanggalDari) ?></span> s/d <span class="font-medium"><?= formatTanggal($tanggalSampai) ?></span></p>
                </div>
                <a href="laporan_kinerja_disposisi_pdf.php?tanggal_dari=<?= $tanggalDari ?>&tanggal_sampai=<?= $tanggalSampai ?>" target="_blank" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg inline-flex items-center justify-center transition-colors text-sm whitespace-nowrap">
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
            
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-3 sm:p-4 border-l-4 border-gray-700">
                    <p class="text-xs text-gray-600 mb-1">Total Disposisi</p>
                    <p class="text-xl font-bold text-gray-800"><?= $totalDisposisi ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-3 sm:p-4 border-l-4 border-green-500">
                    <p class="text-xs text-gray-600 mb-1">Sudah Respon</p>
                    <p class="text-xl font-bold text-green-600"><?= $totalRespon ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-3 sm:p-4 border-l-4 border-yellow-500">
                    <p class="text-xs text-gray-600 mb-1">Belum Respon</p>
                    <p class="text-xl font-bold text-yellow-600"><?= $totalBelum ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-3 sm:p-4 border-l-4 border-blue-500">
                    <p class="text-xs text-gray-600 mb-1">Selesai</p>
                    <p class="text-xl font-bold text-blue-600"><?= $totalSelesai ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-3 sm:p-4 border-l-4 border-purple-500 col-span-2 lg:col-span-1">
                    <p class="text-xs text-gray-600 mb-1">Rata-rata Respon</p>
                    <p class="text-xl font-bold text-purple-600"><?= $avgGlobal ?> jam</p>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="hidden lg:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50"><tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama User</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Respon</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Belum</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Selesai</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ditolak</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Avg Respon</th>
                        </tr></thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($kinerjalist)): ?>
                            <tr><td colspan="9" class="px-6 py-10 text-center text-gray-500"><i class="fas fa-chart-bar text-4xl mb-3 text-gray-300"></i><p>Tidak ada data disposisi</p></td></tr>
                            <?php else: foreach ($kinerjalist as $i => $k): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-4 text-sm"><?= $i + 1 ?></td>
                                <td class="px-4 py-4 text-sm font-medium text-gray-900"><?= htmlspecialchars($k['nama_lengkap']) ?></td>
                                <td class="px-4 py-4 text-sm text-gray-600 capitalize"><?= $k['nama_role'] ?></td>
                                <td class="px-4 py-4 text-sm text-center font-bold"><?= $k['total_disposisi'] ?></td>
                                <td class="px-4 py-4 text-sm text-center text-green-600 font-medium"><?= $k['sudah_respon'] ?></td>
                                <td class="px-4 py-4 text-sm text-center text-yellow-600 font-medium"><?= $k['belum_respon'] ?></td>
                                <td class="px-4 py-4 text-sm text-center text-blue-600 font-medium"><?= $k['selesai'] ?></td>
                                <td class="px-4 py-4 text-sm text-center text-red-600 font-medium"><?= $k['ditolak_count'] ?></td>
                                <td class="px-4 py-4 text-sm text-center">
                                    <?php if ($k['avg_respon_jam'] !== null): ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-medium <?= $k['avg_respon_jam'] <= 24 ? 'bg-green-100 text-green-700' : ($k['avg_respon_jam'] <= 72 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') ?>">
                                            <?= $k['avg_respon_jam'] ?> jam
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="lg:hidden">
                    <?php if (empty($kinerjalist)): ?>
                    <div class="p-8 text-center text-gray-500"><p>Tidak ada data</p></div>
                    <?php else: foreach ($kinerjalist as $i => $k): ?>
                    <div class="border-b border-gray-200 p-4">
                        <div class="font-semibold text-gray-900 text-sm mb-2"><?= htmlspecialchars($k['nama_lengkap']) ?> <span class="text-xs text-gray-500 capitalize">(<?= $k['nama_role'] ?>)</span></div>
                        <div class="grid grid-cols-3 gap-2 text-center text-xs">
                            <div class="bg-gray-50 p-2 rounded"><div class="font-bold text-lg"><?= $k['total_disposisi'] ?></div><div class="text-gray-500">Total</div></div>
                            <div class="bg-green-50 p-2 rounded"><div class="font-bold text-lg text-green-600"><?= $k['sudah_respon'] ?></div><div class="text-gray-500">Respon</div></div>
                            <div class="bg-yellow-50 p-2 rounded"><div class="font-bold text-lg text-yellow-600"><?= $k['belum_respon'] ?></div><div class="text-gray-500">Belum</div></div>
                        </div>
                        <?php if ($k['avg_respon_jam'] !== null): ?>
                        <div class="mt-2 text-xs text-gray-500">Rata-rata respon: <span class="font-medium text-gray-700"><?= $k['avg_respon_jam'] ?> jam</span></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </main>
        <?php include '../partials/footer.php'; ?>
    </div>
</div>
