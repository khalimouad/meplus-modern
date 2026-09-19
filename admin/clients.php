<?php
$page_title = "Gestion des Clients & Références";
$page_subtitle = "Logos des industriels et partenaires de référence";
require_once __DIR__ . '/includes/header.php';

$db = get_db_connection();

$error = null;
$success = null;

// TRAITEMENT AJOUT / MODIFICATION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    if (!verify_csrf_token()) {
        $error = "Erreur de sécurité CSRF.";
    } else {
        $cl_id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $sector = trim($_POST['sector'] ?? '');
        $badge = trim($_POST['badge'] ?? '');
        $logo = trim($_POST['logo'] ?? '');
        $sort_order = (int)($_POST['sort_order'] ?? 10);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($name) || empty($logo)) {
            $error = "Le nom du client et le chemin du logo sont requis.";
        } else {
            if ($cl_id > 0) {
                $upd = $db->prepare("
                    UPDATE clients SET 
                        name = :name, sector = :sector, badge = :badge, logo = :logo,
                        sort_order = :sort_order, is_active = :is_active
                    WHERE id = :id
                ");
                $upd->execute([
                    ':name' => $name, ':sector' => $sector, ':badge' => $badge, ':logo' => $logo,
                    ':sort_order' => $sort_order, ':is_active' => $is_active, ':id' => $cl_id
                ]);
            } else {
                $ins = $db->prepare("
                    INSERT INTO clients (name, sector, badge, logo, sort_order, is_active)
                    VALUES (:name, :sector, :badge, :logo, :sort_order, :is_active)
                ");
                $ins->execute([
                    ':name' => $name, ':sector' => $sector, ':badge' => $badge, ':logo' => $logo,
                    ':sort_order' => $sort_order, ':is_active' => $is_active
                ]);
            }
            sync_cache_files();
            $success = "Client enregistré et cache synchronisé avec succès !";
        }
    }
}

// SUPPRESSION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (verify_csrf_token()) {
        $del_id = (int)($_POST['id'] ?? 0);
        $db->prepare("DELETE FROM clients WHERE id = :id")->execute([':id' => $del_id]);
        sync_cache_files();
        $success = "Client supprimé avec succès.";
    }
}

// Liste des clients
$clients = $db->query("SELECT * FROM clients ORDER BY sort_order ASC, id ASC")->fetchAll();

$edit_cl = null;
if (isset($_GET['edit'])) {
    $e_id = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM clients WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $e_id]);
    $edit_cl = $stmt->fetch();
}
?>

