<?php
require_once '../config/database.php';

$slug = isset($_GET['slug']) ? $_GET['slug'] : '';
if (!$slug) {
    header('Location: index.php');
    exit;
}

// Ambil artikel
$stmt = $pdo->prepare("SELECT a.*, u.username as penulis, k.nama as kategori_nama, k.slug as kategori_slug
                       FROM artikel a
                       LEFT JOIN users u ON a.penulis_id = u.id
                       LEFT JOIN kategori k ON a.kategori_id = k.id
                       WHERE a.slug = ? AND a.status = 'published'");
$stmt->execute([$slug]);
$artikel = $stmt->fetch();

if (!$artikel) {
    header('HTTP/1.0 404 Not Found');
    echo "Artikel tidak ditemukan";
    exit;
}

// Increment views
$pdo->prepare("UPDATE artikel SET views = views + 1 WHERE id = ?")->execute([$artikel['id']]);

// Ambil komentar yang disetujui
$komentar = $pdo->prepare("SELECT * FROM komentar WHERE artikel_id = ? AND status = 'approved' ORDER BY created_at DESC");
$komentar->execute([$artikel['id']]);
$komentar_list = $komentar->fetchAll();

$page_title = htmlspecialchars($artikel['judul']) . ' - DynamicPost';
include 'includes/header.php';
?>

<h1><?= htmlspecialchars($artikel['judul']) ?></h1>
<p class="card-text"><?= nl2br(htmlspecialchars($komen['isi'])) ?></p>
<p class="text-muted">
    Oleh <?= htmlspecialchars($artikel['penulis']) ?> |
    Kategori: <a href="artikel.php?kategori=<?= $artikel['kategori_slug'] ?>"><?= htmlspecialchars($artikel['kategori_nama']) ?></a> |
    Tanggal: <?= date('d-m-Y', strtotime($artikel['created_at'])) ?> |
    Dibaca: <?= $artikel['views'] ?> kali
</p>
<div class="content">
    <?= nl2br(htmlspecialchars($artikel['konten'])) ?>
</div>

<hr>
<h3>Komentar (<?= count($komentar_list) ?>)</h3>
<?php if ($komentar_list): ?>
    <?php foreach ($komentar_list as $komen): ?>
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

<?php if (isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Komentar Anda telah dikirim dan akan ditampilkan setelah disetujui.
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php endif; ?>

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

<?php include 'includes/footer.php'; ?>