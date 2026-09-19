<?php
require_once __DIR__ . '/includes/header.php';

$page_key = $_GET['page'] ?? 'index';

$pages_metadata = [
    'index' => [
        'name' => 'Page d\'Accueil',
        'file' => 'index.html',
        'sections' => [
            'hero' => [
                'name' => 'En-tête Principal (Hero)',
                'desc' => 'Titre principal, accroche, badges d\'accréditation et boutons d\'action',
                'fields' => [
                    ['key' => 'tag', 'label' => 'Bandeau supérieur (Tag)', 'type' => 'text'],
                    ['key' => 'badge_1', 'label' => 'Badge 1', 'type' => 'text'],
                    ['key' => 'badge_2', 'label' => 'Badge 2', 'type' => 'text'],
                    ['key' => 'badge_3', 'label' => 'Badge 3', 'type' => 'text'],
                    ['key' => 'cta_primary_text', 'label' => 'Texte Bouton Principal', 'type' => 'text'],
                    ['key' => 'cta_secondary_text', 'label' => 'Texte Bouton Secondaire', 'type' => 'text']
                ]
            ],
            'stats' => [
                'name' => 'Chiffres Clés & Statistiques',
                'desc' => 'Indicateurs d\'impact et compteurs d\'activité',
                'fields' => [
                    ['key' => 'stat1_val', 'label' => 'Stat 1 Valeur', 'type' => 'text'],
                    ['key' => 'stat1_lbl', 'label' => 'Stat 1 Libellé', 'type' => 'text'],
                    ['key' => 'stat2_val', 'label' => 'Stat 2 Valeur', 'type' => 'text'],
                    ['key' => 'stat2_lbl', 'label' => 'Stat 2 Libellé', 'type' => 'text'],
                    ['key' => 'stat3_val', 'label' => 'Stat 3 Valeur', 'type' => 'text'],
                    ['key' => 'stat3_lbl', 'label' => 'Stat 3 Libellé', 'type' => 'text'],
                    ['key' => 'stat4_val', 'label' => 'Stat 4 Valeur', 'type' => 'text'],
                    ['key' => 'stat4_lbl', 'label' => 'Stat 4 Libellé', 'type' => 'text']
                ]
            ],
            'about' => [
                'name' => 'Présentation & Expertise ME PLUS',
                'desc' => 'Texte de présentation institutionnelle et points forts',
                'fields' => [
                    ['key' => 'intro', 'label' => 'Texte d\'introduction', 'type' => 'textarea'],
                    ['key' => 'point1', 'label' => 'Point fort 1', 'type' => 'text'],
                    ['key' => 'point2', 'label' => 'Point fort 2', 'type' => 'text'],
                    ['key' => 'point3', 'label' => 'Point fort 3', 'type' => 'text']
                ]
            ],
            'cta' => [
                'name' => 'Bandeau d\'Assistance & Contact',
                'desc' => 'Bannière d\'appel à l\'action en bas de page',
                'fields' => [
                    ['key' => 'desc', 'label' => 'Description d\'accompagnement', 'type' => 'textarea'],
                    ['key' => 'phone', 'label' => 'Numéro d\'assistance', 'type' => 'text'],
                    ['key' => 'btn_text', 'label' => 'Libellé du bouton', 'type' => 'text']
                ]
            ]
        ]
    ],
    'formations' => [
        'name' => 'Catalogue des Formations',
        'file' => 'formations.html',
        'sections' => [
            'hero' => [
                'name' => 'Bannière Catalogue',
                'desc' => 'Titre et accroche de la page formations',
                'fields' => [
                    ['key' => 'intro', 'label' => 'Description introductive', 'type' => 'textarea']
                ]
            ],
            'giac_banner' => [
                'name' => 'Bandeau Financement GIAC / CSF',
                'desc' => 'Explication de la prise en charge financière à 80%',
                'fields' => [
                    ['key' => 'details', 'label' => 'Texte explicatif du dispositif', 'type' => 'textarea'],
                    ['key' => 'highlight', 'label' => 'Pourcentage mis en avant', 'type' => 'text']
                ]
            ]
        ]
    ],
    'services' => [
        'name' => 'Services & Ingénierie',
        'file' => 'services.html',
        'sections' => [
            'hero' => [
                'name' => 'En-tête Services',
                'desc' => 'Présentation de l\'offre de conseil',
                'fields' => [
                    ['key' => 'tag', 'label' => 'Badge supérieur', 'type' => 'text']
                ]
            ],
            'ingenierie' => [
                'name' => 'Pôle Ingénierie de Formation',
                'desc' => 'Diagnostic des compétences et élaboration de plans',
                'fields' => [
                    ['key' => 'desc', 'label' => 'Descriptif méthodologique', 'type' => 'textarea']
                ]
            ],
            'giac_support' => [
                'name' => 'Accompagnement GIAC & CSF',
                'desc' => 'Montage et suivi des dossiers de remboursement',
                'fields' => [
                    ['key' => 'desc', 'label' => 'Procédure d\'assistance', 'type' => 'textarea']
                ]
            ]
        ]
    ],
    'simulateur' => [
        'name' => 'Simulateur GIAC & CSF',
        'file' => 'simulateur.html',
        'sections' => [
            'hero' => [
                'name' => 'En-tête du Simulateur',
                'desc' => 'Titre et instructions d\'utilisation',
                'fields' => [
                    ['key' => 'instructions', 'label' => 'Instructions pour l\'utilisateur', 'type' => 'textarea']
                ]
            ],
            'notes' => [
                'name' => 'Règles & Barèmes Légaux',
                'desc' => 'Mentions relatives aux plafonds et barèmes en vigueur',
                'fields' => [
                    ['key' => 'rule_giac', 'label' => 'Règle GIAC (70% - 80%)', 'type' => 'textarea'],
                    ['key' => 'rule_csf', 'label' => 'Règle CSF (Contrats Spéciaux)', 'type' => 'textarea']
                ]
            ]
        ]
    ],
    'reglementation' => [
        'name' => 'Réglementation & Décrets B.O.',
        'file' => 'reglementation.html',
        'sections' => [
            'hero' => [
                'name' => 'En-tête Réglementation',
                'desc' => 'Veille juridique et conformité marocaine',
                'fields' => [
                    ['key' => 'disclaimer', 'label' => 'Avertissement juridique', 'type' => 'textarea']
                ]
            ],
            'framework' => [
                'name' => 'Cadre Légal Marocain',
                'desc' => 'Code du travail Loi 65-99 et arrêtés ministériels',
                'fields' => [
                    ['key' => 'summary', 'label' => 'Synthèse des obligations', 'type' => 'textarea']
                ]
            ]
        ]
    ],
    'consultants' => [
        'name' => 'Consultants Seniors',
        'file' => 'consultants.html',
        'sections' => [
            'hero' => [
                'name' => 'En-tête Équipe Experts',
                'desc' => 'Titre et présentation du corps professoral',
                'fields' => [
                    ['key' => 'charter', 'label' => 'Charte d\'excellence consultants', 'type' => 'textarea']
                ]
            ]
        ]
    ],
    'contact' => [
        'name' => 'Contact & Siège',
        'file' => 'contact.html',
        'sections' => [
            'hero' => [
                'name' => 'En-tête Contact',
                'desc' => 'Titre et message d\'accueil',
                'fields' => [
                    ['key' => 'phone', 'label' => 'Téléphone Siège', 'type' => 'text'],
                    ['key' => 'email', 'label' => 'Email Principal', 'type' => 'text'],
                    ['key' => 'address', 'label' => 'Adresse Casablanca', 'type' => 'text'],
                    ['key' => 'hours', 'label' => 'Horaires d\'ouverture', 'type' => 'text']
                ]
            ]
        ]
    ],
    'global_footer' => [
        'name' => 'Pied de page Global',
        'file' => 'Tous',
        'sections' => [
            'footer_content' => [
                'name' => 'Contenu du Pied de Page',
                'desc' => 'Mentions légales, accréditations et certifications',
                'fields' => [
                    ['key' => 'about_text', 'label' => 'Descriptif court ME PLUS', 'type' => 'textarea'],
                    ['key' => 'cert_text', 'label' => 'Mentions d\'accréditation', 'type' => 'text'],
                    ['key' => 'copyright', 'label' => 'Mention de Copyright', 'type' => 'text']
                ]
            ]
        ]
    ]
];

