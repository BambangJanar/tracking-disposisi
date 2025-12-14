<?php
// public/pengaturan.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../modules/settings/settings_service.php';

requireLogin();
requireRole('superadmin');

$user = getCurrentUser();
$pageTitle = 'Pengaturan Sistem';

// Get current settings
$settings = SettingsService::getSettings();

if (!$settings) {
    SettingsService::initializeDefaults();
    $settings = SettingsService::getSettings();
}
?>

<?php include 'partials/header.php'; ?>

<div class="flex min-h-screen">
    <?php include 'partials/sidebar.php'; ?>
    
    <div class="flex-1 lg:ml-64">
        <main class="p-6 lg:p-8">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Pengaturan Sistem</h1>
                <p class="text-gray-600">Kelola pengaturan aplikasi, instansi, dan tanda tangan</p>
            </div>
            
            <form method="POST" action="../modules/settings/settings_handler.php" enctype="multipart/form-data" id="settingsForm">
                <input type="hidden" name="action" value="update">
                
                <!-- Tabs -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="border-b border-gray-200">
                        <nav class="flex -mb-px" aria-label="Tabs">
                            <button type="button" onclick="showTab('aplikasi')" id="tab-aplikasi" class="tab-button border-b-2 border-blue-600 py-4 px-6 text-sm font-medium text-blue-600">
                                <i class="fas fa-cog mr-2"></i>Aplikasi
                            </button>
                            <button type="button" onclick="showTab('instansi')" id="tab-instansi" class="tab-button border-b-2 border-transparent py-4 px-6 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                <i class="fas fa-building mr-2"></i>Instansi
                            </button>
                            <button type="button" onclick="showTab('ttd')" id="tab-ttd" class="tab-button border-b-2 border-transparent py-4 px-6 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                <i class="fas fa-signature mr-2"></i>Tanda Tangan
                            </button>
                        </nav>
                    </div>
                    
                    <!-- Tab Content: Aplikasi -->
                    <div id="content-aplikasi" class="tab-content p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Pengaturan Aplikasi</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Aplikasi *</label>
                                <input type="text" name="app_name" value="<?= htmlspecialchars($settings['app_name']) ?>" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi Aplikasi</label>
                                <textarea name="app_description" rows="2"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($settings['app_description']) ?></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Logo Aplikasi</label>
                                <?php if ($settings['app_logo']): ?>
                                <div class="mb-2">
                                    <img src="<?= SETTINGS_UPLOAD_URL . $settings['app_logo'] ?>" alt="Logo" class="h-16 border rounded">
                                    <p class="text-xs text-gray-500 mt-1">File saat ini: <?= $settings['app_logo'] ?></p>
                                </div>
                                <?php endif; ?>
                                <input type="file" name="app_logo" accept=".png,.jpg,.jpeg,.svg" id="app_logo_input"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <p class="text-xs text-gray-500 mt-1">Format: PNG, JPG, SVG (Max 2MB)</p>
                                <div id="app_logo_preview" class="mt-2"></div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Favicon</label>
                                <?php if ($settings['app_favicon']): ?>
                                <div class="mb-2">
                                    <img src="<?= SETTINGS_UPLOAD_URL . $settings['app_favicon'] ?>" alt="Favicon" class="h-8 border rounded">
                                    <p class="text-xs text-gray-500 mt-1">File saat ini: <?= $settings['app_favicon'] ?></p>
                                </div>
                                <?php endif; ?>
                                <input type="file" name="app_favicon" accept=".ico,.png" id="app_favicon_input"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <p class="text-xs text-gray-500 mt-1">Format: ICO, PNG (Max 2MB)</p>
                                <div id="app_favicon_preview" class="mt-2"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab Content: Instansi -->
                    <div id="content-instansi" class="tab-content p-6 hidden">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Pengaturan Instansi</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Instansi *</label>
                                <textarea name="instansi_nama" rows="2" required
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($settings['instansi_nama']) ?></textarea>
                                <p class="text-xs text-gray-500 mt-1">Gunakan Enter untuk baris baru</p>
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Alamat Instansi</label>
                                <textarea name="instansi_alamat" rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($settings['instansi_alamat']) ?></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Telepon</label>
                                <input type="text" name="instansi_telepon" value="<?= htmlspecialchars($settings['instansi_telepon']) ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                       placeholder="(0511) 1234567">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                <input type="email" name="instansi_email" value="<?= htmlspecialchars($settings['instansi_email']) ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                       placeholder="admin@instansi.go.id">
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Logo Instansi (untuk KOP Surat)</label>
                                <?php if ($settings['instansi_logo']): ?>
                                <div class="mb-2">
                                    <img src="<?= SETTINGS_UPLOAD_URL . $settings['instansi_logo'] ?>" alt="Logo Instansi" class="h-20 border rounded">
                                    <p class="text-xs text-gray-500 mt-1">File saat ini: <?= $settings['instansi_logo'] ?></p>
                                </div>
                                <?php endif; ?>
                                <input type="file" name="instansi_logo" accept=".png,.jpg,.jpeg,.svg" id="instansi_logo_input"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <p class="text-xs text-gray-500 mt-1">Format: PNG, JPG, SVG (Max 2MB) - Logo ini akan muncul di laporan PDF</p>
                                <div id="instansi_logo_preview" class="mt-2"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab Content: TTD -->
                    <div id="content-ttd" class="tab-content p-6 hidden">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Pengaturan Tanda Tangan</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Penandatangan</label>
                                <input type="text" name="ttd_nama_penandatangan" value="<?= htmlspecialchars($settings['ttd_nama_penandatangan']) ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                       placeholder="Nama Lengkap">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">NIP</label>
                                <input type="text" name="ttd_nip" value="<?= htmlspecialchars($settings['ttd_nip']) ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                       placeholder="19XXXXX XXXXXX X XXX">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Jabatan</label>
                                <input type="text" name="ttd_jabatan" value="<?= htmlspecialchars($settings['ttd_jabatan']) ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                       placeholder="Kepala Dinas">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Kota</label>
                                <input type="text" name="ttd_kota" value="<?= htmlspecialchars($settings['ttd_kota']) ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                       placeholder="Banjarmasin">
                            </div>
                            
                            <div class="md:col-span-2 bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                                <p class="text-sm text-blue-800">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    <strong>Info:</strong> Data tanda tangan ini akan muncul di bagian bawah semua laporan PDF yang dicetak dari sistem.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div class="flex justify-end space-x-2">
                    <a href="<?= BASE_URL ?>/index.php" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Batal
                    </a>
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                        <i class="fas fa-save mr-2"></i>Simpan Perubahan
                    </button>
                </div>
            </form>
        </main>
        
        <?php include 'partials/footer.php'; ?>
    </div>
