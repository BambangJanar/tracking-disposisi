<?php
// public/disposisi_outbox.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/pagination.php';
require_once __DIR__ . '/../modules/disposisi/disposisi_service.php';

requireLogin();

$user = getCurrentUser();
$userRole = $user['id_role'] ?? 3;
$pageTitle = 'Disposisi Keluar';

$filters = [
    'dari_user_id' => $user['id'],
    'status_disposisi' => $_GET['status'] ?? '',
    'search' => $_GET['search'] ?? ''
];

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// ========== PERUBAHAN: Gunakan getOutboxByRole ==========
// Admin melihat semua, karyawan hanya melihat surat yang ia tangani
$totalDisposisi = DisposisiService::countOutboxByRole($user['id'], $userRole, $filters);
$pagination = new Pagination($totalDisposisi, $perPage, $page);

$disposisiList = DisposisiService::getOutboxByRole($user['id'], $userRole, $filters, $perPage, $offset);
?>

<?php include 'partials/header.php'; ?>

<div class="flex min-h-screen bg-gray-50">
    <?php include 'partials/sidebar.php'; ?>
    
    <div class="flex-1 lg:ml-64 transition-all duration-300">
        <main class="p-4 sm:p-6 lg:p-8">
            <div class="mb-4 sm:mb-6">
                <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-1 sm:mb-2">Disposisi Keluar</h1>
                <p class="text-sm sm:text-base text-gray-600">Surat yang sedang beredar dan Anda kirimkan</p>
            </div>
            
            <div class="bg-white rounded-lg shadow p-4 mb-4 sm:mb-6">
                <form method="GET" class="space-y-3 sm:space-y-0 sm:flex sm:gap-2">
                    <input type="text" name="search" value="<?= htmlspecialchars($filters['search']) ?>"
                           placeholder="Cari nomor surat, perihal..." 
                           class="w-full sm:flex-1 px-3 sm:px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    
                    <select name="status" class="w-full sm:w-auto px-3 sm:px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <option value="">Semua Status</option>
                        <option value="dikirim" <?= $filters['status_disposisi'] == 'dikirim' ? 'selected' : '' ?>>Dikirim</option>
                        <option value="diterima" <?= $filters['status_disposisi'] == 'diterima' ? 'selected' : '' ?>>Diterima</option>
                        <option value="diproses" <?= $filters['status_disposisi'] == 'diproses' ? 'selected' : '' ?>>Diproses</option>
                        <option value="selesai" <?= $filters['status_disposisi'] == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                        <option value="ditolak" <?= $filters['status_disposisi'] == 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                    </select>
                    
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 sm:flex-none bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            <i class="fas fa-search"></i><span class="ml-2 sm:hidden">Cari</span>
                        </button>
                        
                        <?php if (!empty($filters['search']) || !empty($filters['status_disposisi'])): ?>
                        <a href="disposisi_outbox.php" class="flex-1 sm:flex-none bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded-lg text-sm font-medium text-center transition-colors">
                            <i class="fas fa-times"></i><span class="ml-2 sm:hidden">Reset</span>
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <div class="hidden lg:block bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kepada</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Surat</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Catatan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($disposisiList)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-paper-plane text-5xl mb-3 text-gray-300"></i>
                                    <p>Belum ada disposisi keluar</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($disposisiList as $disp): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?= $disp['ke_user_nama'] ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?= $disp['nomor_agenda'] ?></div>
                                        <div class="text-xs text-gray-500"><?= truncate($disp['perihal'], 40) ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-700 max-w-xs">
                                            <?= $disp['catatan'] ? truncate($disp['catatan'], 50) : '-' ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <?= formatDateTime($disp['tanggal_disposisi']) ?>
                                        <?php if ($disp['tanggal_respon']): ?>
                                        <div class="text-xs text-green-600 mt-1">
                                            <i class="fas fa-check"></i> <?= formatDateTime($disp['tanggal_respon']) ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?= getDisposisiStatusBadge($disp['status_disposisi']) ?>">
                                            <?= ucfirst($disp['status_disposisi']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <div class="flex gap-2">
                                            <a href="surat_detail.php?id=<?= $disp['id_surat'] ?>" 
                                               class="text-primary-600 hover:text-primary-800 transition-colors" 
                                               title="Lihat Surat">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            <?php 
                                            // Tampilkan button peringatan jika surat belum selesai
                                            if (!in_array($disp['status_disposisi'], ['selesai', 'ditolak'])): 
                                            ?>
                                            <button onclick="kirimPeringatan(<?= $disp['id'] ?>, <?= $disp['ke_user_id'] ?>, '<?= htmlspecialchars($disp['ke_user_nama'], ENT_QUOTES) ?>')" 
                                                    class="text-orange-600 hover:text-orange-800 transition-colors btn-peringatan" 
                                                    data-disposisi-id="<?= $disp['id'] ?>"
                                                    title="Kirim Peringatan">
                                                <i class="fas fa-bell"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($pagination->hasPages()): ?>
                <div class="border-t border-gray-200 px-4 py-3">
                    <?= $pagination->render('disposisi_outbox.php', ['status' => $filters['status_disposisi'], 'search' => $filters['search']]) ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="lg:hidden space-y-4">
                <?php if (empty($disposisiList)): ?>
                <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
                    <i class="fas fa-paper-plane text-5xl mb-3 text-gray-300"></i>
                    <p>Belum ada disposisi keluar</p>
                </div>
                <?php else: ?>
                    <?php foreach ($disposisiList as $disp): ?>
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="p-4">
                            <div class="flex items-center justify-between mb-3">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?= getDisposisiStatusBadge($disp['status_disposisi']) ?>">
                                    <?= ucfirst($disp['status_disposisi']) ?>
                                </span>
                                <span class="text-xs text-gray-500">
                                    <i class="fas fa-user mr-1"></i><?= $disp['ke_user_nama'] ?>
                                </span>
                            </div>
                            
                            <div class="mb-3">
                                <p class="text-sm font-semibold text-gray-900"><?= $disp['nomor_agenda'] ?></p>
                                <p class="text-xs text-gray-500 line-clamp-2"><?= $disp['perihal'] ?></p>
                            </div>
                            
                            <?php if ($disp['catatan']): ?>
                            <div class="mb-3 p-2 bg-gray-50 rounded text-xs text-gray-700">
                                <i class="fas fa-comment-dots mr-1"></i>
                                <?= truncate($disp['catatan'], 100) ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="space-y-1 text-xs text-gray-500 mb-3">
                                <div>
                                    <i class="far fa-clock mr-1"></i>
                                    Dikirim: <?= formatDateTime($disp['tanggal_disposisi']) ?>
                                </div>
                                <?php if ($disp['tanggal_respon']): ?>
                                <div class="text-green-600">
                                    <i class="fas fa-check mr-1"></i>
                                    Respon: <?= formatDateTime($disp['tanggal_respon']) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex gap-2">
                                <a href="surat_detail.php?id=<?= $disp['id_surat'] ?>" 
                                   class="flex-1 bg-primary-50 text-primary-600 hover:bg-primary-100 text-center py-2 px-4 rounded-lg text-sm font-medium transition-colors">
                                    <i class="fas fa-eye mr-1"></i>Lihat Surat
                                </a>
                                
                                <?php if (!in_array($disp['status_disposisi'], ['selesai', 'ditolak'])): ?>
                                <button onclick="kirimPeringatan(<?= $disp['id'] ?>, <?= $disp['ke_user_id'] ?>, '<?= htmlspecialchars($disp['ke_user_nama'], ENT_QUOTES) ?>')" 
                                        class="flex-1 bg-orange-50 text-orange-600 hover:bg-orange-100 text-center py-2 px-4 rounded-lg text-sm font-medium transition-colors btn-peringatan"
                                        data-disposisi-id="<?= $disp['id'] ?>">
                                    <i class="fas fa-bell mr-1"></i>Peringatan
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if ($pagination->hasPages()): ?>
                    <div class="bg-white rounded-lg shadow p-4">
                        <?= $pagination->render('disposisi_outbox.php', ['status' => $filters['status_disposisi'], 'search' => $filters['search']]) ?>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
        
        <?php include 'partials/footer.php'; ?>
    </div>
</div>

<script>
// Tracking cooldown per disposisi (LocalStorage)
const COOLDOWN_DURATION = 3600000; // 1 jam dalam milliseconds

function canSendReminder(disposisiId) {
    const key = `reminder_cooldown_${disposisiId}`;
    const lastSent = localStorage.getItem(key);
    
    if (!lastSent) return true;
    
    const timeSince = Date.now() - parseInt(lastSent);
    return timeSince >= COOLDOWN_DURATION;
}

function setReminderCooldown(disposisiId) {
    const key = `reminder_cooldown_${disposisiId}`;
    localStorage.setItem(key, Date.now().toString());
}

function getRemainingCooldown(disposisiId) {
    const key = `reminder_cooldown_${disposisiId}`;
    const lastSent = localStorage.getItem(key);
    
    if (!lastSent) return 0;
    
    const timeSince = Date.now() - parseInt(lastSent);
    const remaining = COOLDOWN_DURATION - timeSince;
    
    return remaining > 0 ? remaining : 0;
}

function formatCooldownTime(ms) {
    const minutes = Math.floor(ms / 60000);
    const seconds = Math.floor((ms % 60000) / 1000);
    return `${minutes} menit ${seconds} detik`;
}

function kirimPeringatan(disposisiId, userId, userName) {
    // Cek cooldown
    if (!canSendReminder(disposisiId)) {
        const remaining = getRemainingCooldown(disposisiId);
        Swal.fire({
            icon: 'warning',
            title: 'Mohon Tunggu',
            html: `Peringatan sudah dikirim. Anda dapat mengirim lagi dalam:<br><strong>${formatCooldownTime(remaining)}</strong>`,
            confirmButtonText: 'OK'
        });
        return;
    }
    
    // Konfirmasi pengiriman
    Swal.fire({
        title: 'Kirim Peringatan?',
        html: `Anda akan mengirim peringatan ke:<br><strong>${userName}</strong><br><br>Peringatan hanya dapat dikirim 1 kali per jam.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Kirim',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#f97316',
        cancelButtonColor: '#6b7280'
    }).then((result) => {
        if (result.isConfirmed) {
            // Kirim request
            const formData = new FormData();
            formData.append('action', 'send_reminder');
            formData.append('disposisi_id', disposisiId);
            formData.append('user_id', userId);
            
            Swal.fire({
                title: 'Mengirim...',
                html: 'Mohon tunggu sebentar',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch('<?= BASE_URL ?>/../modules/disposisi/disposisi_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Set cooldown
                    setReminderCooldown(disposisiId);
                    
                    // Disable button
                    const buttons = document.querySelectorAll(`.btn-peringatan[data-disposisi-id="${disposisiId}"]`);
                    buttons.forEach(btn => {
                        btn.disabled = true;
                        btn.classList.add('opacity-50', 'cursor-not-allowed');
                    });
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Terjadi kesalahan sistem'
                });
            });
        }
    });
}

// Check cooldown on page load dan disable button jika masih cooldown
document.addEventListener('DOMContentLoaded', () => {
    const buttons = document.querySelectorAll('.btn-peringatan');
    buttons.forEach(btn => {
        const disposisiId = btn.getAttribute('data-disposisi-id');
        if (!canSendReminder(disposisiId)) {
            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');
            btn.title = 'Peringatan sudah dikirim, tunggu 1 jam';
        }
    });
});
</script>

<?php include 'partials/footer.php'; ?>