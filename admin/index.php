<?php
$page_title = "Tableau de Bord";
$page_subtitle = "Vue d'ensemble de la plateforme ME PLUS Maroc";
require_once __DIR__ . '/includes/header.php';

$db = get_db_connection();

// Compteurs
$total_formations = 0;
$total_docs = 0;
$total_consultants = 0;
$total_clients = 0;
$total_leads = 0;

try {
    $total_formations = (int)$db->query("SELECT COUNT(*) FROM formations")->fetchColumn();
    $total_docs = (int)$db->query("SELECT COUNT(*) FROM documents")->fetchColumn();
    $total_consultants = (int)$db->query("SELECT COUNT(*) FROM consultants")->fetchColumn();
    $total_clients = (int)$db->query("SELECT COUNT(*) FROM clients")->fetchColumn();
    $total_leads = (int)$db->query("SELECT COUNT(*) FROM leads")->fetchColumn();
} catch (Exception $e) {}

// Dernières formations
$recent_formations = [];
try {
    $stmt = $db->query("SELECT id, code, title, domain, is_published, updated_at FROM formations ORDER BY updated_at DESC, id DESC LIMIT 6");
    $recent_formations = $stmt->fetchAll();
} catch (Exception $e) {}

// Info cache
$cache_time = file_exists(DATA_JSON_PATH) ? date('d/m/Y H:i', filemtime(DATA_JSON_PATH)) : 'Non généré';
$cache_size = file_exists(DATA_JSON_PATH) ? round(filesize(DATA_JSON_PATH) / 1024, 1) . ' Ko' : '0 Ko';

// Flash messages
$flash_synced = isset($_GET['synced']);
$flash_installed = isset($_GET['msg']) && $_GET['msg'] === 'installed';
?>

