<?php
require_once '../config/database.php';
$page_title = 'Statistik Blog - DynamicPost';
include 'includes/header.php';

// Data untuk grafik artikel per bulan (6 bulan terakhir)
$bulan = [];
$jumlah = [];
for ($i = 5; $i >= 0; $i--) {
    $bulan[] = date('M Y', strtotime("-$i months"));
    $start = date('Y-m-01', strtotime("-$i months"));
    $end = date('Y-m-t', strtotime("-$i months"));
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM artikel WHERE status='published' AND created_at BETWEEN ? AND ?");
    $stmt->execute([$start, $end]);
    $jumlah[] = $stmt->fetchColumn();
}

// Data untuk kategori
$kategori_stats = $pdo->query("SELECT k.nama, COUNT(a.id) as jumlah 
                               FROM kategori k 
                               LEFT JOIN artikel a ON k.id = a.kategori_id AND a.status='published'
                               GROUP BY k.id
                               ORDER BY jumlah DESC LIMIT 5")->fetchAll();

// Total statistik
$total_artikel = $pdo->query("SELECT COUNT(*) FROM artikel WHERE status='published'")->fetchColumn();
$total_komentar = $pdo->query("SELECT COUNT(*) FROM komentar WHERE status='approved'")->fetchColumn();
$total_kategori = $pdo->query("SELECT COUNT(*) FROM kategori")->fetchColumn();
$total_tag = $pdo->query("SELECT COUNT(*) FROM tag")->fetchColumn();
$total_author = $pdo->query("SELECT COUNT(DISTINCT penulis_id) FROM artikel WHERE status='published'")->fetchColumn();
?>

<h2>Statistik Blog</h2>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h5 class="card-title">Total Artikel</h5>
                <p class="card-text display-4"><?= $total_artikel ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h5 class="card-title">Total Komentar</h5>
                <p class="card-text display-4"><?= $total_komentar ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <h5 class="card-title">Total Kategori</h5>
                <p class="card-text display-4"><?= $total_kategori ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <h5 class="card-title">Total Tag</h5>
                <p class="card-text display-4"><?= $total_tag ?></p>
            </div>
        </div>
    </div>
</div>

<h4>Artikel per Bulan (6 bulan terakhir)</h4>
<canvas id="chartPerBulan" width="400" height="200"></canvas>

<h4 class="mt-4">Top 5 Kategori</h4>
<canvas id="chartKategori" width="400" height="200"></canvas>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
    // Grafik garis artikel per bulan
    const ctx1 = document.getElementById('chartPerBulan').getContext('2d');
    new Chart(ctx1, {
        type: 'line',
        data: {
            labels: <?= json_encode($bulan) ?>,
            datasets: [{
                label: 'Jumlah Artikel',
                data: <?= json_encode($jumlah) ?>,
                borderColor: 'blue',
                fill: false
            }]
        }
    });

    // Grafik batang kategori
    const ctx2 = document.getElementById('chartKategori').getContext('2d');
    new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($kategori_stats, 'nama')) ?>,
            datasets: [{
                label: 'Jumlah Artikel',
                data: <?= json_encode(array_column($kategori_stats, 'jumlah')) ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        }
    });
</script>

<?php include 'includes/footer.php'; ?>