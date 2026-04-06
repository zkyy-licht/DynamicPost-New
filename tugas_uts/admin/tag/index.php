<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

// Ambil semua tag beserta jumlah artikel yang menggunakan tag
$sql = "SELECT t.*, COUNT(at.artikel_id) as jumlah_artikel 
        FROM tag t 
        LEFT JOIN artikel_tag at ON t.id = at.tag_id 
        GROUP BY t.id 
        ORDER BY t.nama ASC";
$stmt = $pdo->query($sql);
$tags = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Tag - DynamicPost Admin</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <h1>Manajemen Tag</h1>
    <a href="tambah.php" class="btn btn-primary mb-3">Tambah Tag Baru</a>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <?php 
                if ($_GET['success'] == 'added') echo "Tag berhasil ditambahkan.";
                elseif ($_GET['success'] == 'updated') echo "Tag berhasil diperbarui.";
                elseif ($_GET['success'] == 'deleted') echo "Tag berhasil dihapus.";
            ?>
        </div>
    <?php endif; ?>
    
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama Tag</th>
                <th>Slug</th>
                <th>Jumlah Artikel</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tags as $tag): ?>
            <tr>
                <td><?= $tag['id'] ?></td>
                <td><?= htmlspecialchars($tag['nama']) ?></td>
                <td><?= htmlspecialchars($tag['slug']) ?></td>
                <td><?= $tag['jumlah_artikel'] ?></td>
                <td>
                    <a href="edit.php?id=<?= $tag['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                    <a href="hapus.php?id=<?= $tag['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus tag ini? Semua relasi artikel akan terputus (cascade).')">Hapus</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($tags)): ?>
            <tr><td colspan="5" class="text-center">Belum ada tag.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <a href="../dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a>
</div>
</body>
</html>