<?php
// Ambil data statistik ringkas
$total_artikel = $pdo->query("SELECT COUNT(*) FROM artikel WHERE status='published'")->fetchColumn();
$total_komentar = $pdo->query("SELECT COUNT(*) FROM komentar WHERE status='approved'")->fetchColumn();
$total_kategori = $pdo->query("SELECT COUNT(*) FROM kategori")->fetchColumn();
$total_tag = $pdo->query("SELECT COUNT(*) FROM tag")->fetchColumn();
?>

<div class="card mb-4">
    <div class="card-header bg-primary text-white">Statistik Blog</div>
    <div class="card-body">
        <ul class="list-unstyled">
            <li><strong>Total Artikel:</strong> <?= $total_artikel ?></li>
            <li><strong>Total Komentar:</strong> <?= $total_komentar ?></li>
            <li><strong>Total Kategori:</strong> <?= $total_kategori ?></li>
            <li><strong>Total Tag:</strong> <?= $total_tag ?></li>
        </ul>
    </div>
</div>

<?php
// Artikel terpopuler berdasarkan views
$populer = $pdo->query("SELECT id, judul, slug, views FROM artikel WHERE status='published' ORDER BY views DESC LIMIT 5")->fetchAll();
if ($populer): ?>
<div class="card mb-4">
    <div class="card-header bg-success text-white">Artikel Terpopuler</div>
    <div class="card-body">
        <ul class="list-unstyled">
            <?php foreach ($populer as $art): ?>
                <li><a href="detail.php?slug=<?= $art['slug'] ?>"><?= htmlspecialchars($art['judul']) ?></a> (<?= $art['views'] ?> views)</li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<?php
// Daftar kategori dengan jumlah artikel
$kategori = $pdo->query("SELECT k.nama, k.slug, COUNT(a.id) as jumlah 
                         FROM kategori k 
                         LEFT JOIN artikel a ON k.id = a.kategori_id AND a.status='published'
                         GROUP BY k.id
                         ORDER BY jumlah DESC LIMIT 10")->fetchAll();
if ($kategori): ?>
<div class="card mb-4">
    <div class="card-header bg-warning text-dark">Kategori</div>
    <div class="card-body">
        <ul class="list-unstyled">
            <?php foreach ($kategori as $kat): ?>
                <li><a href="kategori.php?slug=<?= $kat['slug'] ?>"><?= htmlspecialchars($kat['nama']) ?></a> (<?= $kat['jumlah'] ?>)</li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<?php
// Tag populer (tag cloud sederhana)
$tag_populer = $pdo->query("SELECT t.nama, t.slug, COUNT(at.artikel_id) as jumlah 
                            FROM tag t 
                            LEFT JOIN artikel_tag at ON t.id = at.tag_id
                            LEFT JOIN artikel a ON at.artikel_id = a.id AND a.status='published'
                            GROUP BY t.id
                            HAVING jumlah > 0
                            ORDER BY jumlah DESC LIMIT 20")->fetchAll();
if ($tag_populer): ?>
<div class="card mb-4">
    <div class="card-header bg-info text-white">Tag Populer</div>
    <div class="card-body">
        <?php foreach ($tag_populer as $tag): ?>
            <a href="tag.php?slug=<?= $tag['slug'] ?>" class="badge badge-secondary mr-1 mb-1" style="font-size: <?= 12 + min(24, $tag['jumlah']) ?>px;">
                <?= htmlspecialchars($tag['nama']) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>