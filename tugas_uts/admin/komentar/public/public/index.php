<?php
require_once '../config/database.php';
$page_title = 'Beranda - DynamicPost';
include 'includes/header.php';

// Pagination
$limit = 6;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$stmt = $pdo->prepare("SELECT a.*, u.username, k.nama as kategori_nama 
                       FROM artikel a 
                       LEFT JOIN users u ON a.penulis_id = u.id 
                       LEFT JOIN kategori k ON a.kategori_id = k.id 
                       WHERE a.status = 'published' 
                       ORDER BY a.created_at DESC 
                       LIMIT ? OFFSET ?");
$stmt->execute([$limit, $offset]);
$artikel = $stmt->fetchAll();

// Hitung total
$total = $pdo->query("SELECT COUNT(*) FROM artikel WHERE status='published'")->fetchColumn();
$total_pages = ceil($total / $limit);
?>

<h2>Artikel Terbaru</h2>
<?php if (empty($artikel)): ?>
    <p>Belum ada artikel.</p>
<?php else: ?>
    <?php foreach ($artikel as $art): ?>
        <div class="card mb-3">
            <div class="card-body">
                <h3 class="card-title"><a href="detail.php?slug=<?= $art['slug'] ?>"><?= htmlspecialchars($art['judul']) ?></a></h3>
                <p class="card-text text-muted">
                    Oleh <?= htmlspecialchars($art['username']) ?> | 
                    Kategori: <a href="kategori.php?slug=<?= $art['kategori_nama'] ?>"><?= htmlspecialchars($art['kategori_nama']) ?></a> |
                    Tanggal: <?= date('d-m-Y', strtotime($art['created_at'])) ?>
                </p>
                <p class="card-text"><?= nl2br(htmlspecialchars(substr($art['konten'], 0, 200))) ?>...</p>
                <a href="detail.php?slug=<?= $art['slug'] ?>" class="btn btn-primary">Baca Selengkapnya</a>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <nav>
            <ul class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>