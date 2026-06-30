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

/** Retourne l'entrée managers liée à l'utilisateur connecté (par email), ou null */
function currentManagerRecord(): ?array {
    static $mgr = false;
    if ($mgr === false) {
        $user = currentUser();
        if (empty($user)) { $mgr = null; return null; }
        try {
            $db   = getDB();
            $stmt = $db->prepare('SELECT * FROM managers WHERE email = ? LIMIT 1');
            $stmt->execute([$user['email']]);
            $mgr  = $stmt->fetch() ?: null;
        } catch (Exception $e) { $mgr = null; }
    }
    return $mgr;
}

function isManager(): bool {
    return currentManagerRecord() !== null;
}

/** Nombre de CR non lus pour le responsable connecté */
function unreadCount(): int {
    $mgr  = currentManagerRecord();
    $user = currentUser();
    if (!$mgr || !$user) return 0;
    try {
        $db   = getDB();
        $stmt = $db->prepare('
            SELECT COUNT(*) FROM reports r
            WHERE r.manager_id = ?
              AND r.id NOT IN (SELECT report_id FROM report_reads WHERE user_id = ?)
        ');
        $stmt->execute([$mgr['id'], $user['id']]);
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) { return 0; }
}
