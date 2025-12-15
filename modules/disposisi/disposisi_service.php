<?php
// modules/disposisi/disposisi_service.php

require_once __DIR__ . '/../../config/database.php';

class DisposisiService {
    
    /**
     * ========== METHOD BARU: getUniqueInboxByStakeholder ==========
     * Menampilkan inbox yang UNIQUE berdasarkan surat_id
     * Hanya 1 surat ditampilkan meskipun ada multiple disposisi
     * Mengambil disposisi TERBARU untuk setiap surat
     */
    public static function getUniqueInboxByStakeholder($filters = [], $limit = 10, $offset = 0) {
        $query = "SELECT 
                    d.id,
                    d.id_surat,
                    d.dari_user_id,
                    d.ke_user_id,
                    d.status_disposisi,
                    d.catatan,
                    d.tanggal_disposisi,
                    d.tanggal_respon,
                    s.nomor_surat,
                    s.nomor_agenda,
                    s.perihal,
                    s.status_surat,
                    u1.nama_lengkap as dari_user_nama,
                    u1.id_role as dari_user_role,
                    u2.nama_lengkap as ke_user_nama,
                    u2.id_role as ke_user_role
                  FROM disposisi d
                  INNER JOIN (
                      SELECT id_surat, MAX(id) as latest_id
                      FROM disposisi
                      WHERE ke_user_id = ?
                      GROUP BY id_surat
                  ) latest ON d.id = latest.latest_id
                  JOIN surat s ON d.id_surat = s.id
                  JOIN users u1 ON d.dari_user_id = u1.id
                  JOIN users u2 ON d.ke_user_id = u2.id
                  WHERE d.ke_user_id = ?
                  AND s.status_surat NOT IN ('disetujui', 'ditolak', 'arsip')";
        
        $params = [$filters['ke_user_id'], $filters['ke_user_id']];
        $types = 'ii';
        
        // Filter search
        if (!empty($filters['search'])) {
            $query .= " AND (s.nomor_surat LIKE ? OR s.perihal LIKE ? OR s.nomor_agenda LIKE ?)";
            $search = "%{$filters['search']}%";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $types .= 'sss';
        }
        
        // Filter status
        if (!empty($filters['status_disposisi'])) {
            $query .= " AND d.status_disposisi = ?";
            $params[] = $filters['status_disposisi'];
            $types .= 's';
        }
        
        $query .= " ORDER BY d.tanggal_disposisi DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        return dbSelect($query, $params, $types);
    }
    
    /**
     * Count unique inbox
     */
    public static function countUniqueInbox($filters = []) {
        $query = "SELECT COUNT(DISTINCT d.id_surat) as total
                  FROM disposisi d
                  JOIN surat s ON d.id_surat = s.id
                  WHERE d.ke_user_id = ?
                  AND s.status_surat NOT IN ('disetujui', 'ditolak', 'arsip')";
        
        $params = [$filters['ke_user_id']];
        $types = 'i';
        
        if (!empty($filters['search'])) {
            $query .= " AND (s.nomor_surat LIKE ? OR s.perihal LIKE ? OR s.nomor_agenda LIKE ?)";
            $search = "%{$filters['search']}%";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $types .= 'sss';
        }
        
        if (!empty($filters['status_disposisi'])) {
            $query .= " AND d.status_disposisi = ?";
            $params[] = $filters['status_disposisi'];
            $types .= 's';
        }
        
        $result = dbSelectOne($query, $params, $types);
        return $result['total'] ?? 0;
    }
    
    /**
     * ========== METHOD BARU: getOutboxByRole ==========
     * Admin (role 1): Melihat SEMUA surat yang sedang beredar
     * Karyawan (role 2): Hanya surat yang IA tangani (sebagai stakeholder)
     */
    public static function getOutboxByRole($userId, $userRole, $filters = [], $limit = 10, $offset = 0) {
        $query = "SELECT 
                    d.id,
                    d.id_surat,
                    d.dari_user_id,
                    d.ke_user_id,
                    d.status_disposisi,
                    d.catatan,
                    d.tanggal_disposisi,
                    d.tanggal_respon,
                    s.nomor_surat,
                    s.nomor_agenda,
                    s.perihal,
                    s.status_surat,
                    u1.nama_lengkap as dari_user_nama,
                    u2.nama_lengkap as ke_user_nama
                  FROM disposisi d
                  JOIN surat s ON d.id_surat = s.id
                  JOIN users u1 ON d.dari_user_id = u1.id
                  JOIN users u2 ON d.ke_user_id = u2.id
                  WHERE s.status_surat NOT IN ('disetujui', 'ditolak', 'arsip')";
        
        $params = [];
        $types = '';
        
        // Filter berdasarkan role
        if ($userRole == 1) {
            // Superadmin: Tidak ada filter, tampilkan semua
            // Tidak perlu tambahan WHERE clause
        } else {
            // Karyawan/Magang: Hanya surat yang ia terlibat sebagai stakeholder
            $query .= " AND d.id_surat IN (
                            SELECT surat_id FROM surat_stakeholders 
                            WHERE user_id = ? AND is_active = 1
                        )";
            $params[] = $userId;
            $types .= 'i';
        }
        
