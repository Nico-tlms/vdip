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


Faire tourner le site sur Ubuntu
Vaut pour un PC de bureau Ubuntu laissé allumé (serveur interne sur le réseau local) comme pour un serveur / VPS Ubuntu.

Option A — réseau local (le plus simple) : accès en http://ip-du-pc:3000 depuis les autres postes, pas de domaine, pas de HTTPS.
Option B — accessible depuis Internet : ajout d'un nom de domaine + HTTPS automatique (Caddy). Voir la fin du document.
Ce qu'il vous faut
Ubuntu 22.04 ou 24.04.
Un accès administrateur (sudo).
2 Go de RAM libres au minimum (surtout pour le build).
Le code : dépôt Git, ou dossier copié sur la machine.
Deux valeurs à choisir : ADMIN_PASSWORD et SESSION_SECRET.
1. Installer les outils
sudo apt update
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs git build-essential python3 sqlite3
node -v      # doit afficher v22.x
2. Récupérer le code
mkdir -p ~/apps && cd ~/apps
git clone VOTRE_DEPOT.git vdip-tutos
cd vdip-tutos
npm ci
(ou copiez le dossier du projet dans ~/apps/vdip-tutos, sans node_modules, puis npm ci.)

3. Configuration (.env.local)
nano ~/apps/vdip-tutos/.env.local
ADMIN_PASSWORD=votre-mot-de-passe-admin
SESSION_SECRET=collez-ici-une-longue-chaine-aleatoire
NEXT_PUBLIC_SITE_NAME=VDIP
COOKIE_INSECURE=1
Générez le secret : node -e "console.log(require('crypto').randomBytes(48).toString('hex'))"
COOKIE_INSECURE=1 : nécessaire pour l'option A (accès en HTTP sur le réseau local), sinon la connexion à /admin échoue. À retirer si vous passez en HTTPS (option B).
4. Build
npm run build
5. Démarrer automatiquement (service systemd)
which npm       # note le chemin, en général /usr/bin/npm
sudo nano /etc/systemd/system/vdip-tutos.service
[Unit]
Description=VDIP - base de tutoriels
After=network.target

[Service]
Type=simple
User=VOTRE_UTILISATEUR
WorkingDirectory=/home/VOTRE_UTILISATEUR/apps/vdip-tutos
ExecStart=/usr/bin/npm run start
Environment=NODE_ENV=production
Environment=PORT=3000
Restart=on-failure
RestartSec=5

[Install]
WantedBy=multi-user.target
Remplacez VOTRE_UTILISATEUR par votre identifiant Ubuntu (whoami).

sudo systemctl daemon-reload
sudo systemctl enable --now vdip-tutos
sudo systemctl status vdip-tutos      # doit être "active (running)"
Test :

curl -I http://127.0.0.1:3000
6. Ouvrir le port sur le réseau local (option A)
sudo ufw allow 3000/tcp
Trouvez l'adresse IP de la machine :

hostname -I
Depuis n'importe quel poste du réseau : http://ADRESSE_IP:3000 (admin : http://ADRESSE_IP:3000/admin).

Le trafic sur le réseau local n'est pas chiffré. Acceptable en interne ; n'exposez pas ce port directement sur Internet sans HTTPS (option B).

7. Empêcher la mise en veille (PC de bureau)
Pour qu'un poste de bureau reste accessible :

sudo systemctl mask sleep.target suspend.target hibernate.target hybrid-sleep.target
(et dans Paramètres → Alimentation, mettre « Extinction de l'écran » sans suspension automatique).

Mettre à jour le site plus tard
cd ~/apps/vdip-tutos
git pull
npm ci
npm run build
sudo systemctl restart vdip-tutos
Sauvegarde
Tout l'essentiel est dans data/ (base + images + pièces jointes). crontab -e :

0 2 * * * sqlite3 ~/apps/vdip-tutos/data/tutosite.db ".backup '$HOME/backups/tutosite-$(date +\%F).db'" && rsync -a --delete ~/apps/vdip-tutos/data/uploads/ ~/backups/uploads/
Copiez ensuite ~/backups/ sur un autre disque / une autre machine.

Journaux (en cas de souci)
journalctl -u vdip-tutos -f
Option B — accessible depuis Internet (domaine + HTTPS)
Faites pointer un nom de domaine (enregistrement DNS A) vers l'IP publique, et ouvrez les ports 80 et 443 (box / pare-feu).

Retirez COOKIE_INSECURE=1 de .env.local, puis sudo systemctl restart vdip-tutos.

Installez Caddy :

sudo apt install -y debian-keyring debian-archive-keyring apt-transport-https curl
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | sudo gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' | sudo tee /etc/apt/sources.list.d/caddy-stable.list
sudo apt update && sudo apt install -y caddy
/etc/caddy/Caddyfile :

tutoriels.vdip.fr {
    reverse_proxy 127.0.0.1:3000
    request_body {
        max_size 30MB
    }



    ---


    Il vous faut
Ubuntu 22.04 / 24.04, accès sudo, ~2 Go de RAM libres.
Node 22, git, build-essential, python3, sqlite3 :
Le code (dépôt Git ou dossier copié) + deux valeurs : ADMIN_PASSWORD, SESSION_SECRET.
Les étapes
git clone … vdip-tutos && cd vdip-tutos && npm ci
Créer .env.local :
npm run build
Service systemd (démarrage auto + relance si plantage) — modèle dans le guide.
sudo ufw allow 3000/tcp, puis accès depuis n'importe quel poste : http://IP-DU-PC:3000 (hostname -I pour l'IP).
Désactiver la mise en veille du PC.
Point important : COOKIE_INSECURE=1
Je viens d'ajouter cette option. Sans elle, sur un accès HTTP en réseau local (pas de HTTPS), le cookie de connexion serait bloqué et la connexion à /admin échouerait. Avec COOKIE_INSECURE=1, ça marche sur le LAN.
→ À retirer seulement si un jour vous passez en HTTPS avec un nom de domaine (partie « Option B » du guide).

Le trafic sur le réseau local n'est pas chiffré : c'est acceptable en interne, mais n'ouvrez pas ce port sur Internet sans HTTPS.
}
sudo systemctl reload caddy — Caddy obtient le certificat tout seul. Site en ligne : https://tutoriels.vdip.fr.
