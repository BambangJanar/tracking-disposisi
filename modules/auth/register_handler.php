<?php
// modules/auth/register_handler.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/helpers.php';

header('Content-Type: application/json');

// Hanya terima POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request Method']);
    exit;
}

$conn = getConnection();

// Ambil dan bersihkan input
$nama = sanitize($_POST['nama_lengkap'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validasi input kosong
if (empty($nama) || empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Semua kolom wajib diisi!']);
    exit;
}

// 1. Cek apakah Email sudah terdaftar
$check = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Email sudah terdaftar! Gunakan email lain.']);
    exit;
}

// 2. Siapkan Password
$finalPassword = $password; 

// 3. Tentukan Role Default (3 = User Biasa / Magang)
$defaultRoleId = 3; 

// 4. Insert Data User Baru
// status_aktif = 0 (Nonaktif), status = 'pending'
$query = "INSERT INTO users (nama_lengkap, email, password, id_role, status_aktif, status) 
          VALUES (?, ?, ?, ?, 0, 'pending')";

$stmt = $conn->prepare($query);

if ($stmt) {
    // PERBAIKAN DI SINI:
    // Gunakan "sssi" (4 karakter) karena ada 4 variabel:
    // 1. $nama (string)
    // 2. $email (string)
    // 3. $finalPassword (string)
    // 4. $defaultRoleId (integer)
    $stmt->bind_param("sssi", $nama, $email, $finalPassword, $defaultRoleId);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data: ' . $stmt->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $conn->error]);
}
?>