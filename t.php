<?php
require_once __DIR__ . '/util.php';

$token = $_GET['token'] ?? '';
$token = preg_replace('/[^a-f0-9]/', '', (string)$token);
if ($token==='') { http_response_code(400); echo "Token lipsa."; exit; }

$t = require_transfer_by_token($token);

if (!empty($t['password_hash']) && !is_transfer_authed($token)) {
    $err = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pw = (string)($_POST['password'] ?? '');
        if (password_verify($pw, (string)$t['password_hash'])) {
            set_transfer_authed($token);
            header('Location: t.php?token=' . urlencode($token));
            exit;
        } else $err = 'Parola gresita.';
    }
?><!doctype html><html lang="ro"><head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title><?=h(APP_NAME)?> - Parolă</title>
<style>
body{font-family:system-ui,Segoe UI,Roboto,Arial;background:#0b1020;color:#e8ecff;margin:0}
.wrap{max-width:520px;margin:0 auto;padding:28px}
.card{background:#111a33;border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:18px}
label{display:block;font-size:12px;opacity:.85;margin-bottom:6px}
input{width:100%;box-sizing:border-box;border-radius:12px;border:1px solid rgba(255,255,255,.14);
background:#0e1630;color:#e8ecff;padding:12px}
.btn{margin-top:12px;background:#6d5efc;border:none;color:white;padding:12px 14px;border-radius:12px;cursor:pointer;font-weight:600;width:100%}
.err{margin-top:10px;color:#ffb4b4}
.muted{opacity:.8;font-size:13px;margin-top:8px}
</style></head><body>
<div class="wrap">
<h1 style="margin:0 0 12px 0;font-size:22px"><?=h(APP_NAME)?> 🇷🇴</h1>
<div class="card">
<div style="font-weight:800">Acest transfer este protejat cu parola</div>
<div class="muted">Introduceți parola pentru a vizualizarea și descărcarea fișierelor</div>
<form method="post">
<label style="margin-top:12px">Parola</label>
<input type="password" name="password" autofocus />
<button class="btn" type="submit">Continuare</button>
<?php if ($err): ?><div class="err"><?=h($err)?></div><?php endif; ?>
</form>
</div></div>
</body></html>
<?php exit; } ?>

<?php
$files = list_files($t['id']);
$exp = date('d-m-Y H:i', (int)$t['expires_at']);
$expIn = expires_in_text((int)$t['expires_at']);
$linkZip = "download_zip.php?token=" . urlencode($token);
?><!doctype html><html lang="ro"><head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title><?=h(APP_NAME)?> - Descărcare</title>
<style>
body{font-family:system-ui,Segoe UI,Roboto,Arial;background:#0b1020;color:#e8ecff;margin:0}
.wrap{max-width:900px;margin:0 auto;padding:28px}
.card{background:#111a33;border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:18px}
a{color:#9bd1ff}.muted{opacity:.8;font-size:13px}
.btn{display:inline-block;background:#6d5efc;color:white;padding:10px 12px;border-radius:12px;text-decoration:none;font-weight:700}
table{width:100%;border-collapse:collapse;margin-top:12px}
td,th{padding:10px;border-bottom:1px solid rgba(255,255,255,.08);text-align:left}
.mono{font-family:ui-monospace,Menlo,Consolas,monospace}
</style></head><body>
<div class="wrap">
<h1 style="margin:0 0 12px 0;font-size:22px"><?=h(APP_NAME)?> 🇷🇴</h1>
<div class="card">
<?php if (!empty($t['title'])): ?><div style="font-size:18px;font-weight:800"><?=h($t['title'])?></div><?php endif; ?>
<?php if (!empty($t['message'])): ?><div class="muted" style="margin-top:8px"><?=nl2br(h($t['message']))?></div><?php endif; ?>
<div class="muted" style="margin-top:10px">
Expiră la: <b class="mono"><?=h($exp)?></b>
<span style="opacity:.85"> (expiră în: <b><?=h($expIn)?></b>)</span>
</div>
<div style="margin-top:14px"><a class="btn" href="<?=h($linkZip)?>">Descărcare arhivă ZIP</a></div>
<table><thead><tr><th>Fișier</th><th>Dimensiune</th><th></th></tr></thead><tbody>
<?php foreach ($files as $f): ?>
<tr>
<td><?=h($f['original_name'])?></td>
<td class="mono"><?=number_format((int)$f['size']/1024/1024, 2)?> MB</td>
<td><a href="download.php?token=<?=h($token)?>&file=<?=h($f['id'])?>">Descărcare</a></td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div></div></body></html>