        // Filter dari_user_id (opsional, untuk melihat disposisi yang dikirim user)
        if (!empty($filters['dari_user_id'])) {
            $query .= " AND d.dari_user_id = ?";
            $params[] = $filters['dari_user_id'];
            $types .= 'i';
        }
        
        // Filter search
        if (!empty($filters['search'])) {
            $query .= " AND (s.nomor_surat LIKE ? OR s.perihal LIKE ? OR s.nomor_agenda LIKE ?)";
            $search = "%{$filters['search']}%";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $types .= 'sss';
        }
        
        // Filter status
        if (!empty($filters['status_disposisi'])) {
            $query .= " AND d.status_disposisi = ?";
            $params[] = $filters['status_disposisi'];
            $types .= 's';
        }
        
        $query .= " ORDER BY d.tanggal_disposisi DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        return dbSelect($query, $params, $types);
    }
    
    /**
     * Count outbox by role
     */
    public static function countOutboxByRole($userId, $userRole, $filters = []) {
        $query = "SELECT COUNT(*) as total
                  FROM disposisi d
                  JOIN surat s ON d.id_surat = s.id
                  WHERE s.status_surat NOT IN ('disetujui', 'ditolak', 'arsip')";
        
        $params = [];
        $types = '';
        
        if ($userRole == 1) {
            // Admin: no filter
        } else {
            $query .= " AND d.id_surat IN (
                            SELECT surat_id FROM surat_stakeholders 
                            WHERE user_id = ? AND is_active = 1
                        )";
            $params[] = $userId;
            $types .= 'i';
        }
        
        if (!empty($filters['dari_user_id'])) {
            $query .= " AND d.dari_user_id = ?";
            $params[] = $filters['dari_user_id'];
            $types .= 'i';
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (s.nomor_surat LIKE ? OR s.perihal LIKE ? OR s.nomor_agenda LIKE ?)";
            $search = "%{$filters['search']}%";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $types .= 'sss';
        }
        
        if (!empty($filters['status_disposisi'])) {
            $query .= " AND d.status_disposisi = ?";
            $params[] = $filters['status_disposisi'];
            $types .= 's';
        }
        
        $result = dbSelectOne($query, $params, $types);
        return $result['total'] ?? 0;
    }
    
    // ========== EXISTING METHODS ==========
    
    public static function getAll($filters = [], $limit = 10, $offset = 0) {
        $query = "SELECT d.*, 
                         s.nomor_surat, s.nomor_agenda, s.perihal,
                         u1.nama_lengkap as dari_user_nama,
                         u2.nama_lengkap as ke_user_nama
                  FROM disposisi d
                  JOIN surat s ON d.id_surat = s.id
                  JOIN users u1 ON d.dari_user_id = u1.id
                  JOIN users u2 ON d.ke_user_id = u2.id
                  WHERE 1=1";
        
        $params = [];
        $types = '';
        
        if (!empty($filters['dari_user_id'])) {
            $query .= " AND d.dari_user_id = ?";
            $params[] = $filters['dari_user_id'];
            $types .= 'i';
        }
        
        if (!empty($filters['ke_user_id'])) {
            $query .= " AND d.ke_user_id = ?";
            $params[] = $filters['ke_user_id'];
            $types .= 'i';
        }
        
        if (!empty($filters['status_disposisi'])) {
            $query .= " AND d.status_disposisi = ?";
            $params[] = $filters['status_disposisi'];
            $types .= 's';
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (s.nomor_surat LIKE ? OR s.perihal LIKE ?)";
            $search = "%{$filters['search']}%";
            $params[] = $search;
            $params[] = $search;
            $types .= 'ss';
        }
        
        $query .= " ORDER BY d.tanggal_disposisi DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        return dbSelect($query, $params, $types);
    }
    
    public static function count($filters = []) {
        $query = "SELECT COUNT(*) as total FROM disposisi d
                  JOIN surat s ON d.id_surat = s.id
                  WHERE 1=1";
        
        $params = [];
        $types = '';
        
        if (!empty($filters['dari_user_id'])) {
            $query .= " AND d.dari_user_id = ?";
            $params[] = $filters['dari_user_id'];
            $types .= 'i';
        }
        
        if (!empty($filters['ke_user_id'])) {
            $query .= " AND d.ke_user_id = ?";
            $params[] = $filters['ke_user_id'];
            $types .= 'i';
        }
        
        if (!empty($filters['status_disposisi'])) {
            $query .= " AND d.status_disposisi = ?";
            $params[] = $filters['status_disposisi'];
            $types .= 's';
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (s.nomor_surat LIKE ? OR s.perihal LIKE ?)";
            $search = "%{$filters['search']}%";
            $params[] = $search;
            $params[] = $search;
            $types .= 'ss';
        }
        
        $result = dbSelectOne($query, $params, $types);
        return $result['total'] ?? 0;
    }
    
    public static function getById($id) {
        $query = "SELECT d.*, 
                         s.nomor_agenda, s.perihal,
                         u1.nama_lengkap as dari_user_nama,
                         u2.nama_lengkap as ke_user_nama
                  FROM disposisi d
                  JOIN surat s ON d.id_surat = s.id
                  JOIN users u1 ON d.dari_user_id = u1.id
                  JOIN users u2 ON d.ke_user_id = u2.id
                  WHERE d.id = ?";
        
        return dbSelectOne($query, [$id], 'i');
    }
    
    /**
     * Create disposisi (dengan stakeholder tracking & notifikasi)
     */
    public static function create($data) {
        $query = "INSERT INTO disposisi (
                    id_surat, dari_user_id, ke_user_id, 
                    status_disposisi, catatan, tanggal_disposisi
                  ) VALUES (?, ?, ?, ?, ?, NOW())";
        
        $params = [
            $data['id_surat'],
            $data['dari_user_id'],
            $data['ke_user_id'],
            $data['status_disposisi'],
            $data['catatan']
        ];
        
        $result = dbExecute($query, $params, 'iiiss');
        
        if ($result) {
            $disposisiId = dbGetInsertId();
            
            // Add ke_user sebagai stakeholder
            self::addStakeholder($data['id_surat'], $data['ke_user_id'], 'penerima', $data['dari_user_id']);
            
            // Send notification
            if (file_exists(__DIR__ . '/../notifications/notification_service.php')) {
                require_once __DIR__ . '/../notifications/notification_service.php';
                NotificationService::notifyDisposisiBaru($disposisiId);
            }
            
            return $disposisiId;
        }
        
        return false;
    }
    
    /**
     * Update status disposisi (dengan auto-clear notifikasi jika selesai)
     */
    public static function updateStatus($id, $status, $catatan = null) {
        $query = "UPDATE disposisi 
                  SET status_disposisi = ?, 
                      catatan = COALESCE(?, catatan),
                      tanggal_respon = NOW()
                  WHERE id = ?";
        
        $result = dbExecute($query, [$status, $catatan, $id], 'ssi');
        
        if ($result) {
            // Get surat_id
            $disposisi = self::getById($id);
            
            // Jika status jadi selesai, clear notifications
            if ($status === 'selesai' && $disposisi) {
                if (file_exists(__DIR__ . '/../notifications/notification_service.php')) {
                    require_once __DIR__ . '/../notifications/notification_service.php';
                    NotificationService::clearBySurat($disposisi['id_surat']);
                    NotificationService::deactivateStakeholders($disposisi['id_surat']);
                }
                
                // Update status surat juga jadi "disetujui"
                require_once __DIR__ . '/../surat/surat_service.php';
                SuratService::updateStatus($disposisi['id_surat'], 'disetujui');
            }
            
            // Send notification update status
            if (file_exists(__DIR__ . '/../notifications/notification_service.php')) {
                require_once __DIR__ . '/../notifications/notification_service.php';
                NotificationService::notifySuratUpdate($id, $status);
            }
        }
        
        return $result;
    }
    
    /**
     * Get history disposisi by surat
     */
    public static function getHistoryBySurat($suratId) {
        $query = "SELECT d.*, 
                         u1.nama_lengkap as dari_user_nama,
                         u1.id_role as dari_user_role,
                         u2.nama_lengkap as ke_user_nama,
                         u2.id_role as ke_user_role
                  FROM disposisi d
                  JOIN users u1 ON d.dari_user_id = u1.id
                  JOIN users u2 ON d.ke_user_id = u2.id
                  WHERE d.id_surat = ?
                  ORDER BY d.tanggal_disposisi DESC";
        
        return dbSelect($query, [$suratId], 'i');
    }
    
    /**
     * Cek apakah user bisa disposisi surat
     */
    public static function canDispose($userId, $suratId) {
        $query = "SELECT COUNT(*) as total 
                  FROM surat_stakeholders 
                  WHERE user_id = ? AND surat_id = ? AND is_active = 1";
        
        $result = dbSelectOne($query, [$userId, $suratId], 'ii');
        return ($result['total'] ?? 0) > 0;
    }
    
    /**
     * Add stakeholder untuk surat
     */
    private static function addStakeholder($suratId, $userId, $roleType, $assignedBy = null) {
        // Check if already exists
        $existing = dbSelectOne(
            "SELECT id FROM surat_stakeholders WHERE surat_id = ? AND user_id = ?",
            [$suratId, $userId],
            'ii'
        );
        
        if ($existing) {
            return true; // Already a stakeholder
        }
        
        $query = "INSERT INTO surat_stakeholders (surat_id, user_id, role_type, assigned_by, assigned_at, is_active) 
                  VALUES (?, ?, ?, ?, NOW(), 1)";
        
        return dbExecute($query, [$suratId, $userId, $roleType, $assignedBy], 'iisi');
    }
}