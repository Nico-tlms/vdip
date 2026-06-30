<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/mailer.php';
require_once __DIR__ . '/includes/layout.php';

requireLogin();
$user = currentUser();

$db       = getDB();
$managers = $db->query('SELECT * FROM managers ORDER BY name')->fetchAll();

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type       = $_POST['type'] ?? 'chantier';
    $manager_id = (int)($_POST['manager_id'] ?? 0);
    $chantier   = trim($_POST['chantier'] ?? '');

    // Collecte des champs selon le type
    if ($type === 'chantier') {
        $content = [
            'chantier'   => $chantier,
            'actions'    => trim($_POST['actions'] ?? ''),
            'problemes'  => trim($_POST['problemes'] ?? ''),
            'materiel'   => trim($_POST['materiel'] ?? ''),
            'raf'        => trim($_POST['raf'] ?? ''),
        ];
    } else {
        $content = [
            'chantier'        => $chantier,
            'intervenants'    => trim($_POST['intervenants'] ?? ''),
            'objectif_jour'   => trim($_POST['objectif_jour'] ?? ''),
            'actions'         => trim($_POST['actions'] ?? ''),
            'resultats'       => trim($_POST['resultats'] ?? ''),
            'validations'     => trim($_POST['validations'] ?? ''),
            'problemes'       => trim($_POST['problemes'] ?? ''),
            'raf'             => trim($_POST['raf'] ?? ''),
            'anticipe_demain' => trim($_POST['anticipe_demain'] ?? ''),
        ];
    }

    if (!$manager_id || !$chantier) {
        $error = 'Veuillez renseigner le chantier et choisir un responsable.';
    } else {
        // Sauvegarde en BDD
        $stmt = $db->prepare('INSERT INTO reports (user_id, type, manager_id, chantier, content) VALUES (?,?,?,?,?)');
        $stmt->execute([$user['id'], $type, $manager_id, $chantier, json_encode($content)]);
        $reportId = $db->lastInsertId();

        // Récupération du rapport complet + envoi mail
        $report  = $db->prepare('SELECT * FROM reports WHERE id = ?');
        $report->execute([$reportId]);
        $report  = $report->fetch();
        $manager = $db->prepare('SELECT * FROM managers WHERE id = ?');
        $manager->execute([$manager_id]);
        $manager = $manager->fetch();

        $sent = sendReportEmail($report, $manager, $user);
        if ($sent) {
            $success = "✅ Compte rendu envoyé à {$manager['name']} ({$manager['email']}).";
        } else {
            $success = "✅ CR enregistré — l'email n'a pas pu être envoyé (vérifiez la config SMTP).";
        }
    }
}

layoutStart('Nouveau compte rendu');
?>

