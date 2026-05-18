<?php
// koneksi.php — Database connection for LocalMart
// Place this file at the root of the project.
// Both /pembeli/ and /penjual/ folders include it with require_once '../koneksi.php'.

$host   = 'localhost';
$db     = 'db_localmart';
$user   = 'root';
$pass   = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // In production, log this error instead of displaying it
    die('Koneksi database gagal: ' . $e->getMessage());
}
