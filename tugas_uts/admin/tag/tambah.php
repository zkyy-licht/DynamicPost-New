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
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nama)));
    
    if (empty($nama)) {
        $errors[] = "Nama tag wajib diisi.";
    } else {
        // Cek duplikat
        $stmt = $pdo->prepare("SELECT id FROM tag WHERE nama = ? OR slug = ?");
        $stmt->execute([$nama, $slug]);
        if ($stmt->fetch()) {
            $errors[] = "Tag dengan nama atau slug tersebut sudah ada.";
        }
    }
    
    if (empty($errors)) {
        $sql = "INSERT INTO tag (nama, slug) VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nama, $slug]);
        header('Location: index.php?success=added');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Tag - DynamicPost Admin</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <h1>Tambah Tag Baru</h1>
    
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
            <label>Nama Tag *</label>
            <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="index.php" class="btn btn-secondary">Batal</a>
    </form>
</div>
</body>
</html>