<?php
require_once dirname(__DIR__) . '/config.php';

function sendReportEmail(array $report, array $manager, array $tech): bool {
    $date    = date('d/m/Y', strtotime($report['sent_at']));
    $type    = $report['type'] === 'service' ? 'Service Technique' : 'Chantier';
    $subject = "CR $type — {$tech['name']} — $date";
    $content = json_decode($report['content'], true);
    $html    = buildEmailHtml($content, $report['type'], $tech['name'], $date);

    return smtpSend(
        to:      $manager['email'],
        toName:  $manager['name'],
        subject: $subject,
        body:    $html
    );
}

/**
 * Envoi SMTP natif (sans dépendance) — compatible Hostinger port 587 STARTTLS
 */
function smtpSend(string $to, string $toName, string $subject, string $body): bool {
    $host = SMTP_HOST;
    $port = SMTP_PORT;
    $user = SMTP_USER;
    $pass = SMTP_PASS;
    $from = SMTP_USER;
    $name = SMTP_FROM_NAME;

    $errno = 0; $errstr = '';
    $sock = @fsockopen($host, $port, $errno, $errstr, 10);
    if (!$sock) return false;

    $read = function() use ($sock) { return fgets($sock, 512); };
    $send = function(string $cmd) use ($sock) { fwrite($sock, $cmd . "\r\n"); };

    $read(); // 220 greeting
    $send("EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    while (($line = $read()) && substr($line, 3, 1) === '-');

    // STARTTLS
    $send("STARTTLS");
    $read();
    stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

    $send("EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    while (($line = $read()) && substr($line, 3, 1) === '-');

    // Auth
    $send("AUTH LOGIN");
    $read();
    $send(base64_encode($user));
    $read();
    $send(base64_encode($pass));
    $resp = $read();
    if (substr(trim($resp), 0, 3) !== '235') { fclose($sock); return false; }

    // Enveloppe
    $send("MAIL FROM:<$from>");
    $read();
    $send("RCPT TO:<$to>");
    $read();
    $send("DATA");
    $read();

    // Headers + body
    $date8 = date('r');
    $subjectB64 = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $fromName   = '=?UTF-8?B?' . base64_encode($name)    . '?=';
    $toName     = '=?UTF-8?B?' . base64_encode($toName)  . '?=';

    $message  = "Date: $date8\r\n";
    $message .= "From: $fromName <$from>\r\n";
    $message .= "To: $toName <$to>\r\n";
    $message .= "Subject: $subjectB64\r\n";
    $message .= "MIME-Version: 1.0\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n";
    $message .= "\r\n";
    $message .= chunk_split(base64_encode($body));
    $message .= "\r\n.";

    $send($message);
    $resp = $read();

    $send("QUIT");
    fclose($sock);

    return substr(trim($resp), 0, 3) === '250';
}

function buildEmailHtml(array $c, string $type, string $techName, string $date): string {
    $rows = '';
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
