<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/mailer.php';
require_once __DIR__ . '/includes/layout.php';

requireLogin();
$user = currentUser();
$db   = getDB();

$managers = $db->query('SELECT * FROM managers ORDER BY name')->fetchAll();
$success  = '';
$error    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type       = in_array($_POST['type'] ?? '', ['chantier', 'service']) ? $_POST['type'] : 'chantier';
    $manager_id = (int)($_POST['manager_id'] ?? 0);
    $chantier   = trim($_POST['chantier'] ?? '');

    if (!$manager_id || !$chantier) {
        $error = 'Veuillez renseigner le chantier et choisir un responsable.';
    } else {
        if ($type === 'chantier') {
            $content = [
                'chantier'  => $chantier,
                'actions'   => trim($_POST['actions']   ?? ''),
                'problemes' => trim($_POST['problemes'] ?? ''),
                'materiel'  => trim($_POST['materiel']  ?? ''),
                'raf'       => trim($_POST['raf']       ?? ''),
            ];
        } else {
            $content = [
                'chantier'        => $chantier,
                'intervenants'    => trim($_POST['intervenants']    ?? ''),
                'objectif_jour'   => trim($_POST['objectif_jour']   ?? ''),
                'actions'         => trim($_POST['actions']         ?? ''),
                'resultats'       => trim($_POST['resultats']       ?? ''),
                'validations'     => trim($_POST['validations']     ?? ''),
                'problemes'       => trim($_POST['problemes']       ?? ''),
                'raf'             => trim($_POST['raf']             ?? ''),
                'anticipe_demain' => trim($_POST['anticipe_demain'] ?? ''),
            ];
        }

        $stmt = $db->prepare('INSERT INTO reports (user_id,type,manager_id,chantier,content) VALUES (?,?,?,?,?)');
        $stmt->execute([$user['id'], $type, $manager_id, $chantier, json_encode($content, JSON_UNESCAPED_UNICODE)]);
        $reportId = $db->lastInsertId();

        $report  = $db->prepare('SELECT * FROM reports WHERE id=?');
        $report->execute([$reportId]);
        $report  = $report->fetch();

        $mgr = $db->prepare('SELECT * FROM managers WHERE id=?');
        $mgr->execute([$manager_id]);
        $mgr = $mgr->fetch();

        $sent = sendReportEmail($report, $mgr, $user);
        if ($sent) {
            $success = "CR envoyé à {$mgr['name']} ({$mgr['email']}).";
        } else {
            $success = "CR enregistré — email non envoyé (vérifiez la config SMTP dans config.php).";
        }
    }
}

$selectedType = $_POST['type'] ?? 'chantier';
layoutStart('Nouveau CR', 'index');
?>

<div class="page-header">
  <h1>📋 Nouveau compte rendu</h1>
  <p><?= date('l d MMMM Y') ?><?= date('l d F Y') ?></p>
</div>

<?php if ($success): ?>
  <div class="alert alert-success">
    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    <?= htmlspecialchars($success) ?>
  </div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-error">
    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>

<?php if (empty($managers)): ?>
  <div class="alert alert-warning">
    ⚠️ Aucun responsable configuré. Demandez à l'admin d'en ajouter dans la page Admin.
  </div>
<?php endif; ?>

