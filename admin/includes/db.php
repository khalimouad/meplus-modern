<?php
/**
 * ME PLUS Maroc - Fonctions d'accès aux données & Synchronisation Cache
 */

require_once __DIR__ . '/../config.php';

/**
 * Échappe une chaîne pour un affichage HTML sécurisé (Protection XSS)
 */
function e($str) {
    if ($str === null) return '';
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

/**
 * Récupère toutes les formations avec filtres optionnels
 */
function get_all_formations($filter_domain = null, $only_published = false) {
    $db = get_db_connection();
    $sql = "SELECT * FROM formations WHERE 1=1";
    $params = [];

    if ($filter_domain && $filter_domain !== 'all') {
        $sql .= " AND domain = :domain";
        $params[':domain'] = $filter_domain;
    }
    if ($only_published) {
        $sql .= " AND is_published = 1";
    }
    $sql .= " ORDER BY sort_order ASC, title ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Récupère une formation par son ID ou code
 */
function get_formation_by_id($id) {
    $db = get_db_connection();
    $stmt = $db->prepare("SELECT * FROM formations WHERE id = :id OR code = :code LIMIT 1");
    $stmt->execute([':id' => $id, ':code' => $id]);
    return $stmt->fetch();
}

/**
 * Récupère les sections d'une page
 */
function get_page_sections($page_key) {
    $db = get_db_connection();
    $stmt = $db->prepare("SELECT * FROM page_sections WHERE page_key = :page_key ORDER BY section_key ASC");
    $stmt->execute([':page_key' => $page_key]);
    $rows = $stmt->fetchAll();

    $sections = [];
    foreach ($rows as $row) {
        $content = json_decode($row['content_json'] ?? '{}', true) ?: [];
        $sections[$row['section_key']] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'subtitle' => $row['subtitle'],
            'content' => $content
        ];
    }
    return $sections;
}

/**
 * Met à jour ou insère une section de page
 */
function save_page_section($page_key, $section_key, $title, $subtitle = '', $content = []) {
    $db = get_db_connection();
    $content_json = is_string($content) ? $content : json_encode($content, JSON_UNESCAPED_UNICODE);

    // Vérifie si la section existe
    $stmt = $db->prepare("SELECT id FROM page_sections WHERE page_key = :page_key AND section_key = :section_key");
    $stmt->execute([':page_key' => $page_key, ':section_key' => $section_key]);
    $existing = $stmt->fetch();

    if ($existing) {
        $update = $db->prepare("UPDATE page_sections SET title = :title, subtitle = :subtitle, content_json = :content_json, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        $update->execute([
            ':title' => $title,
            ':subtitle' => $subtitle,
            ':content_json' => $content_json,
            ':id' => $existing['id']
        ]);
    } else {
        $insert = $db->prepare("INSERT INTO page_sections (page_key, section_key, title, subtitle, content_json, updated_at) VALUES (:page_key, :section_key, :title, :subtitle, :content_json, CURRENT_TIMESTAMP)");
        $insert->execute([
            ':page_key' => $page_key,
            ':section_key' => $section_key,
            ':title' => $title,
            ':subtitle' => $subtitle,
            ':content_json' => $content_json
        ]);
    }
    sync_cache_files();
}

/**
 * Synchronise les données de la base vers data.json et data.js
 * Assure un affichage immédiat et sans latence sur le site public
 */
function sync_cache_files() {
    $db = get_db_connection();

    // 1. Formations
    $stmt = $db->query("SELECT * FROM formations WHERE is_published = 1 ORDER BY sort_order ASC, title ASC");
    $raw_formations = $stmt->fetchAll();
    $formations = [];
    foreach ($raw_formations as $rf) {
        $objectives = json_decode($rf['objectifs_json'] ?? '[]', true) ?: [];
        $content = json_decode($rf['programme_json'] ?? '[]', true) ?: [];

        $formations[] = [
            'id' => $rf['code'] ?: 'f-' . $rf['id'],
            'title' => $rf['title'],
            'domain' => $rf['domain'],
            'duration_days' => $rf['duration_days'] ?? '',
            'duration_hours' => $rf['duration_hours'] ?? '',
            'public_cible' => $rf['public_cible'] ?? '',
            'prerequis' => $rf['prerequis'] ?? '',
            'objectives' => $objectives,
            'content' => $content,
            'certification' => $rf['certification'] ?? 'Certifiante',
            'attached_file' => $rf['attached_file'] ?? '',
            'is_featured' => (bool)($rf['is_featured'] ?? 0)
        ];
    }

    // 2. Regulations / Documents
    $stmt = $db->query("SELECT * FROM documents ORDER BY id ASC");
    $raw_docs = $stmt->fetchAll();
    $regulations = [];
    foreach ($raw_docs as $rd) {
        $regulations[] = [
            'id' => 'reg-' . $rd['id'],
            'title' => $rd['title'],
            'category' => $rd['category'],
            'date' => $rd['date_text'] ?? '2026',
            'bo' => $rd['bo_ref'] ?? '',
            'description' => $rd['description'] ?? '',
            'pdf' => $rd['file_path']
        ];
    }

    // 3. Consultants
    $stmt = $db->query("SELECT * FROM consultants WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
    $raw_consultants = $stmt->fetchAll();
    $consultants = [];
    foreach ($raw_consultants as $rc) {
        $specs = json_decode($rc['specialties_json'] ?? '[]', true) ?: [];
        $consultants[] = [
            'id' => 'c-' . $rc['id'],
            'name' => $rc['name'],
            'role' => $rc['role'],
            'tag' => $rc['tag'],
            'avatar' => $rc['avatar'],
            'credentials' => $rc['credentials'],
            'specialties' => $specs
        ];
    }

    // 4. Clients
    $stmt = $db->query("SELECT * FROM clients WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
    $raw_clients = $stmt->fetchAll();
    $clients = [];
    foreach ($raw_clients as $rcl) {
        $clients[] = [
            'name' => $rcl['name'],
            'sector' => $rcl['sector'],
            'badge' => $rcl['badge'],
            'logo' => $rcl['logo']
        ];
    }

    // 5. Page Sections
    $stmt = $db->query("SELECT * FROM page_sections ORDER BY page_key ASC, section_key ASC");
    $raw_sections = $stmt->fetchAll();
    $pages_content = [];
    foreach ($raw_sections as $rs) {
        $pages_content[$rs['page_key']][$rs['section_key']] = [
            'title' => $rs['title'],
            'subtitle' => $rs['subtitle'],
            'data' => json_decode($rs['content_json'] ?? '{}', true) ?: []
        ];
    }

    $export_data = [
        'formations' => $formations,
        'regulations' => $regulations,
        'consultants' => $consultants,
        'clients' => $clients,
        'pages' => $pages_content,
        'synced_at' => date('Y-m-d H:i:s')
    ];

    // Sauvegarde data.json
    @file_put_contents(DATA_JSON_PATH, json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // Sauvegarde data.js compatible front-end
    $js_content = "// Données synchronisées MEPLUS Maroc - " . date('Y-m-d H:i:s') . "\n";
    $js_content .= "window.MEPLUS_DATA = " . json_encode($export_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ";\n";
    @file_put_contents(DATA_JS_PATH, $js_content);

    return true;
}
