<?php
/**
 * Migration: Menambahkan kolom status_sebelum_arsip ke tabel surat
 * Jalankan file ini sekali untuk menambahkan kolom
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "<h2>Migration: Add status_sebelum_arsip column</h2>";

try {
    // Cek apakah kolom sudah ada
    $checkColumn = "SELECT COLUMN_NAME 
                    FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'surat' 
                    AND COLUMN_NAME = 'status_sebelum_arsip'";
    
    $result = dbSelectOne($checkColumn);
    
    if ($result) {
        echo "<p style='color: blue;'>✓ Kolom status_sebelum_arsip sudah ada.</p>";
    } else {
        // Tambahkan kolom baru
        $addColumn = "ALTER TABLE surat 
                      ADD COLUMN status_sebelum_arsip VARCHAR(20) NULL DEFAULT NULL 
                      AFTER status_surat";
        
        dbQuery($addColumn);
        echo "<p style='color: green;'>✓ Kolom status_sebelum_arsip berhasil ditambahkan!</p>";
    }
    
    // Update surat yang sudah diarsipkan (set status_sebelum_arsip = 'baru' sebagai default)
    $updateExisting = "UPDATE surat 
                       SET status_sebelum_arsip = 'baru' 
                       WHERE status_surat = 'arsip' 
                       AND status_sebelum_arsip IS NULL";
    dbQuery($updateExisting);
    echo "<p style='color: green;'>✓ Surat arsip existing sudah diupdate dengan default 'baru'.</p>";
    
    echo "<br><p><strong>Migration selesai!</strong></p>";
    echo "<p><a href='../public/arsip_surat.php'>← Kembali ke Arsip Surat</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
