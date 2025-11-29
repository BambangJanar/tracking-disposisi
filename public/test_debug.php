<?php
// public/test_debug.php - Temporary debug page

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();

echo "<h1>Debug Information</h1>";

echo "<h2>1. Database Connection Test</h2>";
try {
    $conn = getConnection();
    echo "✓ Database connected successfully<br>";
    echo "Database name: " . DB_NAME . "<br>";
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "<br>";
}

echo "<h2>2. Upload Directory Test</h2>";
echo "UPLOAD_DIR: " . UPLOAD_DIR . "<br>";
echo "UPLOAD_DIR exists: " . (is_dir(UPLOAD_DIR) ? 'Yes' : 'No') . "<br>";
echo "UPLOAD_DIR writable: " . (is_writable(UPLOAD_DIR) ? 'Yes' : 'No') . "<br>";

if (!is_dir(UPLOAD_DIR)) {
    echo "<strong>Creating upload directory...</strong><br>";
    if (mkdir(UPLOAD_DIR, 0755, true)) {
        echo "✓ Directory created<br>";
    } else {
        echo "✗ Failed to create directory<br>";
    }
}

echo "<h2>3. Generate Nomor Agenda Test</h2>";
for ($i = 1; $i <= 3; $i++) {
    try {
        $nomor = generateNomorAgenda($i);
        echo "Jenis $i: $nomor<br>";
    } catch (Exception $e) {
        echo "Jenis $i: ERROR - " . $e->getMessage() . "<br>";
    }
}

echo "<h2>4. Test Insert Surat</h2>";
try {
    $testData = [
        'id_jenis' => 1,
        'nomor_agenda' => 'TEST/001/11/2025',
        'nomor_surat' => 'TEST/001',
        'tanggal_surat' => date('Y-m-d'),
        'tanggal_diterima' => null,
        'dari_instansi' => 'Test Instansi',
        'ke_instansi' => null,
        'alamat_surat' => 'Test Alamat',
        'perihal' => 'Test Perihal',
        'lampiran_file' => null,
        'status_surat' => 'baru',
        'dibuat_oleh' => getCurrentUser()['id']
    ];
    
    echo "Test data:<pre>";
    print_r($testData);
    echo "</pre>";
    
    $query = "INSERT INTO surat (
                id_jenis, nomor_agenda, nomor_surat, tanggal_surat, 
                tanggal_diterima, dari_instansi, ke_instansi, alamat_surat, 
                perihal, lampiran_file, status_surat, dibuat_oleh
              ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $params = [
        $testData['id_jenis'],
        $testData['nomor_agenda'],
        $testData['nomor_surat'],
        $testData['tanggal_surat'],
        $testData['tanggal_diterima'],
        $testData['dari_instansi'],
        $testData['ke_instansi'],
        $testData['alamat_surat'],
        $testData['perihal'],
        $testData['lampiran_file'],
        $testData['status_surat'],
        $testData['dibuat_oleh']
    ];
    
    $types = 'issssssssssi';
    
    $result = dbExecute($query, $params, $types);
    
    echo "✓ Test insert successful<br>";
    echo "Insert ID: " . $result['insert_id'] . "<br>";
    
    // Delete test data
    dbExecute("DELETE FROM surat WHERE id = ?", [$result['insert_id']], 'i');
    echo "✓ Test data cleaned up<br>";
    
} catch (Exception $e) {
    echo "✗ Test insert failed: " . $e->getMessage() . "<br>";
    echo "Stack trace:<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>5. Session Test</h2>";
echo "User logged in: " . (isLoggedIn() ? 'Yes' : 'No') . "<br>";
$user = getCurrentUser();
echo "User data:<pre>";
print_r($user);
echo "</pre>";

echo "<h2>6. BASE_URL Test</h2>";
echo "BASE_URL: " . BASE_URL . "<br>";

echo "<hr>";
echo "<a href='surat.php'>Back to Surat Page</a>";
?>