<?php
/**
 * index.php - Root redirect file
 * 
 * File ini akan mengarahkan user ke halaman login di public/login.php
 * Jika user sudah login, akan diarahkan ke dashboard
 */

// Mulai session untuk cek login status
session_start();

// Tentukan base path
$basePath = __DIR__;

// Load konfigurasi minimal
require_once $basePath . '/config/config.php';

// Cek apakah user sudah login (cek session)
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    // Jika sudah login, redirect ke dashboard
    header('Location: public/index.php');
    exit;
} else {
    // Jika belum login, redirect ke halaman login
    header('Location: public/login.php');
    exit;
}