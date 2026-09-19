<?php
$page_title = "Édition des Pages";
$page_subtitle = "Personnalisation section par section de l'ensemble du site web";
require_once __DIR__ . '/includes/header.php';

$pages_catalog = [
    'index' => [
        'name' => 'Page d\'Accueil',
        'file' => 'index.html',
        'icon' => 'home',
        'color' => 'blue',
        'sections_count' => 4,
        'description' => 'Hero banner, compteurs statistiques, présentation ME PLUS et assistance.'
    ],
    'formations' => [
        'name' => 'Catalogue Formations',
        'file' => 'formations.html',
        'icon' => 'book-open',
        'color' => 'cyan',
        'sections_count' => 2,
        'description' => 'En-tête du catalogue, filtres pédagogiques et bandeau de prise en charge GIAC.'
    ],
    'services' => [
        'name' => 'Services & Ingénierie',
        'file' => 'services.html',
        'icon' => 'briefcase',
        'color' => 'emerald',
        'sections_count' => 3,
        'description' => 'Ingénierie de formation, audits industriels et accompagnement CSF/F2.'
    ],
    'simulateur' => [
        'name' => 'Simulateur Remboursement GIAC',
        'file' => 'simulateur.html',
        'icon' => 'calculator',
        'color' => 'amber',
        'sections_count' => 2,
        'description' => 'En-tête du simulateur, barèmes légaux et conseils d\'optimisation financière.'
    ],
    'reglementation' => [
        'name' => 'Textes & Décrets Réglementaires',
        'file' => 'reglementation.html',
        'icon' => 'scale',
        'color' => 'rose',
        'sections_count' => 2,
        'description' => 'Cadre légal marocain, Loi 65-99, arrêtés ministériels et veille juridique.'
    ],
    'consultants' => [
        'name' => 'Consultants Seniors',
        'file' => 'consultants.html',
        'icon' => 'users',
        'color' => 'purple',
        'sections_count' => 2,
        'description' => 'Présentation de l\'équipe d\'experts et formateurs industriels agréés.'
    ],
    'contact' => [
        'name' => 'Contact & Demande de Devis',
        'file' => 'contact.html',
        'icon' => 'mail',
        'color' => 'indigo',
        'sections_count' => 2,
        'description' => 'Formulaire de contact, coordonnées géographiques et assistance téléphonique.'
    ],
    'global_footer' => [
        'name' => 'Pied de page Global',
        'file' => 'Tous les fichiers',
        'icon' => 'layout',
        'color' => 'slate',
        'sections_count' => 1,
        'description' => 'Badges d\'homologation, mentions légales, coordonnées du siège de Casablanca.'
    ]
];

$flash_saved = isset($_GET['saved']);
?>

<div class="space-y-6">

  <?php if ($flash_saved): ?>
    <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-400"></i>
        <span class="text-sm font-medium">Les sections de la page ont été mises à jour et le cache synchronisé avec succès !</span>
      </div>
      <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-white"><i data-lucide="x" class="w-4 h-4"></i></button>
    </div>
  <?php endif; ?>

  <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800/80">
    <h3 class="text-base font-bold text-white mb-1">Édition de contenu page par page</h3>
    <p class="text-xs text-slate-400">
      Sélectionnez une page pour modifier directement ses textes, titres de sections, bannières et chiffres clés sans toucher au code HTML.
    </p>
  </div>

  <!-- GRID OF PAGES -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
    <?php foreach ($pages_catalog as $key => $pg): ?>
      <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80 hover:border-slate-700 transition flex flex-col justify-between group">
        <div>
          <div class="flex items-center justify-between mb-4">
            <div class="w-10 h-10 rounded-xl bg-slate-800 flex items-center justify-center text-cyan-400 group-hover:scale-110 transition">
              <i data-lucide="<?= e($pg['icon']) ?>" class="w-5 h-5"></i>
            </div>
            <span class="text-[10px] font-semibold bg-slate-800 text-slate-300 px-2.5 py-1 rounded-full border border-slate-700">
              <?= e($pg['sections_count']) ?> section(s)
            </span>
          </div>

          <h4 class="text-sm font-bold text-white mb-1 group-hover:text-cyan-400 transition"><?= e($pg['name']) ?></h4>
          <p class="text-xs text-slate-400 leading-relaxed mb-4"><?= e($pg['description']) ?></p>
        </div>

        <div class="pt-4 border-t border-slate-800/60 flex items-center justify-between">
          <?php if ($pg['file'] !== 'Tous les fichiers'): ?>
            <a href="../<?= e($pg['file']) ?>" target="_blank" class="text-[11px] text-slate-400 hover:text-white flex items-center gap-1 transition">
              <i data-lucide="external-link" class="w-3 h-3"></i>
              <span>Aperçu</span>
            </a>
          <?php else: ?>
            <span class="text-[11px] text-slate-500">Global</span>
          <?php endif; ?>

          <a href="page_edit.php?page=<?= urlencode($key) ?>" class="px-3.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold flex items-center gap-1.5 transition shadow-md shadow-blue-600/20">
            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
            <span>Modifier les sections</span>
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
