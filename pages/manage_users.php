<?php
require_once 'includes/template.php';

$db = Database::getInstance();

// Get all users
$users = $db->fetchAll("SELECT * FROM users ORDER BY created_at DESC");

// Get user statistics
$adminCount = 0;
$userCount = 0;
foreach ($users as $user) {
    if ($user['role'] === 'admin') {
        $adminCount++;
    } else {
        $userCount++;
    }
}

renderHeader('Kelola Pengguna - Aplikasi Akuntansi');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-users me-2"></i>Kelola Pengguna</h2>
    <div>
        <span class="badge bg-primary fs-6">Total: <?= count($users) ?> pengguna</span>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Daftar Pengguna Terdaftar</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($users)): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Tanggal Daftar</th>
                        <th>Status</th>
                        <th>Aktivitas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <?php 
                    $userEntries = $db->fetchOne("SELECT COUNT(*) as count FROM journal_entries WHERE created_by = ?", [$user['id']])['count'];
                    ?>
                    <tr>
                        <td>
                            <span class="badge bg-secondary">#<?= $user['id'] ?></span>
                        </td>
                        <td>
                            <strong><?= escape($user['username']) ?></strong>
                            <?php if ($user['username'] === $_SESSION['username']): ?>
                            <span class="badge bg-info ms-1">Anda</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($user['role'] === 'admin'): ?>
                            <span class="badge bg-danger">
                                <i class="fas fa-crown me-1"></i>Admin
                            </span>
                            <?php else: ?>
                            <span class="badge bg-success">
                                <i class="fas fa-user me-1"></i>User
                            </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small class="text-muted">
                                <?= date('d/m/Y H:i', strtotime($user['created_at'])) ?>
                            </small>
                        </td>
                        <td>
                            <span class="badge bg-success">
                                <i class="fas fa-check-circle me-1"></i>Aktif
                            </span>
                        </td>
                        <td>
                            <?php if ($userEntries > 0): ?>
                            <small class="text-success">
                                <i class="fas fa-edit me-1"></i><?= $userEntries ?> jurnal
                            </small>
                            <?php else: ?>
                            <small class="text-muted">
                                <i class="fas fa-minus me-1"></i>Belum ada aktivitas
                            </small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-users fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Belum Ada Pengguna Terdaftar</h5>
            <p class="text-muted">Belum ada pengguna yang terdaftar dalam sistem.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6><i class="fas fa-chart-pie me-2"></i>Statistik Pengguna</h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <h4 class="text-danger"><?= $adminCount ?></h4>
                        <small>Admin</small>
                    </div>
                    <div class="col-6">
                        <h4 class="text-success"><?= $userCount ?></h4>
                        <small>User Biasa</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6><i class="fas fa-info-circle me-2"></i>Informasi</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <i class="fas fa-shield-alt text-danger me-2"></i>
                        <strong>Admin:</strong> Akses penuh ke semua fitur
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-user text-success me-2"></i>
                        <strong>User:</strong> Akses terbatas (baca saja)
                    </li>
                    <li>
                        <i class="fas fa-user-plus text-info me-2"></i>
                        Pengguna baru otomatis mendapat role <strong>User</strong>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info mt-4">
    <i class="fas fa-lightbulb me-2"></i>
    <strong>Tips:</strong> 
    Fitur pengelolaan pengguna lanjutan (ubah role, hapus pengguna) dapat ditambahkan sesuai kebutuhan.
    Saat ini semua pengguna yang mendaftar otomatis mendapat role "User" dengan akses terbatas.
</div>

<?php renderFooter(); ?>