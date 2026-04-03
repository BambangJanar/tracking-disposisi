<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireLogin();
$user = getCurrentUser();
$pageTitle = 'Laporan Statistik Pengguna';

$tanggalDari = $_GET['tanggal_dari'] ?? date('Y-m-01');
$tanggalSampai = $_GET['tanggal_sampai'] ?? date('Y-m-d');

// Statistik per user
$query = "SELECT 
            u.id as user_id,
            u.nama_lengkap,
            r.nama_role,
            COALESCE(b.nama_bagian, u.nama_bagian_custom, '-') as nama_bagian,
            (SELECT COUNT(*) FROM surat s WHERE s.dibuat_oleh = u.id AND DATE(s.created_at) BETWEEN ? AND ?) as surat_dibuat,
            (SELECT COUNT(*) FROM disposisi d1 WHERE d1.dari_user_id = u.id AND DATE(d1.tanggal_disposisi) BETWEEN ? AND ?) as disposisi_dikirim,
            (SELECT COUNT(*) FROM disposisi d2 WHERE d2.ke_user_id = u.id AND DATE(d2.tanggal_disposisi) BETWEEN ? AND ?) as disposisi_diterima,
            (SELECT COUNT(*) FROM log_aktivitas la WHERE la.user_id = u.id AND la.aktivitas = 'login' AND DATE(la.created_at) BETWEEN ? AND ?) as total_login,
            (SELECT MAX(la2.created_at) FROM log_aktivitas la2 WHERE la2.user_id = u.id AND la2.aktivitas = 'login') as last_login
          FROM users u
          JOIN roles r ON u.id_role = r.id
          LEFT JOIN bagian b ON u.id_bagian = b.id
          WHERE u.status_aktif = 1
          ORDER BY surat_dibuat DESC, disposisi_dikirim DESC";

$userList = dbSelect($query, [$tanggalDari, $tanggalSampai, $tanggalDari, $tanggalSampai, $tanggalDari, $tanggalSampai, $tanggalDari, $tanggalSampai], 'ssssssss');

// Global stats
$totalUsers = count($userList);
$totalSuratDibuat = array_sum(array_column($userList, 'surat_dibuat'));
$totalDispDikirim = array_sum(array_column($userList, 'disposisi_dikirim'));
$totalLogin = array_sum(array_column($userList, 'total_login'));
?>

<?php include '../partials/header.php'; ?>

<div class="flex min-h-screen">
    <?php include '../partials/sidebar.php'; ?>
    
    <div class="flex-1 lg:ml-64">
        <main class="p-4 sm:p-6 lg:p-8">
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-2">Laporan Statistik Pengguna</h1>
                    <p class="text-sm text-gray-600">Periode: <span class="font-medium"><?= formatTanggal($tanggalDari) ?></span> s/d <span class="font-medium"><?= formatTanggal($tanggalSampai) ?></span></p>
                </div>
                <a href="laporan_statistik_pengguna_pdf.php?tanggal_dari=<?= $tanggalDari ?>&tanggal_sampai=<?= $tanggalSampai ?>" target="_blank" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg inline-flex items-center justify-center transition-colors text-sm whitespace-nowrap">
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
                    <p class="text-xs sm:text-sm text-gray-600 mb-1">User Aktif</p>
                    <p class="text-xl sm:text-2xl font-bold text-indigo-600"><?= $totalUsers ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-3 sm:p-4 border-l-4 border-blue-500">
                    <p class="text-xs sm:text-sm text-gray-600 mb-1">Surat Dibuat</p>
                    <p class="text-xl sm:text-2xl font-bold text-blue-600"><?= $totalSuratDibuat ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-3 sm:p-4 border-l-4 border-green-500">
                    <p class="text-xs sm:text-sm text-gray-600 mb-1">Disposisi Dikirim</p>
                    <p class="text-xl sm:text-2xl font-bold text-green-600"><?= $totalDispDikirim ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-3 sm:p-4 border-l-4 border-orange-500">
                    <p class="text-xs sm:text-sm text-gray-600 mb-1">Total Login</p>
                    <p class="text-xl sm:text-2xl font-bold text-orange-600"><?= $totalLogin ?></p>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="hidden lg:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50"><tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bagian</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Surat</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Disp. Kirim</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Disp. Terima</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Login</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Login Terakhir</th>
                        </tr></thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($userList)): ?>
                            <tr><td colspan="9" class="px-6 py-10 text-center text-gray-500"><i class="fas fa-users text-4xl mb-3 text-gray-300"></i><p>Tidak ada data</p></td></tr>
                            <?php else: foreach ($userList as $i => $u): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-4 text-sm"><?= $i + 1 ?></td>
                                <td class="px-4 py-4 text-sm font-medium text-gray-900"><?= htmlspecialchars($u['nama_lengkap']) ?></td>
                                <td class="px-4 py-4 text-sm capitalize text-gray-600"><?= $u['nama_role'] ?></td>
                                <td class="px-4 py-4 text-sm text-gray-600"><?= htmlspecialchars($u['nama_bagian']) ?></td>
                                <td class="px-4 py-4 text-sm text-center font-medium text-blue-600"><?= $u['surat_dibuat'] ?></td>
                                <td class="px-4 py-4 text-sm text-center font-medium text-green-600"><?= $u['disposisi_dikirim'] ?></td>
                                <td class="px-4 py-4 text-sm text-center font-medium text-orange-600"><?= $u['disposisi_diterima'] ?></td>
                                <td class="px-4 py-4 text-sm text-center font-medium"><?= $u['total_login'] ?></td>
                                <td class="px-4 py-4 text-sm text-gray-500"><?= $u['last_login'] ? formatDateTime($u['last_login']) : '-' ?></td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="lg:hidden">
                    <?php if (empty($userList)): ?>
                    <div class="p-8 text-center text-gray-500"><p>Tidak ada data</p></div>
                    <?php else: foreach ($userList as $i => $u): ?>
                    <div class="border-b border-gray-200 p-4">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <div class="font-semibold text-gray-900 text-sm"><?= htmlspecialchars($u['nama_lengkap']) ?></div>
                                <div class="text-xs text-gray-500 capitalize"><?= $u['nama_role'] ?> · <?= htmlspecialchars($u['nama_bagian']) ?></div>
                            </div>
                        </div>
                        <div class="grid grid-cols-4 gap-2 text-center text-xs">
                            <div class="bg-blue-50 p-2 rounded"><div class="font-bold text-lg text-blue-600"><?= $u['surat_dibuat'] ?></div><div class="text-gray-500">Surat</div></div>
                            <div class="bg-green-50 p-2 rounded"><div class="font-bold text-lg text-green-600"><?= $u['disposisi_dikirim'] ?></div><div class="text-gray-500">Kirim</div></div>
                            <div class="bg-orange-50 p-2 rounded"><div class="font-bold text-lg text-orange-600"><?= $u['disposisi_diterima'] ?></div><div class="text-gray-500">Terima</div></div>
                            <div class="bg-gray-50 p-2 rounded"><div class="font-bold text-lg"><?= $u['total_login'] ?></div><div class="text-gray-500">Login</div></div>
                        </div>
                        <?php if ($u['last_login']): ?>
                        <div class="mt-2 text-xs text-gray-400">Login terakhir: <?= formatDateTime($u['last_login']) ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </main>
        <?php include '../partials/footer.php'; ?>
    </div>
</div>
