<?php
require_once __DIR__ . '/util.php';

start_session();

function bytes_human(int $bytes): string {
    $units = ['B','KB','MB','GB','TB'];
    $i = 0;
    $v = (float)$bytes;
    while ($v >= 1024 && $i < count($units)-1) { $v /= 1024; $i++; }
    $dec = $i === 0 ? 0 : 2;
    return number_format($v, $dec, '.', '') . ' ' . $units[$i];
}

function admin_logged_in(): bool {
    return !empty($_SESSION['admin_ok']);
}

function admin_login_ok(string $u, string $p): bool {
    $uOk = defined('ADMIN_USER') && hash_equals((string)ADMIN_USER, $u);

    if (defined('ADMIN_PASS_HASH')) {
        return $uOk && password_verify($p, (string)ADMIN_PASS_HASH);
    }
    if (defined('ADMIN_PASS')) {
        return $uOk && hash_equals((string)ADMIN_PASS, $p);
    }
    return false;
}

if (isset($_POST['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: admin.php');
    exit;
}

$err = '';
if (!admin_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user'], $_POST['pass'])) {
    if (admin_login_ok(trim($_POST['user']), $_POST['pass'])) {
        $_SESSION['admin_ok'] = 1;
        header('Location: admin.php');
        exit;
    } else {
        $err = 'User sau parolă greșită.';
    }
}

$transfers = [];
if (admin_logged_in()) {
    try {
        $pdo = db();
        $st = $pdo->query("SELECT t.id, t.title, t.token, t.expires_at, t.created_at, t.total_bytes, t.downloads_count, u.email AS user_email
                FROM transfers t
                LEFT JOIN users u ON u.id = t.user_id
                ORDER BY t.created_at DESC");
        $transfers = $st->fetchAll();
    } catch (Throwable $e) {
        // silent fail; UI will show empty list
        $transfers = [];
    }
}
?><!doctype html>
<html lang="ro">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title><?=h(APP_NAME)?> – Admin</title>
<style>
body{margin:0;font-family:system-ui,Segoe UI,Roboto,Arial;background:#0b0f1a;color:#e6e8ee}
.wrap{max-width:980px;margin:0 auto;padding:28px}
.top{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px}
.badge{font-size:12px;padding:10px 14px;border-radius:999px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.08)}
.card{
  background:#12172a;
  border:1px solid rgba(255,255,255,.08);
  border-radius:16px;
  padding:18px;
  box-shadow:0 20px 60px rgba(0,0,0,.35);

  max-width: 445px;   /* latime mai mica */
  margin: 0 auto;     /* centrare orizontala */
}
.row{display:flex;gap:12px;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;background:#6d5efc;color:#fff;width:180px;height:44px;border-radius:12px;font-weight:700;font-size:14px;line-height:1;text-decoration:none;border:none;cursor:pointer;box-sizing:border-box}
.btn.secondary{background:rgba(255,255,255,.10);border:1px solid rgba(255,255,255,.10)}
.btn.danger{background:#b91c1c}
label{display:block;font-size:12px;opacity:.85;margin-bottom:6px}
input{width:100%;border-radius:12px;border:1px solid rgba(255,255,255,.14);
      background:#0e1630;color:#e8ecff;padding:12px}
.muted{opacity:.8;font-size:13px}
.console{margin-top:14px;background:#0e1324;border:1px solid rgba(255,255,255,.08);
         border-radius:14px;padding:0;overflow:hidden}
.err{color:#ffb4b4}
.subcard{margin-top:14px;background:#0e1324;border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:14px}
.trow{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 10px;border-radius:12px}
.trow:nth-child(odd){background:rgba(255,255,255,.04)}
.tmeta{min-width:0}
.tmeta .ttl{font-weight:800;font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.tmeta .sub{opacity:.8;font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.xbtn{width:42px;height:34px;border-radius:10px;border:1px solid rgba(255,255,255,.10);background:#b91c1c;color:#fff;font-weight:900;cursor:pointer}
.xbtn:hover{filter:brightness(1.05)}
.list{margin-top:14px;background:#0e1324;border:1px solid rgba(255,255,255,.08);border-radius:14px;overflow:hidden}
.listHead{display:flex;justify-content:space-between;align-items:center;padding:12px 14px;border-bottom:1px solid rgba(255,255,255,.08)}
.listRow{display:flex;gap:10px;align-items:center;justify-content:space-between;padding:10px 14px;border-bottom:1px solid rgba(255,255,255,.06)}
.listRow:last-child{border-bottom:none}
.pill{font-size:12px;padding:6px 10px;border-radius:999px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.08);white-space:nowrap}
.xbtn{width:34px;height:34px;border-radius:10px;border:1px solid rgba(255,255,255,.12);background:#b91c1c;color:#fff;font-weight:900;cursor:pointer}
.mono{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12px;opacity:.85}
</style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <h1 style="margin:0;font-size:22px"><?=h(APP_NAME)?> 🇷🇴</h1>
    <div class="badge">Panou administrare</div>
  </div>

  <div class="card">
  <?php if (!admin_logged_in()): ?>

    <div style="font-size:16px;font-weight:800">Autentificare</div>
    <div class="muted" style="margin-top:6px">Aceasta pagină este destinată strict adminstratorului.</div>
    
    <form method="post" style="margin-top:12px;max-width:420px">
      <label>Utilizator</label>
      <input name="user" autocomplete="username">

      <label style="margin-top:10px">Parolă</label>
      <input type="password" name="pass" autocomplete="current-password">

      <button class="btn" style="margin-top:12px">Conectare</button>
      <a class="btn secondary" href="index.php" style="margin-top:12px">  ↩ Înapoi</a>
      <?php if ($err): ?>
        <div class="err" style="margin-top:10px"><?=h($err)?></div>
      <?php endif; ?>
    </form>

  <?php else: ?>

    <div class="row" style="justify-content:space-between;align-items:center">
      <div>
        <div style="font-size:16px;font-weight:900">Comenzi administrative</div>
        
      </div>
      <form method="post">
        <button class="btn secondary" name="logout" value="1">Deconectare</button>
      </form>
    </div>

    <!-- BUTOANE -->
    <div class="row" style="margin-top:14px">
      <!-- CRON -->
      <form method="get"
            action="https://cript.rf.gd/rotransfer/cron_cleanup.php"
            target="consoleFrame"
            style="margin:0">
        <input type="hidden" name="key" value="neuro1981sergiu03">
        <button class="btn">✓ Ștergere Transferuri Expirate</button>
      </form>

      <!-- NUKE -->
      <form method="get"
            action="https://cript.rf.gd/rotransfer/clear.php"
            target="consoleFrame"
            style="margin:0"
            onsubmit="return confirm('Sunteți sigur că doriți resetare toatală?')">
        <input type="hidden" name="key" value="neuro1981sergiu03">
        <button class="btn danger">☠ Resetare totală</button>
      </form>

      <!-- LOG EMAIL -->
      <a class="btn" href="log.php" target="_blank" style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center;">Log Email</a>

      <!-- BACK -->
      <a class="btn secondary" href="index.php">↩ Înapoi</a>
    </div>

    <!-- CONSOLA -->
    <div class="console">
      <iframe name="consoleFrame"
              style="width:100%;height:220px;border:0;background:#0e1324;color:#e6e8ee">
      </iframe>
    </div>

    <div class="muted" style="margin-top:10px">
      * Output-ul apare în consolă. Resetarea totală șterge tot din <b>transfers</b>, <b>tmp</b> și <b>DB</b>.
    </div>

    <!-- LISTA TRANSFERURI -->
    <div class="list" style="margin-top:14px">
      <div class="listHead">
        <div style="font-weight:900">Lista tuturor transferurilor</div>
        <div class="muted">Apasă X ca să-l ștergi complet</div>
      </div>

      <?php if (empty($transfers)): ?>
        <div class="muted" style="padding:12px 14px">Nu există transferuri în DB.</div>
      <?php else: ?>
        <?php foreach ($transfers as $t): ?>
          <div class="listRow">
            <div style="min-width:0;flex:1">
              <div style="font-weight:800;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                <?=h($t['title'] ?: 'Transfer')?> | <span class="muted" style="font-weight:600">Efectuat de: <?=h($t['user_email'] ?: 'Guest')?></span>
              </div>
              <div class="mono" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                id: <?=h($t['id'])?> · token: <?=h($t['token'])?>
              </div>
            </div>

            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;justify-content:flex-end">
              <span class="pill"><?=h(bytes_human((int)$t['total_bytes']))?></span>
              <span class="pill">dl: <?= (int)$t['downloads_count'] ?></span>
              <span class="pill">exp: <?=h(expires_in_text((int)$t['expires_at']))?></span>

              <button class="xbtn" title="Șterge" type="button"
                      data-transfer-id="<?=h($t['id'])?>"
                      onclick="deleteTransfer(event,this);">X</button>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <script>
    function escapeHtml(s){
      return (s||'').replace(/[&<>"']/g, function(c){
        return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[c]);
      });
    }
    function deleteTransfer(ev, btn){
      if(ev){ ev.preventDefault(); ev.stopPropagation(); }
      (async ()=>{
      const tid = btn.getAttribute('data-transfer-id') || '';
      if(!tid) return false;
      if(!confirm('Stergi DEFINITIV acest transfer?')) return false;

      btn.disabled = true;
      const row = btn.closest('.listRow');
      if(row) row.style.opacity = '0.6';

      try{
        const body = new URLSearchParams();
        body.set('action','delete_transfer');
        body.set('transfer_id', tid);

        const resp = await fetch('admin_actions.php', {
          method:'POST',
          headers:{'Content-Type':'application/x-www-form-urlencoded; charset=utf-8','X-Requested-With':'fetch'},
          body: body.toString(),
          credentials:'same-origin'
        });
        const text = await resp.text();

        const iframe = document.querySelector('iframe[name="consoleFrame"]');
        if(iframe){
          iframe.srcdoc = '<!doctype html><meta charset="utf-8"><pre style="margin:0;padding:12px;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12px;line-height:1.35;color:#ffffff;background:#0e1324">'
  + escapeHtml(text) + '</pre>';
        }

        if(resp.ok){
          if(row) row.remove();
        } else {
          btn.disabled = false;
          if(row) row.style.opacity = '1';
        }
      }catch(e){
        const iframe = document.querySelector('iframe[name="consoleFrame"]');
        if(iframe){
          iframe.srcdoc = '<!doctype html><meta charset="utf-8"><pre style="margin:0;padding:12px;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12px;line-height:1.35;color:#ffb4b4;background:#0e1324">'
            + escapeHtml(String(e)) + '</pre>';
        }
        btn.disabled = false;
        if(row) row.style.opacity = '1';
      }
      })();
      return false;
    }
    </script>

  <?php endif; ?>
  </div>
</div>
</body>
</html>