<form method="POST" id="reportForm">

  <!-- Type de CR -->
  <div class="card">
    <div class="card-title">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      Choisissez le type de compte rendu
    </div>
    <div class="type-selector">
      <div class="type-card <?= $selectedType === 'chantier' ? 'active' : '' ?>"
           onclick="selectType('chantier', this)">
        <div class="type-icon">🏗️</div>
        <div class="type-label">CR Chantier</div>
        <div class="type-sub">Techniciens terrain</div>
      </div>
      <div class="type-card <?= $selectedType === 'service' ? 'active' : '' ?>"
           onclick="selectType('service', this)">
        <div class="type-icon">💻</div>
        <div class="type-label">CR Service Technique</div>
        <div class="type-sub">Michael &amp; Alexia</div>
      </div>
    </div>
    <input type="hidden" name="type" id="typeInput" value="<?= htmlspecialchars($selectedType) ?>">

    <!-- Chantier + Responsable -->
    <div class="form-row" style="margin-top:20px">
      <div class="form-group" style="margin:0">
        <label for="chantier">📌 Chantier / Affaire *</label>
        <input type="text" id="chantier" name="chantier"
               placeholder="Nom du chantier ou affaire"
               value="<?= htmlspecialchars($_POST['chantier'] ?? '') ?>" required>
      </div>
      <div class="form-group" style="margin:0">
        <label for="manager_id">📨 Envoyer à *</label>
        <select id="manager_id" name="manager_id" required>
          <option value="">— Choisir un responsable —</option>
          <?php foreach ($managers as $m): ?>
            <option value="<?= $m['id'] ?>"
              <?= isset($_POST['manager_id']) && $_POST['manager_id'] == $m['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($m['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>

  <!-- CHANTIER -->
  <div id="form-chantier" <?= $selectedType === 'service' ? 'style="display:none"' : '' ?>>
    <?php
    $chantierFields = [
      ['actions',   '✅ Ce qui a été fait aujourd\'hui', 'Décrivez les travaux réalisés aujourd\'hui...'],
      ['problemes', '⚠️ Problèmes / Blocages rencontrés', 'Aucun, ou décrivez les difficultés rencontrées...'],
      ['materiel',  '🔧 Matériel manquant ou à prévoir', 'Matériel ou fournitures nécessaires...'],
      ['raf',       '📅 Objectif de demain', 'Ce qui est prévu pour demain...'],
    ];
    foreach ($chantierFields as [$name, $label, $placeholder]):
    ?>
      <div class="field-section">
        <div class="field-section-header"><?= $label ?></div>
        <div class="field-section-body">
          <div class="form-group">
            <textarea name="<?= $name ?>" placeholder="<?= htmlspecialchars($placeholder) ?>"><?= htmlspecialchars($_POST[$name] ?? '') ?></textarea>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- SERVICE TECHNIQUE -->
  <div id="form-service" <?= $selectedType !== 'service' ? 'style="display:none"' : '' ?>>
    <?php
    $serviceFields = [
      ['intervenants',    '👥 Intervenants présents',     'Noms des personnes présentes',       false],
      ['objectif_jour',   '🎯 Objectif du jour',           'Objectif fixé ce matin...',          true],
      ['actions',         '✅ Actions menées',             'Détaillez les actions effectuées...',  true],
      ['resultats',       '📊 Résultats obtenus',          "Ce qui a été validé / mis en service / testé\nÉcarts par rapport à l'objectif et raisons...", true],
      ['validations',     '👤 Validations obtenues',       "Validation client : oui / non / en attente\nValidation interne : oui / non / en attente", true],
      ['problemes',       '⚠️ Problèmes rencontrés',       "Nature du problème\nImpact sur le planning\nAction corrective engagée ou à décider...", true],
      ['raf',             '📅 Reste à faire (RAF)',         "Liste des actions restantes\n% d'avancement estimé\nDate de fin estimée...", true],
      ['anticipe_demain', '🔮 Anticipé pour demain',       "Objectif\nCe qu'il faut préparer ou débloquer ce soir...", true],
    ];
    foreach ($serviceFields as [$name, $label, $placeholder, $isTextarea]):
    ?>
      <div class="field-section">
        <div class="field-section-header"><?= $label ?></div>
        <div class="field-section-body">
          <div class="form-group">
            <?php if ($isTextarea): ?>
              <textarea name="<?= $name ?>" placeholder="<?= htmlspecialchars($placeholder) ?>"><?= htmlspecialchars($_POST[$name] ?? '') ?></textarea>
            <?php else: ?>
              <input type="text" name="<?= $name ?>" placeholder="<?= htmlspecialchars($placeholder) ?>"
                     value="<?= htmlspecialchars($_POST[$name] ?? '') ?>">
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="send-footer">
    <button type="submit" class="btn-send">
      <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
      Envoyer le compte rendu
    </button>
  </div>

</form>

<?php layoutEnd(); ?>
