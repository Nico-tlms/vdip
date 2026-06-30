<?php
require_once __DIR__ . '/db.php';

function baseUrl(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Détecte si le site est dans un sous-dossier
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir    = rtrim(dirname($script), '/');
    return $scheme . '://' . $host . $dir;
}

function isLoggedIn(): bool {
    return !empty($_SESSION['user']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . baseUrl() . '/login.php');
        exit;
    }
}

function currentUser(): array {
    return $_SESSION['user'] ?? [];
}

function login(string $email, string $password): bool {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        unset($user['password']);
        $_SESSION['user'] = $user;
        return true;
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
