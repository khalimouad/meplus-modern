<?php
/**
 * ME PLUS Maroc - Module d'Authentification et Sécurité
 */

require_once __DIR__ . '/../config.php';

/**
 * Initialise une session PHP sécurisée
 */
function init_secure_session() {
    if (session_status() === PHP_SESSION_NONE) {
        // Directives de sécurité cookies
        $cookieParams = [
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Strict'
        ];
        session_set_cookie_params($cookieParams);
        session_name('MEPLUS_ADMIN_SESS');
        session_start();
    }

    // Gestion de l'inactivité (30 minutes)
    $timeout = 1800;
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout)) {
        session_unset();
        session_destroy();
        session_start();
    }
    $_SESSION['LAST_ACTIVITY'] = time();
}

/**
 * Génère un jeton anti-CSRF unique
 */
function generate_csrf_token() {
    init_secure_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Affiche le champ input CSRF caché
 */
function csrf_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '" />';
}

/**
 * Vérifie la validité du jeton CSRF
 */
function verify_csrf_token($token = null) {
    init_secure_session();
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Vérifie si l'utilisateur est connecté
 */
function is_logged_in() {
    init_secure_session();
    return !empty($_SESSION['admin_user_id']);
}

/**
 * Protège l'accès à une page admin
 */
function require_auth() {
    init_secure_session();
    if (!is_logged_in()) {
        header('Location: login.php?msg=session_expired');
        exit;
    }
}

/**
 * Authentifie un utilisateur administrateur
 */
function authenticate_admin($username, $password) {
    init_secure_session();
    
    // Limiteur de tentatives anti-bruteforce (max 5 tentatives par tranche de 15 minutes)
    $attempts = $_SESSION['login_attempts'] ?? 0;
    $last_attempt = $_SESSION['last_login_attempt'] ?? 0;
    if ($attempts >= 5 && (time() - $last_attempt) < 900) {
        return ['success' => false, 'error' => 'Trop de tentatives échouées. Veuillez patienter 15 minutes.'];
    }

    $db = get_db_connection();
    $stmt = $db->prepare("SELECT * FROM admins WHERE username = :username LIMIT 1");
    $stmt->execute([':username' => trim($username)]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Succès : réinitialise le compteur de tentatives et régénère l'ID de session
        unset($_SESSION['login_attempts']);
        unset($_SESSION['last_login_attempt']);
        session_regenerate_id(true);

        $_SESSION['admin_user_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['admin_email'] = $user['email'] ?? '';
        $_SESSION['LAST_ACTIVITY'] = time();

        // Mettre à jour la date de dernière connexion
        try {
            $up = $db->prepare("UPDATE admins SET last_login = CURRENT_TIMESTAMP WHERE id = :id");
            $up->execute([':id' => $user['id']]);
        } catch (Exception $e) {}

        return ['success' => true];
    }

    // Échec
    $_SESSION['login_attempts'] = $attempts + 1;
    $_SESSION['last_login_attempt'] = time();
    return ['success' => false, 'error' => 'Identifiant ou mot de passe incorrect.'];
}

/**
 * Déconnecte l'administrateur
 */
function logout_admin() {
    init_secure_session();
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
