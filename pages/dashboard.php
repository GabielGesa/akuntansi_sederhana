<?php
require_once 'includes/template.php';

// Get dashboard statistics
$db = Database::getInstance();

// Get summary statistics
$totalAccounts = $db->fetchOne("SELECT COUNT(*) as count FROM accounts")['count'];
$totalEntries = $db->fetchOne("SELECT COUNT(*) as count FROM journal_entries")['count'];

// Get recent journal entries
$recentEntries = $db->fetchAll(
    "SELECT je.*, da.name as debit_account_name, ca.name as credit_account_name, u.username
     FROM journal_entries je
     JOIN accounts da ON je.debit_account_id = da.id
     JOIN accounts ca ON je.credit_account_id = ca.id
     JOIN users u ON je.created_by = u.id
     ORDER BY je.created_at DESC
     LIMIT 5"
);

// Calculate financial totals
$assetAccounts = $db->fetchAll("SELECT * FROM accounts WHERE account_type = 'Aset'");
$liabilityAccounts = $db->fetchAll("SELECT * FROM accounts WHERE account_type = 'Liabilitas'");
$equityAccounts = $db->fetchAll("SELECT * FROM accounts WHERE account_type = 'Ekuitas'");
$revenueAccounts = $db->fetchAll("SELECT * FROM accounts WHERE account_type = 'Pendapatan'");
$expenseAccounts = $db->fetchAll("SELECT * FROM accounts WHERE account_type = 'Beban'");

$totalAssets = 0;
foreach ($assetAccounts as $account) {
    $totalAssets += getAccountBalance($account['id']);
}

$totalLiabilities = 0;
foreach ($liabilityAccounts as $account) {
    $totalLiabilities += getAccountBalance($account['id']);
}

$totalEquity = 0;
foreach ($equityAccounts as $account) {
    $totalEquity += getAccountBalance($account['id']);
}

$totalRevenue = 0;
foreach ($revenueAccounts as $account) {
    $totalRevenue += getAccountBalance($account['id']);
}

$totalExpenses = 0;
foreach ($expenseAccounts as $account) {
    $totalExpenses += getAccountBalance($account['id']);
}

$netIncome = $totalRevenue - $totalExpenses;

renderHeader('Dashboard - Aplikasi Akuntansi');
?>

<h2 class="mb-4">
    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
    <small class="text-muted">Ringkasan Informasi Akuntansi</small>
</h2>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-list fa-2x text-primary mb-2"></i>
                <h5><?= $totalAccounts ?></h5>
                <small class="text-muted">Total Akun</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-book fa-2x text-success mb-2"></i>
                <h5><?= $totalEntries ?></h5>
                <small class="text-muted">Total Jurnal</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-coins fa-2x text-warning mb-2"></i>
                <h5><?= formatCurrency($totalAssets) ?></h5>
                <small class="text-muted">Total Aset</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-chart-line fa-2x <?= $netIncome >= 0 ? 'text-success' : 'text-danger' ?> mb-2"></i>
                <h5 class="<?= $netIncome >= 0 ? 'text-success' : 'text-danger' ?>"><?= formatCurrency($netIncome) ?></h5>
                <small class="text-muted">Laba Bersih</small>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-chart-bar me-2"></i>Pendapatan vs Beban</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6 text-center">
                        <i class="fas fa-arrow-up fa-2x text-success mb-2"></i>
                        <h6>Pendapatan</h6>
                        <h4 class="text-success"><?= formatCurrency($totalRevenue) ?></h4>
                    </div>
                    <div class="col-6 text-center">
                        <i class="fas fa-arrow-down fa-2x text-danger mb-2"></i>
                        <h6>Beban</h6>
                        <h4 class="text-danger"><?= formatCurrency($totalExpenses) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-balance-scale me-2"></i>Keseimbangan Akuntansi</h5>
            </div>
            <div class="card-body">
                <p><strong>Harta = Kewajiban + Modal</strong></p>
                <p><?= formatCurrency($totalAssets) ?> = <?= formatCurrency($totalLiabilities) ?> + <?= formatCurrency($totalEquity) ?></p>
                
                <?php $balanceCheck = $totalAssets - ($totalLiabilities + $totalEquity); ?>
                <?php if (abs($balanceCheck) < 0.01): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-1"></i>Neraca Seimbang
                </div>
                <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    Selisih: <?= formatCurrency($balanceCheck) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-tasks me-2"></i>Menu Utama</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <a href="/chart-of-accounts" class="btn btn-outline-primary btn-lg w-100">
                            <i class="fas fa-chart-bar fa-2x d-block mb-2"></i>
                            Daftar Akun
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="/journal-entry" class="btn btn-outline-success btn-lg w-100">
                            <i class="fas fa-edit fa-2x d-block mb-2"></i>
                            Jurnal Umum
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="/trial-balance" class="btn btn-outline-info btn-lg w-100">
                            <i class="fas fa-balance-scale fa-2x d-block mb-2"></i>
                            Neraca Saldo
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="/income-statement" class="btn btn-outline-warning btn-lg w-100">
                            <i class="fas fa-chart-line fa-2x d-block mb-2"></i>
                            Laporan Laba Rugi
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="/balance-sheet" class="btn btn-outline-danger btn-lg w-100">
                            <i class="fas fa-clipboard-list fa-2x d-block mb-2"></i>
                            Neraca
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="/general-ledger" class="btn btn-outline-secondary btn-lg w-100">
                            <i class="fas fa-book fa-2x d-block mb-2"></i>
                            Buku Besar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($recentEntries)): ?>
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-clock me-2"></i>Jurnal Terbaru</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Tanggal</th>
                                <th>Deskripsi</th>
                                <th>Debit</th>
                                <th>Kredit</th>
                                <th>Jumlah</th>
                                <th>Dibuat Oleh</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentEntries as $entry): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($entry['date'])) ?></td>
                                <td><?= escape($entry['description']) ?></td>
                                <td><?= escape($entry['debit_account_name']) ?></td>
                                <td><?= escape($entry['credit_account_name']) ?></td>
                                <td><?= formatCurrency($entry['amount']) ?></td>
                                <td><?= escape($entry['username']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php renderFooter(); ?>