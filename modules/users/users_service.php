<?php
// modules/users/users_service.php
require_once __DIR__ . '/../../config/database.php';

class UsersService {
    
    /**
     * Ambil semua user (Support filter status untuk Admin)
     */
    public static function getAll($status = null) {
        $conn = getConnection();
        
        $sql = "SELECT u.*, r.nama_role, b.nama_bagian 
                FROM users u 
                LEFT JOIN roles r ON u.id_role = r.id 
                LEFT JOIN bagian b ON u.id_bagian = b.id 
                WHERE 1=1";
        
        $params = [];
        $types = "";

        // Filter status jika ada dan bukan 'all'
        if ($status && $status !== 'all') {
            $sql .= " AND u.status = ?";
            $params[] = $status;
            $types .= "s";
        }

        $sql .= " ORDER BY u.created_at DESC";
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Ambil data user berdasarkan ID
     */
    public static function getById($id) {
        $conn = getConnection();
        $query = "SELECT u.*, r.nama_role, b.nama_bagian
                  FROM users u
                  LEFT JOIN roles r ON u.id_role = r.id
                  LEFT JOIN bagian b ON u.id_bagian = b.id
                  WHERE u.id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Hitung jumlah user pending (untuk Badge Sidebar)
     */
    public static function countPending() {
        $conn = getConnection();
        $result = $conn->query("SELECT COUNT(*) as total FROM users WHERE status = 'pending'");
        $row = $result->fetch_assoc();
        return $row['total'] ?? 0;
    }

    /**
     * Ambil daftar semua role (untuk Dropdown Ganti Role)
     */
    public static function getRoles() {
        $conn = getConnection();
        $result = $conn->query("SELECT * FROM roles ORDER BY id ASC");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Update Status User (Approve/Reject)
     */
    public static function updateStatus($userId, $status) {
        $conn = getConnection();
        // Update status_aktif juga agar sinkron (1 = active, 0 = lainnya)
        $isActive = ($status === 'active') ? 1 : 0;
        
        $stmt = $conn->prepare("UPDATE users SET status = ?, status_aktif = ? WHERE id = ?");
        $stmt->bind_param("sii", $status, $isActive, $userId);
        
        return $stmt->execute();
    }

    /**
     * Update Role User
     */
    public static function updateRole($userId, $roleId) {
        $conn = getConnection();
        $stmt = $conn->prepare("UPDATE users SET id_role = ? WHERE id = ?");
        $stmt->bind_param("ii", $roleId, $userId);
        return $stmt->execute();
    }

    /**
     * Hapus User
     */
    public static function delete($userId) {
        $conn = getConnection();
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        return $stmt->execute();
    }

    /**
     * Update Profil (Nama & Email)
     */
    public static function updateProfile($id, $data) {
        $conn = getConnection();
        $query = "UPDATE users SET nama_lengkap = ?, email = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssi", $data['nama_lengkap'], $data['email'], $id);
        return $stmt->execute();
    }
    
    /**
     * Ganti Password
     */
    public static function changePassword($id, $newPassword) {
        $conn = getConnection();
        // Disarankan menggunakan password_hash di masa depan
        // $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $query = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $newPassword, $id);
        return $stmt->execute();
    }
    
    /**
     * Cek apakah email sudah ada (Validasi)
     */
    public static function emailExists($email, $excludeId = null) {
        $conn = getConnection();
        $query = "SELECT COUNT(*) as total FROM users WHERE email = ?";
        $types = 's';
        $params = [$email];
        
        if ($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
            $types .= 'i';
        }
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return ($result['total'] ?? 0) > 0;
    }
}
?>