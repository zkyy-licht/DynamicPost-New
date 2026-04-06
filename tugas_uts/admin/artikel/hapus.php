<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}
$id = (int)$_GET['id'];

// Ambil data artikel untuk cek kepemilikan
$sql = "SELECT * FROM artikel WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$artikel = $stmt->fetch();

if (!$artikel) {
    header('Location: index.php');
    exit;
}

// Author hanya bisa hapus artikel miliknya
if ($role == 'author' && $artikel['penulis_id'] != $user_id) {
    header('Location: index.php');
    exit;
}

// Hapus file gambar jika ada
if ($artikel['gambar']) {
    $gambar_path = '../../uploads/' . $artikel['gambar'];
    if (file_exists($gambar_path)) {
        unlink($gambar_path);
    }
}

// Hapus artikel (relasi ke artikel_tag dan komentar akan otomatis terhapus karena ON DELETE CASCADE)
$sql_del = "DELETE FROM artikel WHERE id = ?";
$stmt_del = $pdo->prepare($sql_del);
$stmt_del->execute([$id]);

header('Location: index.php?success=deleted');
exit;
?>