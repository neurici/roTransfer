<?php
require_once __DIR__ . '/util.php';

$raw = file_get_contents('php://input');
$in = json_decode($raw, true);
if (!is_array($in)) json_out(['error'=>'JSON invalid'], 400);

$transferId = (string)($in['transfer_id'] ?? '');
$fileId     = (string)($in['file_id'] ?? '');
if ($transferId==='' || $fileId==='') json_out(['error'=>'params'], 400);

$pdo = db();
$st = $pdo->prepare("SELECT * FROM files WHERE id=:fid AND transfer_id=:tid LIMIT 1");
$st->execute([':fid'=>$fileId, ':tid'=>$transferId]);
$f = $st->fetch();
if (!$f) json_out(['error'=>'file not found'], 404);

$tmpDir = TMP_PATH . "/{$transferId}/{$fileId}";
if (!is_dir($tmpDir)) json_out(['error'=>'missing chunks'], 400);

$parts = glob($tmpDir . "/*.part"); sort($parts);
if (!$parts) json_out(['error'=>'no chunks (limite PHP sau upload respins)'], 400);

$dir = transfer_dir($transferId);
$storedName = $fileId . "_" . preg_replace('/[^A-Za-z0-9\.\-_]+/', '_', $f['original_name']);
$finalPath = $dir . "/" . $storedName;

$out = fopen($finalPath, 'wb');
if (!$out) json_out(['error'=>'cannot open output'], 500);

$hash = hash_init('sha256'); $written = 0;
foreach ($parts as $p) {
    $inF = fopen($p, 'rb');
    if (!$inF) { fclose($out); json_out(['error'=>'cannot read part'], 500); }
    while (!feof($inF)) {
        $buf = fread($inF, 1024*1024);
        if ($buf === false) break;
        $written += strlen($buf);
        hash_update($hash, $buf);
        fwrite($out, $buf);
    }
    fclose($inF);
}
fclose($out);

$expected = (int)$f['size'];
if ($expected !== $written) { @unlink($finalPath); json_out(['error'=>"size mismatch expected={$expected} got={$written}"], 400); }

$sha = hash_final($hash);
$pdo->prepare("UPDATE files SET stored_name=:sn, sha256=:sha WHERE id=:fid")->execute([':sn'=>$storedName, ':sha'=>$sha, ':fid'=>$fileId]);

foreach ($parts as $p) @unlink($p);
@rmdir($tmpDir);

json_out(['ok'=>true]);
