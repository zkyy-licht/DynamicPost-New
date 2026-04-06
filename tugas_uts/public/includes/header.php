<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title : 'DynamicPost' ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">DynamicPost</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="artikel.php">Semua Artikel</a></li>
                    <li class="nav-item"><a class="nav-link" href="kategori.php">Kategori</a></li>
                    <li class="nav-item"><a class="nav-link" href="tag.php">Tag</a></li>
                    <li class="nav-item"><a class="nav-link" href="statistik.php">Statistik</a></li>
                </ul>
                <form class="form-inline" action="cari.php" method="GET">
                    <input class="form-control mr-sm-2" type="search" name="q" placeholder="Cari artikel..." required>
                    <button class="btn btn-outline-light my-2 my-sm-0" type="submit">Cari</button>
                </form>
                <a href="../admin/login.php" class="btn btn-outline-light ml-2">Login Admin</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <!-- konten utama akan diisi oleh halaman masing-masing -->