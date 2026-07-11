<?php
// public/laporan/laporan_log_download.php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/pagination.php';
require_once __DIR__ . '/../../modules/download/download_service.php';

requireLogin();

$user = getCurrentUser();
$userRole = $user['id_role'] ?? 3;

// Hanya superadmin (1) dan admin/karyawan (2)
if ($userRole == 3) {
    redirect('../index.php?error=unauthorized');
    exit;
}

$pageTitle = 'Log Akses File';

// Filter
$filters = [
    'search'         => $_GET['search'] ?? '',
    'user_id'        => $_GET['user_id'] ?? '',
    'aksi'           => $_GET['aksi'] ?? '',
    'tanggal_dari'   => $_GET['tanggal_dari'] ?? '',
    'tanggal_sampai' => $_GET['tanggal_sampai'] ?? '',
];

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

$totalLogs = DownloadService::count($filters);
$pagination = new Pagination($totalLogs, $perPage, $page);
$logs = DownloadService::getAll($filters, $perPage, $offset);

// Daftar user yang pernah download (untuk dropdown filter)
$downloadUsers = DownloadService::getDownloadUsers();

// Statistik
$stats = DownloadService::getStatistics();
?>

<?php include '../partials/header.php'; ?>

<div class="flex min-h-screen bg-gray-50">
    <?php include '../partials/sidebar.php'; ?>

    <div class="flex-1 lg:ml-64 transition-all duration-300">
        <main class="p-4 sm:p-6 lg:p-8">
            <div class="mb-4 sm:mb-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-1 sm:mb-2">
                            <i class="fas fa-shield-alt text-amber-500 mr-2"></i>Log Akses File
                        </h1>
                        <p class="text-sm sm:text-base text-gray-600">
                            Riwayat akses unduh dan lihat file lampiran surat untuk keperluan audit
                        </p>
                    </div>
                    <a href="laporan_log_download_pdf.php?<?= http_build_query($filters) ?>"
                        target="_blank"
                        class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
                        <i class="fas fa-file-pdf mr-2"></i> Cetak PDF
                    </a>
                </div>
            </div>

            <!-- Statistik Ringkas -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">Total Akses</p>
                            <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['total']) ?></p>
                        </div>
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-chart-bar text-blue-600"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">Hari Ini</p>
                            <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['today']) ?></p>
                        </div>
                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-calendar-day text-green-600"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-amber-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">Total Data</p>
                            <p class="text-2xl font-bold text-gray-900"><?= number_format($totalLogs) ?></p>
                        </div>
                        <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-database text-amber-600"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-purple-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">Pengguna Aktif</p>
                            <p class="text-2xl font-bold text-gray-900"><?= count($downloadUsers) ?></p>
                        </div>
                        <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-users text-purple-600"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter -->
            <div class="bg-white rounded-lg shadow p-4 mb-4 sm:mb-6">
                <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                    <input type="text"
                        name="search"
                        value="<?= htmlspecialchars($filters['search']) ?>"
                        placeholder="Cari nama, no. agenda, perihal..."
                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">

                    <select name="user_id"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="">-- Semua User --</option>
                        <?php foreach ($downloadUsers as $du): ?>
                            <option value="<?= $du['id'] ?>" <?= $filters['user_id'] == $du['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($du['nama_lengkap']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="aksi"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="">-- Semua Aksi --</option>
                        <option value="download" <?= $filters['aksi'] === 'download' ? 'selected' : '' ?>>Unduh</option>
                        <option value="view" <?= $filters['aksi'] === 'view' ? 'selected' : '' ?>>Lihat</option>
                    </select>

                    <div class="relative">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Dari Tanggal</label>
                        <input type="date"
                            name="tanggal_dari"
                            value="<?= $filters['tanggal_dari'] ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500"
                            title="Dari tanggal">
                    </div>

                    <div class="relative">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Sampai Tanggal</label>
                        <input type="date"
                            name="tanggal_sampai"
                            value="<?= $filters['tanggal_sampai'] ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500"
                            title="Sampai tanggal">
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            <i class="fas fa-search mr-1"></i> Filter
                        </button>

                        <?php
                        $hasFilter = !empty($filters['search']) || !empty($filters['user_id']) || !empty($filters['aksi']) || !empty($filters['tanggal_dari']) || !empty($filters['tanggal_sampai']);
                        ?>
                        <?php if ($hasFilter): ?>
                            <a href="laporan_log_download.php" class="bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded-lg text-sm font-medium text-center transition-colors">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Desktop Table -->
            <div class="hidden lg:block bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pengguna</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Surat</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">File</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP Address</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Waktu</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                        <i class="fas fa-shield-alt text-5xl mb-3 text-gray-300"></i>
                                        <p>
                                            <?php if ($hasFilter): ?>
                                                Tidak ditemukan log dengan filter yang dipilih
                                            <?php else: ?>
                                                Belum ada riwayat akses file
                                            <?php endif; ?>
                                        </p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $index => $log): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                            <?= $offset + $index + 1 ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 font-bold text-xs mr-3">
                                                    <?= strtoupper(substr($log['nama_lengkap'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($log['nama_lengkap']) ?></div>
                                                    <div class="text-xs text-gray-500"><?= getRoleLabel($log['nama_role']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($log['nomor_agenda']) ?></div>
                                            <div class="text-xs text-gray-500"><?= truncate($log['perihal'], 30) ?></div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="text-xs text-gray-600 truncate max-w-[150px]" title="<?= htmlspecialchars($log['nama_file']) ?>">
                                                <i class="fas fa-file text-gray-400 mr-1"></i>
                                                <?= htmlspecialchars($log['nama_file']) ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <?php if ($log['aksi'] === 'download'): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <i class="fas fa-download mr-1"></i> Unduh
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    <i class="fas fa-eye mr-1"></i> Lihat
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500 font-mono">
                                            <?= htmlspecialchars($log['ip_address'] ?? '-') ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                            <?= formatDateTime($log['created_at']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($pagination->hasPages()): ?>
                    <div class="border-t border-gray-200 px-4 py-3">
                        <?= $pagination->render('laporan_log_download.php', $filters) ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Mobile Card View -->
            <div class="lg:hidden space-y-4">
                <?php if (empty($logs)): ?>
                    <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
                        <i class="fas fa-shield-alt text-5xl mb-3 text-gray-300"></i>
                        <p>
                            <?php if ($hasFilter): ?>
                                Tidak ditemukan log dengan filter yang dipilih
                            <?php else: ?>
                                Belum ada riwayat akses file
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <?php foreach ($logs as $index => $log): ?>
                        <div class="bg-white rounded-lg shadow overflow-hidden">
                            <div class="p-4">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex items-center">
                                        <div class="w-9 h-9 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 font-bold text-sm mr-3">
                                            <?= strtoupper(substr($log['nama_lengkap'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($log['nama_lengkap']) ?></h3>
                                            <p class="text-xs text-gray-500"><?= getRoleLabel($log['nama_role']) ?></p>
                                        </div>
                                    </div>
                                    <?php if ($log['aksi'] === 'download'): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-download mr-1"></i> Unduh
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <i class="fas fa-eye mr-1"></i> Lihat
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="bg-gray-50 rounded-lg p-3 mb-3">
                                    <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($log['nomor_agenda']) ?></p>
                                    <p class="text-xs text-gray-500 mt-0.5"><?= truncate($log['perihal'], 40) ?></p>
                                </div>

                                <div class="flex items-center justify-between text-xs text-gray-500">
                                    <span>
                                        <i class="far fa-clock mr-1"></i>
                                        <?= formatDateTime($log['created_at']) ?>
                                    </span>
                                    <span class="font-mono">
                                        <i class="fas fa-globe mr-1"></i>
                                        <?= htmlspecialchars($log['ip_address'] ?? '-') ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($pagination->hasPages()): ?>
                        <div class="bg-white rounded-lg shadow p-4">
                            <?= $pagination->render('laporan_log_download.php', $filters) ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>

        <?php include '../partials/footer.php'; ?>
    </div>
</div>