<?php
/**
 * Authentication functions
 */

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = Database::getInstance();
    return $db->fetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
}

/**
 * Check if current user is admin
 */
function isAdmin() {
    $user = getCurrentUser();
    return $user && $user['role'] === 'admin';
}

/**
 * Require user to be logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        setFlashMessage('Silakan login terlebih dahulu', 'warning');
        redirect('/login');
    }
}

/**
 * Require user to be admin
 */
function requireAdmin() {
    if (!isAdmin()) {
        setFlashMessage('Akses ditolak. Hanya admin yang dapat mengakses halaman ini.', 'error');
        redirect('/dashboard');
    }
}

/**
 * Login user
 */
function loginUser($username, $password) {
    $db = Database::getInstance();
    $user = $db->fetchOne("SELECT * FROM users WHERE username = ?", [$username]);
    
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    
    return false;
}

/**
 * Register new user
 */
function registerUser($username, $password) {
    $db = Database::getInstance();
    
    // Check if username already exists
    $existingUser = $db->fetchOne("SELECT id FROM users WHERE username = ?", [$username]);
    if ($existingUser) {
        return false;
    }
    
    // Create new user
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $db->execute(
        "INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)",
        [$username, $passwordHash, 'user']
    );
    
    return true;
}

/**
 * Logout user
 */
function logoutUser() {
    session_destroy();
    session_start();
}
?>