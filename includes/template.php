<?php
/**
 * Template functions for rendering HTML
 */

function renderHeader($title = 'Aplikasi Akuntansi') {
    $currentUser = getCurrentUser();
    $flashMessage = getFlashMessage();
    ?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escape($title) ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom Theme CSS -->
    <link href="/static/css/custom-theme.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="/static/css/accounting.css" rel="stylesheet">
</head>
<body style="background-color: var(--bg-primary);">
    <?php if ($currentUser): ?>
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: var(--primary-gradient);">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/dashboard">
                <i class="fas fa-calculator me-2"></i>Aplikasi Akuntansi
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/dashboard">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-list me-1"></i>Akun
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/chart-of-accounts">
                                <i class="fas fa-chart-bar me-1"></i>Daftar Akun
                            </a></li>
                            <?php if ($currentUser['role'] === 'admin'): ?>
                            <li><a class="dropdown-item" href="/add-account">
                                <i class="fas fa-plus me-1"></i>Tambah Akun
                            </a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-book me-1"></i>Jurnal
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/journal-entry">
                                <i class="fas fa-edit me-1"></i>Jurnal Umum
                            </a></li>
                            <li><a class="dropdown-item" href="/adjusting-entry">
                                <i class="fas fa-adjust me-1"></i>Jurnal Penyesuaian
                            </a></li>
                            <?php if ($currentUser['role'] === 'admin'): ?>
                            <li><a class="dropdown-item" href="/closing-entry">
                                <i class="fas fa-lock me-1"></i>Jurnal Penutup
                            </a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-file-alt me-1"></i>Laporan
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/general-ledger">
                                <i class="fas fa-ledger me-1"></i>Buku Besar
                            </a></li>
                            <li><a class="dropdown-item" href="/trial-balance">
                                <i class="fas fa-balance-scale me-1"></i>Neraca Saldo
                            </a></li>
                            <li><a class="dropdown-item" href="/income-statement">
                                <i class="fas fa-chart-line me-1"></i>Laporan Laba Rugi
                            </a></li>
                            <li><a class="dropdown-item" href="/balance-sheet">
                                <i class="fas fa-clipboard-list me-1"></i>Neraca
                            </a></li>
                            <li><a class="dropdown-item" href="/equity-statement">
                                <i class="fas fa-chart-pie me-1"></i>Laporan Perubahan Modal
                            </a></li>
                        </ul>
                    </li>
                    
                    <?php if ($currentUser['role'] === 'admin'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cogs me-1"></i>Sistem
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/manage-users">
                                <i class="fas fa-users me-1"></i>Kelola Pengguna
                            </a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="/search-entries">
                            <i class="fas fa-search me-1"></i>Pencarian
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?= escape($currentUser['username']) ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/logout">
                                <i class="fas fa-sign-out-alt me-1"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <div class="container mt-4">
        <?php if ($flashMessage): ?>
        <div class="alert alert-<?= $flashMessage['type'] === 'error' ? 'danger' : $flashMessage['type'] ?> alert-dismissible fade show">
            <?= escape($flashMessage['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
    <?php
}

function renderFooter() {
    ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="/static/js/accounting.js"></script>
</body>
</html>
    <?php
}
?>