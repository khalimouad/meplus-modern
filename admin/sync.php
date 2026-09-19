<?php
/**
 * ME PLUS Maroc - Déclencheur de synchronisation du cache data.json & data.js
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

require_auth();

$success = sync_cache_files();

$referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
// Nettoie l'URL de retour
$redirect_url = strtok($referer, '?');
$param = $success ? 'synced=1' : 'sync_err=1';

header("Location: {$redirect_url}?{$param}");
exit;