<div class="space-y-8">

  <!-- Flash Alerts -->
  <?php if ($flash_synced): ?>
    <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-400 shrink-0"></i>
        <div class="text-sm font-medium">Le cache du site public (<strong>data.json</strong> et <strong>data.js</strong>) a été régénéré et synchronisé avec succès !</div>
      </div>
      <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-white"><i data-lucide="x" class="w-4 h-4"></i></button>
    </div>
  <?php endif; ?>

  <?php if ($flash_installed): ?>
    <div class="p-4 rounded-2xl bg-cyan-500/10 border border-cyan-500/30 text-cyan-400 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <i data-lucide="sparkles" class="w-5 h-5 text-cyan-400 shrink-0"></i>
        <div class="text-sm font-medium">Base de données ME PLUS initialisée avec succès ! Toutes les formations, décrets et consultants sont prêts.</div>
      </div>
      <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-white"><i data-lucide="x" class="w-4 h-4"></i></button>
    </div>
  <?php endif; ?>

  <!-- KPI CARDS GRID -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
    
    <!-- Card Formations -->
    <a href="formations.php" class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800/80 hover:border-blue-500/40 hover:bg-slate-800/40 transition group">
      <div class="flex items-center justify-between mb-3">
        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Formations Actives</span>
        <div class="w-10 h-10 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 group-hover:scale-110 transition">
          <i data-lucide="book-open" class="w-5 h-5"></i>
        </div>
      </div>
      <div class="text-3xl font-extrabold text-white mb-1"><?= $total_formations ?></div>
      <div class="flex items-center gap-1.5 text-xs text-blue-400">
        <span>Gérer le catalogue</span>
        <i data-lucide="arrow-right" class="w-3.5 h-3.5 group-hover:translate-x-1 transition"></i>
      </div>
    </a>

    <!-- Card Pages & Sections -->
    <a href="pages.php" class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800/80 hover:border-emerald-500/40 hover:bg-slate-800/40 transition group">
      <div class="flex items-center justify-between mb-3">
        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pages Éditables</span>
        <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 group-hover:scale-110 transition">
          <i data-lucide="file-edit" class="w-5 h-5"></i>
        </div>
      </div>
      <div class="text-3xl font-extrabold text-white mb-1">7</div>
      <div class="flex items-center gap-1.5 text-xs text-emerald-400">
        <span>Éditer sections par page</span>
        <i data-lucide="arrow-right" class="w-3.5 h-3.5 group-hover:translate-x-1 transition"></i>
      </div>
    </a>

    <!-- Card Documents & Décrets -->
    <a href="documents.php" class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800/80 hover:border-amber-500/40 hover:bg-slate-800/40 transition group">
      <div class="flex items-center justify-between mb-3">
        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Décrets & Documents</span>
        <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 group-hover:scale-110 transition">
          <i data-lucide="file-text" class="w-5 h-5"></i>
        </div>
      </div>
      <div class="text-3xl font-extrabold text-white mb-1"><?= $total_docs ?></div>
      <div class="flex items-center gap-1.5 text-xs text-amber-400">
        <span>Gérer les fichiers PDF</span>
        <i data-lucide="arrow-right" class="w-3.5 h-3.5 group-hover:translate-x-1 transition"></i>
      </div>
    </a>

    <!-- Card Consultants -->
    <a href="consultants.php" class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800/80 hover:border-purple-500/40 hover:bg-slate-800/40 transition group">
      <div class="flex items-center justify-between mb-3">
        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Consultants Seniors</span>
        <div class="w-10 h-10 rounded-xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400 group-hover:scale-110 transition">
          <i data-lucide="users" class="w-5 h-5"></i>
        </div>
      </div>
      <div class="text-3xl font-extrabold text-white mb-1"><?= $total_consultants ?></div>
      <div class="flex items-center gap-1.5 text-xs text-purple-400">
        <span>Gérer l'équipe d'experts</span>
        <i data-lucide="arrow-right" class="w-3.5 h-3.5 group-hover:translate-x-1 transition"></i>
      </div>
    </a>

  </div>

  <!-- QUICK ACTIONS & RECENT FORMATIONS -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Recent Formations Table (2 cols) -->
    <div class="lg:col-span-2 rounded-2xl bg-slate-900/60 border border-slate-800/80 p-6">
      <div class="flex items-center justify-between mb-5">
        <div>
          <h3 class="text-base font-bold text-white">Dernières Formations Modifiées</h3>
          <p class="text-xs text-slate-400">Catalogue homologué DFP & GIAC</p>
        </div>
        <a href="formations.php" class="text-xs text-blue-400 hover:text-blue-300 font-semibold flex items-center gap-1">
          <span>Voir tout (<?= $total_formations ?>)</span>
          <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
        </a>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="border-b border-slate-800 text-slate-400 pb-2">
              <th class="py-2.5 font-semibold">Titre de la formation</th>
              <th class="py-2.5 font-semibold">Domaine</th>
              <th class="py-2.5 font-semibold">Statut</th>
              <th class="py-2.5 font-semibold text-right">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/60">
            <?php if (empty($recent_formations)): ?>
              <tr>
                <td colspan="4" class="py-8 text-center text-slate-500">Aucune formation trouvée. Veuillez lancer <a href="install.php" class="text-cyan-400 underline">l'installation initiale</a>.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($recent_formations as $rf): ?>
                <tr class="hover:bg-slate-800/30 transition">
                  <td class="py-3 pr-3 font-medium text-white max-w-xs truncate">
                    <?= e($rf['title']) ?>
                  </td>
                  <td class="py-3 pr-3">
                    <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-800 text-slate-300 border border-slate-700">
                      <?= e($rf['domain']) ?>
                    </span>
                  </td>
                  <td class="py-3 pr-3">
                    <?php if ($rf['is_published']): ?>
                      <span class="inline-flex items-center gap-1 text-[11px] text-emerald-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Publié
                      </span>
                    <?php else: ?>
                      <span class="text-[11px] text-amber-400">Brouillon</span>
                    <?php endif; ?>
                  </td>
                  <td class="py-3 text-right">
                    <a href="formation_edit.php?id=<?= $rf['id'] ?>" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 text-[11px] font-medium transition">
                      <i data-lucide="edit-3" class="w-3 h-3"></i>
                      <span>Modifier</span>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="pt-4 mt-4 border-t border-slate-800/60 flex items-center justify-between">
        <a href="formation_edit.php" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold flex items-center gap-2 transition shadow-lg shadow-blue-600/20">
          <i data-lucide="plus-circle" class="w-4 h-4"></i>
          <span>Ajouter une Formation</span>
        </a>
      </div>
    </div>

    <!-- Quick Shortcuts & System Status (1 col) -->
    <div class="space-y-6">

      <!-- Action Card -->
      <div class="rounded-2xl bg-gradient-to-br from-blue-950/40 via-slate-900/60 to-slate-900/60 border border-slate-800/80 p-6">
        <h3 class="text-sm font-bold text-white mb-3 flex items-center gap-2">
          <i data-lucide="sparkles" class="w-4 h-4 text-cyan-400"></i>
          <span>Actions Rapides</span>
        </h3>
        
        <div class="space-y-2">
          <a href="formation_edit.php" class="flex items-center justify-between p-3 rounded-xl bg-slate-800/50 hover:bg-slate-800 text-slate-200 hover:text-white text-xs font-medium transition border border-slate-700/40">
            <span class="flex items-center gap-2.5">
              <i data-lucide="plus" class="w-4 h-4 text-blue-400"></i>
              <span>Nouvelle Formation</span>
            </span>
            <i data-lucide="chevron-right" class="w-4 h-4 text-slate-500"></i>
          </a>

          <a href="page_edit.php?page=index" class="flex items-center justify-between p-3 rounded-xl bg-slate-800/50 hover:bg-slate-800 text-slate-200 hover:text-white text-xs font-medium transition border border-slate-700/40">
            <span class="flex items-center gap-2.5">
              <i data-lucide="edit-3" class="w-4 h-4 text-emerald-400"></i>
              <span>Modifier Page d'Accueil</span>
            </span>
            <i data-lucide="chevron-right" class="w-4 h-4 text-slate-500"></i>
          </a>

          <a href="documents.php" class="flex items-center justify-between p-3 rounded-xl bg-slate-800/50 hover:bg-slate-800 text-slate-200 hover:text-white text-xs font-medium transition border border-slate-700/40">
            <span class="flex items-center gap-2.5">
              <i data-lucide="upload" class="w-4 h-4 text-amber-400"></i>
              <span>Téléverser un PDF Décret</span>
            </span>
            <i data-lucide="chevron-right" class="w-4 h-4 text-slate-500"></i>
          </a>

          <a href="sync.php" class="flex items-center justify-between p-3 rounded-xl bg-cyan-950/30 hover:bg-cyan-900/30 text-cyan-300 text-xs font-semibold transition border border-cyan-800/40">
            <span class="flex items-center gap-2.5">
              <i data-lucide="refresh-cw" class="w-4 h-4 text-cyan-400"></i>
              <span>Forcer Sync Cache</span>
            </span>
            <i data-lucide="arrow-right" class="w-4 h-4 text-cyan-400"></i>
          </a>
        </div>
      </div>

      <!-- System Status Card -->
      <div class="rounded-2xl bg-slate-900/60 border border-slate-800/80 p-6 text-xs">
        <h3 class="text-sm font-bold text-white mb-3 flex items-center gap-2">
          <i data-lucide="shield-check" class="w-4 h-4 text-emerald-400"></i>
          <span>Sécurité & Hébergement</span>
        </h3>

        <div class="space-y-2.5 text-slate-300">
          <div class="flex justify-between py-1.5 border-b border-slate-800">
            <span class="text-slate-400">Moteur Base de données :</span>
            <span class="font-semibold text-emerald-400"><?= DB_DRIVER === 'mysql' ? 'MySQL (cPanel)' : 'SQLite Local' ?></span>
          </div>
          <div class="flex justify-between py-1.5 border-b border-slate-800">
            <span class="text-slate-400">Version PHP requise :</span>
            <span class="font-mono text-slate-200">7.4 - 8.x+</span>
          </div>
          <div class="flex justify-between py-1.5 border-b border-slate-800">
            <span class="text-slate-400">Taille du cache JSON :</span>
            <span class="font-mono text-cyan-400"><?= $cache_size ?></span>
          </div>
          <div class="flex justify-between py-1.5 border-b border-slate-800">
            <span class="text-slate-400">Dernière synchronisation :</span>
            <span class="font-medium text-slate-200"><?= $cache_time ?></span>
          </div>
          <div class="flex justify-between py-1.5">
            <span class="text-slate-400">Documents locaux PDF :</span>
            <span class="font-semibold text-amber-400"><?= $total_docs ?> fichiers téléchargés</span>
          </div>
        </div>
      </div>

    </div>

  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
