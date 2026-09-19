<?php
$page_title = "Gestion des Documents & Décrets";
$page_subtitle = "Fichiers PDF locaux hébergés dans le projet et téléchargeables";
require_once __DIR__ . '/includes/header.php';

$db = get_db_connection();
$upload_dir = __DIR__ . '/../assets/documents/';

// Crée le dossier documents si inexistant
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0755, true);
}

$error = null;
$success = null;

// TRAITEMENT DU TÉLÉVERSEMENT (UPLOAD)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    if (!verify_csrf_token()) {
        $error = "Erreur de sécurité CSRF. Veuillez rafraîchir la page.";
    } else {
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? 'Réglementation');
        $bo_ref = trim($_POST['bo_ref'] ?? '');
        $date_text = trim($_POST['date_text'] ?? date('Y'));
        $description = trim($_POST['description'] ?? '');

        if (empty($title)) {
            $error = "Le titre du document est obligatoire.";
        } elseif (!isset($_FILES['doc_file']) || $_FILES['doc_file']['error'] !== UPLOAD_ERR_OK) {
            $error = "Veuillez sélectionner un fichier valide à téléverser.";
        } else {
            $file = $_FILES['doc_file'];
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip'];

            // Validation de sécurité stricte
            if (!in_array($file_ext, $allowed_exts)) {
                $error = "Type de fichier non autorisé. Seuls les formats PDF, DOC, XLS et ZIP sont acceptés.";
            } elseif ($file['size'] > 50 * 1024 * 1024) { // Max 50 Mo
                $error = "Le fichier dépasse la taille maximale autorisée (50 Mo).";
            } else {
                $safe_filename = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', basename($file['name']));
                $target_path = $upload_dir . $safe_filename;
                
                // Si le fichier existe déjà, ajoute un timestamp
                if (file_exists($target_path)) {
                    $safe_filename = pathinfo($safe_filename, PATHINFO_FILENAME) . '_' . time() . '.' . $file_ext;
                    $target_path = $upload_dir . $safe_filename;
                }

                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    $rel_path = './assets/documents/' . $safe_filename;
                    $file_size = filesize($target_path);

                    $ins = $db->prepare("
                        INSERT INTO documents 
                        (title, category, bo_ref, description, date_text, file_path, file_size, created_at) 
                        VALUES 
                        (:title, :category, :bo_ref, :description, :date_text, :file_path, :file_size, CURRENT_TIMESTAMP)
                    ");
                    $ins->execute([
                        ':title' => $title,
                        ':category' => $category,
                        ':bo_ref' => $bo_ref,
                        ':description' => $description,
                        ':date_text' => $date_text,
                        ':file_path' => $rel_path,
                        ':file_size' => $file_size
                    ]);

                    sync_cache_files();
                    $success = "Le document « {$title} » a été téléversé et intégré avec succès !";
                } else {
                    $error = "Impossible de déplacer le fichier vers le dossier assets/documents/. Vérifiez les permissions en écriture.";
                }
            }
        }
    }
}

// TRAITEMENT DE LA SUPPRESSION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (verify_csrf_token()) {
        $del_id = (int)($_POST['id'] ?? 0);
        if ($del_id > 0) {
            $stmt = $db->prepare("SELECT file_path FROM documents WHERE id = :id");
            $stmt->execute([':id' => $del_id]);
            $doc = $stmt->fetch();

            $del = $db->prepare("DELETE FROM documents WHERE id = :id");
            $del->execute([':id' => $del_id]);

            sync_cache_files();
            $success = "Le document a été supprimé de la base de données et du cache.";
        }
    } else {
        $error = "Échec de validation CSRF.";
    }
}

// Récupération des documents
$documents = $db->query("SELECT * FROM documents ORDER BY id DESC")->fetchAll();

function format_file_size($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' Mo';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 1) . ' Ko';
    } elseif ($bytes > 0) {
        return $bytes . ' o';
    }
    return '0 Ko';
}
?>

