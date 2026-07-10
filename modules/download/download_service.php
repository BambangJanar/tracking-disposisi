<?php
// modules/download/download_service.php

require_once __DIR__ . '/../../config/database.php';

class DownloadService
{

    /**
     * Catat log download/view file ke database
     */
    public static function logDownload($userId, $suratId, $namaFile, $aksi = 'download', $ipAddress = null, $userAgent = null)
    {
        $query = "INSERT INTO log_download (user_id, surat_id, nama_file, aksi, ip_address, user_agent) 
                  VALUES (?, ?, ?, ?, ?, ?)";

        try {
            return dbExecute($query, [$userId, $suratId, $namaFile, $aksi, $ipAddress, $userAgent], 'iissss');
        } catch (Exception $e) {
            error_log("Failed to log download: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ambil log download berdasarkan surat_id
     */
    public static function getLogBySurat($suratId, $limit = 20)
    {
        $query = "SELECT ld.*, u.nama_lengkap, u.email, r.nama_role
                  FROM log_download ld
                  JOIN users u ON ld.user_id = u.id
                  JOIN roles r ON u.id_role = r.id
                  WHERE ld.surat_id = ?
                  ORDER BY ld.created_at DESC
                  LIMIT ?";

        return dbSelect($query, [$suratId, $limit], 'ii');
    }

    /**
     * Ambil semua log download dengan filter
     */
    public static function getAll($filters = [], $limit = 10, $offset = 0)
    {
        $sql = "SELECT ld.*, 
                       u.nama_lengkap, u.email, r.nama_role,
                       s.nomor_agenda, s.nomor_surat, s.perihal
                FROM log_download ld
                JOIN users u ON ld.user_id = u.id
                JOIN roles r ON u.id_role = r.id
                JOIN surat s ON ld.surat_id = s.id
                WHERE 1=1";

        $params = [];
        $types = "";

        // Filter pencarian
        if (!empty($filters['search'])) {
            $search = "%" . $filters['search'] . "%";
            $sql .= " AND (u.nama_lengkap LIKE ? OR s.nomor_agenda LIKE ? OR s.perihal LIKE ? OR ld.nama_file LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $types .= "ssss";
        }

        // Filter user
        if (!empty($filters['user_id'])) {
            $sql .= " AND ld.user_id = ?";
            $params[] = $filters['user_id'];
            $types .= "i";
        }

        // Filter aksi
        if (!empty($filters['aksi'])) {
            $sql .= " AND ld.aksi = ?";
            $params[] = $filters['aksi'];
            $types .= "s";
        }

        // Filter tanggal
        if (!empty($filters['tanggal_dari'])) {
            $sql .= " AND DATE(ld.created_at) >= ?";
            $params[] = $filters['tanggal_dari'];
            $types .= "s";
        }
        if (!empty($filters['tanggal_sampai'])) {
            $sql .= " AND DATE(ld.created_at) <= ?";
            $params[] = $filters['tanggal_sampai'];
            $types .= "s";
        }

        $sql .= " ORDER BY ld.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        return dbSelect($sql, $params, $types);
    }

    /**
     * Hitung total log untuk pagination
     */
    public static function count($filters = [])
    {
        $sql = "SELECT COUNT(*) as total
                FROM log_download ld
                JOIN users u ON ld.user_id = u.id
                JOIN surat s ON ld.surat_id = s.id
                WHERE 1=1";

        $params = [];
        $types = "";

        if (!empty($filters['search'])) {
            $search = "%" . $filters['search'] . "%";
            $sql .= " AND (u.nama_lengkap LIKE ? OR s.nomor_agenda LIKE ? OR s.perihal LIKE ? OR ld.nama_file LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $types .= "ssss";
        }

        if (!empty($filters['user_id'])) {
            $sql .= " AND ld.user_id = ?";
            $params[] = $filters['user_id'];
            $types .= "i";
        }

        if (!empty($filters['aksi'])) {
            $sql .= " AND ld.aksi = ?";
            $params[] = $filters['aksi'];
            $types .= "s";
        }

        if (!empty($filters['tanggal_dari'])) {
            $sql .= " AND DATE(ld.created_at) >= ?";
            $params[] = $filters['tanggal_dari'];
            $types .= "s";
        }
        if (!empty($filters['tanggal_sampai'])) {
            $sql .= " AND DATE(ld.created_at) <= ?";
            $params[] = $filters['tanggal_sampai'];
            $types .= "s";
        }

        $result = dbSelectOne($sql, $params, $types);
        return $result['total'] ?? 0;
    }

    /**
     * Statistik download
     */
    public static function getStatistics()
    {
        $stats = [];

        // Total download keseluruhan
        $total = dbSelectOne("SELECT COUNT(*) as total FROM log_download");
        $stats['total'] = $total['total'] ?? 0;

        // Total download hari ini
        $today = dbSelectOne("SELECT COUNT(*) as total FROM log_download WHERE DATE(created_at) = CURDATE()");
        $stats['today'] = $today['total'] ?? 0;

        // Top 5 user paling banyak download
        $stats['top_users'] = dbSelect(
            "SELECT u.nama_lengkap, r.nama_role, COUNT(*) as total_download
             FROM log_download ld
             JOIN users u ON ld.user_id = u.id
             JOIN roles r ON u.id_role = r.id
             GROUP BY ld.user_id
             ORDER BY total_download DESC
             LIMIT 5"
        );

        // Top 5 file paling sering didownload
        $stats['top_files'] = dbSelect(
            "SELECT s.nomor_agenda, s.perihal, COUNT(*) as total_download
             FROM log_download ld
             JOIN surat s ON ld.surat_id = s.id
             GROUP BY ld.surat_id
             ORDER BY total_download DESC
             LIMIT 5"
        );

        return $stats;
    }

    /**
     * Ambil daftar user yang pernah download (untuk filter dropdown)
     */
    public static function getDownloadUsers()
    {
        return dbSelect(
            "SELECT DISTINCT u.id, u.nama_lengkap, r.nama_role
             FROM log_download ld
             JOIN users u ON ld.user_id = u.id
             JOIN roles r ON u.id_role = r.id
             ORDER BY u.nama_lengkap ASC"
        );
    }

    /**
     * Dapatkan IP Address pengguna
     */
    public static function getClientIp()
    {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];

        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
