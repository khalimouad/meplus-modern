<?php
/**
 * ME PLUS Maroc - Script d'Installation & Initialisation Automatique
 * Crée les tables et importe les données initiales (85 formations, 7 consultants, 18 logos, décrets).
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

$message = '';
$error = '';
$is_installed = false;

// Vérifier si l'installation a déjà été faite
$db = get_db_connection();
$driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);

try {
    $check = $db->query("SELECT COUNT(*) FROM admins");
    if ($check && $check->fetchColumn() > 0) {
        $is_installed = true;
    }
} catch (Exception $e) {
    $is_installed = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'install') {
    try {
        // 1. Création des tables adaptée au driver (MySQL ou SQLite)
        if ($driver === 'sqlite') {
            $db->exec("
                CREATE TABLE IF NOT EXISTS admins (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    username TEXT NOT NULL UNIQUE,
                    email TEXT DEFAULT '',
                    password_hash TEXT NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    last_login DATETIME
                );
                CREATE TABLE IF NOT EXISTS formations (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    code TEXT NOT NULL UNIQUE,
                    title TEXT NOT NULL,
                    domain TEXT NOT NULL,
                    duration_days TEXT DEFAULT '2-3 jours',
                    duration_hours TEXT DEFAULT '14-21 heures',
                    public_cible TEXT,
                    prerequis TEXT,
                    objectifs_json TEXT,
                    programme_json TEXT,
                    certification TEXT DEFAULT 'Certifiante / Homologuée',
                    attached_file TEXT DEFAULT '',
                    is_featured INTEGER DEFAULT 0,
                    is_published INTEGER DEFAULT 1,
                    sort_order INTEGER DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );
                CREATE TABLE IF NOT EXISTS page_sections (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    page_key TEXT NOT NULL,
                    section_key TEXT NOT NULL,
                    title TEXT,
                    subtitle TEXT,
                    content_json TEXT,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE(page_key, section_key)
                );
                CREATE TABLE IF NOT EXISTS consultants (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    role TEXT NOT NULL,
                    tag TEXT DEFAULT 'Expert Senior',
                    avatar TEXT NOT NULL,
                    credentials TEXT,
                    specialties_json TEXT,
                    is_active INTEGER DEFAULT 1,
                    sort_order INTEGER DEFAULT 0
                );
                CREATE TABLE IF NOT EXISTS clients (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    sector TEXT DEFAULT '',
                    badge TEXT DEFAULT '',
                    logo TEXT NOT NULL,
                    is_active INTEGER DEFAULT 1,
                    sort_order INTEGER DEFAULT 0
                );
                CREATE TABLE IF NOT EXISTS documents (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    title TEXT NOT NULL,
                    category TEXT NOT NULL,
                    bo_ref TEXT DEFAULT '',
                    description TEXT,
                    date_text TEXT DEFAULT '2026',
                    file_path TEXT NOT NULL,
                    file_size INTEGER DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );
                CREATE TABLE IF NOT EXISTS leads (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    full_name TEXT NOT NULL,
                    company TEXT DEFAULT '',
                    phone TEXT DEFAULT '',
                    email TEXT DEFAULT '',
                    subject TEXT DEFAULT '',
                    message TEXT,
                    is_read INTEGER DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );
            ");
        } else {
            $schema_sql = file_get_contents(__DIR__ . '/schema.sql');
            $db->exec($schema_sql);
        }

        // 2. Création de l'Administrateur
        $admin_user = trim($_POST['admin_user'] ?? 'admin');
        $admin_pass = trim($_POST['admin_pass'] ?? 'admin123#');
        $admin_email = trim($_POST['admin_email'] ?? 'direction@meplus.ma');
        $pass_hash = password_hash($admin_pass, PASSWORD_BCRYPT);

        $stmt = $db->prepare("DELETE FROM admins WHERE username = :u");
        $stmt->execute([':u' => $admin_user]);

        $stmt = $db->prepare("INSERT INTO admins (username, email, password_hash, created_at) VALUES (:u, :e, :p, CURRENT_TIMESTAMP)");
        $stmt->execute([
            ':u' => $admin_user,
            ':e' => $admin_email,
            ':p' => $pass_hash
        ]);

        // 3. Import des données depuis data.js
        $js_data = file_get_contents(DATA_JS_PATH);
        // Nettoie l'assignation JS pour extraire le JSON
        $json_str = preg_replace('/^.*?window\.MEPLUS_DATA\s*=\s*/s', '', $js_data);
        $json_str = preg_replace('/;\s*$/s', '', $json_str);
        $data = json_decode($json_str, true);

        if ($data && !empty($data['formations'])) {
            // Import Formations
            $ins_form = $db->prepare("INSERT " . ($driver === 'sqlite' ? 'OR REPLACE' : '') . " INTO formations 
                (code, title, domain, duration_days, duration_hours, public_cible, prerequis, objectifs_json, programme_json, certification, attached_file, is_featured, is_published, sort_order) 
                VALUES (:code, :title, :domain, :dur_days, :dur_hours, :public, :prereq, :obj, :prog, :cert, :att, :feat, :pub, :sort)"
                . ($driver === 'mysql' ? " ON DUPLICATE KEY UPDATE title=VALUES(title), domain=VALUES(domain), objectifs_json=VALUES(objectifs_json), programme_json=VALUES(programme_json)" : "")
            );

            $order = 0;
            foreach ($data['formations'] as $f) {
                $order += 10;
                $code = $f['id'] ?? ('f-' . $order);
                $is_featured = in_array($code, ['f-44', 'f-45', 'f-47', 'f-28', 'f-54', 'f-53']) ? 1 : 0;
                $cert = stripos($f['title'], 'habilitation') !== false ? 'Habilitation NM 06.1.225' : 'Certifiante / Homologuée';

                $ins_form->execute([
                    ':code' => $code,
                    ':title' => $f['title'],
                    ':domain' => $f['domain'] ?? 'Technique',
                    ':dur_days' => $f['duration_days'] ?? '2-3 jours',
                    ':dur_hours' => $f['duration_hours'] ?? '14-21 heures',
                    ':public' => 'Ingénieurs, Responsables Maintenance, Techniciens et Opérateurs industriels.',
                    ':prereq' => 'Prérequis de base selon le module choisi.',
                    ':obj' => json_encode($f['objectives'] ?? [], JSON_UNESCAPED_UNICODE),
                    ':prog' => json_encode($f['content'] ?? [], JSON_UNESCAPED_UNICODE),
                    ':cert' => $cert,
                    ':att' => '',
                    ':feat' => $is_featured,
                    ':pub' => 1,
                    ':sort' => $order
                ]);
            }

            // Import Consultants
            if (!empty($data['consultants'])) {
                $ins_c = $db->prepare("INSERT INTO consultants (name, role, tag, avatar, credentials, specialties_json, is_active, sort_order) VALUES (:name, :role, :tag, :avatar, :cred, :spec, 1, :sort)");
                $cord = 0;
                $db->exec("DELETE FROM consultants");
                foreach ($data['consultants'] as $c) {
                    $cord += 10;
                    $ins_c->execute([
                        ':name' => $c['name'],
                        ':role' => $c['role'],
                        ':tag' => $c['tag'] ?? 'Expert Senior',
                        ':avatar' => $c['avatar'],
                        ':cred' => $c['credentials'],
                        ':spec' => json_encode($c['specialties'] ?? [], JSON_UNESCAPED_UNICODE),
                        ':sort' => $cord
                    ]);
                }
            }

            // Import Clients
            if (!empty($data['clients'])) {
                $ins_cl = $db->prepare("INSERT INTO clients (name, sector, badge, logo, is_active, sort_order) VALUES (:name, :sector, :badge, :logo, 1, :sort)");
                $cl_ord = 0;
                $db->exec("DELETE FROM clients");
                foreach ($data['clients'] as $cl) {
                    $cl_ord += 10;
                    $ins_cl->execute([
                        ':name' => $cl['name'],
                        ':sector' => $cl['sector'] ?? '',
                        ':badge' => $cl['badge'] ?? '',
                        ':logo' => $cl['logo'],
                        ':sort' => $cl_ord
                    ]);
                }
            }

            // Import Regulations / Documents
            if (!empty($data['regulations'])) {
                $ins_doc = $db->prepare("INSERT INTO documents (title, category, bo_ref, description, date_text, file_path, file_size) VALUES (:title, :cat, :bo, :desc, :dt, :fp, :fs)");
                $db->exec("DELETE FROM documents");
                foreach ($data['regulations'] as $reg) {
                    $filePath = __DIR__ . '/../' . ltrim($reg['pdf'], './');
                    $fileSize = file_exists($filePath) ? filesize($filePath) : 0;
                    $ins_doc->execute([
                        ':title' => $reg['title'],
                        ':cat' => $reg['category'],
                        ':bo' => $reg['bo'] ?? '',
                        ':desc' => $reg['description'] ?? '',
                        ':dt' => $reg['date'] ?? '2026',
                        ':fp' => $reg['pdf'],
                        ':fs' => $fileSize
                    ]);
                }
            }
        }

        // 4. Initialisation des Sections des Pages
        $default_sections = [
            // Accueil (index)
            ['index', 'hero', 'Ingénierie & Formation Continue au Maroc', '350+ Industriels & Multinationales accompagnés depuis 2004', [
                'tag' => 'Centre Agrée État Marocain • Habilitations NM 06.1.225',
                'badge_1' => 'Homologué DFP & GIAC',
                'badge_2' => '80% Pris en Charge CSF',
                'badge_3' => 'Intervenants Seniors Reconnus',
                'cta_primary_text' => 'Explorer le Catalogue (85+)',
                'cta_secondary_text' => 'Simulateur Remboursement CSF'
            ]],
            ['index', 'stats', 'Chiffres Clés de Référence', 'L’impact mesuré sur vos opérations industrielles', [
                'stat1_val' => '350+', 'stat1_lbl' => 'Usines & Clients',
                'stat2_val' => '85+', 'stat2_lbl' => 'Modules Pratiques',
                'stat3_val' => '18', 'stat3_lbl' => 'Ans d\'Expérience Terrain',
                'stat4_val' => '80%', 'stat4_lbl' => 'Remboursement Moyen CSF'
            ]],
            ['index', 'about', 'Cabinet Fondateur d’Excellence Industrielle', '20 ans de rigueur au service de la performance technique et humaine', [
                'intro' => 'Fondé en 2008 dans la continuité de ME Consult (2004), ME PLUS est le partenaire stratégique des plus grands donneurs d’ordre du Royaume pour la formation technique, l’audit de conformité et l’ingénierie de maintenance.',
                'point1' => 'Audits réglementaires (Décret 2.14.499, Décret 2.12.236)',
                'point2' => 'Habilitations électriques certifiantes sous NM 06.1.225',
                'point3' => 'Accompagnement administratif complet dossiers CSF et F2'
            ]],
            // Services
            ['services', 'header', 'Services d’Audit & Ingénierie Pédagogique', 'Diagnostics GPEC, TPM, et Audits de Conformité Réglementaire sur site', [
                'badge' => 'Audit & Conseil Terrain',
                'cta_text' => 'Demander un Diagnostic'
            ]],
            // Simulateur
            ['simulateur', 'header', 'Simulateur de Remboursement CSF / OFPPT', 'Estimez votre prise en charge par la Taxe de Formation Professionnelle', [
                'default_rate' => '80%',
                'disclaimer' => 'Simulation indicative basée sur la réglementation marocaine des Contrats Spéciaux de Formation (CSF).'
            ]],
            // Réglementation
            ['reglementation', 'header', 'Veille Réglementaire & Textes Juridiques Marocains', 'Bulletins Officiels et décrets de référence applicables aux sites industriels', [
                'badge' => 'Bulletins Officiels Téléchargeables',
                'cta_text' => 'Audit de Conformité'
            ]],
            // Consultants
            ['consultants', 'header', 'Notre Équipe d’Experts & Consultants Seniors', 'Des praticiens du terrain, anciens directeurs d’usines et ingénieurs d’État', [
                'badge' => 'Pratique Terrain • Capital Humain',
                'charter_title' => 'La Charte Pédagogique ME PLUS',
                'charter_desc' => '70% de pratique en atelier et 30% d\'apports méthodologiques structurés.'
            ]],
            // Contact
            ['contact', 'info', 'Siège Social Casablanca & Contact Direct', 'Nos conseillers formation sont à votre disposition du lundi au vendredi', [
                'address' => 'Rue Moussa Al Kadim, Imm. Le Lys N° 21, Bourgogne, Casablanca, Maroc',
                'phone1' => '+212 6 61 45 02 48',
                'phone2' => '+212 5 22 48 39 48',
                'email' => 'formation@meplus.ma',
                'hours' => 'Lundi - Vendredi : 08h30 - 18h00'
            ]]
        ];

        foreach ($default_sections as $sec) {
            save_page_section($sec[0], $sec[1], $sec[2], $sec[3], $sec[4]);
        }

        // Synchronise data.json et data.js
        sync_cache_files();

        $message = "Installation réussie ! La base de données a été configurée et initialisée avec l'ensemble des 85 formations, 7 consultants, 18 logos clients et textes par page.";
        $is_installed = true;

    } catch (Exception $e) {
        $error = "Erreur lors de l'installation : " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr" class="h-full bg-slate-950 text-slate-100">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Installation Backend - ME PLUS Maroc</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-full flex items-center justify-center p-6 bg-gradient-to-b from-slate-900 to-slate-950 font-sans">
  <div class="max-w-xl w-full bg-slate-900/90 border border-slate-800 rounded-3xl p-8 shadow-2xl backdrop-blur-xl">
    
    <div class="flex items-center gap-3 mb-6">
      <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-blue-600 to-cyan-400 p-0.5">
        <div class="w-full h-full bg-[#070e1b] rounded-2xl flex items-center justify-center font-bold text-sm text-cyan-400">
          ME+
        </div>
      </div>
      <div>
        <h1 class="text-xl font-bold text-white">Installation Backend ME PLUS</h1>
        <p class="text-xs text-slate-400">Initialisation de la base de données (MySQL / SQLite)</p>
      </div>
    </div>

    <?php if ($message): ?>
      <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm mb-6">
        <div class="font-bold mb-1">Succès !</div>
        <?= htmlspecialchars($message) ?>
      </div>
      <div class="flex justify-center">
        <a href="login.php" class="px-6 py-3 rounded-full bg-gradient-to-r from-blue-600 to-cyan-500 text-white font-bold text-sm uppercase tracking-wider hover:opacity-90 transition shadow-lg shadow-cyan-500/20">
          Accéder à l'Espace Admin &rarr;
        </a>
      </div>
    <?php else: ?>

      <?php if ($error): ?>
        <div class="p-4 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm mb-6">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <p class="text-xs text-slate-300 leading-relaxed mb-6">
        Cet assistant va configurer les tables nécessaires et importer automatiquement :
        <strong class="text-cyan-400">85 modules de formation</strong>, 
        <strong class="text-cyan-400">7 profils de consultants seniors</strong>, 
        <strong class="text-cyan-400">18 logos d'entreprises partenaires</strong>,
        et les textes de toutes les sections de vos pages.
      </p>

      <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="install">

        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Identifiant Administrateur</label>
          <input type="text" name="admin_user" value="admin" required class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:border-cyan-500 outline-none">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Mot de Passe Administrateur</label>
          <input type="text" name="admin_pass" value="admin123#" required class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:border-cyan-500 outline-none">
          <p class="text-[10px] text-slate-500 mt-1">Vous pourrez modifier ce mot de passe à tout moment depuis les Paramètres.</p>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Email de Contact</label>
          <input type="email" name="admin_email" value="direction@meplus.ma" required class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:border-cyan-500 outline-none">
        </div>

        <div class="pt-4">
          <button type="submit" class="w-full py-3.5 rounded-full bg-gradient-to-r from-blue-600 to-cyan-500 text-white font-bold text-xs uppercase tracking-wider hover:opacity-90 transition shadow-lg shadow-cyan-500/20">
            <?= $is_installed ? 'Réinitialiser & Synchroniser la Base' : 'Lancer l’Installation Automatique' ?>
          </button>
        </div>
      </form>
    <?php endif; ?>

  </div>
</body>
</html>
