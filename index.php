<?php
session_start();

// Include configuration and functions
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Initialize database
initializeDatabase();

// Simple routing
$request = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($request, PHP_URL_PATH) ?? '/';

// Remove leading slash and handle empty path
$path = trim($path, '/');
if (empty($path)) {
    $path = 'home';
}

// Route handling
switch ($path) {
    case 'home':
    case '':
        if (isLoggedIn()) {
            header('Location: /dashboard');
            exit();
        } else {
            header('Location: /login');
            exit();
        }
        break;
        
    case 'login':
        require_once 'pages/login.php';
        break;
        
    case 'register':
        require_once 'pages/register.php';
        break;
        
    case 'logout':
        require_once 'pages/logout.php';
        break;
        
    case 'dashboard':
        requireLogin();
        require_once 'pages/dashboard.php';
        break;
        
    case 'chart-of-accounts':
        requireLogin();
        require_once 'pages/chart_of_accounts.php';
        break;
        
    case 'add-account':
        requireLogin();
        requireAdmin();
        require_once 'pages/add_account.php';
        break;
        
    case 'journal-entry':
        requireLogin();
        require_once 'pages/journal_entry.php';
        break;
        
    case 'adjusting-entry':
        requireLogin();
        require_once 'pages/adjusting_entry.php';
        break;
        
    case 'closing-entry':
        requireLogin();
        requireAdmin();
        require_once 'pages/closing_entry.php';
        break;
        
    case 'general-ledger':
        requireLogin();
        require_once 'pages/general_ledger.php';
        break;
        
    case 'trial-balance':
        requireLogin();
        require_once 'pages/trial_balance.php';
        break;
        
    case 'income-statement':
        requireLogin();
        require_once 'pages/income_statement.php';
        break;
        
    case 'balance-sheet':
        requireLogin();
        require_once 'pages/balance_sheet.php';
        break;
        
    case 'equity-statement':
        requireLogin();
        require_once 'pages/equity_statement.php';
        break;
        
    case 'search-entries':
        requireLogin();
        require_once 'pages/search_entries.php';
        break;
        
    case 'manage-users':
        requireLogin();
        requireAdmin();
        require_once 'pages/manage_users.php';
        break;
        
    default:
        http_response_code(404);
        require_once 'pages/404.php';
        break;
}
?>