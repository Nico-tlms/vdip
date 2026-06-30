<?php
require_once __DIR__ . '/db.php';

function isLoggedIn(): bool {
    return !empty($_SESSION['user']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /login.php');
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
    header('Location: /login.php');
    exit;
}

function isAdmin(): bool {
    return (currentUser()['role'] ?? '') === 'admin';
}
