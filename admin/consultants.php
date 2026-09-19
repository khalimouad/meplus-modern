<?php
$page_title = "Gestion des Consultants Seniors";
$page_subtitle = "Équipe d'experts et intervenants formateurs homologués";
require_once __DIR__ . '/includes/header.php';

$db = get_db_connection();

$error = null;
$success = null;

// TRAITEMENT AJOUT / MODIFICATION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    if (!verify_csrf_token()) {
        $error = "Erreur de sécurité CSRF.";
    } else {
        $c_id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $tag = trim($_POST['tag'] ?? '');
        $credentials = trim($_POST['credentials'] ?? '');
        $avatar = trim($_POST['avatar'] ?? './assets/images/consultants/avatar-placeholder.jpg');
        $sort_order = (int)($_POST['sort_order'] ?? 10);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        $raw_specs = explode("\n", str_replace("\r", "", $_POST['specialties'] ?? ''));
        $clean_specs = [];
        foreach ($raw_specs as $sp) {
            $sp = trim($sp, " \t\n\r\0\x0B-•*");
            if ($sp !== '') $clean_specs[] = $sp;
        }
        $specialties_json = json_encode($clean_specs, JSON_UNESCAPED_UNICODE);

        if (empty($name) || empty($role)) {
            $error = "Le nom et la fonction du consultant sont requis.";
        } else {
            if ($c_id > 0) {
                $upd = $db->prepare("
                    UPDATE consultants SET 
                        name = :name, role = :role, tag = :tag, credentials = :credentials,
                        avatar = :avatar, specialties_json = :specialties_json,
                        sort_order = :sort_order, is_active = :is_active, updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                ");
                $upd->execute([
                    ':name' => $name, ':role' => $role, ':tag' => $tag, ':credentials' => $credentials,
                    ':avatar' => $avatar, ':specialties_json' => $specialties_json,
                    ':sort_order' => $sort_order, ':is_active' => $is_active, ':id' => $c_id
                ]);
            } else {
                $ins = $db->prepare("
                    INSERT INTO consultants (name, role, tag, credentials, avatar, specialties_json, sort_order, is_active, updated_at)
                    VALUES (:name, :role, :tag, :credentials, :avatar, :specialties_json, :sort_order, :is_active, CURRENT_TIMESTAMP)
                ");
                $ins->execute([
                    ':name' => $name, ':role' => $role, ':tag' => $tag, ':credentials' => $credentials,
                    ':avatar' => $avatar, ':specialties_json' => $specialties_json,
                    ':sort_order' => $sort_order, ':is_active' => $is_active
                ]);
            }
            sync_cache_files();
            $success = "Consultant enregistré avec succès et cache synchronisé !";
        }
    }
}

// SUPPRESSION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (verify_csrf_token()) {
        $del_id = (int)($_POST['id'] ?? 0);
        $db->prepare("DELETE FROM consultants WHERE id = :id")->execute([':id' => $del_id]);
        sync_cache_files();
        $success = "Consultant supprimé avec succès.";
    }
}

// Liste
$consultants = $db->query("SELECT * FROM consultants ORDER BY sort_order ASC, id ASC")->fetchAll();

