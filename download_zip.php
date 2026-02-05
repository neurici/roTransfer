<?php
require_once __DIR__ . '/util.php';

$token = $_GET['token'] ?? '';
$token = preg_replace('/[^a-f0-9]/', '', (string)$token);
if ($token==='') { http_response_code(400); echo "Bad token"; exit; }

$t = require_transfer_by_token($token);
require_transfer_password_ok($t);

$files = list_files($t['id']);
if (!$files) { http_response_code(404); echo "No files"; exit; }

log_download($t['id'], null, 'zip');
bump_downloads($t['id']);

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="rotransfer_' . $token . '.zip"');
header('X-Content-Type-Options: nosniff');

$tmpZip = TMP_PATH . "/zip_" . $t['id'] . "_" . bin2hex(random_bytes(4)) . ".zip";
$za = new ZipArchive();
if ($za->open($tmpZip, ZipArchive::CREATE)!==true) { http_response_code(500); echo "zip open failed"; exit; }
foreach ($files as $f) {
    $path = TRANSFERS_PATH . '/' . $t['id'] . '/' . $f['stored_name'];
    if (is_file($path)) $za->addFile($path, $f['original_name']);
}
$za->close();

header('Content-Length: ' . filesize($tmpZip));
readfile($tmpZip);
@unlink($tmpZip);
