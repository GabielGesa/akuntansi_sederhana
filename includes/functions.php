<?php
/**
 * Helper functions for the accounting application
 */

/**
 * Format currency in Indonesian Rupiah
 */
function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

/**
 * Get account balance
 */
function getAccountBalance($accountId) {
    $db = Database::getInstance();
    
    // Get account info
    $account = $db->fetchOne("SELECT * FROM accounts WHERE id = ?", [$accountId]);
    if (!$account) {
        return 0;
    }
    
    // Calculate balance from journal entries
    $debitEntries = $db->fetchAll(
        "SELECT SUM(amount) as total FROM journal_entries WHERE debit_account_id = ?",
        [$accountId]
    );
    $creditEntries = $db->fetchAll(
        "SELECT SUM(amount) as total FROM journal_entries WHERE credit_account_id = ?",
        [$accountId]
    );
    
    $debitTotal = $debitEntries[0]['total'] ?? 0;
    $creditTotal = $creditEntries[0]['total'] ?? 0;
    
    // Calculate balance based on normal balance
    if ($account['normal_balance'] === 'debit') {
        return $debitTotal - $creditTotal;
    } else {
        return $creditTotal - $debitTotal;
    }
}

/**
 * Update all account balances
 */
function updateAccountBalances() {
    $db = Database::getInstance();
    $accounts = $db->fetchAll("SELECT id FROM accounts");
    
    foreach ($accounts as $account) {
        $balance = getAccountBalance($account['id']);
        $db->execute("UPDATE accounts SET balance = ? WHERE id = ?", [$balance, $account['id']]);
    }
}

/**
 * Calculate trial balance
 */
function calculateTrialBalance() {
    $db = Database::getInstance();
    $accounts = $db->fetchAll("SELECT * FROM accounts ORDER BY code");
    
    $trialBalance = [];
    $totalDebits = 0;
    $totalCredits = 0;
    
    foreach ($accounts as $account) {
        $balance = getAccountBalance($account['id']);
        
        if ($balance != 0) {
            $debitBalance = 0;
            $creditBalance = 0;
            
            if ($account['normal_balance'] === 'debit' && $balance > 0) {
                $debitBalance = $balance;
                $totalDebits += $balance;
            } elseif ($account['normal_balance'] === 'credit' && $balance > 0) {
                $creditBalance = $balance;
                $totalCredits += $balance;
            } elseif ($account['normal_balance'] === 'debit' && $balance < 0) {
                $creditBalance = abs($balance);
                $totalCredits += abs($balance);
            } elseif ($account['normal_balance'] === 'credit' && $balance < 0) {
                $debitBalance = abs($balance);
                $totalDebits += abs($balance);
            }
            
            $trialBalance[] = [
                'account' => $account,
                'debit' => $debitBalance,
                'credit' => $creditBalance
            ];
        }
    }
    
    return [
        'accounts' => $trialBalance,
        'total_debits' => $totalDebits,
        'total_credits' => $totalCredits
    ];
}

/**
 * Generate income statement data
 */
function generateIncomeStatement() {
    $db = Database::getInstance();
    
    // Get revenue accounts
    $revenueAccounts = $db->fetchAll("SELECT * FROM accounts WHERE account_type = 'Pendapatan' ORDER BY code");
    $expenseAccounts = $db->fetchAll("SELECT * FROM accounts WHERE account_type = 'Beban' ORDER BY code");
    
    $totalRevenue = 0;
    $totalExpenses = 0;
    
    $revenues = [];
    foreach ($revenueAccounts as $account) {
        $balance = getAccountBalance($account['id']);
        if ($balance != 0) {
            $revenues[] = ['account' => $account, 'amount' => $balance];
            $totalRevenue += $balance;
        }
    }
    
    $expenses = [];
    foreach ($expenseAccounts as $account) {
        $balance = getAccountBalance($account['id']);
        if ($balance != 0) {
            $expenses[] = ['account' => $account, 'amount' => $balance];
            $totalExpenses += $balance;
        }
    }
    
    return [
        'revenues' => $revenues,
        'expenses' => $expenses,
        'total_revenue' => $totalRevenue,
        'total_expenses' => $totalExpenses,
        'net_income' => $totalRevenue - $totalExpenses
    ];
}

/**
 * Generate balance sheet data
 */
function generateBalanceSheet() {
    $db = Database::getInstance();
    
    $assets = $db->fetchAll("SELECT * FROM accounts WHERE account_type = 'Aset' ORDER BY code");
    $liabilities = $db->fetchAll("SELECT * FROM accounts WHERE account_type = 'Liabilitas' ORDER BY code");
    $equity = $db->fetchAll("SELECT * FROM accounts WHERE account_type = 'Ekuitas' ORDER BY code");
    
    $totalAssets = 0;
    $totalLiabilities = 0;
    $totalEquity = 0;
    
    $assetData = [];
    foreach ($assets as $account) {
        $balance = getAccountBalance($account['id']);
        if ($balance != 0) {
            $assetData[] = ['account' => $account, 'amount' => $balance];
            $totalAssets += $balance;
        }
    }
    
    $liabilityData = [];
    foreach ($liabilities as $account) {
        $balance = getAccountBalance($account['id']);
        if ($balance != 0) {
            $liabilityData[] = ['account' => $account, 'amount' => $balance];
            $totalLiabilities += $balance;
        }
    }
    
    $equityData = [];
    foreach ($equity as $account) {
        $balance = getAccountBalance($account['id']);
        if ($balance != 0) {
            $equityData[] = ['account' => $account, 'amount' => $balance];
            $totalEquity += $balance;
        }
    }
    
    return [
        'assets' => $assetData,
        'liabilities' => $liabilityData,
        'equity' => $equityData,
        'total_assets' => $totalAssets,
        'total_liabilities' => $totalLiabilities,
        'total_equity' => $totalEquity
    ];
}

/**
 * Set flash message
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * Redirect to a URL
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Escape HTML output
 */
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Check if request is POST
 */
function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Get POST data
 */
function getPost($key, $default = '') {
    return $_POST[$key] ?? $default;
}

/**
 * Get GET data
 */
function getGet($key, $default = '') {
    return $_GET[$key] ?? $default;
}
?>