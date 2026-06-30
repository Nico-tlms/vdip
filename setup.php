<?php
/**
 * SETUP — Visite cette page UNE SEULE FOIS pour créer les tables et l'admin.
 * Ensuite, supprimez ou renommez ce fichier.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

$messages = [];
$error    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = getDB();

        // Création des tables
        $db->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(150) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                role ENUM('technicien','service_technique','admin') NOT NULL DEFAULT 'technicien',
                created_at DATETIME DEFAULT NOW()
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        $messages[] = '✅ Table <b>users</b> créée.';

        $db->exec("
            CREATE TABLE IF NOT EXISTS managers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(150) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        $messages[] = '✅ Table <b>managers</b> créée.';

        $db->exec("
            CREATE TABLE IF NOT EXISTS reports (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                type ENUM('chantier','service') NOT NULL,
                manager_id INT NOT NULL,
                chantier VARCHAR(200),
                content TEXT NOT NULL,
                sent_at DATETIME DEFAULT NOW(),
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (manager_id) REFERENCES managers(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        $messages[] = '✅ Table <b>reports</b> créée.';

        // Compte admin
        $adminEmail = trim($_POST['admin_email'] ?? 'admin@vdip.fr');
        $adminPass  = $_POST['admin_pass'] ?? '';
        $adminName  = trim($_POST['admin_name'] ?? 'Administrateur');

        if (strlen($adminPass) < 6) throw new Exception("Le mot de passe doit faire au moins 6 caractères.");

        $hash = password_hash($adminPass, PASSWORD_DEFAULT);
        $check = $db->prepare('SELECT id FROM users WHERE email = ?');
        $check->execute([$adminEmail]);
        if ($check->fetch()) {
            $db->prepare('UPDATE users SET password=?, name=?, role=? WHERE email=?')
               ->execute([$hash, $adminName, 'admin', $adminEmail]);
            $messages[] = "✅ Compte admin mis à jour : <b>$adminEmail</b>";
        } else {
            $db->prepare('INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)')
               ->execute([$adminName, $adminEmail, $hash, 'admin']);
            $messages[] = "✅ Compte admin créé : <b>$adminEmail</b>";
        }

        // Responsables par défaut
        $mgrCount = $db->query('SELECT COUNT(*) FROM managers')->fetchColumn();
        if ($mgrCount == 0) {
            $mgrName  = trim($_POST['mgr_name']  ?? '');
            $mgrEmail = trim($_POST['mgr_email'] ?? '');
            if ($mgrName && $mgrEmail) {
                $db->prepare('INSERT INTO managers (name,email) VALUES (?,?)')->execute([$mgrName, $mgrEmail]);
                $messages[] = "✅ Responsable ajouté : <b>$mgrName</b>";
            }
        }

        $messages[] = '<br>🎉 <b>Installation terminée !</b> Supprimez <code>setup.php</code> puis <a href="login.php">connectez-vous</a>.';

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Installation — VDIP</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
  <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<div class="setup-page">
  <div class="setup-card">
    <div style="text-align:center;margin-bottom:28px">
      <div style="font-size:40px;margin-bottom:10px">⚙️</div>
      <h1 style="font-size:22px;font-weight:800;color:#1e3a5f;margin-bottom:4px">Installation VDIP</h1>
      <p style="font-size:13px;color:#64748b">Création des tables et du compte administrateur</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error" style="margin-bottom:20px">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($messages): ?>
      <div class="alert alert-success" style="flex-direction:column;align-items:flex-start;gap:6px">
        <?php foreach ($messages as $m): echo "<div>$m</div>"; endforeach; ?>
      </div>
    <?php else: ?>
      <form method="POST">
        <div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:8px;padding:12px 16px;margin-bottom:24px;font-size:13px;color:#92400e">
          ⚠️ À n'utiliser qu'<b>une seule fois</b>. Supprimez ce fichier après installation.
        </div>

        <p style="font-size:13px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:14px">Compte administrateur</p>

        <div class="form-group">
          <label>Nom complet</label>
          <input type="text" name="admin_name" value="Administrateur" required>
        </div>
        <div class="form-group">
          <label>Email admin</label>
          <input type="email" name="admin_email" value="admin@vdip.fr" required>
        </div>
        <div class="form-group">
          <label>Mot de passe (min. 6 caractères)</label>
          <input type="password" name="admin_pass" placeholder="Choisissez un mot de passe sécurisé" required>
        </div>

        <p style="font-size:13px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin:20px 0 14px">Premier responsable</p>

        <div class="form-row">
          <div class="form-group">
            <label>Nom</label>
            <input type="text" name="mgr_name" placeholder="Ex : Nicolas">
          </div>
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="mgr_email" placeholder="responsable@example.com">
          </div>
        </div>

        <button type="submit" class="btn-login" style="margin-top:16px">
          🚀 Lancer l'installation
        </button>
      </form>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
