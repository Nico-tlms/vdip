<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . baseUrl() . '/index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (login($_POST['email'] ?? '', $_POST['password'] ?? '')) {
        header('Location: ' . baseUrl() . '/index.php');
        exit;
    }
    $error = 'Email ou mot de passe incorrect.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Connexion — VDIP</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
  <link rel="stylesheet" href="<?= baseUrl() ?>/assets/css/app.css">
</head>
<body>
<div class="login-page">
  <div class="login-card">
    <div class="login-logo">🔧</div>
    <h1 class="login-title">VDIP</h1>
    <p class="login-sub">Portail Comptes Rendus Techniciens</p>

    <?php if ($error): ?>
      <div class="alert alert-error">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label for="email">Adresse email</label>
        <div class="input-group">
          <span class="input-icon">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 7 10-7"/></svg>
          </span>
          <input type="email" id="email" name="email" placeholder="votre@email.com" required autofocus
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
      </div>
      <div class="form-group">
        <label for="password">Mot de passe</label>
        <div class="input-group">
          <span class="input-icon">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          </span>
          <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>
      </div>
      <button type="submit" class="btn-login">Se connecter</button>
    </form>
  </div>
</div>
</body>
</html>
