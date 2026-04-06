<?php
require_once '../config/database.php';
$page_title = 'Semua Artikel - DynamicPost';
include 'includes/header.php';

// Ambil parameter filter
$kategori_slug = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$tag_slug = isset($_GET['tag']) ? $_GET['tag'] : '';

// Build query
$sql = "SELECT a.*, u.username, k.nama as kategori_nama, k.slug as kategori_slug
        FROM artikel a 
        LEFT JOIN users u ON a.penulis_id = u.id 
        LEFT JOIN kategori k ON a.kategori_id = k.id 
        WHERE a.status = 'published'";
$params = [];

if ($kategori_slug) {
    $sql .= " AND k.slug = ?";
    $params[] = $kategori_slug;
} elseif ($tag_slug) {
    $sql .= " AND a.id IN (SELECT artikel_id FROM artikel_tag at JOIN tag t ON at.tag_id = t.id WHERE t.slug = ?)";
    $params[] = $tag_slug;
}

$sql .= " ORDER BY a.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$artikel = $stmt->fetchAll();
?>

<h2>
    <?php if ($kategori_slug): ?>
        Artikel Kategori: <?= htmlspecialchars($kategori_slug) ?>
    <?php elseif ($tag_slug): ?>
        Artikel dengan Tag: <?= htmlspecialchars($tag_slug) ?>
    <?php else: ?>
        Semua Artikel
    <?php endif; ?>
</h2>

<?php if (empty($artikel)): ?>
    <p>Tidak ada artikel ditemukan.</p>
<?php else: ?>
    <?php foreach ($artikel as $art): ?>
        <div class="card mb-3">
            <div class="card-body">
                <h3 class="card-title"><a href="detail.php?slug=<?= $art['slug'] ?>"><?= htmlspecialchars($art['judul']) ?></a></h3>
                <p class="card-text text-muted">
                    Oleh <?= htmlspecialchars($art['username']) ?> | 
                    Kategori: <a href="artikel.php?kategori=<?= $art['kategori_slug'] ?>"><?= htmlspecialchars($art['kategori_nama']) ?></a> |
                    Tanggal: <?= date('d-m-Y', strtotime($art['created_at'])) ?>
                </p>
                <p class="card-text"><?= nl2br(htmlspecialchars(substr($art['konten'], 0, 200))) ?>...</p>
                <a href="detail.php?slug=<?= $art['slug'] ?>" class="btn btn-primary">Baca Selengkapnya</a>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>