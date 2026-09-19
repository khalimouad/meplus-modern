<?php
$page_title = "Paramètres & Sécurité";
$page_subtitle = "Configuration système, sécurité administrateur et déploiement cPanel";
require_once __DIR__ . '/includes/header.php';

$db = get_db_connection();
$admin_id = $_SESSION['admin_user_id'] ?? 0;

$stmt = $db->prepare("SELECT * FROM admins WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $admin_id]);
$current_admin = $stmt->fetch();

$error = null;
$success = null;

// TRAITEMENT DU CHANGEMENT DE MOT DE PASSE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    if (!verify_csrf_token()) {
        $error = "Erreur de sécurité CSRF.";
    } else {
        $old_pass = $_POST['old_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        if (!password_verify($old_pass, $current_admin['password_hash'])) {
            $error = "Le mot de passe actuel est incorrect.";
        } elseif (strlen($new_pass) < 8) {
            $error = "Le nouveau mot de passe doit comporter au moins 8 caractères.";
        } elseif ($new_pass !== $confirm_pass) {
            $error = "Les nouveaux mots de passe ne correspondent pas.";
        } else {
            $new_hash = password_hash($new_pass, PASSWORD_BCRYPT);
            $upd = $db->prepare("UPDATE admins SET password_hash = :hash WHERE id = :id");
            $upd->execute([':hash' => $new_hash, ':id' => $admin_id]);
            $success = "Votre mot de passe a été modifié avec succès.";
        }
    }
}

// TRAITEMENT DES INFOS DU PROFIL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    if (!verify_csrf_token()) {
        $error = "Erreur de sécurité CSRF.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($username) || empty($email)) {
            $error = "L'identifiant et l'adresse email sont requis.";
        } else {
            $upd = $db->prepare("UPDATE admins SET username = :username, email = :email WHERE id = :id");
            $upd->execute([':username' => $username, ':email' => $email, ':id' => $admin_id]);
            $_SESSION['admin_username'] = $username;
            $_SESSION['admin_email'] = $email;
            $success = "Profil administrateur mis à jour avec succès.";
            $current_admin['username'] = $username;
            $current_admin['email'] = $email;
        }
    }
}
?>

<div class="max-w-4xl mx-auto space-y-8">

  <!-- Alerts -->
  <?php if ($success): ?>
    <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-400"></i>
        <span class="text-sm font-medium"><?= e($success) ?></span>
      </div>
      <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-white"><i data-lucide="x" class="w-4 h-4"></i></button>
    </div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-400"></i>
        <span class="text-sm font-medium"><?= e($error) ?></span>
      </div>
      <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-white"><i data-lucide="x" class="w-4 h-4"></i></button>
    </div>
  <?php endif; ?>

  <!-- Profil & Identifiants -->
  <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800/80 space-y-4">
    <div class="border-b border-slate-800 pb-3">
      <h3 class="text-sm font-bold text-white flex items-center gap-2">
        <i data-lucide="user" class="w-4 h-4 text-blue-400"></i>
        <span>Profil Administrateur</span>
      </h3>
      <p class="text-xs text-slate-400 mt-0.5">Identifiants de connexion au panneau de gestion</p>
    </div>

    <form method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="update_profile">

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">Nom d'utilisateur / Identifiant</label>
        <input 
          type="text" 
          name="username" 
          value="<?= e($current_admin['username'] ?? 'admin') ?>" 
          required 
          class="w-full px-3.5 py-2 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-blue-500"
        >
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">Adresse Email</label>
        <input 
          type="email" 
          name="email" 
          value="<?= e($current_admin['email'] ?? 'direction@meplus.ma') ?>" 
          required 
          class="w-full px-3.5 py-2 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-blue-500"
        >
      </div>

      <div class="sm:col-span-2 flex justify-end">
        <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white text-xs font-semibold transition">
          Mettre à jour le profil
        </button>
      </div>
    </form>
  </div>

  <!-- Modification du Mot de passe -->
  <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800/80 space-y-4">
    <div class="border-b border-slate-800 pb-3">
      <h3 class="text-sm font-bold text-white flex items-center gap-2">
        <i data-lucide="key" class="w-4 h-4 text-emerald-400"></i>
        <span>Changer le Mot de Passe Administrateur</span>
      </h3>
      <p class="text-xs text-slate-400 mt-0.5">Le mot de passe est sécurisé avec l'algorithme BCrypt</p>
    </div>

    <form method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="change_password">

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">Mot de passe actuel</label>
        <input 
          type="password" 
          name="old_password" 
          required 
          class="w-full px-3.5 py-2 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500"
        >
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">Nouveau mot de passe (min 8 car.)</label>
        <input 
          type="password" 
          name="new_password" 
          required 
          minlength="8"
          class="w-full px-3.5 py-2 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500"
        >
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">Confirmer le mot de passe</label>
        <input 
          type="password" 
          name="confirm_password" 
          required 
          minlength="8"
          class="w-full px-3.5 py-2 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500"
        >
      </div>

      <div class="sm:col-span-3 flex justify-end">
        <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition shadow-lg shadow-emerald-600/20">
          Enregistrer le nouveau mot de passe
        </button>
      </div>
    </form>
  </div>

  <!-- Guide de Déploiement cPanel & MySQL -->
  <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800/80 space-y-4 text-xs">
    <div class="border-b border-slate-800 pb-3">
      <h3 class="text-sm font-bold text-white flex items-center gap-2">
        <i data-lucide="server" class="w-4 h-4 text-cyan-400"></i>
        <span>Guide de Configuration cPanel & MySQL</span>
      </h3>
      <p class="text-xs text-slate-400 mt-0.5">Pour le déploiement en production sur votre hébergement cPanel</p>
    </div>

    <div class="space-y-3 text-slate-300 leading-relaxed">
      <p>
        Pour connecter l'application à votre base de données MySQL cPanel, éditez simplement les variables dans le fichier 
        <code class="px-2 py-0.5 rounded bg-slate-800 text-cyan-300 font-mono">admin/config.php</code> :
      </p>

      <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 font-mono text-[11px] text-cyan-200 space-y-1">
        <div>define('DB_HOST', 'localhost');</div>
        <div>define('DB_NAME', 'votre_cpanel_meplus_db');</div>
        <div>define('DB_USER', 'votre_cpanel_meplus_user');</div>
        <div>define('DB_PASS', 'votre_mot_de_passe_robuste');</div>
      </div>

      <p>
        Ensuite, rendez-vous sur <code class="px-2 py-0.5 rounded bg-slate-800 text-cyan-300 font-mono">https://votre-domaine.ma/admin/install.php</code> pour créer les tables automatiquement et importer l'intégralité des données en 1 clic !
      </p>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
