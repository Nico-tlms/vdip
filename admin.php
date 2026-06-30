<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

requireLogin();
if (!isAdmin()) { header('Location: ' . baseUrl() . '/index.php'); exit; }

$db      = getDB();
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_user') {
        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $role  = in_array($_POST['role'] ?? '', ['technicien','service_technique','admin']) ? $_POST['role'] : 'technicien';
        if (!$name || !$email || strlen($pass) < 4) {
            $error = "Remplissez tous les champs (mot de passe min. 4 caractères).";
        } else {
            try {
                $db->prepare('INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)')
                   ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), $role]);
                $success = "Utilisateur «$name» créé.";
            } catch (Exception $e) { $error = "Cet email est déjà utilisé."; }
        }
    }

    if ($action === 'add_manager') {
        $name  = trim($_POST['mgr_name']  ?? '');
        $email = trim($_POST['mgr_email'] ?? '');
        if (!$name || !$email) { $error = "Nom et email obligatoires."; }
        else {
            $db->prepare('INSERT INTO managers (name,email) VALUES (?,?)')->execute([$name, $email]);
            $success = "Responsable «$name» ajouté.";
        }
    }

    if ($action === 'del_user' && ($id = (int)($_POST['id'] ?? 0))) {
        $db->prepare('DELETE FROM users WHERE id=? AND email != ?')->execute([$id, 'admin@vdip.fr']);
        $success = "Utilisateur supprimé.";
    }

    if ($action === 'del_manager' && ($id = (int)($_POST['id'] ?? 0))) {
        $db->prepare('DELETE FROM managers WHERE id=?')->execute([$id]);
        $success = "Responsable supprimé.";
    }

    if ($action === 'change_pass') {
        $id      = (int)($_POST['id'] ?? 0);
        $newpass = $_POST['new_pass'] ?? '';
        if ($id && strlen($newpass) >= 4) {
            $db->prepare('UPDATE users SET password=? WHERE id=?')
               ->execute([password_hash($newpass, PASSWORD_DEFAULT), $id]);
            $success = "Mot de passe modifié.";
        } else { $error = "Mot de passe trop court (min. 4 caractères)."; }
    }
}

$users    = $db->query('SELECT id,name,email,role,created_at FROM users ORDER BY name')->fetchAll();
$managers = $db->query('SELECT * FROM managers ORDER BY name')->fetchAll();

layoutStart('Administration', '');
?>

<div class="page-header">
  <h1>⚙️ Administration</h1>
  <p>Gestion des utilisateurs et des responsables</p>
</div>

<?php if ($success): ?>
  <div class="alert alert-success">
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
    <?= htmlspecialchars($success) ?>
  </div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-error">
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>

<!-- UTILISATEURS -->
<div class="card">
  <div class="card-title">
    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    Utilisateurs (<?= count($users) ?>)
  </div>

  <table class="admin-table">
    <thead>
      <tr>
        <th>Nom</th><th>Email</th><th>Rôle</th><th>Inscrit le</th><th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u):
        $roleClass = match($u['role']) {
          'admin'             => 'role-admin',
          'service_technique' => 'role-service',
          default             => 'role-tech',
        };
        $roleLabel = match($u['role']) {
          'admin'             => 'Admin',
          'service_technique' => 'Service Tech.',
          default             => 'Technicien',
        };
      ?>
        <tr>
          <td><strong><?= htmlspecialchars($u['name']) ?></strong></td>
          <td style="color:#64748b;font-size:13px"><?= htmlspecialchars($u['email']) ?></td>
          <td><span class="role-badge <?= $roleClass ?>"><?= $roleLabel ?></span></td>
          <td style="color:#64748b;font-size:13px"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
          <td>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
              <!-- Changer MDP -->
              <button onclick="togglePassForm(<?= $u['id'] ?>)" class="btn btn-secondary"
                      style="padding:5px 12px;font-size:12px">🔑 MDP</button>
              <?php if ($u['email'] !== 'admin@vdip.fr'): ?>
                <form method="POST" onsubmit="return confirm('Supprimer <?= htmlspecialchars($u['name']) ?> ?')">
                  <input type="hidden" name="action" value="del_user">
                  <input type="hidden" name="id" value="<?= $u['id'] ?>">
                  <button type="submit" class="btn btn-danger">Supprimer</button>
                </form>
              <?php endif; ?>
            </div>
            <!-- Form changement MDP inline -->
            <div id="pass-form-<?= $u['id'] ?>" style="display:none;margin-top:10px">
              <form method="POST" style="display:flex;gap:8px;align-items:flex-end">
                <input type="hidden" name="action" value="change_pass">
                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                <input type="password" name="new_pass" placeholder="Nouveau mot de passe"
                       style="max-width:200px;margin:0">
                <button type="submit" class="btn btn-primary" style="padding:9px 14px;font-size:13px">OK</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="add-form" style="margin-top:20px">
    <p style="font-size:13px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:16px">
      ➕ Ajouter un utilisateur
    </p>
    <form method="POST">
      <input type="hidden" name="action" value="add_user">
      <div class="form-row">
        <div class="form-group" style="margin:0">
          <label>Nom complet</label>
          <input type="text" name="name" required>
        </div>
        <div class="form-group" style="margin:0">
          <label>Email</label>
          <input type="email" name="email" required>
        </div>
        <div class="form-group" style="margin:0">
          <label>Mot de passe</label>
          <input type="password" name="password" placeholder="Min. 4 caractères" required>
        </div>
        <div class="form-group" style="margin:0">
          <label>Rôle</label>
          <select name="role">
            <option value="technicien">Technicien</option>
            <option value="service_technique">Service Technique</option>
            <option value="admin">Admin</option>
          </select>
        </div>
      </div>
      <button type="submit" class="btn btn-primary" style="margin-top:12px">Créer l'utilisateur</button>
    </form>
  </div>
</div>

<!-- RESPONSABLES -->
<div class="card">
  <div class="card-title">
    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
    Responsables (<?= count($managers) ?>)
  </div>

  <table class="admin-table">
    <thead>
      <tr><th>Nom</th><th>Email (destinataire)</th><th>Action</th></tr>
    </thead>
    <tbody>
      <?php foreach ($managers as $m): ?>
        <tr>
          <td><strong><?= htmlspecialchars($m['name']) ?></strong></td>
          <td style="color:#64748b;font-size:13px"><?= htmlspecialchars($m['email']) ?></td>
          <td>
            <form method="POST" onsubmit="return confirm('Supprimer ce responsable ?')">
              <input type="hidden" name="action" value="del_manager">
              <input type="hidden" name="id" value="<?= $m['id'] ?>">
              <button type="submit" class="btn btn-danger">Supprimer</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="add-form" style="margin-top:20px">
    <p style="font-size:13px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:16px">
      ➕ Ajouter un responsable
    </p>
    <form method="POST" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
      <input type="hidden" name="action" value="add_manager">
      <div class="form-group" style="flex:1;min-width:150px;margin:0">
        <label>Nom</label>
        <input type="text" name="mgr_name" placeholder="Ex : Nicolas" required>
      </div>
      <div class="form-group" style="flex:2;min-width:200px;margin:0">
        <label>Email du responsable</label>
        <input type="email" name="mgr_email" placeholder="responsable@example.com" required>
      </div>
      <button type="submit" class="btn btn-primary" style="height:42px;margin-bottom:0">Ajouter</button>
    </form>
  </div>
</div>

<script>
function togglePassForm(id) {
  var el = document.getElementById('pass-form-' + id);
  el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>

<?php layoutEnd(); ?>
