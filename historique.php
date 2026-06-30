<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
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

layoutStart('Mon historique', 'hist');
?>

<div class="page-header">
  <h1>🗂️ Mon historique</h1>
  <p><?= count($reports) ?> compte<?= count($reports) > 1 ? 's' : '' ?> rendu<?= count($reports) > 1 ? 's' : '' ?> envoyé<?= count($reports) > 1 ? 's' : '' ?></p>
</div>

<?php if (empty($reports)): ?>
  <div class="card">
    <div class="empty-state">
      <div class="empty-icon">📭</div>
      <h3>Aucun compte rendu</h3>
      <p>Vous n'avez encore envoyé aucun compte rendu.</p>
    </div>
  </div>
<?php else: ?>
  <div class="history-list">
    <?php foreach ($reports as $i => $r):
      $content = json_decode($r['content'], true) ?? [];
      $type    = $r['type'];
      $date    = date('d/m/Y à H:i', strtotime($r['sent_at']));
    ?>
      <div class="history-item">
        <div class="history-header" onclick="toggleHistory(<?= $i ?>)" id="hdr-<?= $i ?>">
          <div class="history-type-dot dot-<?= $type ?>"></div>
          <div class="history-meta">
            <div class="history-chantier"><?= htmlspecialchars($r['chantier']) ?></div>
            <div class="history-info">
              <?= $date ?> &nbsp;·&nbsp; Envoyé à <?= htmlspecialchars($r['manager_name']) ?>
            </div>
          </div>
          <span class="history-badge badge-<?= $type ?>">
            <?= $type === 'service' ? '💻 Service Technique' : '🏗️ Chantier' ?>
          </span>
          <svg class="history-chevron" id="chv-<?= $i ?>" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="history-body" id="body-<?= $i ?>">
          <div class="history-fields">
            <?php foreach ($content as $key => $val):
              if (trim((string)$val) === '') continue;
            ?>
              <div>
                <div class="hf-label"><?= htmlspecialchars(fieldLabel($key)) ?></div>
                <div class="hf-value"><?= nl2br(htmlspecialchars($val)) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="history-footer">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            Envoyé à <?= htmlspecialchars($r['manager_name']) ?> — <?= htmlspecialchars($r['manager_email']) ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php layoutEnd(); ?>
