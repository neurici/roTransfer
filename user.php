<?php
require_once __DIR__ . '/util.php';

$u = require_login();
$pdo = db();

$st = $pdo->prepare("SELECT id, token, title, recipient_email, total_bytes, downloads_count, created_at, expires_at
                    FROM transfers WHERE user_id = :uid ORDER BY created_at DESC");
$st->execute([':uid' => $u['id']]);
$transfers = $st->fetchAll();

function b2mb(int $bytes): string { return number_format($bytes / 1024 / 1024, 2) . ' MB'; }
?><!doctype html>
<html lang="ro"><head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title><?=h(APP_NAME)?> - Cont</title>
<style>
body{font-family:system-ui,Segoe UI,Roboto,Arial;background:#0b1020;color:#e8ecff;margin:0}
.wrap{max-width:980px;margin:0 auto;padding:28px}
.card{background:#111a33;border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:18px}
.top{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px}
.badge{font-size:12px;padding:6px 10px;border-radius:999px;background:rgba(255,255,255,.08)}
a{color:#9bd1ff}
.table{width:100%;border-collapse:separate;border-spacing:0;border:1px solid rgba(255,255,255,.10);border-radius:14px;overflow:hidden}
.table th,.table td{padding:11px 12px;border-bottom:1px solid rgba(255,255,255,.08);text-align:left;font-size:13px}
.table th{background:rgba(255,255,255,.06);font-size:12px;letter-spacing:.06em;text-transform:uppercase;opacity:.9}
.table tr:last-child td{border-bottom:none}
.btn{background:#6d5efc;border:none;color:white;padding:10px 12px;border-radius:12px;cursor:pointer;font-weight:700;text-decoration:none;display:inline-block}
.muted{opacity:.8;font-size:13px}
.pill{display:inline-block;padding:4px 8px;border-radius:999px;background:rgba(255,255,255,.08);font-size:12px}
</style></head>
<body><div class="wrap">
<div class="top">
  <h1 style="margin:0;font-size:22px"><?=h(APP_NAME)?> • Cont</h1>
  <div style="display:flex;gap:10px;align-items:center">
    <a class="badge" href="index.php" style="text-decoration:none;color:inherit;font-weight:700">Acasă</a>
    <div class="badge">Autentificat ca: <?=h($u['email'])?></div>
    <a class="badge" href="logout.php" style="text-decoration:none;color:inherit;font-weight:700">Deconectare</a>
  </div>
</div>

<div class="card">
  <h2 style="margin:0 0 8px 0;font-size:16px">Transferurile mele</h2>
  <div class="muted" style="margin-bottom:12px">Aici sunt toate transferurile create din contul dvs..</div>

  <?php if (!$transfers): ?>
    <div class="muted">Nu aveți nici un transfer efectuat. Efectuați unul din <a href="index.php">pagina principală</a>.</div>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>Data</th>
          <th>Subiect</th>
          <th>Destinatar</th>
          <th>Mărime</th>
          <th>Descărcări</th>
          <th>Expiră</th>
          <th>Link</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($transfers as $t):
        $link = APP_BASE_URL . '/t.php?token=' . urlencode($t['token']);
        $created = date('d-m-Y H:i', (int)$t['created_at']);
        $exp = date('d-m-Y H:i', (int)$t['expires_at']);
        $expIn = expires_in_text((int)$t['expires_at']);
        $title = (string)($t['title'] ?? '—');
        $to = (string)($t['recipient_email'] ?? '—');
        ?>
        <tr>
          <td><span class="pill"><?=h($created)?></span></td>
          <td><?=h($title)?></td>
          <td><?=h($to)?></td>
          <td><?=h(b2mb((int)$t['total_bytes']))?></td>
          <td><?= (int)$t['downloads_count'] ?></td>
          <td><?=h($exp)?> <span class="muted">(<?=h($expIn)?>)</span></td>
          <td><a href="<?=h($link)?>" target="_blank" rel="noopener">Deschide</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <div style="margin-top:14px" class="muted">
    Notă: Transferurile mai vechi (create înainte de activarea conturilor) nu apar aici.
  </div>
</div>

</div></body></html>
