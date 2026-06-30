<?php
function layoutStart(string $title = ''): void {
    $user = $_SESSION['user'] ?? null;
    $appName = APP_NAME;
    $pageTitle = $title ? "$title — $appName" : $appName;
    echo <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>$pageTitle</title>
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
HTML;
    if ($user) {
        $name   = htmlspecialchars($user['name']);
        $role   = $user['role'] === 'service_technique' ? 'Service Technique' : ucfirst($user['role']);
        $admin  = $user['role'] === 'admin' ? '<a href="/admin.php">⚙ Admin</a>' : '';
        echo <<<HTML
<nav>
  <div class="nav-brand">🔧 VDIP</div>
  <div class="nav-links">
    <a href="/index.php">Nouveau CR</a>
    <a href="/historique.php">Historique</a>
    $admin
    <span class="nav-user">$name <small>($role)</small></span>
    <a href="/logout.php" class="btn-logout">Déconnexion</a>
  </div>
</nav>
HTML;
    }
    echo '<main>';
}

function layoutEnd(): void {
    echo '</main><footer><p>© ' . date('Y') . ' VDIP — Portail Comptes Rendus</p></footer></body></html>';
}
