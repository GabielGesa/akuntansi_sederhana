<?php
require_once 'includes/template.php';

$db = Database::getInstance();

// Get all accounts ordered by code
$accounts = $db->fetchAll("SELECT * FROM accounts ORDER BY code");

// Group accounts by type
$accountTypes = [
    'Aset' => [],
    'Liabilitas' => [],
    'Ekuitas' => [],
    'Pendapatan' => [],
    'Beban' => []
];

foreach ($accounts as $account) {
    $account['balance'] = getAccountBalance($account['id']);
    $accountTypes[$account['account_type']][] = $account;
}

renderHeader('Daftar Akun - Aplikasi Akuntansi');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-chart-bar me-2"></i>Daftar Akun</h2>
    <?php if (isAdmin()): ?>
    <a href="/add-account" class="btn btn-success">
        <i class="fas fa-plus me-1"></i>Tambah Akun
    </a>
    <?php endif; ?>
</div>

<?php foreach ($accountTypes as $type => $typeAccounts): ?>
<?php if (!empty($typeAccounts)): ?>
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-<?= $type === 'Aset' ? 'coins' : ($type === 'Liabilitas' ? 'credit-card' : ($type === 'Ekuitas' ? 'user-tie' : ($type === 'Pendapatan' ? 'arrow-up' : 'arrow-down'))) ?> me-2"></i>
            <?= $type ?>
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Kode</th>
                        <th>Nama Akun</th>
                        <th>Saldo Normal</th>
                        <th>Saldo Saat Ini</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($typeAccounts as $account): ?>
                    <tr>
                        <td><span class="badge bg-secondary"><?= escape($account['code']) ?></span></td>
                        <td><?= escape($account['name']) ?></td>
                        <td>
                            <span class="badge bg-<?= $account['normal_balance'] === 'debit' ? 'primary' : 'success' ?>">
                                <?= ucfirst($account['normal_balance']) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <strong class="<?= $account['balance'] < 0 ? 'text-danger' : 'text-success' ?>">
                                <?= formatCurrency($account['balance']) ?>
                            </strong>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endforeach; ?>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-info-circle me-2"></i>Informasi</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>Total Akun: <?= count($accounts) ?></h6>
                <ul class="list-unstyled">
                    <?php foreach ($accountTypes as $type => $typeAccounts): ?>
                    <li>• <?= $type ?>: <?= count($typeAccounts) ?> akun</li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="col-md-6">
                <h6>Keterangan:</h6>
                <ul class="list-unstyled">
                    <li><span class="badge bg-primary">Debit</span> - Saldo normal debit</li>
                    <li><span class="badge bg-success">Credit</span> - Saldo normal kredit</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>