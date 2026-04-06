<?php
// public/detail.php
require_once '../config/database.php';

// Ambil slug dari URL, misal: detail.php?slug=judul-artikel
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

if (empty($slug)) {
    header('Location: index.php');
    exit;
}

// Ambil data artikel berdasarkan slug, hanya yang status published
$stmt = $pdo->prepare("SELECT a.*, u.username as penulis, k.nama as kategori_nama 
                        FROM artikel a
                        LEFT JOIN users u ON a.penulis_id = u.id
                        LEFT JOIN kategori k ON a.kategori_id = k.id
                        WHERE a.slug = ? AND a.status = 'published'");
$stmt->execute([$slug]);
$artikel = $stmt->fetch();

if (!$artikel) {
    // Artikel tidak ditemukan
    header('HTTP/1.0 404 Not Found');
    echo "Artikel tidak ditemukan";
    exit;
}

// Increment views (opsional)
$stmt = $pdo->prepare("UPDATE artikel SET views = views + 1 WHERE id = ?");
$stmt->execute([$artikel['id']]);

// Ambil komentar yang sudah disetujui
$stmt = $pdo->prepare("SELECT * FROM komentar WHERE artikel_id = ? AND status = 'approved' ORDER BY created_at DESC");
$stmt->execute([$artikel['id']]);
$komentar = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($artikel['judul']) ?> - DynamicPost</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <article>
            <h1><?= htmlspecialchars($artikel['judul']) ?></h1>
            <p class="text-muted">
                Oleh <?= htmlspecialchars($artikel['penulis']) ?> | 
                Kategori: <a href="kategori.php?slug=<?= $artikel['kategori_nama'] ?>"><?= htmlspecialchars($artikel['kategori_nama']) ?></a> |
                Tanggal: <?= date('d-m-Y', strtotime($artikel['created_at'])) ?> |
                Dilihat: <?= $artikel['views'] ?> kali
            </p>
            <div class="content">
                <?= nl2br(htmlspecialchars($artikel['konten'])) ?>
            </div>
        </article>

        <!-- Bagian Komentar -->
        <hr>
        <h3>Komentar (<?= count($komentar) ?>)</h3>
        <?php if (count($komentar) > 0): ?>
            <?php foreach ($komentar as $komen): ?>
                <div class="card mb-2">
                    <div class="card-body">
                        <h6 class="card-title"><?= htmlspecialchars($komen['nama']) ?></h6>
                        <p class="card-text"><?= nl2br(htmlspecialchars($komen['isi'])) ?></p>
                        <small class="text-muted"><?= date('d-m-Y H:i', strtotime($komen['created_at'])) ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Belum ada komentar. Jadilah yang pertama!</p>
        <?php endif; ?>

        <!-- Form Tambah Komentar -->
        <h4>Tinggalkan Komentar</h4>
        <form action="proses_komentar.php" method="POST">
            <input type="hidden" name="artikel_id" value="<?= $artikel['id'] ?>">
            <div class="form-group">
                <label>Nama *</label>
                <input type="text" name="nama" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Email (opsional)</label>
                <input type="email" name="email" class="form-control">
            </div>
            <div class="form-group">
                <label>Komentar *</label>
                <textarea name="isi" class="form-control" rows="5" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Kirim Komentar</button>
        </form>

        <a href="index.php" class="btn btn-secondary mt-3">Kembali ke Beranda</a>
    </div>
</body>
</html>