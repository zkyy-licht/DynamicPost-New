<?php
require_once '../config/database.php';

$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

if ($slug) {
    // Menampilkan artikel dengan tag tertentu
    $stmt = $pdo->prepare("SELECT id, nama FROM tag WHERE slug = ?");
    $stmt->execute([$slug]);
    $tag = $stmt->fetch();
    if (!$tag) {
        header('Location: tag.php');
        exit;
    }
    $page_title = 'Tag: ' . htmlspecialchars($tag['nama']) . ' - DynamicPost';
    include 'includes/header.php';
    ?>
    <h2>Artikel dengan Tag: <?= htmlspecialchars($tag['nama']) ?></h2>
    <?php
    $stmt = $pdo->prepare("SELECT a.*, u.username FROM artikel a
                           JOIN artikel_tag at ON a.id = at.artikel_id
                           LEFT JOIN users u ON a.penulis_id = u.id
                           WHERE at.tag_id = ? AND a.status = 'published'
                           ORDER BY a.created_at DESC");
    $stmt->execute([$tag['id']]);
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
        echo "<p>Tidak ada artikel dengan tag ini.</p>";
    }
} else {
    // Menampilkan semua tag (tag cloud)
    $page_title = 'Tag Cloud - DynamicPost';
    include 'includes/header.php';
    $tags = $pdo->query("SELECT t.nama, t.slug, COUNT(at.artikel_id) as jumlah 
                         FROM tag t 
                         LEFT JOIN artikel_tag at ON t.id = at.tag_id
                         LEFT JOIN artikel a ON at.artikel_id = a.id AND a.status='published'
                         GROUP BY t.id
                         HAVING jumlah > 0
                         ORDER BY t.nama")->fetchAll();
    ?>
    <h2>Tag Cloud</h2>
    <div class="tag-cloud">
        <?php foreach ($tags as $tag): ?>
            <a href="tag.php?slug=<?= $tag['slug'] ?>" class="badge badge-secondary mr-2 mb-2" style="font-size: <?= 12 + min(30, $tag['jumlah']) ?>px;">
                <?= htmlspecialchars($tag['nama']) ?> (<?= $tag['jumlah'] ?>)
            </a>
        <?php endforeach; ?>
    </div>
    <?php
}
include 'includes/footer.php';
?>