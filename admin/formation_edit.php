<?php
require_once __DIR__ . '/includes/header.php';

$db = get_db_connection();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_new = ($id === 0);

// Chargement des documents disponibles pour la liaison de fichier
$docs_stmt = $db->query("SELECT id, title, file_path FROM documents ORDER BY title ASC");
$available_docs = $docs_stmt->fetchAll();

// Valeurs par défaut
$formation = [
    'id' => 0,
    'code' => '',
    'title' => '',
    'domain' => 'Sécurité – hygiène',
    'duration_days' => '2',
    'duration_hours' => '14h',
    'public_cible' => '',
    'prerequis' => '',
    'objectifs_json' => '[]',
    'programme_json' => '[]',
    'certification' => 'Certifiante (Homologué GIAC & DFP)',
    'attached_file' => '',
    'is_published' => 1,
    'is_featured' => 0,
    'sort_order' => 10
];

if (!$is_new) {
    $stmt = $db->prepare("SELECT * FROM formations WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $found = $stmt->fetch();
    if ($found) {
        $formation = array_merge($formation, $found);
    } else {
        header("Location: formations.php");
        exit;
    }
}

// Convertit les JSON en lignes de texte simples pour une édition facile sans HTML
$objectives_arr = json_decode($formation['objectifs_json'] ?? '[]', true) ?: [];
$objectives_text = implode("\n", $objectives_arr);

$programme_arr = json_decode($formation['programme_json'] ?? '[]', true) ?: [];
$programme_text = implode("\n", $programme_arr);

$error = null;

// TRAITEMENT DU FORMULAIRE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = "Erreur de sécurité CSRF. Veuillez réessayer.";
    } else {
        $title = trim($_POST['title'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $domain = trim($_POST['domain'] ?? 'Sécurité – hygiène');
        $duration_days = trim($_POST['duration_days'] ?? '');
        $duration_hours = trim($_POST['duration_hours'] ?? '');
        $public_cible = trim($_POST['public_cible'] ?? '');
        $prerequis = trim($_POST['prerequis'] ?? '');
        $certification = trim($_POST['certification'] ?? 'Certifiante');
        $attached_file = trim($_POST['attached_file'] ?? '');
        $sort_order = (int)($_POST['sort_order'] ?? 10);
        $is_published = isset($_POST['is_published']) ? 1 : 0;
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;

        // Génère le code / slug si vide
        if (empty($code) && !empty($title)) {
            $code = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
        }

        // Conversion des lignes en tableaux JSON propres
        $raw_obj_lines = explode("\n", str_replace("\r", "", $_POST['objectives_text'] ?? ''));
        $clean_objectives = [];
        foreach ($raw_obj_lines as $line) {
            $line = trim($line, " \t\n\r\0\x0B-•*");
            if ($line !== '') {
                $clean_objectives[] = $line;
            }
        }
        $objectifs_json = json_encode($clean_objectives, JSON_UNESCAPED_UNICODE);

        $raw_prog_lines = explode("\n", str_replace("\r", "", $_POST['programme_text'] ?? ''));
        $clean_programme = [];
        foreach ($raw_prog_lines as $line) {
            $line = trim($line, " \t\n\r\0\x0B");
            if ($line !== '') {
                $clean_programme[] = $line;
            }
        }
        $programme_json = json_encode($clean_programme, JSON_UNESCAPED_UNICODE);

        if (empty($title)) {
            $error = "Le titre de la formation est obligatoire.";
        } else {
            if ($is_new) {
                $ins = $db->prepare("
                    INSERT INTO formations 
                    (code, title, domain, duration_days, duration_hours, public_cible, prerequis, objectifs_json, programme_json, certification, attached_file, is_published, is_featured, sort_order, updated_at) 
                    VALUES 
                    (:code, :title, :domain, :duration_days, :duration_hours, :public_cible, :prerequis, :objectifs_json, :programme_json, :certification, :attached_file, :is_published, :is_featured, :sort_order, CURRENT_TIMESTAMP)
                ");
                $ins->execute([
                    ':code' => $code,
                    ':title' => $title,
                    ':domain' => $domain,
                    ':duration_days' => $duration_days,
                    ':duration_hours' => $duration_hours,
                    ':public_cible' => $public_cible,
                    ':prerequis' => $prerequis,
                    ':objectifs_json' => $objectifs_json,
                    ':programme_json' => $programme_json,
                    ':certification' => $certification,
                    ':attached_file' => $attached_file,
                    ':is_published' => $is_published,
                    ':is_featured' => $is_featured,
                    ':sort_order' => $sort_order
                ]);
            } else {
                $upd = $db->prepare("
                    UPDATE formations SET 
                        code = :code,
                        title = :title,
                        domain = :domain,
                        duration_days = :duration_days,
                        duration_hours = :duration_hours,
                        public_cible = :public_cible,
                        prerequis = :prerequis,
                        objectifs_json = :objectifs_json,
                        programme_json = :programme_json,
                        certification = :certification,
                        attached_file = :attached_file,
                        is_published = :is_published,
                        is_featured = :is_featured,
                        sort_order = :sort_order,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                ");
                $upd->execute([
                    ':code' => $code,
                    ':title' => $title,
                    ':domain' => $domain,
                    ':duration_days' => $duration_days,
                    ':duration_hours' => $duration_hours,
                    ':public_cible' => $public_cible,
                    ':prerequis' => $prerequis,
                    ':objectifs_json' => $objectifs_json,
                    ':programme_json' => $programme_json,
                    ':certification' => $certification,
                    ':attached_file' => $attached_file,
                    ':is_published' => $is_published,
                    ':is_featured' => $is_featured,
                    ':sort_order' => $sort_order,
                    ':id' => $id
                ]);
            }

            // Synchronisation automatique immédiate vers data.json et data.js
            sync_cache_files();

            header("Location: formations.php?saved=1");
            exit;
        }
    }
}

$page_title = $is_new ? "Nouvelle Formation" : "Modifier : " . $formation['title'];
$page_subtitle = "Formulaire structuré par champs • Aucune balise HTML requise";
?>

<div class="max-w-4xl mx-auto space-y-6">

  <!-- Navigation Return -->
  <div class="flex items-center justify-between">
    <a href="formations.php" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-400 hover:text-white transition">
      <i data-lucide="arrow-left" class="w-4 h-4"></i>
      <span>Retour à la liste des formations</span>
    </a>

    <?php if (!$is_new): ?>
      <a href="../formation-detail.html?id=<?= urlencode($formation['code'] ?: 'f-' . $formation['id']) ?>" target="_blank" class="text-xs text-cyan-400 hover:text-cyan-300 font-medium flex items-center gap-1">
        <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
        <span>Aperçu sur le site</span>
      </a>
    <?php endif; ?>
  </div>

  <?php if ($error): ?>
    <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm flex items-center gap-3">
      <i data-lucide="alert-triangle" class="w-5 h-5 shrink-0"></i>
      <span><?= e($error) ?></span>
    </div>
  <?php endif; ?>

  <!-- STRUCTURED FIELDS FORM -->
  <form method="POST" class="space-y-6">
    <?= csrf_field() ?>

    <!-- 1. IDENTIFICATION -->
    <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800/80 space-y-5">
      <div class="border-b border-slate-800 pb-3">
        <h3 class="text-sm font-bold text-white flex items-center gap-2">
          <i data-lucide="info" class="w-4 h-4 text-blue-400"></i>
          <span>1. Informations Générales</span>
        </h3>
        <p class="text-xs text-slate-400 mt-0.5">Titre officiel, domaine d'activité et référencement</p>
      </div>

      <div class="space-y-4">
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1.5">Titre de la formation <span class="text-rose-400">*</span></label>
          <input 
            type="text" 
            name="title" 
            value="<?= e($formation['title']) ?>" 
            required 
            placeholder="Ex : Conduite défensive et sécurité routière"
            class="w-full px-4 py-2.5 bg-slate-800/80 border border-slate-700/80 rounded-xl text-sm text-white focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
          >
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Domaine de compétence</label>
            <select 
              name="domain" 
              class="w-full px-3.5 py-2.5 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-blue-500"
            >
              <?php 
              $domain_options = ['Sécurité – hygiène', 'Technique', 'Management', 'Qualité', 'Logistique & Achats', 'Soft Skills'];
              foreach ($domain_options as $d): ?>
                <option value="<?= e($d) ?>" <?= $formation['domain'] === $d ? 'selected' : '' ?>>
                  <?= e($d) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Identifiant / Code Slug (optionnel)</label>
            <input 
              type="text" 
              name="code" 
              value="<?= e($formation['code']) ?>" 
              placeholder="auto-généré si vide (ex: conduite-defensive)"
              class="w-full px-4 py-2.5 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white font-mono placeholder-slate-500 focus:outline-none focus:border-blue-500"
            >
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1.5">Certification / Reconnaissance</label>
          <input 
            type="text" 
            name="certification" 
            value="<?= e($formation['certification']) ?>" 
            placeholder="Ex : Certifiante (Homologué GIAC & DFP)"
            class="w-full px-4 py-2 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-blue-500"
          >
        </div>
      </div>
    </div>

    <!-- 2. DURÉE ET PARAMÈTRES D'AFFICHAGE -->
    <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800/80 space-y-5">
      <div class="border-b border-slate-800 pb-3">
        <h3 class="text-sm font-bold text-white flex items-center gap-2">
          <i data-lucide="clock" class="w-4 h-4 text-emerald-400"></i>
          <span>2. Durée & Publication</span>
        </h3>
        <p class="text-xs text-slate-400 mt-0.5">Visibilité et planification pédagogique</p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1.5">Durée (Jours)</label>
          <input 
            type="text" 
            name="duration_days" 
            value="<?= e($formation['duration_days']) ?>" 
            placeholder="Ex : 2 ou 3"
            class="w-full px-4 py-2 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500"
          >
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1.5">Durée (Heures)</label>
          <input 
            type="text" 
            name="duration_hours" 
            value="<?= e($formation['duration_hours']) ?>" 
            placeholder="Ex : 14h"
            class="w-full px-4 py-2 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500"
          >
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1.5">Ordre de tri</label>
          <input 
            type="number" 
            name="sort_order" 
            value="<?= (int)$formation['sort_order'] ?>" 
            class="w-full px-4 py-2 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500"
          >
        </div>
      </div>

      <div class="pt-2 flex flex-wrap items-center gap-6">
        <label class="inline-flex items-center gap-3 cursor-pointer">
          <input 
            type="checkbox" 
            name="is_published" 
            value="1" 
            <?= $formation['is_published'] ? 'checked' : '' ?>
            class="w-4 h-4 rounded bg-slate-800 border-slate-700 text-blue-600 focus:ring-blue-500 focus:ring-offset-slate-900"
          >
          <span class="text-xs font-medium text-white">Publié en ligne (visible sur le catalogue public)</span>
        </label>

        <label class="inline-flex items-center gap-3 cursor-pointer">
          <input 
            type="checkbox" 
            name="is_featured" 
            value="1" 
            <?= $formation['is_featured'] ? 'checked' : '' ?>
            class="w-4 h-4 rounded bg-slate-800 border-slate-700 text-amber-500 focus:ring-amber-400 focus:ring-offset-slate-900"
          >
          <span class="text-xs font-medium text-amber-300">Mettre en avant sur la page d'accueil</span>
        </label>
      </div>
    </div>

    <!-- 3. PUBLIC CIBLE & PRÉREQUIS -->
    <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800/80 space-y-5">
      <div class="border-b border-slate-800 pb-3">
        <h3 class="text-sm font-bold text-white flex items-center gap-2">
          <i data-lucide="target" class="w-4 h-4 text-purple-400"></i>
          <span>3. Public Cible & Prérequis</span>
        </h3>
        <p class="text-xs text-slate-400 mt-0.5">Critères d'admission et profils concernés</p>
      </div>

      <div class="space-y-4">
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1.5">Public Cible</label>
          <textarea 
            name="public_cible" 
            rows="2" 
            placeholder="Ex : Responsables HSE, techniciens de maintenance, opérateurs industriels..."
            class="w-full px-4 py-2.5 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-purple-500"
          ><?= e($formation['public_cible']) ?></textarea>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1.5">Prérequis Pédagogiques</label>
          <textarea 
            name="prerequis" 
            rows="2" 
            placeholder="Ex : Connaissance élémentaire de l'environnement de production industrielle..."
            class="w-full px-4 py-2.5 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-purple-500"
          ><?= e($formation['prerequis']) ?></textarea>
        </div>
      </div>
    </div>

    <!-- 4. OBJECTIFS PÉDAGOGIQUES (CHAMP STRUCTURÉ LIGNE PAR LIGNE) -->
    <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800/80 space-y-4">
      <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
        <div>
          <h3 class="text-sm font-bold text-white flex items-center gap-2">
            <i data-lucide="check-square" class="w-4 h-4 text-cyan-400"></i>
            <span>4. Objectifs Pédagogiques</span>
          </h3>
          <p class="text-xs text-slate-400 mt-0.5">Saisissez <strong>un objectif par ligne</strong>. Le système gère les puces et le formatage automatiquement.</p>
        </div>
        <span class="text-[11px] font-semibold text-cyan-400 bg-cyan-500/10 px-2.5 py-1 rounded-full border border-cyan-500/20">1 ligne = 1 objectif</span>
      </div>

      <textarea 
        name="objectives_text" 
        rows="5" 
        placeholder="Identifier les situations à risque&#10;Maîtriser les procédures de sécurité&#10;Mettre en œuvre les mesures de prévention"
        class="w-full px-4 py-3 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white font-mono leading-relaxed focus:outline-none focus:border-cyan-500"
      ><?= e($objectives_text) ?></textarea>
    </div>

    <!-- 5. PROGRAMME DÉTAILLÉ (CHAMP STRUCTURÉ LIGNE PAR LIGNE) -->
    <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800/80 space-y-4">
      <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
        <div>
          <h3 class="text-sm font-bold text-white flex items-center gap-2">
            <i data-lucide="layers" class="w-4 h-4 text-amber-400"></i>
            <span>5. Programme & Modules de Formation</span>
          </h3>
          <p class="text-xs text-slate-400 mt-0.5">Saisissez <strong>un module ou sous-point par ligne</strong> (ex: Module 1 : Introduction, Module 2 : Cas pratiques...)</p>
        </div>
        <span class="text-[11px] font-semibold text-amber-400 bg-amber-500/10 px-2.5 py-1 rounded-full border border-amber-500/20">1 ligne = 1 module</span>
      </div>

      <textarea 
        name="programme_text" 
        rows="6" 
        placeholder="1) Cadre légal et réglementaire au Maroc&#10;2) Analyse technique des risques&#10;3) Exercices pratiques et études de cas&#10;4) Évaluation et validation des acquis"
        class="w-full px-4 py-3 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white font-mono leading-relaxed focus:outline-none focus:border-amber-500"
      ><?= e($programme_text) ?></textarea>
    </div>

    <!-- 6. FICHIER JOINT / DOCUMENT PDF -->
    <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800/80 space-y-4">
      <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
        <div>
          <h3 class="text-sm font-bold text-white flex items-center gap-2">
            <i data-lucide="paperclip" class="w-4 h-4 text-rose-400"></i>
            <span>6. Fichier PDF Associé (Syllabus ou Décret)</span>
          </h3>
          <p class="text-xs text-slate-400 mt-0.5">Liez un document local téléchargé dans le projet ou téléversez-en un nouveau</p>
        </div>
        <a href="documents.php" target="_blank" class="text-xs text-rose-400 hover:text-rose-300 font-semibold flex items-center gap-1">
          <i data-lucide="upload" class="w-3.5 h-3.5"></i>
          <span>Gérer les documents</span>
        </a>
      </div>

      <div class="space-y-3">
        <label class="block text-xs font-semibold text-slate-300">Sélectionner parmi les documents locaux téléchargés :</label>
        <select 
          name="attached_file" 
          class="w-full px-3.5 py-2.5 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-rose-500"
        >
          <option value="">-- Aucun fichier joint --</option>
          <?php foreach ($available_docs as $doc): ?>
            <option value="<?= e($doc['file_path']) ?>" <?= $formation['attached_file'] === $doc['file_path'] ? 'selected' : '' ?>>
              <?= e($doc['title']) ?> (<?= e(basename($doc['file_path'])) ?>)
            </option>
          <?php endforeach; ?>
        </select>

        <?php if (!empty($formation['attached_file'])): ?>
          <div class="p-3 rounded-xl bg-slate-800/40 border border-slate-700/60 flex items-center justify-between text-xs">
            <span class="text-slate-400">Fichier actuellement lié :</span>
            <a href="<?= e($formation['attached_file']) ?>" target="_blank" class="text-cyan-400 font-mono underline hover:text-cyan-300 flex items-center gap-1">
              <i data-lucide="file-check" class="w-3.5 h-3.5"></i>
              <span><?= e($formation['attached_file']) ?></span>
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- SUBMIT ACTIONS BAR -->
    <div class="flex items-center justify-between p-4 rounded-2xl bg-slate-900/90 border border-slate-800 sticky bottom-4 shadow-2xl backdrop-blur-xl">
      <a href="formations.php" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:text-white transition">
        Annuler
      </a>

      <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-500 hover:to-cyan-400 text-white text-xs font-bold flex items-center gap-2 shadow-lg shadow-cyan-500/20 transition">
        <i data-lucide="save" class="w-4 h-4"></i>
        <span>Enregistrer & Synchroniser</span>
      </button>
    </div>

  </form>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
