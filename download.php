<?php
require_once __DIR__ . '/util.php';

$token = $_GET['token'] ?? '';
$fileId= $_GET['file'] ?? '';
$token = preg_replace('/[^a-f0-9]/', '', (string)$token);
$fileId= preg_replace('/[^a-f0-9\-]/', '', (string)$fileId);
if ($token==='' || $fileId==='') { http_response_code(400); echo "Bad params"; exit; }

$t = require_transfer_by_token($token);
require_transfer_password_ok($t);

$pdo = db();
$st = $pdo->prepare("SELECT * FROM files WHERE id=:fid AND transfer_id=:tid LIMIT 1");
$st->execute([':fid'=>$fileId, ':tid'=>$t['id']]);
$f = $st->fetch();
if (!$f) { http_response_code(404); echo "File not found"; exit; }

$path = TRANSFERS_PATH . '/' . $t['id'] . '/' . $f['stored_name'];
if (!is_file($path)) { http_response_code(404); echo "Missing file"; exit; }

log_download($t['id'], $fileId, 'file');
bump_downloads($t['id']);

header('Content-Type: ' . ($f['mime'] ?: 'application/octet-stream'));
header('Content-Length: ' . filesize($path));
header('Content-Disposition: attachment; filename="' . rawurlencode($f['original_name']) . '"');
header('X-Content-Type-Options: nosniff');
readfile($path);