$page_info = $pages_metadata[$page_key] ?? $pages_metadata['index'];
$existing_sections = get_page_sections($page_key);

$error = null;

// TRAITEMENT DU FORMULAIRE DE SAUVEGARDE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = "Erreur de sécurité CSRF. Veuillez réessayer.";
    } else {
        foreach ($page_info['sections'] as $s_key => $s_config) {
            $sec_title = trim($_POST['sections'][$s_key]['title'] ?? '');
            $sec_sub = trim($_POST['sections'][$s_key]['subtitle'] ?? '');
            
            $sec_content = [];
            if (!empty($s_config['fields'])) {
                foreach ($s_config['fields'] as $fld) {
                    $val = trim($_POST['sections'][$s_key]['fields'][$fld['key']] ?? '');
                    $sec_content[$fld['key']] = $val;
                }
            }

            save_page_section($page_key, $s_key, $sec_title, $sec_sub, $sec_content);
        }

        // Synchronise le cache
        sync_cache_files();

        header("Location: pages.php?saved=1");
        exit;
    }
}

$page_title = "Éditer : " . $page_info['name'];
$page_subtitle = "Modification des sections sans code HTML";
?>

<div class="max-w-4xl mx-auto space-y-6">

  <!-- Top bar -->
  <div class="flex items-center justify-between">
    <a href="pages.php" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-400 hover:text-white transition">
      <i data-lucide="arrow-left" class="w-4 h-4"></i>
      <span>Retour à la liste des pages</span>
    </a>

    <?php if ($page_info['file'] !== 'Tous'): ?>
      <a href="../<?= e($page_info['file']) ?>" target="_blank" class="text-xs text-cyan-400 hover:text-cyan-300 font-medium flex items-center gap-1">
        <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
        <span>Voir <?= e($page_info['file']) ?> en direct</span>
      </a>
    <?php endif; ?>
  </div>

  <?php if ($error): ?>
    <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm flex items-center gap-3">
      <i data-lucide="alert-triangle" class="w-5 h-5 shrink-0"></i>
      <span><?= e($error) ?></span>
    </div>
  <?php endif; ?>

  <!-- FORMULAIRE SECTION PAR SECTION -->
  <form method="POST" class="space-y-6">
    <?= csrf_field() ?>

    <?php foreach ($page_info['sections'] as $sec_key => $sec_meta): 
      $current_sec = $existing_sections[$sec_key] ?? [
          'title' => '',
          'subtitle' => '',
          'content' => []
      ];
    ?>
      <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800/80 space-y-5">
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
          <div>
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
              <i data-lucide="layout" class="w-4 h-4 text-cyan-400"></i>
              <span>Section : <?= e($sec_meta['name']) ?></span>
            </h3>
            <p class="text-xs text-slate-400 mt-0.5"><?= e($sec_meta['desc']) ?></p>
          </div>
          <span class="text-[10px] font-mono text-slate-500 bg-slate-800 px-2 py-0.5 rounded">
            ID: <?= e($sec_key) ?>
          </span>
        </div>

        <div class="space-y-4">
          <!-- Titre principal de section -->
          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Titre de la section</label>
            <input 
              type="text" 
              name="sections[<?= e($sec_key) ?>][title]" 
              value="<?= e($current_sec['title']) ?>" 
              placeholder="Titre de la section..."
              class="w-full px-4 py-2.5 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-cyan-500"
            >
          </div>

          <!-- Sous-titre ou description de section -->
          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Sous-titre / Accroche de la section</label>
            <textarea 
              name="sections[<?= e($sec_key) ?>][subtitle]" 
              rows="2" 
              placeholder="Sous-titre explicatif..."
              class="w-full px-4 py-2 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-cyan-500"
            ><?= e($current_sec['subtitle']) ?></textarea>
          </div>

          <!-- Champs personnalisés de la section -->
          <?php if (!empty($sec_meta['fields'])): ?>
            <div class="pt-3 border-t border-slate-800/60 space-y-3">
              <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Champs de contenu spécifiques</div>
              
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?php foreach ($sec_meta['fields'] as $f): 
                  $val = $current_sec['content'][$f['key']] ?? '';
                  $is_full = ($f['type'] === 'textarea');
                ?>
                  <div class="<?= $is_full ? 'sm:col-span-2' : '' ?>">
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1"><?= e($f['label']) ?></label>
                    <?php if ($f['type'] === 'textarea'): ?>
                      <textarea 
                        name="sections[<?= e($sec_key) ?>][fields][<?= e($f['key']) ?>]" 
                        rows="3" 
                        class="w-full px-3.5 py-2 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-cyan-500"
                      ><?= e($val) ?></textarea>
                    <?php else: ?>
                      <input 
                        type="text" 
                        name="sections[<?= e($sec_key) ?>][fields][<?= e($f['key']) ?>]" 
                        value="<?= e($val) ?>" 
                        class="w-full px-3.5 py-2 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-cyan-500"
                      >
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>

    <!-- SUBMIT ACTIONS BAR -->
    <div class="flex items-center justify-between p-4 rounded-2xl bg-slate-900/90 border border-slate-800 sticky bottom-4 shadow-2xl backdrop-blur-xl">
      <a href="pages.php" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:text-white transition">
        Annuler
      </a>

      <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-cyan-500 hover:from-emerald-500 hover:to-cyan-400 text-white text-xs font-bold flex items-center gap-2 shadow-lg shadow-emerald-500/20 transition">
        <i data-lucide="save" class="w-4 h-4"></i>
        <span>Enregistrer les sections & Synchroniser</span>
      </button>
    </div>

  </form>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
