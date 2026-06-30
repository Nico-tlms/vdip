<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/mailer.php';
require_once __DIR__ . '/includes/layout.php';

requireLogin();
$user = currentUser();
$db   = getDB();

$stmt = $db->prepare('
    SELECT r.*, m.name AS manager_name, m.email AS manager_email
    FROM reports r
    JOIN managers m ON m.id = r.manager_id
    WHERE r.user_id = ?
    ORDER BY r.sent_at DESC
');
$stmt->execute([$user['id']]);
$reports = $stmt->fetchAll();

layoutStart('Mon historique');
?>

<div class="card">
  <div class="card-title">🗂️ Historique de mes comptes rendus</div>

  <?php if (empty($reports)): ?>
    <div class="alert alert-info">Vous n'avez encore envoyé aucun compte rendu.</div>
  <?php endif; ?>

  <?php foreach ($reports as $i => $r):
    $content = json_decode($r['content'], true);
    $type    = $r['type'];
    $badge   = $type === 'service' ? '<span class="badge badge-service">Service Technique</span>'
                                   : '<span class="badge badge-chantier">Chantier</span>';
    $date    = date('d/m/Y à H:i', strtotime($r['sent_at']));
  ?>
    <div class="history-item">
      <div class="history-header" onclick="toggleHistory(<?= $i ?>)">
        <div>
          <div class="date"><?= $date ?></div>
          <div class="meta">
            📌 <?= htmlspecialchars($r['chantier']) ?>
            &nbsp;→&nbsp; <?= htmlspecialchars($r['manager_name']) ?>
          </div>
        </div>
        <div style="display:flex;align-items:center;gap:10px">
          <?= $badge ?>
          <span id="arrow-<?= $i ?>" style="color:#6b7280;font-size:18px;transition:transform .2s">▼</span>
        </div>
      </div>
      <div class="history-body" id="body-<?= $i ?>">
        <?php foreach ($content as $key => $val):
          if (empty($val)) continue;
        ?>
          <div class="history-field">
            <div class="field-label"><?= htmlspecialchars(fieldLabel($key)) ?></div>
            <div class="field-value"><?= nl2br(htmlspecialchars($val)) ?></div>
          </div>
        <?php endforeach; ?>
        <div class="history-field" style="margin-top:14px;padding-top:12px;border-top:1px solid var(--border)">
          <div class="field-label">Envoyé à</div>
          <div class="field-value">
            <?= htmlspecialchars($r['manager_name']) ?>
            &lt;<?= htmlspecialchars($r['manager_email']) ?>&gt;
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<script>
function toggleHistory(i) {
  const body  = document.getElementById('body-' + i);
  const arrow = document.getElementById('arrow-' + i);
  const open  = body.classList.toggle('open');
  arrow.style.transform = open ? 'rotate(180deg)' : '';
}
</script>

<?php layoutEnd(); ?>
