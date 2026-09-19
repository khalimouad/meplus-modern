<?php
/**
 * ME PLUS Maroc - Configuration Backend & Base de Données
 * Compatible cPanel / Apache / PHP 7.4 - 8.x
 */

// Définition du fuseau horaire
date_default_timezone_set('Africa/Casablanca');

// Paramètres de l'Application
define('APP_NAME', 'ME PLUS Maroc - Administration');
define('APP_VERSION', '2.0.0');
define('APP_URL', '/admin');
define('UPLOAD_DOCS_DIR', __DIR__ . '/../assets/documents');
define('UPLOAD_DOCS_URL', './assets/documents');
define('DATA_JS_PATH', __DIR__ . '/../data.js');
define('DATA_JSON_PATH', __DIR__ . '/../data.json');

// Clé secrète pour le hachage CSRF et les sessions (à personnaliser sur cPanel)
define('APP_SECRET', 'meplus_maroc_secret_key_2026_casablanca_bourgone');

// Configuration Base de Données MySQL (cPanel)
// Modifiez ces valeurs avec vos identifiants cPanel MySQL :
define('DB_HOST', 'localhost');
define('DB_NAME', 'meplus_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Fallback SQLite local si MySQL n'est pas encore configuré
define('DB_USE_SQLITE_FALLBACK', true);
define('SQLITE_FILE', __DIR__ . '/data/meplus.sqlite');

/**
 * Connexion PDO Singleton
 */
function get_db_connection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    // Tentative de connexion MySQL
    $mysql_error = null;
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        $mysql_error = $e->getMessage();
    }

    // Si MySQL échoue et que le fallback SQLite est actif (par exemple en environnement local)
    if (DB_USE_SQLITE_FALLBACK) {
        $sqlite_dir = dirname(SQLITE_FILE);
        if (!is_dir($sqlite_dir)) {
            mkdir($sqlite_dir, 0755, true);
        }
        try {
            $pdo = new PDO("sqlite:" . SQLITE_FILE);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $pdo;
        } catch (PDOException $e) {
            die("Erreur critique de base de données : Impossible de se connecter à MySQL (" . htmlspecialchars($mysql_error) . ") ni à SQLite (" . htmlspecialchars($e->getMessage()) . ").");
        }
    }

    die("Erreur de connexion MySQL : " . htmlspecialchars($mysql_error));
}