<div class="space-y-8">

  <!-- Alerts -->
  <?php if ($success): ?>
    <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-400 shrink-0"></i>
        <span class="text-sm font-medium"><?= e($success) ?></span>
      </div>
      <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-white"><i data-lucide="x" class="w-4 h-4"></i></button>
    </div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-400 shrink-0"></i>
        <span class="text-sm font-medium"><?= e($error) ?></span>
      </div>
      <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-white"><i data-lucide="x" class="w-4 h-4"></i></button>
    </div>
  <?php endif; ?>

  <!-- UPLOAD CARD -->
  <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800/80">
    <div class="flex items-center justify-between mb-4 border-b border-slate-800 pb-3">
      <div>
        <h3 class="text-base font-bold text-white flex items-center gap-2">
          <i data-lucide="upload-cloud" class="w-5 h-5 text-cyan-400"></i>
          <span>Téléverser & Attacher un Nouveau Document PDF</span>
        </h3>
        <p class="text-xs text-slate-400 mt-0.5">Le fichier sera stocké localement dans <code>assets/documents/</code> et disponible sur le site et dans l'éditeur de formations.</p>
      </div>
    </div>

    <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="upload">

      <div class="md:col-span-2">
        <label class="block text-xs font-semibold text-slate-300 mb-1">Titre officiel du document <span class="text-rose-400">*</span></label>
        <input 
          type="text" 
          name="title" 
          required 
          placeholder="Ex : Décret 2-14-499 relatif à la sécurité des équipements de travail"
          class="w-full px-3.5 py-2 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-cyan-500"
        >
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">Catégorie</label>
        <select 
          name="category" 
          class="w-full px-3 py-2 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-cyan-500"
        >
          <option value="Décret B.O.">Décret B.O.</option>
          <option value="Réglementation">Réglementation</option>
          <option value="Arrêté ministériel">Arrêté ministériel</option>
          <option value="Syllabus Formation">Syllabus Formation</option>
          <option value="Formulaire GIAC/CSF">Formulaire GIAC/CSF</option>
          <option value="Autre">Autre</option>
        </select>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">Référence B.O. (Bulletin Officiel)</label>
        <input 
          type="text" 
          name="bo_ref" 
          placeholder="Ex : B.O. n° 6374 du 2 juillet 2015"
          class="w-full px-3 py-2 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-cyan-500"
        >
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">Date / Année d'application</label>
        <input 
          type="text" 
          name="date_text" 
          value="<?= date('Y') ?>"
          placeholder="Ex : 2026 ou 2 juillet 2015"
          class="w-full px-3 py-2 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-cyan-500"
        >
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">Sélectionner le fichier (PDF, max 50 Mo) <span class="text-rose-400">*</span></label>
        <input 
          type="file" 
          name="doc_file" 
          required 
          accept=".pdf,.doc,.docx,.xls,.xlsx,.zip"
          class="w-full px-3 py-1.5 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-slate-300 file:mr-3 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-500 focus:outline-none"
        >
      </div>

      <div class="md:col-span-3">
        <label class="block text-xs font-semibold text-slate-300 mb-1">Description sommaire (optionnel)</label>
        <textarea 
          name="description" 
          rows="2" 
          placeholder="Résumé des obligations, champ d'application ou sanctions applicables..."
          class="w-full px-3 py-2 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-cyan-500"
        ></textarea>
      </div>

      <div class="md:col-span-3 flex justify-end">
        <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold flex items-center gap-2 transition shadow-lg shadow-blue-600/20">
          <i data-lucide="upload" class="w-4 h-4"></i>
          <span>Téléverser et Publier</span>
        </button>
      </div>
    </form>
  </div>

  <!-- DOCUMENTS LIST TABLE -->
  <div class="rounded-2xl bg-slate-900/60 border border-slate-800/80 overflow-hidden shadow-xl">
    <div class="p-4 border-b border-slate-800 flex items-center justify-between text-xs text-slate-400">
      <div class="flex items-center gap-2">
        <i data-lucide="files" class="w-4 h-4 text-cyan-400"></i>
        <span><strong><?= count($documents) ?></strong> document(s) téléchargé(s) localement dans le projet</span>
      </div>
      <a href="../reglementation.html" target="_blank" class="text-cyan-400 hover:underline flex items-center gap-1">
        <span>Voir page Réglementation</span>
        <i data-lucide="external-link" class="w-3 h-3"></i>
      </a>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-900/90 text-slate-400 border-b border-slate-800">
          <tr>
            <th class="py-3 px-4 font-semibold">Titre du Document</th>
            <th class="py-3 px-4 font-semibold">Catégorie</th>
            <th class="py-3 px-4 font-semibold">B.O. / Date</th>
            <th class="py-3 px-4 font-semibold">Taille & Emplacement</th>
            <th class="py-3 px-4 font-semibold text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800/60">
          <?php if (empty($documents)): ?>
            <tr>
              <td colspan="5" class="py-12 text-center text-slate-500">
                Aucun document enregistré. Utilisez le formulaire ci-dessus pour téléverser votre premier fichier.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($documents as $d): 
              $disk_file = __DIR__ . '/../' . ltrim($d['file_path'], './');
              $exists_on_disk = file_exists($disk_file);
              $actual_size = $exists_on_disk ? filesize($disk_file) : (int)$d['file_size'];
            ?>
              <tr class="hover:bg-slate-800/30 transition group">
                <td class="py-3.5 px-4 max-w-sm">
                  <div class="font-bold text-white group-hover:text-cyan-400 transition">
                    <?= e($d['title']) ?>
                  </div>
                  <?php if (!empty($d['description'])): ?>
                    <div class="text-[11px] text-slate-400 truncate mt-0.5"><?= e($d['description']) ?></div>
                  <?php endif; ?>
                </td>
                <td class="py-3.5 px-4">
                  <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-800 text-cyan-300 border border-slate-700">
                    <?= e($d['category'] ?: 'Général') ?>
                  </span>
                </td>
                <td class="py-3.5 px-4 text-slate-300">
                  <div class="font-mono text-[11px] text-amber-300"><?= e($d['bo_ref'] ?: '—') ?></div>
                  <div class="text-[10px] text-slate-500"><?= e($d['date_text'] ?: '') ?></div>
                </td>
                <td class="py-3.5 px-4">
                  <div class="font-mono text-[11px] text-slate-300"><?= format_file_size($actual_size) ?></div>
                  <div class="text-[10px] font-mono text-slate-500 truncate max-w-[200px]" title="<?= e($d['file_path']) ?>">
                    <?= e($d['file_path']) ?>
                  </div>
                  <?php if (!$exists_on_disk): ?>
                    <span class="text-[10px] text-rose-400 font-semibold">Fichier manquant sur le disque</span>
                  <?php endif; ?>
                </td>
                <td class="py-3.5 px-4 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <a href="../<?= ltrim($d['file_path'], './') ?>" target="_blank" class="px-2.5 py-1.5 rounded-lg bg-cyan-500/10 hover:bg-cyan-500/20 text-cyan-400 text-xs font-medium transition flex items-center gap-1">
                      <i data-lucide="download" class="w-3.5 h-3.5"></i>
                      <span>Télécharger</span>
                    </a>

                    <form method="POST" onsubmit="return confirm('Êtes-vous certain de vouloir supprimer le document « <?= addslashes($d['title']) ?> » ?');" class="inline">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= $d['id'] ?>">
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
