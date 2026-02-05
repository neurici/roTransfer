<?php
require_once __DIR__ . '/util.php';

// ===== SECURITATE CRON HTTP =====
// - Din CLI: ruleaza fara cheie (php cron_cleanup.php)
// - Din browser/curl: ruleaza doar cu ?key=CRON_HTTP_KEY
if (php_sapi_name() !== 'cli') {
    $key = $_GET['key'] ?? '';
    if (!defined('CRON_HTTP_KEY') || $key !== CRON_HTTP_KEY) {
        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');

        echo '<!doctype html>
<html lang="ro">
<head>
<meta charset="utf-8">
<title>403 – Acces restricționat</title>
<style>
html,body{
  margin:0;
  height:100%;
  background:#0b0f1a;
  color:#e6e8ee;
  font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
}
.wrap{
  display:flex;
  align-items:center;
  justify-content:center;
  height:100%;
}
.card{
  background:#12172a;
  border:1px solid rgba(255,255,255,.08);
  border-radius:14px;
  padding:26px 30px;
  max-width:520px;
  box-shadow:0 20px 60px rgba(0,0,0,.45);
}
h1{
  margin:0 0 12px 0;
  font-size:20px;
  font-weight:700;
}
p{
  margin:6px 0;
  font-size:14px;
  opacity:.85;
}
.code{
  margin-top:14px;
  font-family: ui-monospace, Menlo, Consolas, monospace;
  font-size:13px;
  opacity:.6;
}
</style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <h1>Acces restricționat</h1>
    <p>Acest endpoint este protejat.</p>
    <p><b>Nu ai cheia corectă, nu funcționează.</b></p>
    <p>Dacă ai ajuns aici din greșeală, poți închide pagina.</p>
    <div class="code">HTTP 403 • roTransfer cron endpoint</div>
  </div>
</div>
</body>
</html>';
        exit;
    }
}

$pdo = db();
$now = now();

$st = $pdo->prepare("SELECT id FROM transfers WHERE expires_at <= :now");
$st->execute([':now'=>$now]);
$expired = $st->fetchAll();

$deleted = 0;
foreach ($expired as $t) {
    $tid = $t['id'];

    $dir = TRANSFERS_PATH . '/' . $tid;
    if (is_dir($dir)) {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $f) {
            $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        }
        @rmdir($dir);
    }

    $tmp = TMP_PATH . '/' . $tid;
    if (is_dir($tmp)) {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tmp, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $f) {
            $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        }
        @rmdir($tmp);
    }

    $pdo->prepare("DELETE FROM download_logs WHERE transfer_id=:tid")->execute([':tid'=>$tid]);
    $pdo->prepare("DELETE FROM files WHERE transfer_id=:tid")->execute([':tid'=>$tid]);
    $pdo->prepare("DELETE FROM transfers WHERE id=:tid")->execute([':tid'=>$tid]);
    $deleted++;
}

if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/html; charset=utf-8');

    echo '<!doctype html>
<html lang="ro">
<head>
<meta charset="utf-8">
<title>roTransfer – Cleanup OK</title>
<style>
html,body{
  margin:0;
  height:100%;
  background:#0b0f1a;
  color:#e6e8ee;
  font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
}
.wrap{
  display:flex;
  align-items:center;
  justify-content:center;
  height:100%;
}
.card{
  background:#12172a;
  border:1px solid rgba(255,255,255,.08);
  border-radius:14px;
  padding:28px 32px;
  max-width:520px;
  box-shadow:0 20px 60px rgba(0,0,0,.45);
}
.ok{
  display:flex;
  align-items:center;
  gap:10px;
  color:#2ecc71;
  font-size:18px;
  font-weight:700;
  margin-bottom:10px;
}
.ok span{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  width:26px;
  height:26px;
  border-radius:50%;
  background:rgba(46,204,113,.15);
  border:1px solid rgba(46,204,113,.5);
}
p{
  margin:6px 0;
  font-size:14px;
  opacity:.9;
}
.code{
  margin-top:14px;
  font-family: ui-monospace, Menlo, Consolas, monospace;
  font-size:13px;
  opacity:.65;
}
</style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <div class="ok">
      <span>✓</span>
      Ștergere executată cu succes
    </div>
    <p>Transferuri șterse: <b style="color:#2ecc71">'.$deleted.'</b></p>
    <div class="code">roTransfer - Cron executat la '.date('d-m-Y H:i').'</div>
  </div>
</div>
</body>
</html>';
} else {
    // CLI / cron clasic
    echo "Done. Deleted transfers: {$deleted}\n";
}
