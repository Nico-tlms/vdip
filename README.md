# VDIP — Portail Comptes Rendus Techniciens

Application PHP/MySQL pour l'envoi quotidien des comptes rendus de fin de journée.

## Installation sur hébergement mutualisé

### 1. Créer la base de données
Dans phpMyAdmin (ou via SSH), importer le fichier `install.sql`.

### 2. Configurer `config.php`
```php
define('DB_HOST', 'localhost');      // Souvent localhost
define('DB_NAME', 'votre_bdd');     // Nom de votre base
define('DB_USER', 'votre_user');    // Utilisateur MySQL
define('DB_PASS', 'votre_mdp');     // Mot de passe MySQL

define('SMTP_USER', 'votre.email@gmail.com');
define('SMTP_PASS', 'mot_de_passe_application_gmail');
```

### 3. Uploader les fichiers
Uploader tout le contenu dans le répertoire `public_html` (ou `www`) de votre hébergeur.

### 4. Connexion initiale
- URL : `https://votredomaine.com/login.php`
- Email : `admin@vdip.fr`
- Mot de passe : `password` ← **à changer immédiatement dans l'admin**

### 5. Créer les comptes techniciens
Allez dans **Admin** → Ajouter les utilisateurs avec leur rôle :
- `technicien` → formulaire Chantier simplifié
- `service_technique` → formulaire complet (Michael, Alexia)

---

## Structure des fichiers
```
├── config.php          ← Configuration BDD & SMTP
├── install.sql         ← Script de création BDD
├── .htaccess           ← Sécurité Apache
├── index.php           ← Formulaire nouveau CR
├── historique.php      ← Historique personnel
├── admin.php           ← Gestion utilisateurs & responsables
├── login.php / logout.php
├── includes/
│   ├── auth.php        ← Authentification
│   ├── db.php          ← Connexion PDO
│   ├── mailer.php      ← Envoi email
│   └── layout.php      ← Gabarit HTML
└── assets/
    ├── css/app.css
    └── js/app.js
```

## Configuration email (Gmail)
1. Activer la validation en 2 étapes sur votre compte Google
2. Créer un "Mot de passe d'application" (Compte Google → Sécurité → Mots de passe des applications)
3. Utiliser ce mot de passe dans `SMTP_PASS`

Si votre hébergeur bloque la fonction `mail()`, installez PHPMailer via Composer ou demandez à votre hébergeur d'activer l'envoi SMTP.
