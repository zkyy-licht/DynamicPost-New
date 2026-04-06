<?php
// public/proses_komentar.php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $artikel_id = (int)$_POST['artikel_id'];
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email'] ?? '');
    $isi = trim($_POST['isi']);

    // Validasi sederhana
    if (empty($nama) || empty($isi)) {
        // redirect dengan error
        header("Location: detail.php?slug=error");
        exit;
    }

    // Simpan komentar dengan status pending (perlu moderasi)
    $stmt = $pdo->prepare("INSERT INTO komentar (artikel_id, nama, email, isi, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([$artikel_id, $nama, $email, $isi]);

    // Redirect kembali ke halaman artikel dengan pesan sukses
    // Ambil slug artikel untuk redirect
    $stmt = $pdo->prepare("SELECT slug FROM artikel WHERE id = ?");
    $stmt->execute([$artikel_id]);
    $artikel = $stmt->fetch();
    header("Location: detail.php?slug=" . $artikel['slug'] . "&msg=success");
    exit;
}
?>