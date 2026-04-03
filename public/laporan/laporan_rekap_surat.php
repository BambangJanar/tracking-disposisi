<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/pagination.php';

requireLogin();
$user = getCurrentUser();
$pageTitle = 'Laporan Rekap Surat';

$tanggalDari = $_GET['tanggal_dari'] ?? date('Y-m-01');
$tanggalSampai = $_GET['tanggal_sampai'] ?? date('Y-m-d');
$page = $_GET['page'] ?? 1;
$limit = 10;

$whereClause = "WHERE DATE(s.tanggal_diterima) BETWEEN ? AND ?";
$params = [$tanggalDari, $tanggalSampai];
$types = 'ss';

// Stats by jenis
$statsJenis = dbSelect("SELECT j.nama_jenis, COUNT(*) as total FROM surat s JOIN jenis_surat j ON s.id_jenis = j.id $whereClause GROUP BY s.id_jenis", $params, $types);
$totalSurat = 0;
$byJenis = [];
foreach ($statsJenis as $stat) { $byJenis[$stat['nama_jenis']] = $stat['total']; $totalSurat += $stat['total']; }

// Stats by status
$statsStatus = dbSelect("SELECT s.status_surat, COUNT(*) as total FROM surat s $whereClause GROUP BY s.status_surat", $params, $types);
$byStatus = [];
foreach ($statsStatus as $stat) { $byStatus[$stat['status_surat']] = $stat['total']; }

// Pagination
$countResult = dbSelect("SELECT COUNT(*) as total FROM surat s $whereClause", $params, $types);
$totalRows = $countResult[0]['total'] ?? 0;
$pagination = new Pagination($totalRows, $limit, $page);
$offset = $pagination->getOffset();

// Fetch
$query = "SELECT s.*, j.nama_jenis FROM surat s JOIN jenis_surat j ON s.id_jenis = j.id $whereClause ORDER BY s.tanggal_diterima DESC, s.created_at DESC LIMIT ? OFFSET ?";
$suratList = dbSelect($query, array_merge($params, [$limit, $offset]), $types . 'ii');
?>

<?php include '../partials/header.php'; ?>

