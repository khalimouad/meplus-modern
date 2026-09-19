<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_auth();

$current_page = basename($_SERVER['PHP_SELF']);
$admin_user = $_SESSION['admin_username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="fr" class="h-full bg-slate-950 text-slate-100">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($page_title) ? e($page_title) . ' - ' : '' ?>Administration ME PLUS Maroc</title>
  
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  
  <!-- Lucide Icons -->
  <script src="https://unpkg.com/lucide@latest"></script>

  <!-- Google Fonts Inter & Outfit -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">

  <style>
    body { font-family: 'Inter', sans-serif; }
    h1, h2, h3, h4 { font-family: 'Outfit', sans-serif; }
    .nav-active {
      background: linear-gradient(135deg, rgba(37, 99, 235, 0.25), rgba(6, 182, 212, 0.15));
      border-color: rgba(6, 182, 212, 0.4);
      color: #38bdf8;
      font-weight: 700;
    }
  </style>
</head>
<body class="min-h-full flex flex-col md:flex-row bg-[#060c18] text-slate-200">

  <!-- SIDEBAR NAVIGATION -->
  <aside class="w-full md:w-64 bg-slate-900/80 border-r border-slate-800/80 p-5 flex flex-col justify-between shrink-0 backdrop-blur-xl">
    <div>
      
      <!-- Brand Logo Header -->
      <div class="flex items-center justify-between pb-6 mb-6 border-b border-slate-800">
        <a href="index.php" class="flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-2xl bg-gradient-to-tr from-blue-600 to-cyan-400 p-0.5 shadow-md shadow-cyan-500/10">
            <div class="w-full h-full bg-[#070e1b] rounded-2xl flex items-center justify-center font-bold text-xs font-mono text-cyan-400">
              ME+
            </div>
          </div>
          <div>
            <span class="text-base font-extrabold text-white tracking-tight">ME<span class="text-blue-500">PLUS</span></span>
            <span class="block text-[9px] uppercase tracking-wider text-slate-400 font-semibold">Administration</span>
          </div>
        </a>
      </div>

      <!-- Navigation Links -->
      <nav class="space-y-1 text-xs font-medium">
        <a href="index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl border border-transparent hover:bg-slate-800/60 transition <?= $current_page === 'index.php' ? 'nav-active' : 'text-slate-300' ?>">
          <i data-lucide="layout-dashboard" class="w-4 h-4 text-cyan-400"></i>
          <span>Tableau de Bord</span>
        </a>

        <a href="formations.php" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl border border-transparent hover:bg-slate-800/60 transition <?= in_array($current_page, ['formations.php', 'formation_edit.php']) ? 'nav-active' : 'text-slate-300' ?>">
          <div class="flex items-center gap-3">
            <i data-lucide="book-open" class="w-4 h-4 text-blue-400"></i>
            <span>Formations</span>
          </div>
          <span class="text-[10px] font-bold bg-blue-500/20 text-blue-400 px-2 py-0.5 rounded-full border border-blue-500/30">85+</span>
        </a>

        <a href="pages.php" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl border border-transparent hover:bg-slate-800/60 transition <?= in_array($current_page, ['pages.php', 'page_edit.php']) ? 'nav-active' : 'text-slate-300' ?>">
          <div class="flex items-center gap-3">
            <i data-lucide="file-edit" class="w-4 h-4 text-emerald-400"></i>
            <span>Pages & Sections</span>
          </div>
          <span class="text-[10px] text-slate-400">7 pages</span>
        </a>

        <a href="documents.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl border border-transparent hover:bg-slate-800/60 transition <?= $current_page === 'documents.php' ? 'nav-active' : 'text-slate-300' ?>">
          <i data-lucide="file-text" class="w-4 h-4 text-amber-400"></i>
          <span>Documents & Décrets</span>
        </a>

        <a href="consultants.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl border border-transparent hover:bg-slate-800/60 transition <?= $current_page === 'consultants.php' ? 'nav-active' : 'text-slate-300' ?>">
          <i data-lucide="users" class="w-4 h-4 text-purple-400"></i>
          <span>Consultants Seniors</span>
        </a>

        <a href="clients.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl border border-transparent hover:bg-slate-800/60 transition <?= $current_page === 'clients.php' ? 'nav-active' : 'text-slate-300' ?>">
          <i data-lucide="building-2" class="w-4 h-4 text-cyan-400"></i>
          <span>Clients & Logos</span>
        </a>

        <a href="leads.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl border border-transparent hover:bg-slate-800/60 transition <?= $current_page === 'leads.php' ? 'nav-active' : 'text-slate-300' ?>">
          <i data-lucide="inbox" class="w-4 h-4 text-rose-400"></i>
          <span>Demandes & Devis</span>
        </a>

        <a href="settings.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl border border-transparent hover:bg-slate-800/60 transition <?= $current_page === 'settings.php' ? 'nav-active' : 'text-slate-300' ?>">
          <i data-lucide="settings" class="w-4 h-4 text-slate-400"></i>
          <span>Paramètres & Sécurité</span>
        </a>
      </nav>
    </div>

    <!-- Bottom User Section -->
    <div class="pt-6 mt-6 border-t border-slate-800">
      <div class="flex items-center justify-between mb-3 px-1 text-xs">
        <span class="text-slate-400">Connecté en :</span>
        <span class="font-bold text-white"><?= e($admin_user) ?></span>
      </div>
      <div class="flex items-center gap-2">
        <a href="../index.html" target="_blank" class="flex-1 py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-[11px] font-semibold flex items-center justify-center gap-1.5 transition">
          <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
          <span>Voir Site</span>
        </a>
        <a href="logout.php" class="py-2 px-3 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-400 text-[11px] font-semibold flex items-center justify-center gap-1 transition" title="Déconnexion">
          <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
        </a>
      </div>
    </div>
  </aside>

  <!-- MAIN VIEWPORT -->
  <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
    
    <!-- Top Action Bar -->
    <header class="h-16 border-b border-slate-800/80 px-6 sm:px-8 flex items-center justify-between bg-slate-900/40 backdrop-blur-md sticky top-0 z-30">
      <div class="flex items-center gap-3">
        <h2 class="text-base sm:text-lg font-bold text-white"><?= e($page_title ?? 'Administration') ?></h2>
        <?php if (isset($page_subtitle)): ?>
          <span class="hidden sm:inline text-xs text-slate-400">• <?= e($page_subtitle) ?></span>
        <?php endif; ?>
      </div>

      <div class="flex items-center gap-3">
        <a href="sync.php" class="px-3.5 py-1.5 rounded-full bg-cyan-500/10 text-cyan-300 border border-cyan-500/20 text-xs font-semibold hover:bg-cyan-500/20 transition flex items-center gap-1.5">
          <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
          <span>Synchroniser le Cache</span>
        </a>
      </div>
    </header>

    <!-- Page Content Container -->
    <main class="flex-1 p-6 sm:p-8 max-w-7xl w-full mx-auto">
