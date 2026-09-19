<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';

init_secure_session();

// Si déjà connecté, rediriger vers le tableau de bord
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
$msg = $_GET['msg'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = 'Jeton de sécurité CSRF expiré ou invalide. Veuillez réessayer.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            $error = 'Veuillez renseigner votre identifiant et votre mot de passe.';
        } else {
            $res = authenticate_admin($username, $password);
            if ($res['success']) {
                header('Location: index.php');
                exit;
            } else {
                $error = $res['error'] ?? 'Échec de connexion.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr" class="h-full bg-slate-950 text-slate-100">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion Sécurisée - ME PLUS Maroc</title>
  
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
  
  <style>
    body { font-family: 'Inter', sans-serif; }
    h1 { font-family: 'Outfit', sans-serif; }
  </style>
</head>
<body class="min-h-full flex items-center justify-center p-4 sm:p-6 bg-gradient-to-br from-slate-950 via-[#070e1b] to-slate-900">
  
  <div class="max-w-md w-full bg-slate-900/90 border border-slate-800 rounded-3xl p-8 sm:p-10 shadow-2xl backdrop-blur-2xl">
    
    <!-- Header -->
    <div class="text-center mb-8">
      <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 to-cyan-400 p-0.5 mx-auto mb-3 shadow-lg shadow-cyan-500/20">
        <div class="w-full h-full bg-[#070e1b] rounded-2xl flex items-center justify-center font-bold text-sm font-mono text-cyan-400">
          ME+
        </div>
      </div>
      <h1 class="text-2xl font-black text-white tracking-tight">ME<span class="text-blue-500">PLUS</span> Maroc</h1>
      <p class="text-xs text-slate-400 mt-1 uppercase tracking-wider font-semibold">Espace d'Administration Sécurisé</p>
    </div>

    <!-- Alerts -->
    <?php if ($msg === 'logged_out'): ?>
      <div class="p-3.5 rounded-2xl bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs mb-5 text-center flex items-center justify-center gap-2">
        <i data-lucide="check-circle-2" class="w-4 h-4"></i>
        <span>Vous avez été déconnecté avec succès.</span>
      </div>
    <?php elseif ($msg === 'session_expired'): ?>
      <div class="p-3.5 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400 text-xs mb-5 text-center flex items-center justify-center gap-2">
        <i data-lucide="clock" class="w-4 h-4"></i>
        <span>Session expirée pour inactivité. Veuillez vous reconnecter.</span>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="p-3.5 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-400 text-xs mb-5 text-center flex items-center justify-center gap-2">
        <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
        <span><?= htmlspecialchars($error) ?></span>
      </div>
    <?php endif; ?>

    <!-- Login Form -->
    <form method="POST" action="login.php" class="space-y-4">
      <?= csrf_field() ?>

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1.5 flex items-center gap-1.5">
          <i data-lucide="user" class="w-3.5 h-3.5 text-cyan-400"></i>
          <span>Identifiant</span>
        </label>
        <input 
          type="text" 
          name="username" 
          required 
          autocomplete="username"
          placeholder="ex. admin" 
          class="w-full px-4 py-3 rounded-2xl bg-slate-950 border border-slate-800 text-sm text-white placeholder-slate-500 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 outline-none transition" 
        />
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1.5 flex items-center gap-1.5">
          <i data-lucide="lock" class="w-3.5 h-3.5 text-cyan-400"></i>
          <span>Mot de Passe</span>
        </label>
        <input 
          type="password" 
          name="password" 
          required 
          autocomplete="current-password"
          placeholder="••••••••" 
          class="w-full px-4 py-3 rounded-2xl bg-slate-950 border border-slate-800 text-sm text-white placeholder-slate-500 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 outline-none transition" 
        />
      </div>

      <div class="pt-3">
        <button 
          type="submit" 
          class="w-full py-3.5 rounded-full bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-500 hover:to-cyan-400 text-white font-bold text-xs uppercase tracking-wider transition duration-200 shadow-xl shadow-cyan-500/20 flex items-center justify-center gap-2"
        >
          <span>Se Connecter</span>
          <i data-lucide="arrow-right" class="w-4 h-4"></i>
        </button>
      </div>
    </form>

    <div class="mt-8 pt-6 border-t border-slate-800 text-center text-xs text-slate-500">
      <a href="../index.html" class="hover:text-cyan-400 transition flex items-center justify-center gap-1">
        <i data-lucide="chevron-left" class="w-3.5 h-3.5"></i>
        <span>Retour au site public ME PLUS</span>
      </a>
    </div>

  </div>

  <script>
    if (window.lucide) window.lucide.createIcons();
  </script>
</body>
</html>
