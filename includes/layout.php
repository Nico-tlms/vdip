<?php
function layoutStart(string $title = ''): void {
    $user    = $_SESSION['user'] ?? null;
    $appName = APP_NAME;
    $base    = baseUrl();
    $pageTitle = $title ? "$title — $appName" : $appName;
    echo <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>$pageTitle</title>
  <link rel="stylesheet" href="$base/assets/css/app.css">
</head>
<body>
HTML;
    if ($user) {
        $name   = htmlspecialchars($user['name']);
        $role   = $user['role'] === 'service_technique' ? 'Service Technique' : ucfirst($user['role']);
        $admin  = $user['role'] === 'admin' ? "<a href=\"$base/admin.php\">⚙ Admin</a>" : '';
        echo <<<HTML
<nav>
  <div class="nav-brand">🔧 VDIP</div>
  <div class="nav-links">
    <a href="$base/index.php">Nouveau CR</a>
    <a href="$base/historique.php">Historique</a>
    $admin
    <span class="nav-user">$name <small>($role)</small></span>
    <a href="$base/logout.php" class="btn-logout">Déconnexion</a>
  </div>
</nav>
HTML;
    }
    echo '<main>';
}

function layoutEnd(): void {
    $base = baseUrl();
    echo '</main><footer><p>© ' . date('Y') . ' VDIP — Portail Comptes Rendus</p></footer>'
       . "<script src=\"$base/assets/js/app.js\"></script>"
       . '</body></html>';
}
