<?php
session_start();
require_once '../config/database.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$username = $_SESSION['username'];

// ========== STATISTIK UTAMA ==========
if ($role == 'admin') {
    // Admin: lihat semua data
    $total_artikel = $pdo->query("SELECT COUNT(*) FROM artikel WHERE status='published'")->fetchColumn();
    $total_komentar = $pdo->query("SELECT COUNT(*) FROM komentar WHERE status='approved'")->fetchColumn();
    $total_kategori = $pdo->query("SELECT COUNT(*) FROM kategori")->fetchColumn();
    $total_tag = $pdo->query("SELECT COUNT(*) FROM tag")->fetchColumn();
    $total_views = $pdo->query("SELECT SUM(views) FROM artikel WHERE status='published'")->fetchColumn();

    // Grafik artikel per bulan (6 bulan terakhir)
    $bulan_labels = [];
    $bulan_data = [];
    for ($i = 5; $i >= 0; $i--) {
        $bulan = date('Y-m', strtotime("-$i months"));
        $bulan_labels[] = date('M Y', strtotime("-$i months"));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM artikel WHERE status='published' AND DATE_FORMAT(created_at, '%Y-%m') = ?");
        $stmt->execute([$bulan]);
        $bulan_data[] = $stmt->fetchColumn();
    }

    // Data kategori untuk grafik batang (top 5)
    $kategori_data = $pdo->query("SELECT k.nama, COUNT(a.id) as jumlah 
                                  FROM kategori k 
                                  LEFT JOIN artikel a ON k.id = a.kategori_id AND a.status='published'
                                  GROUP BY k.id
                                  ORDER BY jumlah DESC LIMIT 5")->fetchAll();
    $kategori_labels = array_column($kategori_data, 'nama');
    $kategori_counts = array_column($kategori_data, 'jumlah');

    // Artikel terbaru (5)
    $artikel_terbaru = $pdo->query("SELECT a.id, a.judul, a.slug, a.created_at, u.username 
                                    FROM artikel a 
                                    LEFT JOIN users u ON a.penulis_id = u.id 
                                    WHERE a.status='published' 
                                    ORDER BY a.created_at DESC LIMIT 5")->fetchAll();

    // Komentar terbaru (5)
    $komentar_terbaru = $pdo->query("SELECT k.*, a.judul as artikel_judul, a.slug 
                                     FROM komentar k 
                                     JOIN artikel a ON k.artikel_id = a.id 
                                     WHERE k.status='pending' OR k.status='approved'
                                     ORDER BY k.created_at DESC LIMIT 5")->fetchAll();
} else {
    // Author: hanya data miliknya sendiri
    $total_artikel = $pdo->prepare("SELECT COUNT(*) FROM artikel WHERE status='published' AND penulis_id = ?");
    $total_artikel->execute([$user_id]);
    $total_artikel = $total_artikel->fetchColumn();

    $total_komentar = $pdo->prepare("SELECT COUNT(*) FROM komentar k 
                                     JOIN artikel a ON k.artikel_id = a.id 
                                     WHERE k.status='approved' AND a.penulis_id = ?");
    $total_komentar->execute([$user_id]);
    $total_komentar = $total_komentar->fetchColumn();

    $total_kategori = $pdo->query("SELECT COUNT(*) FROM kategori")->fetchColumn(); // sama
    $total_tag = $pdo->query("SELECT COUNT(*) FROM tag")->fetchColumn();

    $total_views = $pdo->prepare("SELECT SUM(views) FROM artikel WHERE status='published' AND penulis_id = ?");
    $total_views->execute([$user_id]);
    $total_views = $total_views->fetchColumn();

    // Grafik artikel per bulan (hanya untuk artikel penulis)
    $bulan_labels = [];
    $bulan_data = [];
    for ($i = 5; $i >= 0; $i--) {
        $bulan = date('Y-m', strtotime("-$i months"));
        $bulan_labels[] = date('M Y', strtotime("-$i months"));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM artikel WHERE status='published' AND penulis_id = ? AND DATE_FORMAT(created_at, '%Y-%m') = ?");
        $stmt->execute([$user_id, $bulan]);
        $bulan_data[] = $stmt->fetchColumn();
    }

    // Kategori (top 5 untuk penulis)
    $kategori_data = $pdo->prepare("SELECT k.nama, COUNT(a.id) as jumlah 
                                    FROM kategori k 
                                    LEFT JOIN artikel a ON k.id = a.kategori_id AND a.status='published' AND a.penulis_id = ?
                                    GROUP BY k.id
                                    ORDER BY jumlah DESC LIMIT 5");
    $kategori_data->execute([$user_id]);
    $kategori_data = $kategori_data->fetchAll();
    $kategori_labels = array_column($kategori_data, 'nama');
    $kategori_counts = array_column($kategori_data, 'jumlah');

    // Artikel terbaru milik penulis
    $artikel_terbaru = $pdo->prepare("SELECT a.id, a.judul, a.slug, a.created_at, u.username 
                                      FROM artikel a 
                                      LEFT JOIN users u ON a.penulis_id = u.id 
                                      WHERE a.status='published' AND a.penulis_id = ?
                                      ORDER BY a.created_at DESC LIMIT 5");
    $artikel_terbaru->execute([$user_id]);
    $artikel_terbaru = $artikel_terbaru->fetchAll();

    // Komentar terbaru pada artikel milik penulis
    $komentar_terbaru = $pdo->prepare("SELECT k.*, a.judul as artikel_judul, a.slug 
                                       FROM komentar k 
                                       JOIN artikel a ON k.artikel_id = a.id 
                                       WHERE a.penulis_id = ? AND (k.status='pending' OR k.status='approved')
                                       ORDER BY k.created_at DESC LIMIT 5");
    $komentar_terbaru->execute([$user_id]);
    $komentar_terbaru = $komentar_terbaru->fetchAll();
}

// Jika total_views null, set 0
$total_views = $total_views ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | DynamicPost</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts (Inter) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- React & ReactDOM & Babel (untuk widget interaktif) -->
    <script src="https://cdn.jsdelivr.net/npm/react@18.2.0/umd/react.development.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/react-dom@18.2.0/umd/react-dom.development.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@babel/standalone/babel.min.js"></script>
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
        }
        .sidebar {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s;
        }
        .sidebar .nav-link {
            color: #cbd5e1;
            border-radius: 12px;
            margin: 4px 12px;
            transition: 0.2s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(99, 102, 241, 0.2);
            color: white;
        }
        .sidebar .nav-link i {
            width: 24px;
        }
        .card-glass {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 24px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card-glass:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 30px -12px rgba(0,0,0,0.3);
        }
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.7;
        }
        .table-dark-custom {
            background: transparent;
            color: #e2e8f0;
        }
        .table-dark-custom th {
            border-bottom-color: rgba(255,255,255,0.2);
        }
        .table-dark-custom td {
            border-color: rgba(255,255,255,0.08);
        }
        .badge-pending {
            background-color: #f59e0b;
        }
        .badge-approved {
            background-color: #10b981;
        }
        .btn-outline-glass {
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            border-radius: 40px;
            padding: 6px 16px;
            transition: 0.2s;
        }
        .btn-outline-glass:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        .navbar-glass {
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .text-gradient {
            background: linear-gradient(90deg, #a5b4fc, #c7d2fe);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .react-widget {
            background: rgba(0,0,0,0.3);
            border-radius: 20px;
            padding: 8px 16px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container-fluid px-0">
        <div class="row g-0">
            <!-- Sidebar -->
            <div class="col-auto sidebar min-vh-100 p-3" style="width: 280px;">
                <div class="d-flex flex-column h-100">
                    <div class="text-center mb-4 mt-3">
                        <i class="fas fa-blog fa-2x text-primary"></i>
                        <h4 class="text-white mt-2">DynamicPost</h4>
                        <span class="badge bg-secondary"><?= ucfirst($role) ?></span>
                    </div>
                    <ul class="nav nav-pills flex-column mb-auto">
                        <li class="nav-item">
                            <a href="dashboard.php" class="nav-link active">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="artikel/index.php" class="nav-link">
                                <i class="fas fa-newspaper me-2"></i> Artikel
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="kategori/index.php" class="nav-link">
                                <i class="fas fa-folder me-2"></i> Kategori
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="tag/index.php" class="nav-link">
                                <i class="fas fa-tags me-2"></i> Tag
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="komentar/index.php" class="nav-link">
                                <i class="fas fa-comments me-2"></i> Komentar
                            </a>
                        </li>
                        <?php if ($role == 'admin'): ?>
                        <li class="nav-item">
                            <a href="users/index.php" class="nav-link">
                                <i class="fas fa-users me-2"></i> Manajemen Users
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                    <div class="mt-auto">
                        <hr class="bg-white-50">
                        <div class="d-flex align-items-center text-white-50 mb-3">
                            <i class="fas fa-user-circle fa-2x me-2"></i>
                            <div>
                                <small><?= htmlspecialchars($username) ?></small><br>
                                <a href="logout.php" class="btn btn-sm btn-outline-glass mt-1">Logout</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col p-4">
                <!-- Navbar atas -->
                <div class="navbar-glass rounded-3 p-3 mb-4 d-flex justify-content-between align-items-center">
                    <h3 class="text-white mb-0">Dashboard</h3>
                    <div id="react-clock-widget"></div>
                </div>

                <!-- Stat Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="card-glass p-3 text-white">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <small class="text-white-50">Total Artikel</small>
                                    <h2 class="mt-2"><?= number_format($total_artikel) ?></h2>
                                </div>
                                <i class="fas fa-file-alt stat-icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card-glass p-3 text-white">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <small class="text-white-50">Komentar Disetujui</small>
                                    <h2 class="mt-2"><?= number_format($total_komentar) ?></h2>
                                </div>
                                <i class="fas fa-comment-dots stat-icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card-glass p-3 text-white">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <small class="text-white-50">Total Kategori</small>
                                    <h2 class="mt-2"><?= number_format($total_kategori) ?></h2>
                                </div>
                                <i class="fas fa-folder-open stat-icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card-glass p-3 text-white">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <small class="text-white-50">Total Views</small>
                                    <h2 class="mt-2"><?= number_format($total_views) ?></h2>
                                </div>
                                <i class="fas fa-eye stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grafik -->
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="card-glass p-3 text-white">
                            <h5 class="mb-3"><i class="fas fa-chart-line me-2"></i> Tren Artikel (6 Bulan)</h5>
                            <canvas id="trendChart" height="200"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card-glass p-3 text-white">
                            <h5 class="mb-3"><i class="fas fa-chart-bar me-2"></i> Top 5 Kategori</h5>
                            <canvas id="kategoriChart" height="200"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Tabel Artikel Terbaru & Komentar -->
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card-glass p-3 text-white">
                            <h5 class="mb-3"><i class="fas fa-clock me-2"></i> Artikel Terbaru</h5>
                            <div class="table-responsive">
                                <table class="table table-dark-custom">
                                    <thead>
                                        <tr><th>Judul</th><th>Penulis</th><th>Tanggal</th><th>Aksi</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($artikel_terbaru as $art): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(substr($art['judul'], 0, 30)) ?>...</td>
                                            <td><?= htmlspecialchars($art['username']) ?></td>
                                            <td><?= date('d/m', strtotime($art['created_at'])) ?></td>
                                            <td><a href="artikel/edit.php?id=<?= $art['id'] ?>" class="btn btn-sm btn-outline-glass">Edit</a></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($artikel_terbaru)): ?>
                                        <tr><td colspan="4" class="text-center">Belum ada artikel</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card-glass p-3 text-white">
                            <h5 class="mb-3"><i class="fas fa-comment-alt me-2"></i> Komentar Terbaru</h5>
                            <div class="table-responsive">
                                <table class="table table-dark-custom">
                                    <thead>
                                        <tr><th>Isi</th><th>Artikel</th><th>Status</th><th>Aksi</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($komentar_terbaru as $kom): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(substr($kom['isi'], 0, 30)) ?>...</td>
                                            <td><a href="../public/detail.php?slug=<?= $kom['slug'] ?>" target="_blank" class="text-white-50"><?= htmlspecialchars(substr($kom['artikel_judul'], 0, 20)) ?></a></td>
                                            <td><span class="badge <?= $kom['status'] == 'pending' ? 'badge-pending' : 'badge-approved' ?>"><?= $kom['status'] ?></span></td>
                                            <td><a href="komentar/index.php?status=pending" class="btn btn-sm btn-outline-glass">Moderasi</a></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($komentar_terbaru)): ?>
                                        <tr><td colspan="4" class="text-center">Belum ada komentar</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Grafik dengan Chart.js
        const ctxLine = document.getElementById('trendChart').getContext('2d');
        new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: <?= json_encode($bulan_labels) ?>,
                datasets: [{
                    label: 'Jumlah Artikel',
                    data: <?= json_encode($bulan_data) ?>,
                    borderColor: '#818cf8',
                    backgroundColor: 'rgba(129, 140, 248, 0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { labels: { color: '#cbd5e1' } } },
                scales: { y: { ticks: { color: '#cbd5e1' }, grid: { color: 'rgba(255,255,255,0.1)' } },
                          x: { ticks: { color: '#cbd5e1' }, grid: { color: 'rgba(255,255,255,0.1)' } } }
            }
        });

        const ctxBar = document.getElementById('kategoriChart').getContext('2d');
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: <?= json_encode($kategori_labels) ?>,
                datasets: [{
                    label: 'Jumlah Artikel',
                    data: <?= json_encode($kategori_counts) ?>,
                    backgroundColor: '#a78bfa',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { labels: { color: '#cbd5e1' } } },
                scales: { y: { ticks: { color: '#cbd5e1' }, grid: { color: 'rgba(255,255,255,0.1)' } },
                          x: { ticks: { color: '#cbd5e1' }, grid: { color: 'rgba(255,255,255,0.1)' } } }
            }
        });
    </script>

    <!-- React Widget (Jam Digital) -->
    <script type="text/babel">
        function ClockWidget() {
            const [time, setTime] = React.useState(new Date().toLocaleTimeString('id-ID'));
            React.useEffect(() => {
                const interval = setInterval(() => {
                    setTime(new Date().toLocaleTimeString('id-ID'));
                }, 1000);
                return () => clearInterval(interval);
            }, []);
            return (
                <div className="react-widget text-white">
                    <i className="fas fa-clock me-2"></i> {time}
                </div>
            );
        }
        ReactDOM.createRoot(document.getElementById('react-clock-widget')).render(<ClockWidget />);
    </script>
</body>
</html>