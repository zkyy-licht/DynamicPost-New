<?php
session_start();
require_once '../../config/database.php';

// Cek login dan role (hanya admin yang boleh mengelola kategori)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

// Ambil semua kategori beserta jumlah artikel
$sql = "SELECT k.*, COUNT(a.id) as jumlah_artikel 
        FROM kategori k 
        LEFT JOIN artikel a ON k.id = a.kategori_id 
        GROUP BY k.id 
        ORDER BY k.nama ASC";
$stmt = $pdo->query($sql);
$kategori = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Kategori - DynamicPost Admin</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <h1>Manajemen Kategori</h1>
    <a href="tambah.php" class="btn btn-primary mb-3">Tambah Kategori Baru</a>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <?php 
                if ($_GET['success'] == 'added') echo "Kategori berhasil ditambahkan.";
                elseif ($_GET['success'] == 'updated') echo "Kategori berhasil diperbarui.";
                elseif ($_GET['success'] == 'deleted') echo "Kategori berhasil dihapus.";
            ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            <?php 
                if ($_GET['error'] == 'has_articles') echo "Kategori tidak dapat dihapus karena masih memiliki artikel.";
                else echo "Terjadi kesalahan.";
            ?>
        </div>
    <?php endif; ?>
    
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama Kategori</th>
                <th>Slug</th>
                <th>Deskripsi</th>
                <th>Jumlah Artikel</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($kategori as $kat): ?>
            <tr>
                <td><?= $kat['id'] ?></td>
                <td><?= htmlspecialchars($kat['nama']) ?></td>
                <td><?= htmlspecialchars($kat['slug']) ?></td>
                <td><?= htmlspecialchars($kat['deskripsi']) ?></td>
                <td><?= $kat['jumlah_artikel'] ?></td>
                <td>
                    <a href="edit.php?id=<?= $kat['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                    <a href="hapus.php?id=<?= $kat['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus kategori ini?')">Hapus</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($kategori)): ?>
            <tr><td colspan="6" class="text-center">Belum ada kategori.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <a href="../dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a>
</div>
</body>
</html>