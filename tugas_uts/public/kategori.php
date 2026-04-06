<?php
require_once '../config/database.php';

$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

if ($slug) {
    // Menampilkan artikel dalam kategori tertentu
    $stmt = $pdo->prepare("SELECT id, nama FROM kategori WHERE slug = ?");
    $stmt->execute([$slug]);
    $kategori = $stmt->fetch();
    if (!$kategori) {
        header('Location: kategori.php');
        exit;
    }
    $page_title = 'Kategori: ' . htmlspecialchars($kategori['nama']) . ' - DynamicPost';
    include 'includes/header.php';
    ?>
    <h2>Artikel dalam Kategori: <?= htmlspecialchars($kategori['nama']) ?></h2>
    <?php
    $stmt = $pdo->prepare("SELECT a.*, u.username FROM artikel a
                           LEFT JOIN users u ON a.penulis_id = u.id
                           WHERE a.kategori_id = ? AND a.status = 'published'
                           ORDER BY a.created_at DESC");
    $stmt->execute([$kategori['id']]);
    $artikel = $stmt->fetchAll();
    if ($artikel) {
        foreach ($artikel as $art) {
            ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h3 class="card-title"><a href="detail.php?slug=<?= $art['slug'] ?>"><?= htmlspecialchars($art['judul']) ?></a></h3>
                    <p class="card-text text-muted">Oleh <?= htmlspecialchars($art['username']) ?> | <?= date('d-m-Y', strtotime($art['created_at'])) ?></p>
                    <p><?= nl2br(htmlspecialchars(substr($art['konten'], 0, 200))) ?>...</p>
                </div>
            </div>
            <?php
        }
    } else {
        echo "<p>Tidak ada artikel dalam kategori ini.</p>";
    }
} else {
    // Menampilkan semua kategori
    $page_title = 'Daftar Kategori - DynamicPost';
    include 'includes/header.php';
    $kategori = $pdo->query("SELECT k.*, COUNT(a.id) as jumlah 
                             FROM kategori k 
                             LEFT JOIN artikel a ON k.id = a.kategori_id AND a.status='published'
                             GROUP BY k.id
                             ORDER BY k.nama")->fetchAll();
    ?>
    <h2>Daftar Kategori</h2>
    <ul class="list-group">
        <?php foreach ($kategori as $kat): ?>
            <li class="list-group-item">
                <a href="kategori.php?slug=<?= $kat['slug'] ?>"><?= htmlspecialchars($kat['nama']) ?></a>
                <span class="badge badge-primary float-right"><?= $kat['jumlah'] ?> artikel</span>
                <?php if ($kat['deskripsi']): ?>
                    <br><small><?= htmlspecialchars($kat['deskripsi']) ?></small>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
}
include 'includes/footer.php';
?>