<div class="card">
  <div class="card-title">📋 Nouveau compte rendu de fin de journée</div>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" id="reportForm">

    <!-- Type de CR -->
    <div class="form-group">
      <label>Type de compte rendu</label>
      <div class="type-selector">
        <div class="type-card <?= ($_POST['type'] ?? 'chantier') === 'chantier' ? 'active' : '' ?>"
             onclick="selectType('chantier')">
          <div class="type-icon">🏗️</div>
          <div class="type-label">CR Chantier</div>
          <div class="type-sub">Techniciens terrain</div>
        </div>
        <div class="type-card <?= ($_POST['type'] ?? '') === 'service' ? 'active' : '' ?>"
             onclick="selectType('service')">
          <div class="type-icon">💻</div>
          <div class="type-label">CR Service Technique</div>
          <div class="type-sub">Michael &amp; Alexia</div>
        </div>
      </div>
      <input type="hidden" name="type" id="typeInput"
             value="<?= htmlspecialchars($_POST['type'] ?? 'chantier') ?>">
    </div>

    <!-- Chantier + Responsable -->
    <div class="form-group">
      <label for="chantier">📌 Chantier / Affaire</label>
      <input type="text" id="chantier" name="chantier" placeholder="Nom du chantier ou de l'affaire"
             value="<?= htmlspecialchars($_POST['chantier'] ?? '') ?>" required>
    </div>

    <div class="form-group">
      <label for="manager_id">📨 Envoyer à</label>
      <select id="manager_id" name="manager_id" required>
        <option value="">— Choisir un responsable —</option>
        <?php foreach ($managers as $m): ?>
          <option value="<?= $m['id'] ?>"
            <?= isset($_POST['manager_id']) && $_POST['manager_id'] == $m['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($m['name']) ?> (<?= htmlspecialchars($m['email']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- === FORMULAIRE CHANTIER === -->
    <div id="form-chantier">
      <fieldset class="section-form">
        <legend>✅ Ce qui a été fait aujourd'hui</legend>
        <div class="form-group" style="margin-bottom:0">
          <textarea name="actions" rows="4"
                    placeholder="Décrivez les travaux réalisés..."><?= htmlspecialchars($_POST['actions'] ?? '') ?></textarea>
        </div>
      </fieldset>

      <fieldset class="section-form">
        <legend>⚠️ Problèmes / Blocages rencontrés</legend>
        <div class="form-group" style="margin-bottom:0">
          <textarea name="problemes" rows="3"
                    placeholder="Aucun, ou décrivez les problèmes..."><?= htmlspecialchars($_POST['problemes'] ?? '') ?></textarea>
        </div>
      </fieldset>

      <fieldset class="section-form">
        <legend>🔧 Matériel manquant ou à prévoir</legend>
        <div class="form-group" style="margin-bottom:0">
          <textarea name="materiel" rows="3"
                    placeholder="Matériel ou fournitures nécessaires..."><?= htmlspecialchars($_POST['materiel'] ?? '') ?></textarea>
        </div>
      </fieldset>

      <fieldset class="section-form">
        <legend>📅 Objectif de demain</legend>
        <div class="form-group" style="margin-bottom:0">
          <textarea name="raf" rows="3"
                    placeholder="Ce qui est prévu pour demain..."><?= htmlspecialchars($_POST['raf'] ?? '') ?></textarea>
        </div>
      </fieldset>
    </div>

    <!-- === FORMULAIRE SERVICE TECHNIQUE === -->
    <div id="form-service" style="display:none">
      <fieldset class="section-form">
        <legend>👥 Intervenants présents</legend>
        <div class="form-group" style="margin-bottom:0">
          <input type="text" name="intervenants" placeholder="Noms des intervenants"
                 value="<?= htmlspecialchars($_POST['intervenants'] ?? '') ?>">
        </div>
      </fieldset>

      <fieldset class="section-form">
        <legend>🎯 Objectif du jour</legend>
        <div class="form-group" style="margin-bottom:0">
          <textarea name="objectif_jour" rows="2"
                    placeholder="Objectif fixé ce matin..."><?= htmlspecialchars($_POST['objectif_jour'] ?? '') ?></textarea>
        </div>
      </fieldset>

      <fieldset class="section-form">
        <legend>✅ Actions menées</legend>
        <div class="form-group" style="margin-bottom:0">
          <textarea name="actions" rows="4"
                    placeholder="Détaillez les actions effectuées..."><?= htmlspecialchars($_POST['actions'] ?? '') ?></textarea>
        </div>
      </fieldset>

      <fieldset class="section-form">
        <legend>📊 Résultats obtenus</legend>
        <div class="form-group" style="margin-bottom:0">
          <textarea name="resultats" rows="4"
                    placeholder="Ce qui a été validé / mis en service / testé&#10;Écarts par rapport à l'objectif..."><?= htmlspecialchars($_POST['resultats'] ?? '') ?></textarea>
        </div>
      </fieldset>

      <fieldset class="section-form">
        <legend>👤 Validations obtenues</legend>
        <div class="form-group" style="margin-bottom:0">
          <textarea name="validations" rows="3"
                    placeholder="Validation client : oui / non / en attente&#10;Validation interne : oui / non / en attente"><?= htmlspecialchars($_POST['validations'] ?? '') ?></textarea>
        </div>
      </fieldset>

      <fieldset class="section-form">
        <legend>⚠️ Problèmes rencontrés</legend>
        <div class="form-group" style="margin-bottom:0">
          <textarea name="problemes" rows="4"
                    placeholder="Nature du problème&#10;Impact sur le planning&#10;Action corrective..."><?= htmlspecialchars($_POST['problemes'] ?? '') ?></textarea>
        </div>
      </fieldset>

      <fieldset class="section-form">
        <legend>📅 Reste à faire (RAF)</legend>
        <div class="form-group" style="margin-bottom:0">
          <textarea name="raf" rows="4"
                    placeholder="Liste des actions restantes&#10;% d'avancement estimé&#10;Date de fin estimée..."><?= htmlspecialchars($_POST['raf'] ?? '') ?></textarea>
        </div>
      </fieldset>

      <fieldset class="section-form">
        <legend>🔮 Anticipé pour demain</legend>
        <div class="form-group" style="margin-bottom:0">
          <textarea name="anticipe_demain" rows="3"
                    placeholder="Objectif de demain&#10;Ce qu'il faut préparer ou débloquer ce soir..."><?= htmlspecialchars($_POST['anticipe_demain'] ?? '') ?></textarea>
        </div>
      </fieldset>
    </div>

    <div style="margin-top:24px;text-align:center">
      <button type="submit" class="btn btn-send">
        📨 Envoyer le compte rendu
      </button>
    </div>
  </form>
</div>

<script src="/assets/js/app.js"></script>
<?php layoutEnd(); ?>
