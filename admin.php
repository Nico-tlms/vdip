<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/layout.php';

requireLogin();
if (!isAdmin()) {
    header('Location: /index.php');
    exit;
}

$db      = getDB();
$success = '';
$error   = '';

// --- Actions POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Ajouter un technicien
    if ($action === 'add_user') {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? 'technicien';
        if ($name && $email && $password) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            try {
                $db->prepare('INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)')
                   ->execute([$name, $email, $hash, $role]);
                $success = "Utilisateur $name créé.";
            } catch (Exception $e) {
                $error = "Cet email existe déjà.";
            }
        } else {
            $error = "Tous les champs sont obligatoires.";
        }
    }

    // Ajouter un responsable
    if ($action === 'add_manager') {
        $name  = trim($_POST['mgr_name'] ?? '');
        $email = trim($_POST['mgr_email'] ?? '');
        if ($name && $email) {
            $db->prepare('INSERT INTO managers (name,email) VALUES (?,?)')->execute([$name, $email]);
            $success = "Responsable $name ajouté.";
        } else {
            $error = "Nom et email obligatoires.";
        }
    }

    // Supprimer utilisateur
    if ($action === 'del_user') {
        $id = (int)($_POST['id'] ?? 0);
        $db->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        $success = "Utilisateur supprimé.";
    }

    // Supprimer responsable
    if ($action === 'del_manager') {
        $id = (int)($_POST['id'] ?? 0);
        $db->prepare('DELETE FROM managers WHERE id = ?')->execute([$id]);
        $success = "Responsable supprimé.";
    }
}

$users    = $db->query('SELECT id,name,email,role,created_at FROM users ORDER BY name')->fetchAll();
$managers = $db->query('SELECT * FROM managers ORDER BY name')->fetchAll();

layoutStart('Administration');
?>

<?php if ($success): ?>
  <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Utilisateurs -->
<div class="card">
  <div class="card-title">👷 Gestion des utilisateurs</div>

  <table class="admin-table" style="margin-bottom:24px">
    <thead>
      <tr>
        <th>Nom</th><th>Email</th><th>Rôle</th><th>Créé le</th><th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?= htmlspecialchars($u['name']) ?></td>
          <td><?= htmlspecialchars($u['email']) ?></td>
          <td><?= htmlspecialchars($u['role']) ?></td>
          <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
          <td>
            <?php if ($u['email'] !== 'admin@vdip.fr'): ?>
              <form method="POST" onsubmit="return confirm('Supprimer cet utilisateur ?')">
                <input type="hidden" name="action" value="del_user">
                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                <button type="submit" class="btn btn-danger" style="padding:5px 12px;font-size:12px">Supprimer</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <details>
    <summary style="cursor:pointer;font-weight:600;color:var(--blue2);margin-bottom:14px">
      ➕ Ajouter un utilisateur
    </summary>
    <form method="POST" style="margin-top:14px">
      <input type="hidden" name="action" value="add_user">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group">
          <label>Nom complet</label>
          <input type="text" name="name" required>
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" required>
        </div>
        <div class="form-group">
          <label>Mot de passe</label>
          <input type="password" name="password" required>
        </div>
        <div class="form-group">
          <label>Rôle</label>
          <select name="role">
            <option value="technicien">Technicien</option>
            <option value="service_technique">Service Technique</option>
            <option value="admin">Admin</option>
          </select>
        </div>
      </div>
      <button type="submit" class="btn btn-primary">Créer l'utilisateur</button>
    </form>
  </details>
</div>

<!-- Responsables -->
<div class="card">
  <div class="card-title">📨 Gestion des responsables</div>

  <table class="admin-table" style="margin-bottom:24px">
    <thead>
      <tr><th>Nom</th><th>Email</th><th>Action</th></tr>
    </thead>
    <tbody>
      <?php foreach ($managers as $m): ?>
        <tr>
          <td><?= htmlspecialchars($m['name']) ?></td>
          <td><?= htmlspecialchars($m['email']) ?></td>
          <td>
            <form method="POST" onsubmit="return confirm('Supprimer ce responsable ?')">
              <input type="hidden" name="action" value="del_manager">
              <input type="hidden" name="id" value="<?= $m['id'] ?>">
              <button type="submit" class="btn btn-danger" style="padding:5px 12px;font-size:12px">Supprimer</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <details>
    <summary style="cursor:pointer;font-weight:600;color:var(--blue2);margin-bottom:14px">
      ➕ Ajouter un responsable
    </summary>
    <form method="POST" style="margin-top:14px;display:flex;gap:12px;align-items:flex-end">
      <input type="hidden" name="action" value="add_manager">
      <div class="form-group" style="flex:1;margin:0">
        <label>Nom</label>
        <input type="text" name="mgr_name" required>
      </div>
      <div class="form-group" style="flex:2;margin:0">
        <label>Email du responsable</label>
        <input type="email" name="mgr_email" required>
      </div>
      <button type="submit" class="btn btn-primary" style="height:42px">Ajouter</button>
    </form>
  </details>
</div>

<?php layoutEnd(); ?>