</div>

<script>
// Tab switching
function showTab(tabName) {
    // Hide all contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Remove active state from all buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('border-blue-600', 'text-blue-600');
        button.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Show selected content
    document.getElementById('content-' + tabName).classList.remove('hidden');
    
    // Activate selected button
    const activeButton = document.getElementById('tab-' + tabName);
    activeButton.classList.remove('border-transparent', 'text-gray-500');
    activeButton.classList.add('border-blue-600', 'text-blue-600');
}

// Image preview
function setupImagePreview(inputId, previewId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    input.addEventListener('change', function(e) {
        const file = e.target.files[0];
        const preview = document.getElementById(previewId);
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = '<img src="' + e.target.result + '" class="h-20 border rounded mt-2" alt="Preview">';
            };
            reader.readAsDataURL(file);
        } else {
            preview.innerHTML = '';
        }
    });
}

setupImagePreview('app_logo_input', 'app_logo_preview');
setupImagePreview('app_favicon_input', 'app_favicon_preview');
setupImagePreview('instansi_logo_input', 'instansi_logo_preview');

// Form validation
document.getElementById('settingsForm').addEventListener('submit', function(e) {
    const appName = document.querySelector('[name="app_name"]').value.trim();
    const instansiNama = document.querySelector('[name="instansi_nama"]').value.trim();
    
    if (!appName) {
        e.preventDefault();
        showError('Nama aplikasi harus diisi');
        showTab('aplikasi');
        return false;
    }
    
    if (!instansiNama) {
        e.preventDefault();
        showError('Nama instansi harus diisi');
        showTab('instansi');
        return false;
    }
    
    showLoading('Menyimpan pengaturan...');
});
</script>

<?php include 'partials/footer.php'; ?>