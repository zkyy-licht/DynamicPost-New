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

// Hapus tag (relasi di artikel_tag akan otomatis terhapus karena ON DELETE CASCADE)
$stmt = $pdo->prepare("DELETE FROM tag WHERE id = ?");
$stmt->execute([$id]);

header('Location: index.php?success=deleted');
exit;
?>