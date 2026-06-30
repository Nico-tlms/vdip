-- Exécuter ce fichier dans la base de données déjà créée via votre panneau d'hébergement
-- NE PAS inclure CREATE DATABASE (interdit sur hébergement mutualisé)

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('technicien','service_technique','admin') NOT NULL DEFAULT 'technicien',
    created_at DATETIME DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS managers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL
);

CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('chantier','service') NOT NULL,
    manager_id INT NOT NULL,
    chantier VARCHAR(200),
    content JSON NOT NULL,
    sent_at DATETIME DEFAULT NOW(),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (manager_id) REFERENCES managers(id)
);

-- Responsables par défaut (modifier les emails)
INSERT INTO managers (name, email) VALUES
    ('Nicolas', 'nicolas@example.com'),
    ('Responsable 2', 'responsable2@example.com');

-- Compte admin par défaut : admin@vdip.fr / admin123
INSERT INTO users (name, email, password, role) VALUES
    ('Administrateur', 'admin@vdip.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
