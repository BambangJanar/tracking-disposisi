<?php
// modules/users/users_handler.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/users_service.php';

requireLogin();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user = getCurrentUser();

// ============================================================================
// A. HANDLER KHUSUS ADMIN (AJAX - RETURN JSON)
// ============================================================================
// Menangani: Approve, Reject, Delete, Ganti Role
if (in_array($action, ['approve', 'reject', 'delete', 'change_role'])) {
    
    // Set header JSON agar JS bisa membaca response
    header('Content-Type: application/json');
    
    try {
        // Keamanan: Hanya Superadmin yang boleh akses
        if (!hasRole('superadmin')) {
            throw new Exception('Akses ditolak. Hanya Superadmin.');
        }

        $targetId = $_POST['id'] ?? 0;
        if (!$targetId) throw new Exception('ID User tidak valid');

        switch ($action) {
            case 'approve':
                if (UsersService::updateStatus($targetId, 'active')) {
                    echo json_encode(['status' => 'success', 'message' => 'User berhasil diaktifkan']);
                } else {
                    throw new Exception('Gagal mengaktifkan user');
                }
                break;

            case 'reject':
                if (UsersService::updateStatus($targetId, 'rejected')) {
                    echo json_encode(['status' => 'success', 'message' => 'User berhasil ditolak']);
                } else {
                    throw new Exception('Gagal menolak user');
                }
                break;

            case 'delete':
                // Cegah hapus diri sendiri
                if ($targetId == $user['id']) {
                    throw new Exception('Anda tidak dapat menghapus akun sendiri.');
                }
                if (UsersService::delete($targetId)) {
                    echo json_encode(['status' => 'success', 'message' => 'User berhasil dihapus']);
                } else {
                    throw new Exception('Gagal menghapus user');
                }
                break;

            case 'change_role':
                $roleId = $_POST['role_id'] ?? 0;
                
                // Cegah ubah role diri sendiri agar tidak terkunci
                if ($targetId == $user['id']) {
                    throw new Exception('Demi keamanan, Anda tidak dapat mengubah role akun sendiri.');
                }

                if (UsersService::updateRole($targetId, $roleId)) {
                    echo json_encode(['status' => 'success', 'message' => 'Role berhasil diperbarui']);
                } else {
                    throw new Exception('Gagal memperbarui role');
                }
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit; // Stop script di sini untuk request AJAX
}

// ============================================================================
// B. HANDLER KHUSUS PROFIL USER (FORM SUBMIT - REDIRECT)
// ============================================================================
// Menangani: Update Profil Sendiri, Ganti Password Sendiri
try {
    switch ($action) {
        case 'update_profile':
            $data = [
                'nama_lengkap' => sanitize($_POST['nama_lengkap']),
                'email' => sanitize($_POST['email'])
            ];
            
            if (empty($data['nama_lengkap']) || empty($data['email'])) {
                throw new Exception('Nama dan email harus diisi');
            }
            
            // Cek apakah email sudah digunakan user lain
            if (UsersService::emailExists($data['email'], $user['id'])) {
                throw new Exception('Email sudah digunakan oleh user lain');
            }
            
            UsersService::updateProfile($user['id'], $data);
            
            // Update data di session agar langsung berubah
            $_SESSION['nama_lengkap'] = $data['nama_lengkap'];
            $_SESSION['email'] = $data['email'];
            
            logActivity($user['id'], 'update_profil', 'Mengupdate profil');
            
            setFlash('success', 'Profil berhasil diperbarui');
            header("Location: " . BASE_URL . "/profil.php?success=updated");
            exit;
            
        case 'change_password':
            $passwordLama = $_POST['password_lama'];
            $passwordBaru = $_POST['password_baru'];
            $passwordKonfirmasi = $_POST['password_konfirmasi'];
            
            if (empty($passwordLama) || empty($passwordBaru) || empty($passwordKonfirmasi)) {
                throw new Exception('Semua field password harus diisi');
            }
            
            // Ambil data user saat ini untuk cek password lama
            $currentUser = UsersService::getById($user['id']);
            
            // Verifikasi password lama (Plain text 'admin123' sesuai sistem Anda)
            // Jika nanti migrasi ke hash, ganti dengan password_verify()
            if ($passwordLama !== $currentUser['password']) {
                throw new Exception('Password lama tidak sesuai');
            }
            
            // Validasi password baru
            if ($passwordBaru !== $passwordKonfirmasi) {
                throw new Exception('Password baru dan konfirmasi tidak cocok');
            }
            
            if (strlen($passwordBaru) < 6) {
                throw new Exception('Password baru minimal 6 karakter');
            }
            
            UsersService::changePassword($user['id'], $passwordBaru);
            logActivity($user['id'], 'ganti_password', 'Mengganti password');
            
            setFlash('success', 'Password berhasil diubah');
            header("Location: " . BASE_URL . "/profil.php?success=password_changed");
            exit;
            
        default:
            // Jika action tidak dikenali, lempar error (kecuali halaman baru dimuat)
            if(!empty($action)) {
                throw new Exception('Action tidak valid');
            }
    }
    
} catch (Exception $e) {
    setFlash('error', $e->getMessage());
    header("Location: " . BASE_URL . "/profil.php?error=process_failed");
    exit;
}
?>