<div class="flex min-h-screen">
    <?php include '../partials/sidebar.php'; ?>
    
    <div class="flex-1 lg:ml-64">
        <main class="p-4 sm:p-6 lg:p-8">
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-2">Laporan Rekap Surat</h1>
                    <p class="text-sm sm:text-base text-gray-600">Periode: <span class="font-medium"><?= formatTanggal($tanggalDari) ?></span> s/d <span class="font-medium"><?= formatTanggal($tanggalSampai) ?></span></p>
                </div>
                <a href="laporan_rekap_surat_pdf.php?tanggal_dari=<?= $tanggalDari ?>&tanggal_sampai=<?= $tanggalSampai ?>" target="_blank" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg inline-flex items-center justify-center transition-colors text-sm whitespace-nowrap">
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
            
            <!-- Stats by Jenis -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-4">
                <div class="bg-white rounded-lg shadow p-3 sm:p-4 border-l-4 border-gray-700">
                    <p class="text-xs sm:text-sm text-gray-600 mb-1">Total Semua Surat</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-800"><?= $totalSurat ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-3 sm:p-4 border-l-4 border-blue-500">
                    <p class="text-xs sm:text-sm text-gray-600 mb-1">Surat Masuk</p>
                    <p class="text-xl sm:text-2xl font-bold text-blue-600"><?= $byJenis['Surat Masuk'] ?? 0 ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-3 sm:p-4 border-l-4 border-orange-500">
                    <p class="text-xs sm:text-sm text-gray-600 mb-1">Surat Keluar</p>
                    <p class="text-xl sm:text-2xl font-bold text-orange-600"><?= $byJenis['Surat Keluar'] ?? 0 ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-3 sm:p-4 border-l-4 border-purple-500">
                    <p class="text-xs sm:text-sm text-gray-600 mb-1">Lainnya</p>
                    <p class="text-xl sm:text-2xl font-bold text-purple-600"><?= $totalSurat - ($byJenis['Surat Masuk'] ?? 0) - ($byJenis['Surat Keluar'] ?? 0) ?></p>
                </div>
            </div>

            <!-- Stats by Status -->
            <div class="grid grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-3 border-l-4 border-blue-400">
                    <p class="text-xs text-gray-600 mb-1">Baru</p>
                    <p class="text-lg font-bold text-blue-600"><?= $byStatus['baru'] ?? 0 ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-3 border-l-4 border-yellow-400">
                    <p class="text-xs text-gray-600 mb-1">Proses</p>
                    <p class="text-lg font-bold text-yellow-600"><?= $byStatus['proses'] ?? 0 ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-3 border-l-4 border-green-400">
                    <p class="text-xs text-gray-600 mb-1">Disetujui</p>
                    <p class="text-lg font-bold text-green-600"><?= $byStatus['disetujui'] ?? 0 ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-3 border-l-4 border-red-400">
                    <p class="text-xs text-gray-600 mb-1">Ditolak</p>
                    <p class="text-lg font-bold text-red-600"><?= $byStatus['ditolak'] ?? 0 ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-3 border-l-4 border-gray-400 col-span-3 lg:col-span-1">
                    <p class="text-xs text-gray-600 mb-1">Arsip</p>
                    <p class="text-lg font-bold text-gray-600"><?= $byStatus['arsip'] ?? 0 ?></p>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="hidden lg:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50"><tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No. Agenda</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jenis</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Perihal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Asal/Tujuan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tgl Diterima</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr></thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($suratList)): ?>
                            <tr><td colspan="7" class="px-6 py-10 text-center text-gray-500"><i class="fas fa-folder-open text-4xl mb-3 text-gray-300"></i><p>Tidak ada data</p></td></tr>
                            <?php else: foreach ($suratList as $i => $surat): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm"><?= $offset + $i + 1 ?></td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900"><?= htmlspecialchars($surat['nomor_agenda']) ?></td>
                                <td class="px-6 py-4"><span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-700"><?= $surat['nama_jenis'] ?></span></td>
                                <td class="px-6 py-4 text-sm text-gray-700 max-w-xs truncate"><?= htmlspecialchars(truncate($surat['perihal'], 35)) ?></td>
                                <td class="px-6 py-4 text-sm text-gray-700"><?= htmlspecialchars($surat['dari_instansi'] ?? $surat['ke_instansi'] ?? '-') ?></td>
                                <td class="px-6 py-4 text-sm text-gray-700"><?= formatTanggal($surat['tanggal_diterima']) ?></td>
                                <td class="px-6 py-4"><span class="px-2.5 py-1 text-xs font-medium rounded-full <?= getStatusBadge($surat['status_surat']) ?>"><?= ucfirst($surat['status_surat']) ?></span></td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="lg:hidden">
                    <?php if (empty($suratList)): ?>
                    <div class="p-8 text-center text-gray-500"><p>Tidak ada data</p></div>
                    <?php else: foreach ($suratList as $i => $surat): ?>
                    <div class="border-b border-gray-200 p-4 hover:bg-gray-50">
                        <div class="flex justify-between items-start mb-2">
                            <div class="font-semibold text-gray-900 text-sm"><?= htmlspecialchars($surat['nomor_agenda']) ?></div>
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full <?= getStatusBadge($surat['status_surat']) ?>"><?= ucfirst($surat['status_surat']) ?></span>
                        </div>
                        <div class="text-xs text-gray-600 mb-2"><?= htmlspecialchars(truncate($surat['perihal'], 60)) ?></div>
                        <div class="space-y-1 text-sm">
                            <div class="flex"><span class="text-gray-500 w-24">Jenis:</span><span><?= $surat['nama_jenis'] ?></span></div>
                            <div class="flex"><span class="text-gray-500 w-24">Tgl Diterima:</span><span><?= formatTanggal($surat['tanggal_diterima']) ?></span></div>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
                <div class="bg-gray-50 border-t border-gray-200"><?= $pagination->render(BASE_URL . '/laporan/laporan_rekap_surat.php', $_GET) ?></div>
            </div>
        </main>
        <?php include '../partials/footer.php'; ?>
    </div>
</div>
