<?php
session_start();
require_once '../../config/database.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Filter status (draft/published/semua)
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Query dasar
$sql = "SELECT a.*, u.username, k.nama as kategori_nama 
        FROM artikel a 
        LEFT JOIN users u ON a.penulis_id = u.id 
        LEFT JOIN kategori k ON a.kategori_id = k.id 
        WHERE 1=1";
$params = [];

// Filter berdasarkan role (author hanya melihat artikelnya sendiri)
if ($role == 'author') {
    $sql .= " AND a.penulis_id = ?";
    $params[] = $user_id;
}

// Filter status
if ($status_filter == 'draft') {
    $sql .= " AND a.status = 'draft'";
} elseif ($status_filter == 'published') {
    $sql .= " AND a.status = 'published'";
}

// Urutkan berdasarkan terbaru
$sql .= " ORDER BY a.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$articles = $stmt->fetchAll();

// Hitung total data untuk paginasi
$sql_count = "SELECT COUNT(*) FROM artikel WHERE 1=1";
$params_count = [];
if ($role == 'author') {
    $sql_count .= " AND penulis_id = ?";
    $params_count[] = $user_id;
}
if ($status_filter == 'draft') {
    $sql_count .= " AND status = 'draft'";
} elseif ($status_filter == 'published') {
    $sql_count .= " AND status = 'published'";
}
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params_count);
$total = $stmt_count->fetchColumn();
$total_pages = ceil($total / $limit);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Artikel - DynamicPost Admin</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <h1>Manajemen Artikel</h1>
    <a href="tambah.php" class="btn btn-primary mb-3">Tambah Artikel Baru</a>
    
    <!-- Filter Status -->
    <div class="mb-3">
        <a href="?status=" class="btn btn-secondary <?= $status_filter==''?'active':'' ?>">Semua</a>
        <a href="?status=draft" class="btn btn-secondary <?= $status_filter=='draft'?'active':'' ?>">Draft</a>
        <a href="?status=published" class="btn btn-secondary <?= $status_filter=='published'?'active':'' ?>">Published</a>
    </div>
    
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Judul</th>
                <th>Kategori</th>
                <th>Penulis</th>
                <th>Status</th>
                <th>Tanggal</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($articles as $art): ?>
            <tr>
                <td><?= $art['id'] ?></td>
                <td><?= htmlspecialchars($art['judul']) ?></td>
                <td><?= htmlspecialchars($art['kategori_nama']) ?></td>
                <td><?= htmlspecialchars($art['username']) ?></td>
                <td>
                    <?php if ($art['status'] == 'draft'): ?>
                        <span class="badge badge-warning">Draft</span>
                    <?php else: ?>
                        <span class="badge badge-success">Published</span>
                    <?php endif; ?>
                </td>
                <td><?= date('d-m-Y', strtotime($art['created_at'])) ?></td>
                <td>
                    <a href="edit.php?id=<?= $art['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                    <a href="hapus.php?id=<?= $art['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus?')">Hapus</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($articles)): ?>
            <tr><td colspan="7" class="text-center">Tidak ada artikel.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Paginasi -->
    <?php if ($total_pages > 1): ?>
    <nav>
        <ul class="pagination">
            <?php for ($i=1; $i<=$total_pages; $i++): ?>
                <li class="page-item <?= ($i==$page) ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&status=<?= $status_filter ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>
</body>
</html>