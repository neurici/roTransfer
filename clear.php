<?php
require_once __DIR__ . '/util.php';

function page_forbidden(): void {
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html>
<html lang="ro"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>403 – Acces restricționat</title>
<style>
html,body{margin:0;height:100%;background:#0b0f1a;color:#e6e8ee;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif}
.wrap{display:flex;align-items:center;justify-content:center;height:100%}
.card{background:#12172a;border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:26px 30px;max-width:560px;box-shadow:0 20px 60px rgba(0,0,0,.45)}
h1{margin:0 0 12px 0;font-size:20px;font-weight:700}
p{margin:6px 0;font-size:14px;opacity:.88}
.code{margin-top:14px;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:13px;opacity:.6}
</style></head><body>
<div class="wrap"><div class="card">
<h1>Acces restricționat</h1>
<p>Acest endpoint este protejat.</p>
<p><b>Nu ai cheia corectă, nu funcționează.</b></p>
<p>Dacă ai ajuns aici din greșeală, poți închide pagina.</p>
<div class="code">HTTP 403 • roTransfer nuke endpoint</div>
</div></div>
</body></html>';
    exit;
}

function page_ok(int $tf, int $td, int $pf, int $pd): void {
    http_response_code(200);
    header('Content-Type: text/html; charset=utf-8');
    $ts = date('d-m-Y H:i');
    echo '<!doctype html>
<html lang="ro"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>roTransfer – Reset OK</title>
<style>
html,body{margin:0;height:100%;background:#0b0f1a;color:#e6e8ee;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif}
.wrap{display:flex;align-items:center;justify-content:center;height:100%}
.card{background:#12172a;border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:28px 32px;max-width:640px;box-shadow:0 20px 60px rgba(0,0,0,.45)}
.ok{display:flex;align-items:center;gap:10px;color:#2ecc71;font-size:18px;font-weight:800;margin-bottom:10px}
.ok span{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:50%;
background:rgba(46,204,113,.15);border:1px solid rgba(46,204,113,.5)}
p{margin:6px 0;font-size:14px;opacity:.9}
.mono{font-family:ui-monospace,Menlo,Consolas,monospace}
.line{margin-top:10px;padding-top:10px;border-top:1px solid rgba(255,255,255,.08)}
.code{margin-top:14px;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:13px;opacity:.65}
</style></head><body>
<div class="wrap"><div class="card">
<div class="ok"><span>✓</span> Reset complet executat</div>
<p class="mono">storage/transfers: fisiere sterse = <b style="color:#2ecc71">'.$tf.'</b>, directoare sterse = <b style="color:#2ecc71">'.$td.'</b></p>
<p class="mono">storage/tmp:       fisiere sterse = <b style="color:#2ecc71">'.$pf.'</b>, directoare sterse = <b style="color:#2ecc71">'.$pd.'</b></p>
<div class="line"></div>
<p>DB curățat: <b style="color:#2ecc71">transfereuri + fișiere + loguri</b> (ȘTERSE)</p>
<div class="code">roTransfer • Resetat total la '.$ts.'</div>
</div></div>
</body></html>';
    exit;
}

function page_error(Throwable $e): void {
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    $msg = h($e->getMessage());
    $at  = h($e->getFile() . ':' . $e->getLine());
    echo '<!doctype html>
<html lang="ro"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>roTransfer – Eroare</title>
<style>
html,body{margin:0;height:100%;background:#0b0f1a;color:#e6e8ee;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif}
.wrap{display:flex;align-items:center;justify-content:center;height:100%}
.card{background:#12172a;border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:26px 30px;max-width:760px;box-shadow:0 20px 60px rgba(0,0,0,.45)}
h1{margin:0 0 12px 0;font-size:20px;font-weight:800;color:#ffb4b4}
p{margin:6px 0;font-size:14px;opacity:.9}
.mono{font-family:ui-monospace,Menlo,Consolas,monospace;font-size:13px;opacity:.85;white-space:pre-wrap}
</style></head><body>
<div class="wrap"><div class="card">
<h1>Eroare la resetare</h1>
<p><b>Mesaj:</b></p>
<div class="mono">'.$msg.'</div>
<p style="margin-top:12px"><b>Loc:</b></p>
<div class="mono">'.$at.'</div>
</div></div>
</body></html>';
    exit;
}

function rrmdir_contents(string $dir): array {
    $deletedFiles = 0;
    $deletedDirs  = 0;

    if (!is_dir($dir)) return [$deletedFiles, $deletedDirs];

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($it as $f) {
        if ($f->isDir()) {
            if (@rmdir($f->getPathname())) $deletedDirs++;
        } else {
            if (@unlink($f->getPathname())) $deletedFiles++;
        }
    }

    return [$deletedFiles, $deletedDirs];
}

// ===== SECURITATE =====
$key = $_GET['key'] ?? '';
if (!defined('NUKE_HTTP_KEY') || $key !== NUKE_HTTP_KEY) {
    page_forbidden();
}

// ===== EXECUTIE =====
try {
    ensure_dirs();

    // Sterge TOT continutul din transfers si tmp
    [$tf, $td] = rrmdir_contents(TRANSFERS_PATH);
    [$pf, $pd] = rrmdir_contents(TMP_PATH);

    // Curata DB complet (recomandat)
    $pdo = db();
    $pdo->exec("DELETE FROM download_logs;");
    $pdo->exec("DELETE FROM files;");
    $pdo->exec("DELETE FROM transfers;");
    $pdo->exec("VACUUM;");

    page_ok($tf, $td, $pf, $pd);

} catch (Throwable $e) {
    // log local
    ensure_dirs();
    @file_put_contents(
        LOG_PATH . '/clear_errors.log',
        date('c') . " EROARE: " . $e->getMessage() . " @ " . $e->getFile() . ":" . $e->getLine() . "\n",
        FILE_APPEND
    );
    page_error($e);
}