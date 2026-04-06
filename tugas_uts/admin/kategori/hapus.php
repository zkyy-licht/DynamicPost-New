<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}
$id = (int)$_GET['id'];

// Cek apakah kategori memiliki artikel
$stmt = $pdo->prepare("SELECT COUNT(*) FROM artikel WHERE kategori_id = ?");
$stmt->execute([$id]);
$jumlah_artikel = $stmt->fetchColumn();

if ($jumlah_artikel > 0) {
    // Masih ada artikel, redirect dengan error
    header('Location: index.php?error=has_articles');
    exit;
}

// Hapus kategori
$stmt = $pdo->prepare("DELETE FROM kategori WHERE id = ?");
$stmt->execute([$id]);

header('Location: index.php?success=deleted');
exit;
?>