<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/mailer.php';
require_once __DIR__ . '/includes/layout.php';

requireLogin();

$mgr  = currentManagerRecord();
$user = currentUser();

if (!$mgr) {
    header('Location: ' . baseUrl() . '/index.php');
    exit;
}

$db = getDB();

// Vue détail d'un CR
if (isset($_GET['id'])) {
    $reportId = (int)$_GET['id'];

    $stmt = $db->prepare('
        SELECT r.*, u.name AS tech_name, u.email AS tech_email
        FROM reports r
        JOIN users u ON u.id = r.user_id
        WHERE r.id = ? AND r.manager_id = ?
    ');
    $stmt->execute([$reportId, $mgr['id']]);
    $report = $stmt->fetch();

    if (!$report) {
        header('Location: ' . baseUrl() . '/inbox.php');
        exit;
    }

    // Marquer comme lu
    $db->prepare('INSERT IGNORE INTO report_reads (report_id, user_id) VALUES (?, ?)')
       ->execute([$reportId, $user['id']]);

    $content = json_decode($report['content'], true) ?? [];
    $date    = date('d/m/Y à H:i', strtotime($report['sent_at']));
    $type    = $report['type'];
    $initials = strtoupper(mb_substr($report['tech_name'], 0, 1));

    layoutStart('CR de ' . $report['tech_name'], 'inbox');
    ?>

    <div class="page-header">
      <a href="<?= baseUrl() ?>/inbox.php" style="display:inline-flex;align-items:center;gap:6px;color:var(--blue-600);font-size:14px;font-weight:600;text-decoration:none;margin-bottom:14px">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
        Retour à la boîte de réception
      </a>
    </div>

    <div class="card">
      <div class="cr-detail-header">
        <div class="cr-detail-avatar <?= $type === 'service' ? 'service' : '' ?>"><?= $initials ?></div>
        <div class="cr-detail-meta">
          <h2><?= htmlspecialchars($report['chantier']) ?></h2>
          <p>
            De <strong><?= htmlspecialchars($report['tech_name']) ?></strong>
            &nbsp;·&nbsp; <?= $date ?>
            &nbsp;·&nbsp;
            <span class="history-badge badge-<?= $type ?>">
              <?= $type === 'service' ? '💻 Service Technique' : '🏗️ Chantier' ?>
            </span>
          </p>
        </div>
      </div>

      <div class="cr-fields">
        <?php foreach ($content as $key => $val):
          if (trim((string)$val) === '') continue;
        ?>
          <div>
            <div class="cr-field-label"><?= htmlspecialchars(fieldLabel($key)) ?></div>
            <div class="cr-field-value"><?= nl2br(htmlspecialchars($val)) ?></div>
          </div>
        <?php endforeach; ?>
      </div>

      <div style="margin-top:24px;padding-top:16px;border-top:1px solid var(--slate-200);display:flex;justify-content:space-between;align-items:center">
        <span style="font-size:13px;color:var(--slate-400)">
          📧 <?= htmlspecialchars($report['tech_email']) ?>
        </span>
        <a href="mailto:<?= htmlspecialchars($report['tech_email']) ?>?subject=Re: CR <?= urlencode($report['chantier']) ?>"
           class="btn btn-secondary" style="font-size:13px;padding:8px 16px">
          Répondre par email
        </a>
      </div>
    </div>

    <?php layoutEnd();
    exit;
}

// Liste des CR reçus
$stmt = $db->prepare('
    SELECT r.*,
           u.name  AS tech_name,
           u.email AS tech_email,
           IF(rr.id IS NOT NULL, 1, 0) AS is_read
    FROM reports r
    JOIN users u ON u.id = r.user_id
    LEFT JOIN report_reads rr ON rr.report_id = r.id AND rr.user_id = ?
    WHERE r.manager_id = ?
    ORDER BY r.sent_at DESC
');
$stmt->execute([$user['id'], $mgr['id']]);
$reports = $stmt->fetchAll();

$unread = array_sum(array_column($reports, 'is_read') === [] ? [] :
    array_map(fn($r) => $r['is_read'] ? 0 : 1, $reports));

layoutStart('Boîte de réception', 'inbox');
?>

<div class="page-header">
  <h1>📥 Boîte de réception</h1>
  <p>
    <?= count($reports) ?> message<?= count($reports) > 1 ? 's' : '' ?>
    <?php if ($unread > 0): ?>
      &nbsp;·&nbsp; <strong style="color:var(--blue-600)"><?= $unread ?> non lu<?= $unread > 1 ? 's' : '' ?></strong>
    <?php endif; ?>
  </p>
</div>

<div class="card" style="padding:8px 0">
  <?php if (empty($reports)): ?>
    <div class="empty-state">
      <div class="empty-icon">📭</div>
      <h3>Aucun message reçu</h3>
      <p>Les comptes rendus envoyés par vos techniciens apparaîtront ici.</p>
    </div>
  <?php else: ?>
    <div class="inbox-list">
      <?php foreach ($reports as $r):
        $content  = json_decode($r['content'], true) ?? [];
        $isUnread = !$r['is_read'];
        $date     = date('d/m/Y', strtotime($r['sent_at']));
        $time     = date('H:i', strtotime($r['sent_at']));
        $isToday  = date('Y-m-d', strtotime($r['sent_at'])) === date('Y-m-d');
        $displayDate = $isToday ? $time : $date;
        $initials = strtoupper(mb_substr($r['tech_name'], 0, 1));

        // Prévisualisation
        $preview = '';
        foreach (['actions', 'objectif_jour', 'resultats'] as $k) {
            if (!empty($content[$k])) {
                $preview = mb_substr(strip_tags($content[$k]), 0, 80);
                if (mb_strlen($content[$k]) > 80) $preview .= '…';
                break;
            }
        }
      ?>
        <a href="<?= baseUrl() ?>/inbox.php?id=<?= $r['id'] ?>"
           class="inbox-item <?= $isUnread ? 'unread' : '' ?>">
          <div class="inbox-dot <?= $isUnread ? 'unread-dot' : 'read-dot' ?>"></div>
          <div class="inbox-avatar <?= $r['type'] === 'service' ? 'service' : '' ?>"><?= $initials ?></div>
          <div class="inbox-body">
            <div class="inbox-top">
              <span class="inbox-sender"><?= htmlspecialchars($r['tech_name']) ?></span>
              <span class="inbox-date"><?= $displayDate ?></span>
            </div>
            <div class="inbox-chantier"><?= htmlspecialchars($r['chantier']) ?></div>
            <?php if ($preview): ?>
              <div class="inbox-preview"><?= htmlspecialchars($preview) ?></div>
            <?php endif; ?>
          </div>
          <span class="inbox-type-chip <?= $r['type'] === 'service' ? 'badge-service' : 'badge-chantier' ?>">
            <?= $r['type'] === 'service' ? '💻' : '🏗️' ?>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php layoutEnd(); ?>
