<?php
// Configuration base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'vdip');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configuration email
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'votre.email@gmail.com');
define('SMTP_PASS', 'votre_mot_de_passe_application');
define('SMTP_FROM_NAME', 'Portail CR VDIP');

define('APP_NAME', 'VDIP — Comptes Rendus');
define('APP_URL', 'http://localhost');

session_start();
