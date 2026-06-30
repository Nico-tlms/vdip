<?php
/**
 * Migration — à exécuter UNE FOIS si vous avez déjà fait setup.php.
 * Ajoute la table report_reads pour la boîte de réception.
 * Supprimez ce fichier après exécution.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

try {
    $db = getDB();
    $db->exec("
        CREATE TABLE IF NOT EXISTS report_reads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            report_id INT NOT NULL,
            user_id INT NOT NULL,
            read_at DATETIME DEFAULT NOW(),
            UNIQUE KEY unique_read (report_id, user_id),
            FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo '<div style="font-family:sans-serif;padding:40px;max-width:500px;margin:auto">
        <h2 style="color:#16a34a">✅ Migration réussie</h2>
        <p>Table <code>report_reads</code> créée.</p>
        <p style="margin-top:16px;color:#dc2626"><strong>Supprimez ce fichier (migrate.php) maintenant.</strong></p>
        <a href="login.php" style="display:inline-block;margin-top:20px;padding:10px 20px;background:#2563eb;color:#fff;border-radius:8px;text-decoration:none">Aller au site</a>
    </div>';
} catch (Exception $e) {
    echo '<div style="font-family:sans-serif;padding:40px;color:#dc2626">
        <h2>❌ Erreur</h2><p>' . htmlspecialchars($e->getMessage()) . '</p>
    </div>';
}
