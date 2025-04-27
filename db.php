<?php
// db.php

$host = 'localhost'; // atau alamat IP server database Anda
$dbname = 'inventory_db';
$username = 'root'; // username default XAMPP/MAMP
$password = ''; // password default XAMPP/MAMP (kosong)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // Set error mode ke exception untuk penanganan error yang lebih baik
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set fetch mode default ke associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Jika koneksi gagal, tampilkan pesan error dan hentikan script
    die("Koneksi database gagal: " . $e->getMessage());
}

// Anda bisa menambahkan fungsi atau konfigurasi lain di sini jika diperlukan
?>