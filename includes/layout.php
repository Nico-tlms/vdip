<?php
function layoutHead(string $title = ''): void {
    $appName   = APP_NAME;
    $pageTitle = $title ? "$title — $appName" : $appName;
    $base      = baseUrl();
    echo <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>$pageTitle</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
  <link rel="stylesheet" href="$base/assets/css/app.css">
</head>
<body>
HTML;
}

function layoutNav(): void {
    $user  = currentUser();
    $base  = baseUrl();
    $name  = htmlspecialchars($user['name']);
    $role  = match($user['role']) {
        'service_technique' => 'Service Technique',
        'admin'             => 'Administrateur',
        default             => 'Technicien',
    };
    $initials = strtoupper(mb_substr($user['name'], 0, 1));
    $adminLink = isAdmin()
        ? "<a href=\"$base/admin.php\" class=\"nav-link\"><svg width='16' height='16' fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><circle cx='12' cy='8' r='4'/><path d='M4 20c0-4 3.6-7 8-7s8 3 8 7'/></svg>Admin</a>"
        : '';

    $currentPage = basename($_SERVER['PHP_SELF']);

    echo <<<HTML
<nav class="navbar">
  <a href="$base/index.php" class="navbar-brand">
    <span class="brand-icon">🔧</span>
    <span class="brand-name">VDIP</span>
  </a>
  <div class="navbar-links">
    <a href="$base/index.php" class="nav-link {$_SESSION['_nav_class']['index']}">
      <svg width='16' height='16' fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><path d='M12 2l9 9-9 9-9-9 9-9z'/><path d='M12 11v5'/></svg>
      Nouveau CR
    </a>
    <a href="$base/historique.php" class="nav-link {$_SESSION['_nav_class']['hist']}">
      <svg width='16' height='16' fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><circle cx='12' cy='12' r='10'/><path d='M12 6v6l4 2'/></svg>
      Historique
    </a>
    $adminLink
  </div>
  <div class="navbar-user">
    <div class="user-info">
      <span class="user-name">$name</span>
      <span class="user-role">$role</span>
    </div>
    <div class="user-avatar">$initials</div>
    <a href="$base/logout.php" class="btn-logout" title="Déconnexion">
      <svg width='16' height='16' fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><path d='M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4'/><polyline points='16 17 21 12 16 7'/><line x1='21' y1='12' x2='9' y2='12'/></svg>
    </a>
  </div>
</nav>
HTML;
}

function layoutStart(string $title = '', string $page = ''): void {
    $_SESSION['_nav_class'] = [
        'index' => $page === 'index' ? 'active' : '',
        'hist'  => $page === 'hist'  ? 'active' : '',
    ];
    layoutHead($title);
    layoutNav();
    echo '<div class="page-wrapper"><main class="main-content">';
}

function layoutEnd(): void {
    $base = baseUrl();
    echo <<<HTML
    </main>
  </div>
  <script src="$base/assets/js/app.js"></script>
</body>
</html>
HTML;
}
