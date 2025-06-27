<?php
require_once 'includes/template.php';

renderHeader('Halaman Tidak Ditemukan - Aplikasi Akuntansi');
?>

<div class="row justify-content-center">
    <div class="col-md-6 text-center">
        <div class="card">
            <div class="card-body">
                <i class="fas fa-exclamation-triangle fa-5x text-warning mb-4"></i>
                <h2>404 - Halaman Tidak Ditemukan</h2>
                <p class="lead">Maaf, halaman yang Anda cari tidak dapat ditemukan.</p>
                <hr>
                <a href="/dashboard" class="btn btn-primary">
                    <i class="fas fa-home me-1"></i>Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>