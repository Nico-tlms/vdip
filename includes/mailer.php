<?php
require_once dirname(__DIR__) . '/config.php';

function sendReportEmail(array $report, array $manager, array $tech): bool {
    $date    = date('d/m/Y', strtotime($report['sent_at']));
    $type    = $report['type'] === 'service' ? 'Service Technique' : 'Chantier';
    $subject = "=?UTF-8?B?" . base64_encode("CR $type — {$tech['name']} — $date") . "?=";
    $content = json_decode($report['content'], true);
    $html    = buildEmailHtml($content, $report['type'], $tech['name'], $date);

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: =?UTF-8?B?" . base64_encode(SMTP_FROM_NAME) . "?= <" . SMTP_USER . ">\r\n";
    $headers .= "Reply-To: {$tech['email']}\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    return @mail($manager['email'], $subject, $html, $headers);
}

function buildEmailHtml(array $c, string $type, string $techName, string $date): string {
    $rows  = '';
    $color = $type === 'service' ? '#2563eb' : '#d97706';
    foreach ($c as $key => $val) {
        if (trim((string)$val) === '') continue;
        $label = fieldLabel($key);
        $val   = nl2br(htmlspecialchars((string)$val));
        $rows .= "<tr>
            <td style='background:#f8fafc;padding:10px 14px;font-weight:700;font-size:12px;
                        color:#475569;text-transform:uppercase;letter-spacing:.5px;
                        width:35%;vertical-align:top;border-bottom:1px solid #e2e8f0'>$label</td>
            <td style='padding:10px 14px;font-size:14px;line-height:1.6;
                        vertical-align:top;border-bottom:1px solid #e2e8f0'>$val</td>
        </tr>";
    }
    $typeLabel = $type === 'service' ? '💻 SERVICE TECHNIQUE' : '🏗️ CHANTIER';
    return <<<HTML
<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:20px;background:#f1f5f9;font-family:'Segoe UI',Arial,sans-serif">
  <div style="max-width:640px;margin:auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.1)">
    <div style="background:linear-gradient(135deg,#1e3a5f,#2563eb);padding:24px 28px">
      <div style="font-size:11px;color:rgba(255,255,255,.7);text-transform:uppercase;letter-spacing:1px;margin-bottom:6px">Compte Rendu de fin de journée</div>
      <h1 style="margin:0;color:#fff;font-size:20px;font-weight:700">$typeLabel</h1>
      <p style="margin:8px 0 0;color:rgba(255,255,255,.85);font-size:14px">
        👤 $techName &nbsp;·&nbsp; 📅 $date
      </p>
    </div>
    <table style="width:100%;border-collapse:collapse">$rows</table>
    <div style="padding:16px 28px;background:#f8fafc;border-top:1px solid #e2e8f0;
                font-size:11px;color:#94a3b8;text-align:center">
      Envoyé depuis le Portail CR VDIP
    </div>
  </div>
</body></html>
HTML;
}

function fieldLabel(string $key): string {
    return [
        'chantier'        => '📌 Chantier',
        'intervenants'    => '👥 Intervenants',
        'objectif_jour'   => '🎯 Objectif du jour',
        'actions'         => '✅ Actions réalisées',
        'resultats'       => '📊 Résultats obtenus',
        'validations'     => '👤 Validations',
        'problemes'       => '⚠️ Problèmes / Blocages',
        'materiel'        => '🔧 Matériel à prévoir',
        'raf'             => '📅 Reste à faire',
        'anticipe_demain' => '🔮 Anticipé pour demain',
    ][$key] ?? ucfirst($key);
}
