<?php
session_start();
require_once '../../config/database.php';

// Cek login (admin atau author)
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Filter status (default pending)
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'pending';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Proses aksi (approve, spam, delete)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $komentar_id = (int)$_GET['id'];
    
    // Cek apakah komentar ada
    $stmt = $pdo->prepare("SELECT * FROM komentar WHERE id = ?");
    $stmt->execute([$komentar_id]);
    $komentar = $stmt->fetch();
    
    if ($komentar) {
        // Untuk author, mungkin bisa dibatasi hanya komentar pada artikel miliknya (opsional)
        // Di sini kita izinkan semua komentar untuk author juga
        if ($action == 'approve') {
            $stmt = $pdo->prepare("UPDATE komentar SET status = 'approved' WHERE id = ?");
            $stmt->execute([$komentar_id]);
            $msg = "Komentar berhasil disetujui.";
        } elseif ($action == 'spam') {
            $stmt = $pdo->prepare("UPDATE komentar SET status = 'spam' WHERE id = ?");
            $stmt->execute([$komentar_id]);
            $msg = "Komentar ditandai sebagai spam.";
        } elseif ($action == 'delete') {
            $stmt = $pdo->prepare("DELETE FROM komentar WHERE id = ?");
            $stmt->execute([$komentar_id]);
            $msg = "Komentar berhasil dihapus.";
        }
        
        // Redirect kembali dengan pesan sukses
        header("Location: index.php?status=$status_filter&page=$page&success=" . urlencode($msg));
        exit;
    }
}

// Ambil komentar berdasarkan filter status dan paginasi
$sql = "SELECT k.*, a.judul as artikel_judul, a.slug as artikel_slug, u.username as penulis_artikel
        FROM komentar k
        JOIN artikel a ON k.artikel_id = a.id
        LEFT JOIN users u ON a.penulis_id = u.id
        WHERE 1=1";
$params = [];

if ($status_filter != 'all') {
    $sql .= " AND k.status = ?";
    $params[] = $status_filter;
}

// Urutkan dari terbaru
$sql .= " ORDER BY k.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$komentar_list = $stmt->fetchAll();

// Hitung total komentar untuk paginasi
$sql_count = "SELECT COUNT(*) FROM komentar k";
if ($status_filter != 'all') {
    $sql_count .= " WHERE k.status = ?";
    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute([$status_filter]);
} else {
    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute();
}
$total = $stmt_count->fetchColumn();
$total_pages = ceil($total / $limit);

// Pesan sukses
$success_msg = isset($_GET['success']) ? $_GET['success'] : '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Moderasi Komentar - DynamicPost Admin</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <h1>Moderasi Komentar</h1>
    
    <?php if ($success_msg): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($success_msg) ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>
    
    <!-- Filter Status -->
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link <?= $status_filter == 'pending' ? 'active' : '' ?>" href="?status=pending">
                Pending <span class="badge badge-secondary"><?= hitungStatus('pending', $pdo) ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $status_filter == 'approved' ? 'active' : '' ?>" href="?status=approved">
                Disetujui <span class="badge badge-secondary"><?= hitungStatus('approved', $pdo) ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $status_filter == 'spam' ? 'active' : '' ?>" href="?status=spam">
                Spam <span class="badge badge-secondary"><?= hitungStatus('spam', $pdo) ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $status_filter == 'all' ? 'active' : '' ?>" href="?status=all">
                Semua <span class="badge badge-secondary"><?= hitungStatus('all', $pdo) ?></span>
            </a>
        </li>
    </ul>
    
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Artikel</th>
                <th>Nama</th>
                <th>Email</th>
                <th>Komentar</th>
                <th>Status</th>
                <th>Tanggal</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($komentar_list as $kom): ?>
                <tr>
                    <td><?= $kom['id'] ?></td>
                    <td>
                        <a href="../../public/detail.php?slug=<?= $kom['artikel_slug'] ?>" target="_blank">
                            <?= htmlspecialchars($kom['artikel_judul']) ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars($kom['nama']) ?></td>
                    <td><?= htmlspecialchars($kom['email']) ?></td>
                    <td><?= nl2br(htmlspecialchars($kom['isi'])) ?></td>
                    <td>
                        <?php
                        $status_badge = [
                            'pending' => 'badge-warning',
                            'approved' => 'badge-success',
                            'spam' => 'badge-danger'
                        ];
                        $badge = isset($status_badge[$kom['status']]) ? $status_badge[$kom['status']] : 'badge-secondary';
                        ?>
                        <span class="badge <?= $badge ?>"><?= ucfirst($kom['status']) ?></span>
                    </td>
                    <td><?= date('d-m-Y H:i', strtotime($kom['created_at'])) ?></td>
                    <td>
                        <?php if ($kom['status'] == 'pending'): ?>
                            <a href="?action=approve&id=<?= $kom['id'] ?>&status=<?= $status_filter ?>&page=<?= $page ?>" 
                               class="btn btn-sm btn-success" onclick="return confirm('Setujui komentar ini?')">Approve</a>
                            <a href="?action=spam&id=<?= $kom['id'] ?>&status=<?= $status_filter ?>&page=<?= $page ?>" 
                               class="btn btn-sm btn-warning" onclick="return confirm('Tandai sebagai spam?')">Spam</a>
                        <?php endif; ?>
                        <a href="?action=delete&id=<?= $kom['id'] ?>&status=<?= $status_filter ?>&page=<?= $page ?>" 
                           class="btn btn-sm btn-danger" onclick="return confirm('Hapus komentar ini?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($komentar_list)): ?>
                <tr>
                    <td colspan="8" class="text-center">Tidak ada komentar dengan status ini.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <nav>
            <ul class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?status=<?= $status_filter ?>&page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
    
    <a href="../dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Fungsi helper untuk menghitung jumlah komentar per status
function hitungStatus($status, $pdo) {
    if ($status == 'all') {
        $stmt = $pdo->query("SELECT COUNT(*) FROM komentar");
        return $stmt->fetchColumn();
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM komentar WHERE status = ?");
        $stmt->execute([$status]);
        return $stmt->fetchColumn();
    }
}
?>