$edit_c = null;
if (isset($_GET['edit'])) {
    $e_id = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM consultants WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $e_id]);
    $edit_c = $stmt->fetch();
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
        <i data-lucide="<?= $edit_c ? 'edit-3' : 'user-plus' ?>" class="w-4 h-4 text-purple-400"></i>
        <span><?= $edit_c ? 'Modifier : ' . e($edit_c['name']) : 'Ajouter un Nouveau Consultant Senior' ?></span>
      </h3>
      <?php if ($edit_c): ?>
        <a href="consultants.php" class="text-xs text-slate-400 hover:text-white underline">Annuler la modification</a>
      <?php endif; ?>
    </div>

    <form method="POST" class="space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= $edit_c ? (int)$edit_c['id'] : 0 ?>">

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Nom complet du Consultant <span class="text-rose-400">*</span></label>
          <input 
            type="text" 
            name="name" 
            value="<?= e($edit_c['name'] ?? '') ?>" 
            required 
            placeholder="Ex : El Mostafa EL HOUARI"
            class="w-full px-3.5 py-2 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-purple-500"
          >
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Rôle / Titre <span class="text-rose-400">*</span></label>
          <input 
            type="text" 
            name="role" 
            value="<?= e($edit_c['role'] ?? '') ?>" 
            required 
            placeholder="Ex : Fondateur & Expert Senior Maintenance"
            class="w-full px-3.5 py-2 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-purple-500"
          >
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Badge d'expertise (Tag)</label>
          <input 
            type="text" 
            name="tag" 
            value="<?= e($edit_c['tag'] ?? '') ?>" 
            placeholder="Ex : Expert Référent Maintenance & GMAO"
            class="w-full px-3.5 py-2 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-purple-500"
          >
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Chemin de la photo (Avatar)</label>
          <input 
            type="text" 
            name="avatar" 
            value="<?= e($edit_c['avatar'] ?? './assets/images/consultants/avatar-placeholder.jpg') ?>" 
            placeholder="./assets/images/consultants/nom.jpg"
            class="w-full px-3.5 py-2 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white font-mono focus:outline-none focus:border-purple-500"
          >
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Accréditations & Certifications</label>
          <input 
            type="text" 
            name="credentials" 
            value="<?= e($edit_c['credentials'] ?? '') ?>" 
            placeholder="Ex : Ingénieur d'État • Habilité NM 06.1.225"
            class="w-full px-3.5 py-2 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-purple-500"
          >
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Ordre d'affichage</label>
          <input 
            type="number" 
            name="sort_order" 
            value="<?= (int)($edit_c['sort_order'] ?? 10) ?>" 
            class="w-full px-3.5 py-2 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-purple-500"
          >
        </div>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">Spécialités d'intervention (1 par ligne)</label>
        <?php 
          $sp_text = '';
          if ($edit_c) {
              $sp_arr = json_decode($edit_c['specialties_json'] ?? '[]', true) ?: [];
              $sp_text = implode("\n", $sp_arr);
          }
        ?>
        <textarea 
          name="specialties" 
          rows="3" 
          placeholder="Gestion de maintenance industrielle&#10;Audit technique & fiabilité&#10;Ingénierie GMAO"
          class="w-full px-3.5 py-2 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white font-mono focus:outline-none focus:border-purple-500"
        ><?= e($sp_text) ?></textarea>
      </div>

      <div class="flex items-center justify-between pt-2">
        <label class="inline-flex items-center gap-2 cursor-pointer">
          <input 
            type="checkbox" 
            name="is_active" 
            value="1" 
            <?= (!$edit_c || $edit_c['is_active']) ? 'checked' : '' ?>
            class="w-4 h-4 rounded bg-slate-800 border-slate-700 text-purple-600 focus:ring-purple-500"
          >
          <span class="text-xs text-white">Actif et visible sur le site</span>
        </label>

        <button type="submit" class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold flex items-center gap-2 transition shadow-lg shadow-purple-600/20">
          <i data-lucide="save" class="w-4 h-4"></i>
          <span><?= $edit_c ? 'Enregistrer les modifications' : 'Ajouter le Consultant' ?></span>
        </button>
      </div>
    </form>
  </div>

  <!-- CONSULTANTS LIST GRID -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
    <?php foreach ($consultants as $c): 
      $specs = json_decode($c['specialties_json'] ?? '[]', true) ?: [];
    ?>
      <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80 hover:border-purple-500/40 transition flex flex-col justify-between group">
        <div>
          <div class="flex items-start gap-3.5 mb-4">
            <img 
              src="../<?= ltrim($c['avatar'], './') ?>" 
              alt="<?= e($c['name']) ?>" 
              class="w-12 h-12 rounded-xl object-cover border border-purple-500/30 bg-slate-800 shrink-0"
              onerror="this.src='../assets/images/consultants/avatar-placeholder.jpg'"
            >
            <div class="min-w-0">
              <h4 class="font-bold text-white text-sm group-hover:text-purple-300 transition truncate"><?= e($c['name']) ?></h4>
              <div class="text-[11px] text-purple-400 font-medium truncate"><?= e($c['role']) ?></div>
              <?php if (!empty($c['credentials'])): ?>
                <div class="text-[10px] text-slate-400 truncate mt-0.5"><?= e($c['credentials']) ?></div>
              <?php endif; ?>
            </div>
          </div>

          <?php if (!empty($specs)): ?>
            <div class="space-y-1 mb-4">
              <?php foreach (array_slice($specs, 0, 3) as $s): ?>
                <div class="text-[11px] text-slate-300 flex items-center gap-1.5">
                  <span class="w-1 h-1 rounded-full bg-cyan-400 shrink-0"></span>
                  <span class="truncate"><?= e($s) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="pt-3 border-t border-slate-800/60 flex items-center justify-between">
          <span class="text-[10px] <?= $c['is_active'] ? 'text-emerald-400' : 'text-slate-500' ?>">
            <?= $c['is_active'] ? '● En ligne' : '○ Inactif' ?>
          </span>

          <div class="flex items-center gap-2">
            <a href="consultants.php?edit=<?= $c['id'] ?>" class="px-2.5 py-1 rounded-lg bg-purple-500/10 hover:bg-purple-500/20 text-purple-400 text-xs font-medium transition flex items-center gap-1">
              <i data-lucide="edit-3" class="w-3 h-3"></i>
              <span>Éditer</span>
            </a>

            <form method="POST" onsubmit="return confirm('Supprimer ce consultant ?');" class="inline">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $c['id'] ?>">
              <button type="submit" class="p-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 transition" title="Supprimer">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
              </button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
