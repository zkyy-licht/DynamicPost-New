<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Ambil daftar kategori untuk dropdown
$stmt_kategori = $pdo->query("SELECT id, nama FROM kategori ORDER BY nama");
$kategori_list = $stmt_kategori->fetchAll();

// Ambil daftar tag untuk checkbox
$stmt_tag = $pdo->query("SELECT id, nama FROM tag ORDER BY nama");
$tag_list = $stmt_tag->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = trim($_POST['judul']);
    $konten = $_POST['konten'];
    $kategori_id = $_POST['kategori_id'];
    $status = $_POST['status'];
    $tags = isset($_POST['tags']) ? $_POST['tags'] : []; // array tag_id
    
    // Validasi
    if (empty($judul)) $errors[] = "Judul wajib diisi.";
    if (empty($konten)) $errors[] = "Konten wajib diisi.";
    if (empty($kategori_id)) $errors[] = "Kategori wajib dipilih.";
    
    // Buat slug dari judul
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $judul)));
    
    // Upload gambar (opsional)
    $gambar = null;
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $target_dir = "../../uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext;
        $target_file = $target_dir . $filename;
        if (move_uploaded_file($_FILES['gambar']['tmp_name'], $target_file)) {
            $gambar = $filename;
        } else {
            $errors[] = "Gagal mengupload gambar.";
        }
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Insert artikel
            $sql = "INSERT INTO artikel (judul, slug, konten, gambar, penulis_id, kategori_id, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$judul, $slug, $konten, $gambar, $user_id, $kategori_id, $status]);
            $artikel_id = $pdo->lastInsertId();
            
            // Insert relasi tag
            if (!empty($tags)) {
                $sql_tag = "INSERT INTO artikel_tag (artikel_id, tag_id) VALUES (?, ?)";
                $stmt_tag = $pdo->prepare($sql_tag);
                foreach ($tags as $tag_id) {
                    $stmt_tag->execute([$artikel_id, $tag_id]);
                }
            }
            
            $pdo->commit();
            header('Location: index.php?success=added');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Artikel - DynamicPost Admin</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
</head>
<body>
<div class="container mt-4">
    <h1>Tambah Artikel Baru</h1>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?= $err ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Judul *</label>
            <input type="text" name="judul" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Konten *</label>
            <textarea name="konten" id="editor" class="form-control" rows="10"></textarea>
        </div>
        <div class="form-group">
            <label>Kategori *</label>
            <select name="kategori_id" class="form-control" required>
                <option value="">-- Pilih Kategori --</option>
                <?php foreach ($kategori_list as $kat): ?>
                    <option value="<?= $kat['id'] ?>"><?= htmlspecialchars($kat['nama']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Tag</label><br>
            <?php foreach ($tag_list as $tag): ?>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="tags[]" value="<?= $tag['id'] ?>" id="tag_<?= $tag['id'] ?>">
                    <label class="form-check-label" for="tag_<?= $tag['id'] ?>"><?= htmlspecialchars($tag['nama']) ?></label>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-control">
                <option value="draft">Draft</option>
                <option value="published">Published</option>
            </select>
        </div>
        <div class="form-group">
            <label>Gambar (opsional)</label>
            <input type="file" name="gambar" class="form-control-file">
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="index.php" class="btn btn-secondary">Batal</a>
    </form>
</div>
<script>
    CKEDITOR.replace('editor');
</script>
</body>
</html>
