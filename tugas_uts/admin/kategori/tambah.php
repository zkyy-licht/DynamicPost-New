<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = trim($_POST['nama']);
    $deskripsi = trim($_POST['deskripsi']);
    
    // Buat slug dari nama
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nama)));
    
    if (empty($nama)) {
        $errors[] = "Nama kategori wajib diisi.";
    } else {
        // Cek duplikat nama atau slug
        $stmt = $pdo->prepare("SELECT id FROM kategori WHERE nama = ? OR slug = ?");
        $stmt->execute([$nama, $slug]);
        if ($stmt->fetch()) {
            $errors[] = "Kategori dengan nama atau slug tersebut sudah ada.";
        }
    }
    
    if (empty($errors)) {
        $sql = "INSERT INTO kategori (nama, slug, deskripsi) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nama, $slug, $deskripsi]);
        header('Location: index.php?success=added');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Kategori - DynamicPost Admin</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <h1>Tambah Kategori Baru</h1>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?= $err ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>Nama Kategori *</label>
            <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>Deskripsi (opsional)</label>
            <textarea name="deskripsi" class="form-control" rows="3"><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="index.php" class="btn btn-secondary">Batal</a>
    </form>
</div>
</body>
</html>