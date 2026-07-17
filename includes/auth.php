<?php
/**
 * Authentication Helper
 * Smart Instructor Coordination and Workload Management System
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // XAMPP HTTP. Set to 1 in HTTPS production.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUser() {
    global $pdo;
    if (!isLoggedIn()) {
        return null;
    }
    static $cachedUser = null;
    if ($cachedUser === null) {
        $stmt = $pdo->prepare("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $cachedUser = $stmt->fetch();
    }
    return $cachedUser;
}

function getCurrentRoleId() {
    return isset($_SESSION['role_id']) ? (int)$_SESSION['role_id'] : null;
}

function getCurrentRoleName() {
    $user = getCurrentUser();
    return $user ? $user['role_name'] : null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? app_url('/');
        header('Location: ' . app_url('auth/login.php'));
        exit();
    }
}

function regenerateSession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}


function loginUser($email, $password) {
    global $pdo;

    $email = trim((string)$email);
    if ($email === '' || $password === '') {
        return false;
    }

    $stmt = $pdo->prepare("\n        SELECT u.*, r.role_name\n        FROM users u\n        JOIN roles r ON u.role_id = r.id\n        WHERE u.email = :email\n          AND u.status = 'active'\n        LIMIT 1\n    ");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        return false;
    }

    regenerateSession();
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['role_id'] = (int)$user['role_id'];
    $_SESSION['role_name'] = $user['role_name'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];

    require_once __DIR__ . '/functions.php';
    logActivity((int)$user['id'], 'Login', 'User logged in successfully');

    return true;
}

function logoutUser() {
    require_once __DIR__ . '/functions.php';
    if (isLoggedIn()) {
        logActivity($_SESSION['user_id'], 'Logout', 'User logged out successfully');
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    header('Location: ' . app_url('auth/login.php?logged_out=1'));
    exit();
}
?>