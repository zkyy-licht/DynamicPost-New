<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $artikel_id = (int)$_POST['artikel_id'];
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email'] ?? '');
    $isi = trim($_POST['isi']);

    if (empty($nama) || empty($isi)) {
        // Redirect dengan error
        $stmt = $pdo->prepare("SELECT slug FROM artikel WHERE id = ?");
        $stmt->execute([$artikel_id]);
        $artikel = $stmt->fetch();
        header("Location: detail.php?slug=" . $artikel['slug'] . "&error=empty");
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO komentar (artikel_id, nama, email, isi, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([$artikel_id, $nama, $email, $isi]);

    $stmt = $pdo->prepare("SELECT slug FROM artikel WHERE id = ?");
    $stmt->execute([$artikel_id]);
    $artikel = $stmt->fetch();
    header("Location: detail.php?slug=" . $artikel['slug'] . "&msg=success");
    exit;
}
?>