<div class="space-y-8">

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

  <!-- ADD / EDIT FORM CARD -->
  <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800/80">
    <div class="flex items-center justify-between mb-4 border-b border-slate-800 pb-3">
      <h3 class="text-sm font-bold text-white flex items-center gap-2">
        <i data-lucide="<?= $edit_cl ? 'edit-3' : 'plus-circle' ?>" class="w-4 h-4 text-cyan-400"></i>
        <span><?= $edit_cl ? 'Modifier : ' . e($edit_cl['name']) : 'Ajouter un Partenaire ou Client Référencé' ?></span>
      </h3>
      <?php if ($edit_cl): ?>
        <a href="clients.php" class="text-xs text-slate-400 hover:text-white underline">Annuler la modification</a>
      <?php endif; ?>
    </div>

    <form method="POST" class="space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= $edit_cl ? (int)$edit_cl['id'] : 0 ?>">

      <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Nom du client <span class="text-rose-400">*</span></label>
          <input 
            type="text" 
            name="name" 
            value="<?= e($edit_cl['name'] ?? '') ?>" 
            required 
            placeholder="Ex : OCP Group"
            class="w-full px-3.5 py-2 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-cyan-500"
          >
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Secteur d'activité</label>
          <input 
            type="text" 
            name="sector" 
            value="<?= e($edit_cl['sector'] ?? '') ?>" 
            placeholder="Ex : Mines & Chimie"
            class="w-full px-3.5 py-2 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-cyan-500"
          >
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Chemin du Logo <span class="text-rose-400">*</span></label>
          <input 
            type="text" 
            name="logo" 
            value="<?= e($edit_cl['logo'] ?? './assets/images/clients/') ?>" 
            required
            placeholder="./assets/images/clients/nom.png"
            class="w-full px-3.5 py-2 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white font-mono focus:outline-none focus:border-cyan-500"
          >
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Ordre d'affichage</label>
          <input 
            type="number" 
            name="sort_order" 
            value="<?= (int)($edit_cl['sort_order'] ?? 10) ?>" 
            class="w-full px-3.5 py-2 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-cyan-500"
          >
        </div>
      </div>

      <div class="flex items-center justify-between pt-2">
        <label class="inline-flex items-center gap-2 cursor-pointer">
          <input 
            type="checkbox" 
            name="is_active" 
            value="1" 
            <?= (!$edit_cl || $edit_cl['is_active']) ? 'checked' : '' ?>
            class="w-4 h-4 rounded bg-slate-800 border-slate-700 text-cyan-500 focus:ring-cyan-400"
          >
          <span class="text-xs text-white">Actif et visible sur le carrousel de logos</span>
        </label>

        <button type="submit" class="px-5 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-bold flex items-center gap-2 transition shadow-lg shadow-cyan-600/20">
          <i data-lucide="save" class="w-4 h-4"></i>
          <span><?= $edit_cl ? 'Enregistrer les modifications' : 'Ajouter le Client' ?></span>
        </button>
      </div>
    </form>
  </div>

  <!-- CLIENTS TABLE -->
  <div class="rounded-2xl bg-slate-900/60 border border-slate-800/80 overflow-hidden shadow-xl">
    <div class="p-4 border-b border-slate-800 flex items-center justify-between text-xs text-slate-400">
      <span><strong><?= count($clients) ?></strong> références industrielles enregistrées</span>
      <span>Affiché sur l'ensemble des pages d'accueil et services</span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-900/90 text-slate-400 border-b border-slate-800">
          <tr>
            <th class="py-3 px-4 font-semibold">Logo</th>
            <th class="py-3 px-4 font-semibold">Entreprise</th>
            <th class="py-3 px-4 font-semibold">Secteur</th>
            <th class="py-3 px-4 font-semibold">Ordre</th>
            <th class="py-3 px-4 font-semibold">Statut</th>
            <th class="py-3 px-4 font-semibold text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800/60">
          <?php foreach ($clients as $cl): ?>
            <tr class="hover:bg-slate-800/30 transition">
              <td class="py-3 px-4">
                <div class="w-16 h-10 rounded-lg bg-white/10 p-1 flex items-center justify-center border border-slate-700">
                  <img 
                    src="../<?= ltrim($cl['logo'], './') ?>" 
                    alt="<?= e($cl['name']) ?>" 
                    class="max-h-8 max-w-full object-contain filter grayscale hover:grayscale-0 transition"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
                  >
                  <span class="text-[10px] text-slate-400 font-bold hidden"><?= e(substr($cl['name'], 0, 4)) ?></span>
                </div>
              </td>
              <td class="py-3 px-4 font-bold text-white"><?= e($cl['name']) ?></td>
              <td class="py-3 px-4 text-slate-300"><?= e($cl['sector'] ?: '—') ?></td>
              <td class="py-3 px-4 text-slate-400 font-mono"><?= (int)$cl['sort_order'] ?></td>
              <td class="py-3 px-4">
                <?= $cl['is_active'] ? '<span class="text-emerald-400">● Actif</span>' : '<span class="text-slate-500">○ Inactif</span>' ?>
              </td>
              <td class="py-3 px-4 text-right">
                <div class="flex items-center justify-end gap-2">
                  <a href="clients.php?edit=<?= $cl['id'] ?>" class="px-2.5 py-1 rounded-lg bg-cyan-500/10 hover:bg-cyan-500/20 text-cyan-400 text-xs font-medium transition">
                    Éditer
                  </a>

                  <form method="POST" onsubmit="return confirm('Supprimer ce client ?');" class="inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $cl['id'] ?>">
                    <button type="submit" class="p-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 transition" title="Supprimer">
                      <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
