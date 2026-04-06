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

// Ambil data kategori
$stmt = $pdo->prepare("SELECT * FROM kategori WHERE id = ?");
$stmt->execute([$id]);
$kategori = $stmt->fetch();

if (!$kategori) {
    header('Location: index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = trim($_POST['nama']);
    $deskripsi = trim($_POST['deskripsi']);
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nama)));
    
    if (empty($nama)) {
        $errors[] = "Nama kategori wajib diisi.";
    } else {
        // Cek duplikat kecuali dirinya sendiri
        $stmt = $pdo->prepare("SELECT id FROM kategori WHERE (nama = ? OR slug = ?) AND id != ?");
        $stmt->execute([$nama, $slug, $id]);
        if ($stmt->fetch()) {
            $errors[] = "Kategori dengan nama atau slug tersebut sudah ada.";
        }
    }
    
    if (empty($errors)) {
        $sql = "UPDATE kategori SET nama = ?, slug = ?, deskripsi = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nama, $slug, $deskripsi, $id]);
        header('Location: index.php?success=updated');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Kategori - DynamicPost Admin</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <h1>Edit Kategori</h1>
    
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
            <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($kategori['nama']) ?>" required>
        </div>
        <div class="form-group">
            <label>Deskripsi (opsional)</label>
            <textarea name="deskripsi" class="form-control" rows="3"><?= htmlspecialchars($kategori['deskripsi']) ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Update</button>
        <a href="index.php" class="btn btn-secondary">Batal</a>
    </form>
</div>
</body>
</html>