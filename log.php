<?php
require_once __DIR__ . '/util.php';

start_session();

// Protectie: doar admin
if (empty($_SESSION['admin_ok'])) {
    header('Location: admin.php');
    exit;
}

$logFile = LOG_PATH . '/mail.log';
$rows = [];

if (file_exists($logFile)) {
    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Format asteptat:
        // [YYYY-mm-dd HH:ii:ss] initiator=... recipient=... transfer_id=... path=...
        if (preg_match('/^\[(.*?)\]\s+initiator=(.*?)\s+recipient=(.*?)\s+transfer_id=(.*?)\s+path=(.*)$/', $line, $m)) {
            $rows[] = [
                'dt' => trim($m[1]),
                'initiator' => trim($m[2]),
                'recipient' => trim($m[3]),
                'transfer_id' => trim($m[4]),
                'path' => trim($m[5]),
                'raw' => $line
            ];
        } else {
            // fallback: pastreaza linia in caz ca formatul difera
            $rows[] = [
                'dt' => '',
                'initiator' => '',
                'recipient' => '',
                'transfer_id' => '',
                'path' => '',
                'raw' => $line
            ];
        }
    }
    // cele mai noi sus
    $rows = array_reverse($rows);
}

$q = trim((string)($_GET['q'] ?? ''));
if ($q !== '') {
    $rows = array_values(array_filter($rows, function($r) use ($q){
        $hay = strtolower($r['raw']);
        return strpos($hay, strtolower($q)) !== false;
    }));
}

?><!doctype html>
<html lang="ro"><head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title><?=h(APP_NAME)?> - Log Email</title>
<style>
body{font-family:system-ui,Segoe UI,Roboto,Arial;background:#0b1020;color:#e8ecff;margin:0}
.wrap{max-width:1100px;margin:0 auto;padding:28px}
.card{background:#111a33;border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:18px}
a{color:#9bd1ff}
.top{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;gap:12px;flex-wrap:wrap}
.badge{font-size:12px;padding:6px 10px;border-radius:999px;background:rgba(255,255,255,.08)}
.row{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
input{border-radius:12px;border:1px solid rgba(255,255,255,.14);background:#0e1630;color:#e8ecff;padding:10px 12px;min-width:260px}
.btn{background:#6d5efc;border:none;color:white;padding:10px 12px;border-radius:12px;cursor:pointer;font-weight:700}
.tablewrap{overflow:auto;border-radius:14px;border:1px solid rgba(255,255,255,.10)}
table{width:100%;border-collapse:collapse;min-width:980px}
thead th{position:sticky;top:0;background:#0e1630;color:#e8ecff;text-align:left;font-size:12px;letter-spacing:.04em;padding:12px;border-bottom:1px solid rgba(255,255,255,.12)}
tbody td{padding:12px;border-bottom:1px solid rgba(255,255,255,.08);vertical-align:top;font-size:13px}
tbody tr:hover{background:rgba(255,255,255,.04)}
.mono{font-family:ui-monospace,Menlo,Consolas,monospace}
.small{font-size:12px;opacity:.85}
.pill{display:inline-block;padding:3px 8px;border-radius:999px;background:rgba(57,217,138,.14);border:1px solid rgba(57,217,138,.35);color:#bff3da;font-size:12px}
.pill2{display:inline-block;padding:3px 8px;border-radius:999px;background:rgba(155,209,255,.12);border:1px solid rgba(155,209,255,.25);color:#d7f0ff;font-size:12px}
@media (max-width: 720px){
  .wrap{padding:16px}
  .card{padding:14px}
  input{min-width:180px;width:100%}
  .btn{width:100%}
}
</style></head>
<body><div class="wrap">
  <div class="top">
    <div class="row">
      <h1 style="margin:0;font-size:22px"><?=h(APP_NAME)?> — Log Email</h1>
      <span class="badge"><?=count($rows)?> înregistrări</span>
    </div>
    <div class="row">
      <a class="badge" href="admin.php" style="text-decoration:none;color:inherit;font-weight:700;">Înapoi la Administrare</a>
      <a class="badge" href="index.php" style="text-decoration:none;color:inherit;font-weight:700;">Acasă</a>
    </div>
  </div>

  <div class="card">
    <form method="get" class="row" style="margin:0 0 12px 0">
      <input name="q" value="<?=h($q)?>" placeholder="Caută (email / transfer_id / path)..." />
      <button class="btn" type="submit">Caută</button>
      <?php if ($q !== ''): ?>
        <a class="badge" href="log.php" style="text-decoration:none;color:inherit;font-weight:700;">Resetează</a>
      <?php endif; ?>
      <span class="small">Fișier: <span class="mono"><?=h($logFile)?></span></span>
    </form>

    <?php if (!file_exists($logFile)): ?>
      <div class="small">Nu există încă <span class="mono">mail.log</span>. Se creează automat după primul email trimis către destinatar.</div>
    <?php else: ?>
      <div class="tablewrap">
        <table>
          <thead>
            <tr>
              <th>Data/Ora</th>
              <th>Inițiator</th>
              <th>Destinatar</th>
              <th>Transfer ID</th>
              <th>Cale reală către transfer</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td class="mono"><?=h($r['dt'])?></td>
              <td><span class="pill2"><?=h($r['initiator'])?></span></td>
              <td><span class="pill"><?=h($r['recipient'])?></span></td>
              <td class="mono"><?=h($r['transfer_id'])?></td>
              <td class="mono small"><?=h($r['path'])?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?>
            <tr><td colspan="5" class="small">Nicio înregistrare (sau nu se potrivește căutarea).</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="small" style="margin-top:10px;opacity:.75">
        Notă: sunt afișate doar emailurile trimise către destinatar (fără copia către inițiator).
      </div>
    <?php endif; ?>
  </div>
</div></body></html>
