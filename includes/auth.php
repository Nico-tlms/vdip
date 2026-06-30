<?php
require_once dirname(__DIR__) . '/includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function baseUrl(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '/';
    $dir    = dirname($script);
    $base   = ($dir === '/' || $dir === '.') ? '' : rtrim($dir, '/');
    return $scheme . '://' . $host . $base;
}

function isLoggedIn(): bool {
    return !empty($_SESSION['vdip_user']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . baseUrl() . '/login.php');
        exit;
    }
}

function currentUser(): array {
    return $_SESSION['vdip_user'] ?? [];
}

function login(string $email, string $password): bool {
    try {
        $db   = getDB();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([trim($email)]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']);
            $_SESSION['vdip_user'] = $user;
            return true;
        }
    } catch (Exception $e) {
        // DB error — login fails gracefully
    }
    return false;
}

function logout(): void {
    session_destroy();
    header('Location: ' . baseUrl() . '/login.php');
    exit;
}

function isAdmin(): bool {
    return (currentUser()['role'] ?? '') === 'admin';
}
