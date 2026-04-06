<?php
require_once '../config/database.php';

$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$page_title = 'Hasil Pencarian: ' . htmlspecialchars($keyword) . ' - DynamicPost';
include 'includes/header.php';
?>

<h2>Hasil Pencarian: "<?= htmlspecialchars($keyword) ?>"</h2>

<?php if ($keyword): ?>
    <?php
    $stmt = $pdo->prepare("SELECT a.*, u.username, k.nama as kategori_nama, k.slug as kategori_slug
                           FROM artikel a
                           LEFT JOIN users u ON a.penulis_id = u.id
                           LEFT JOIN kategori k ON a.kategori_id = k.id
                           WHERE a.status = 'published' AND (a.judul LIKE ? OR a.konten LIKE ?)
                           ORDER BY a.created_at DESC");
    $search = "%$keyword%";
    $stmt->execute([$search, $search]);
    $results = $stmt->fetchAll();
    ?>
    <?php if (count($results) > 0): ?>
        <p>Ditemukan <?= count($results) ?> artikel.</p>
        <?php foreach ($results as $art): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h3 class="card-title"><a href="detail.php?slug=<?= $art['slug'] ?>"><?= htmlspecialchars($art['judul']) ?></a></h3>
                    <p class="card-text text-muted">
                        Oleh <?= htmlspecialchars($art['username']) ?> |
                        Kategori: <a href="artikel.php?kategori=<?= $art['kategori_slug'] ?>"><?= htmlspecialchars($art['kategori_nama']) ?></a> |
                        Tanggal: <?= date('d-m-Y', strtotime($art['created_at'])) ?>
                    </p>
                    <p><?= nl2br(htmlspecialchars(substr($art['konten'], 0, 200))) ?>...</p>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Tidak ada artikel yang cocok dengan kata kunci tersebut.</p>
    <?php endif; ?>
<?php else: ?>
    <p>Silakan masukkan kata kunci pencarian.</p>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>