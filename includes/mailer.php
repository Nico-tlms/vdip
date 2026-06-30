<?php
require_once __DIR__ . '/../config.php';

/**
 * Envoi d'un email via SMTP en PHP natif (sans dépendance externe).
 * Pour un hébergement mutualisé la fonction mail() suffit souvent.
 * Si votre hébergeur supporte PHP mail(), laissez USE_PHP_MAIL = true.
 */
define('USE_PHP_MAIL', true);

function sendReportEmail(array $report, array $manager, array $tech): bool {
    $date    = date('d/m/Y', strtotime($report['sent_at']));
    $type    = $report['type'] === 'service' ? 'Service Technique' : 'Chantier';
    $subject = "CR $type — {$tech['name']} — $date";
    $content = json_decode($report['content'], true);

    $html = buildEmailHtml($content, $report['type'], $tech['name'], $date, $report['chantier']);

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_USER . ">\r\n";
    $headers .= "Reply-To: {$tech['email']}\r\n";

    return mail($manager['email'], $subject, $html, $headers);
}

function buildEmailHtml(array $c, string $type, string $techName, string $date, ?string $chantier): string {
    $rows = '';
    foreach ($c as $key => $val) {
        if (empty($val)) continue;
        $label = fieldLabel($key);
        $val   = nl2br(htmlspecialchars((string)$val));
        $rows .= "<tr><td style='background:#f5f5f5;padding:8px 12px;font-weight:600;width:38%;vertical-align:top;border-bottom:1px solid #e0e0e0'>$label</td>"
               . "<td style='padding:8px 12px;vertical-align:top;border-bottom:1px solid #e0e0e0'>$val</td></tr>";
    }

    $typeLabel = $type === 'service' ? 'SERVICE TECHNIQUE' : 'CHANTIER';

    return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:Arial,sans-serif;background:#f0f0f0;margin:0;padding:20px">
  <div style="max-width:680px;margin:auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.15)">
    <div style="background:#1a3a5c;color:#fff;padding:20px 24px">
      <h2 style="margin:0;font-size:18px">🔧 COMPTE RENDU — $typeLabel</h2>
      <p style="margin:6px 0 0;opacity:.85;font-size:14px">$techName &nbsp;|&nbsp; $date</p>
    </div>
    <table style="width:100%;border-collapse:collapse;font-size:14px">
      $rows
    </table>
    <div style="padding:14px 24px;background:#f9f9f9;font-size:12px;color:#999;border-top:1px solid #e0e0e0">
      Envoyé depuis le portail CR VDIP
    </div>
  </div>
</body>
</html>
HTML;
}

function fieldLabel(string $key): string {
    $labels = [
        'chantier'          => '📌 Chantier / Affaire',
        'intervenants'      => '👥 Intervenants présents',
        'objectif_jour'     => '🎯 Objectif du jour',
        'actions'           => '✅ Actions menées / Ce qui a été fait',
        'resultats'         => '📊 Résultats obtenus',
        'validations'       => '👤 Validations obtenues',
        'problemes'         => '⚠️ Problèmes / Blocages rencontrés',
        'materiel'          => '🔧 Matériel manquant ou à prévoir',
        'raf'               => '📅 Reste à faire / Objectif demain',
        'anticipe_demain'   => '🔮 Anticipé pour demain',
    ];
    return $labels[$key] ?? ucfirst($key);
}
