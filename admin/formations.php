<?php
$page_title = "Gestion des Formations";
$page_subtitle = "Catalogue des formations professionnelles continues";
require_once __DIR__ . '/includes/header.php';

$db = get_db_connection();

// Traitement de la suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (verify_csrf_token()) {
        $del_id = (int)($_POST['id'] ?? 0);
        if ($del_id > 0) {
            $stmt = $db->prepare("DELETE FROM formations WHERE id = :id");
            $stmt->execute([':id' => $del_id]);
            sync_cache_files();
            header("Location: formations.php?deleted=1");
            exit;
        }
    } else {
        $error = "Échec du jeton de sécurité CSRF.";
    }
}

// Filtres
$filter_domain = $_GET['domain'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$sql = "SELECT * FROM formations WHERE 1=1";
$params = [];

if ($filter_domain !== 'all' && !empty($filter_domain)) {
    $sql .= " AND domain = :domain";
    $params[':domain'] = $filter_domain;
}

if (!empty($search)) {
    $sql .= " AND (title LIKE :search OR code LIKE :search OR domain LIKE :search)";
    $params[':search'] = "%{$search}%";
}

$sql .= " ORDER BY sort_order ASC, title ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$formations = $stmt->fetchAll();

// Liste des domaines disponibles
$domains_stmt = $db->query("SELECT DISTINCT domain FROM formations WHERE domain IS NOT NULL AND domain != '' ORDER BY domain ASC");
$all_domains = $domains_stmt->fetchAll(PDO::FETCH_COLUMN);

$flash_saved = isset($_GET['saved']);
$flash_deleted = isset($_GET['deleted']);
?>

<div class="space-y-6">

  <!-- Flash Messages -->
  <?php if ($flash_saved): ?>
    <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <i data-lucide="check-circle" class="w-5 h-5 text-emerald-400"></i>
        <span class="text-sm font-medium">Formation enregistrée et synchronisée avec succès !</span>
      </div>
      <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-white"><i data-lucide="x" class="w-4 h-4"></i></button>
    </div>
  <?php endif; ?>

  <?php if ($flash_deleted): ?>
    <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <i data-lucide="trash-2" class="w-5 h-5 text-rose-400"></i>
        <span class="text-sm font-medium">La formation a été supprimée et le cache mis à jour.</span>
      </div>
      <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-white"><i data-lucide="x" class="w-4 h-4"></i></button>
    </div>
  <?php endif; ?>

  <!-- Actions Bar & Filters -->
  <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80">
    
    <!-- Search & Filter Form -->
    <form method="GET" class="flex flex-wrap items-center gap-3 flex-1">
      <div class="relative flex-1 min-w-[220px]">
        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
        <input 
          type="text" 
          name="search" 
          value="<?= e($search) ?>" 
          placeholder="Rechercher une formation..."
          class="w-full pl-10 pr-4 py-2 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white placeholder-slate-400 focus:outline-none focus:border-blue-500"
        >
      </div>

      <select 
        name="domain" 
        onchange="this.form.submit()" 
        class="py-2 px-3 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-slate-200 focus:outline-none focus:border-blue-500"
      >
        <option value="all">Tous les domaines</option>
        <?php foreach ($all_domains as $dom): ?>
          <option value="<?= e($dom) ?>" <?= $filter_domain === $dom ? 'selected' : '' ?>>
            <?= e($dom) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <button type="submit" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-xl transition">
        Filtrer
      </button>

      <?php if (!empty($search) || $filter_domain !== 'all'): ?>
        <a href="formations.php" class="text-xs text-slate-400 hover:text-white underline">
          Réinitialiser
        </a>
      <?php endif; ?>
    </form>

    <!-- Add Formation Button -->
    <a href="formation_edit.php" class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold flex items-center justify-center gap-2 transition shadow-lg shadow-blue-600/20 shrink-0">
      <i data-lucide="plus-circle" class="w-4 h-4"></i>
      <span>Ajouter une Formation</span>
    </a>
  </div>

  <!-- Formations Table -->
  <div class="rounded-2xl bg-slate-900/60 border border-slate-800/80 overflow-hidden shadow-xl">
    <div class="p-4 border-b border-slate-800 flex items-center justify-between text-xs text-slate-400">
      <span>Affichage de <strong><?= count($formations) ?></strong> formation(s)</span>
      <span>Total homologué GIAC & DFP</span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-900/90 text-slate-400 border-b border-slate-800">
          <tr>
            <th class="py-3 px-4 font-semibold">Titre & Code</th>
            <th class="py-3 px-4 font-semibold">Domaine</th>
            <th class="py-3 px-4 font-semibold">Durée</th>
            <th class="py-3 px-4 font-semibold">Document lié</th>
            <th class="py-3 px-4 font-semibold">Statut</th>
            <th class="py-3 px-4 font-semibold text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800/60">
          <?php if (empty($formations)): ?>
            <tr>
              <td colspan="6" class="py-12 text-center text-slate-500">
                <div class="flex flex-col items-center justify-center gap-2">
                  <i data-lucide="book-x" class="w-8 h-8 text-slate-600"></i>
                  <span>Aucune formation ne correspond à votre recherche.</span>
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($formations as $f): ?>
              <tr class="hover:bg-slate-800/30 transition group">
                <td class="py-3.5 px-4">
                  <div class="font-bold text-white group-hover:text-blue-400 transition">
                    <?= e($f['title']) ?>
                  </div>
                  <div class="text-[11px] font-mono text-slate-400 mt-0.5">
                    <?= e($f['code'] ?: 'ID #' . $f['id']) ?>
                    <?php if ($f['is_featured']): ?>
                      <span class="ml-1.5 px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-300 text-[10px] font-semibold border border-amber-500/30">À la une</span>
                    <?php endif; ?>
                  </div>
                </td>
                <td class="py-3.5 px-4">
                  <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-medium bg-slate-800 text-slate-300 border border-slate-700">
                    <?= e($f['domain'] ?: 'Général') ?>
                  </span>
                </td>
                <td class="py-3.5 px-4 text-slate-300">
                  <?php if (!empty($f['duration_days'])): ?>
                    <span><?= e($f['duration_days']) ?> jour(s)</span>
                  <?php elseif (!empty($f['duration_hours'])): ?>
                    <span><?= e($f['duration_hours']) ?> h</span>
                  <?php else: ?>
                    <span class="text-slate-500">Modulable</span>
                  <?php endif; ?>
                </td>
                <td class="py-3.5 px-4 text-slate-300">
                  <?php if (!empty($f['attached_file'])): ?>
                    <a href="<?= e($f['attached_file']) ?>" target="_blank" class="inline-flex items-center gap-1 text-[11px] text-cyan-400 hover:text-cyan-300 hover:underline">
                      <i data-lucide="paperclip" class="w-3.5 h-3.5"></i>
                      <span><?= e(basename($f['attached_file'])) ?></span>
                    </a>
                  <?php else: ?>
                    <span class="text-slate-500">—</span>
                  <?php endif; ?>
                </td>
                <td class="py-3.5 px-4">
                  <?php if ($f['is_published']): ?>
                    <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-emerald-400">
                      <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                      Publié
                    </span>
                  <?php else: ?>
                    <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-amber-400">
                      <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                      Brouillon
                    </span>
                  <?php endif; ?>
                </td>
                <td class="py-3.5 px-4 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <a href="formation_edit.php?id=<?= $f['id'] ?>" class="px-2.5 py-1.5 rounded-lg bg-blue-500/10 hover:bg-blue-500/20 text-blue-400 text-xs font-semibold transition flex items-center gap-1">
                      <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                      <span>Modifier</span>
                    </a>
                    
                    <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer la formation « <?= addslashes($f['title']) ?> » ?');" class="inline">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= $f['id'] ?>">
                      <button type="submit" class="p-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 transition" title="Supprimer">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
