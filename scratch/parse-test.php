<?php
require_once dirname(__DIR__) . '/admin/config.php';
$js = file_get_contents(DATA_JS_PATH);
$json = preg_replace('/^.*?window\.MEPLUS_DATA\s*=\s*/s', '', $js);
$json = preg_replace('/;\s*$/s', '', $json);
$data = json_decode($json, true);
echo "JSON error: " . json_last_error_msg() . "\n";
echo "Formations count: " . count($data['formations'] ?? []) . "\n";
echo "Regulations count: " . count($data['regulations'] ?? []) . "\n";
echo "Consultants count: " . count($data['consultants'] ?? []) . "\n";
echo "Clients count: " . count($data['clients'] ?? []) . "\n";
