<?php
require_once __DIR__ . '/util.php';
require_once __DIR__ . '/smtp.php';

start_session();
$pdo = db();

$next = (string)($_GET['next'] ?? 'user.php');
if ($next === '') $next = 'user.php';

$msg = '';
$err = '';
$active = (string)($_GET['tab'] ?? 'login');

function send_welcome_email(string $toEmail, string $plainPw): void {
    $loginUrl = APP_BASE_URL . '/account.php';
    $subject = APP_NAME . ' • Bine ați venit!';

    $html = "
<div style='background:#f5f7fb;padding:26px 10px'>
  <div style='max-width:720px;margin:0 auto;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid rgba(255,255,255,.08)'>

    <div style='padding:18px 20px;background:linear-gradient(135deg,#6d5efc,#4c3cff);color:#ffffff'>
      <div style='font-family:system-ui,Segoe UI,Roboto,Arial;font-weight:900;font-size:18px;letter-spacing:.02em'>".h(APP_NAME)."</div>
      <div style='opacity:.9;font-family:system-ui,Segoe UI,Roboto,Arial;font-size:13px;margin-top:4px'>Cont nou creat</div>
    </div>

    <div style='padding:18px 20px;font-family:system-ui,Segoe UI,Roboto,Arial;color:#111827'>
      <h2 style='margin:0 0 10px 0;font-size:18px'>Bun venit!</h2>
      <div style='color:#374151;font-size:14px;line-height:1.5'>
        Contul dvs. a fost creat cu succes. Mai jos găsiți datele de autentificare.
      </div>

      <div style='margin-top:14px;padding:12px 14px;border:1px solid #e5e7eb;background:#f8fafc;border-radius:12px'>
        <div style='font-size:12px;letter-spacing:.06em;color:#6b7280;font-weight:800;margin-bottom:8px'>DATE AUTENTIFICARE</div>
        <div style='font-size:14px;color:#111827;line-height:1.7'>
          <b>Username (E-mail):</b> ".h($toEmail)."<br>
          <b>Parolă:</b> <span style='font-family:ui-monospace,Menlo,Consolas,monospace'>".h($plainPw)."</span>
        </div>
      </div>

      <div style='margin:16px 0'>
        <a href='{$loginUrl}' style='display:inline-block;background:#6d5efc;color:#ffffff;text-decoration:none;padding:12px 16px;border-radius:12px;font-weight:800'>
          Autentificare
        </a>
      </div>

      <div style='margin-top:14px;font-size:12px;color:#6b7280'>
        Dacă butonul nu funcționează, copiați link-ul acesta în browser:
        <div style='margin-top:6px;word-break:break-all'>
          <a href='{$loginUrl}' style='font-family:ui-monospace,Menlo,Consolas,monospace;font-size:12px;color:#2563eb;text-decoration:none'>{$loginUrl}</a>
        </div>
      </div>
    </div>

    <div style='padding:14px 20px;background:#0e1324;color:#aab3d0;font-family:system-ui,Segoe UI,Roboto,Arial;font-size:12px'>
      Acest mesaj a fost trimis automat de ".h(APP_NAME).".
    </div>

  </div>
</div>";

    $text = APP_NAME . " - Bun venit!\n\n" .
            "Username (E-mail): {$toEmail}\n" .
            "Parola: {$plainPw}\n" .
            "Autentificare: {$loginUrl}\n";

    smtp_send_mail($toEmail, $subject, $html, $text);
}

function send_reset_email(string $toEmail, string $plainPw): void {
    $loginUrl = APP_BASE_URL . '/account.php';
    $subject = APP_NAME . ' • Resetare parolă';

    $html = "
<div style='background:#f5f7fb;padding:26px 10px'>
  <div style='max-width:720px;margin:0 auto;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid rgba(255,255,255,.08)'>

    <div style='padding:18px 20px;background:linear-gradient(135deg,#6d5efc,#4c3cff);color:#ffffff'>
      <div style='font-family:system-ui,Segoe UI,Roboto,Arial;font-weight:900;font-size:18px;letter-spacing:.02em'>".h(APP_NAME)."</div>
      <div style='opacity:.9;font-family:system-ui,Segoe UI,Roboto,Arial;font-size:13px;margin-top:4px'>Recuperare acces</div>
    </div>

    <div style='padding:18px 20px;font-family:system-ui,Segoe UI,Roboto,Arial;color:#111827'>
      <h2 style='margin:0 0 10px 0;font-size:18px'>Datele dvs. de autentificare</h2>
      <div style='color:#374151;font-size:14px;line-height:1.5'>
        Ați solicitat resetarea parolei. Mai jos găsiți datele de autentificare.
      </div>

      <div style='margin-top:14px;padding:12px 14px;border:1px solid #e5e7eb;background:#f8fafc;border-radius:12px'>
        <div style='font-size:12px;letter-spacing:.06em;color:#6b7280;font-weight:800;margin-bottom:8px'>DATE AUTENTIFICARE</div>
        <div style='font-size:14px;color:#111827;line-height:1.7'>
          <b>Username (E-mail):</b> ".h($toEmail)."<br>
          <b>Parolă:</b> <span style='font-family:ui-monospace,Menlo,Consolas,monospace'>".h($plainPw)."</span>
        </div>
      </div>

      <div style='margin:16px 0'>
        <a href='{$loginUrl}' style='display:inline-block;background:#6d5efc;color:#ffffff;text-decoration:none;padding:12px 16px;border-radius:12px;font-weight:800'>
          Autentificare
        </a>
      </div>

      <div style='margin-top:14px;font-size:12px;color:#6b7280'>
        Dacă nu ați solicitat resetarea parolei, puteți ignora e-mailul.
      </div>
    </div>

    <div style='padding:14px 20px;background:#0e1324;color:#aab3d0;font-family:system-ui,Segoe UI,Roboto,Arial;font-size:12px'>
      Acest mesaj a fost trimis automat de ".h(APP_NAME).".
    </div>

  </div>
</div>";

    $text = APP_NAME . " - Resetare parolă\n\n" .
            "Username (E-mail): {$toEmail}\n" .
            "Parola: {$plainPw}\n" .
            "Autentificare: {$loginUrl}\n";

    smtp_send_mail($toEmail, $subject, $html, $text);
}

// If already logged in, go to user page
if (current_user()) {
    header('Location: user.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'register') {
        $active = 'register';
        $email = trim((string)($_POST['email'] ?? ''));
        $pw = (string)($_POST['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $err = 'Adresa de e-mail nu este validă.';
        } elseif (strlen($pw) < 4) {
            $err = 'Parola este prea scurtă (minim 4 caractere).';
        } else {
            $st = $pdo->prepare('SELECT 1 FROM users WHERE email = :e LIMIT 1');
            $st->execute([':e' => $email]);
            if ($st->fetch()) {
                $err = 'Există deja un cont cu acest e-mail.';
            } else {
                $id = uuidv4();
                $hash = password_hash($pw, PASSWORD_DEFAULT);
                $pdo->prepare('INSERT INTO users(id,email,password_hash,password_plain,created_at) VALUES(:id,:e,:h,:p,:ts)')
                    ->execute([':id'=>$id, ':e'=>$email, ':h'=>$hash, ':p'=>$pw, ':ts'=>now()]);

                // Send welcome mail (best-effort)
                try { send_welcome_email($email, $pw); }
                catch (Exception $e) {
                    ensure_dirs();
                    file_put_contents(LOG_PATH.'/smtp_errors.log', date('c')." " .$e->getMessage()."\n", FILE_APPEND);
                }

                $msg = 'Cont creat cu succes! Verificați e-mailul pentru datele de autentificare.';
                $active = 'login';
            }
        }
    }

    if ($action === 'login') {
        $active = 'login';
        $email = trim((string)($_POST['email'] ?? ''));
        $pw = (string)($_POST['password'] ?? '');

        $st = $pdo->prepare('SELECT * FROM users WHERE email = :e LIMIT 1');
        $st->execute([':e' => $email]);
        $u = $st->fetch();
        if (!$u || !password_verify($pw, (string)$u['password_hash'])) {
            $err = 'Date de autentificare incorecte.';
        } else {
            $pdo->prepare('UPDATE users SET last_login_at = :ts WHERE id = :id')->execute([':ts'=>now(), ':id'=>$u['id']]);
            login_user($u);
            header('Location: ' . $next);
            exit;
        }
    }

    if ($action === 'reset') {
        $active = 'reset';
        $email = trim((string)($_POST['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $err = 'Adresa de e-mail nu este validă.';
        } else {
            $st = $pdo->prepare('SELECT * FROM users WHERE email = :e LIMIT 1');
            $st->execute([':e' => $email]);
            $u = $st->fetch();

            // Always show generic message
            $msg = 'Dacă există un cont cu acest e-mail, veți primi în câteva momente un mesaj cu datele de autentificare.';

            if ($u) {
                try { send_reset_email($email, (string)$u['password_plain']); }
                catch (Exception $e) {
                    ensure_dirs();
                    file_put_contents(LOG_PATH.'/smtp_errors.log', date('c')." " .$e->getMessage()."\n", FILE_APPEND);
                }
            }
        }
    }
}

?><!doctype html>
<html lang="ro"><head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title><?=h(APP_NAME)?> - Cont</title>
<style>
body{font-family:system-ui,Segoe UI,Roboto,Arial;background:#0b1020;color:#e8ecff;margin:0}
.wrap{max-width:900px;margin:0 auto;padding:28px}
.card{background:#111a33;border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:18px}
.row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
label{display:block;font-size:12px;opacity:.85;margin-bottom:6px}
input{width:100%;box-sizing:border-box;border-radius:12px;border:1px solid rgba(255,255,255,.14);background:#0e1630;color:#e8ecff;padding:12px}
.btn{background:#6d5efc;border:none;color:white;padding:12px 14px;border-radius:12px;cursor:pointer;font-weight:700}
.btn2{background:rgba(255,255,255,.10);border:1px solid rgba(255,255,255,.10);color:#e8ecff;padding:10px 12px;border-radius:12px;cursor:pointer;font-weight:700;text-decoration:none;display:inline-block}
.muted{opacity:.8;font-size:13px}
a{color:#9bd1ff}
.top{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px}
.badge{font-size:12px;padding:6px 10px;border-radius:999px;background:rgba(255,255,255,.08)}
.tabs{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px}
.tab{padding:8px 12px;border-radius:999px;background:rgba(255,255,255,.08);color:#e8ecff;text-decoration:none;font-weight:800;font-size:12px;opacity:.9}
.tab.active{background:#6d5efc;opacity:1}
.alert{margin:12px 0;padding:12px 14px;border-radius:12px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.06)}
.alert.err{border-color:rgba(239,68,68,.35);background:rgba(239,68,68,.10)}
.alert.ok{border-color:rgba(57,217,138,.35);background:rgba(57,217,138,.10)}
</style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <h1 style="margin:0;font-size:22px"><?=h(APP_NAME)?> 🇷🇴</h1>
    <a class="badge" href="index.php" style="text-decoration:none;color:inherit;font-weight:800">Înapoi</a>
  </div>

  <div class="tabs">
    <a class="tab <?= $active==='login'?'active':'' ?>" href="account.php?tab=login&next=<?=h(urlencode($next))?>">Autentificare</a>
    <a class="tab <?= $active==='register'?'active':'' ?>" href="account.php?tab=register&next=<?=h(urlencode($next))?>">Creare cont</a>
    <a class="tab <?= $active==='reset'?'active':'' ?>" href="account.php?tab=reset&next=<?=h(urlencode($next))?>">Resetare parolă</a>
  </div>

  <div class="card">

    <?php if ($err !== ''): ?>
      <div class="alert err"><?=h($err)?></div>
    <?php endif; ?>

    <?php if ($msg !== ''): ?>
      <div class="alert ok"><?=h($msg)?></div>
    <?php endif; ?>

    <?php if ($active === 'register'): ?>
      <h2 style="margin:0 0 10px 0;font-size:18px">Creare cont</h2>
      <div class="muted" style="margin-bottom:12px">E-mailul va fi folosit ca username.</div>
      <form method="post" autocomplete="off">
        <input type="hidden" name="action" value="register"/>
        <input type="hidden" name="next" value="<?=h($next)?>"/>
        <div class="row">
          <div>
            <label>Adresă e-mail</label>
            <input name="email" type="email" placeholder="nume@domeniu.ro" required />
          </div>
          <div>
            <label>Parola</label>
            <input name="password" type="text" placeholder="Parola" required />
          </div>
        </div>
        <div style="margin-top:12px">
          <button class="btn" type="submit">Creare cont</button>
        </div>
        <div class="muted" style="margin-top:10px">Veți primi un e-mail cu datele de autentificare.</div>
      </form>

    <?php elseif ($active === 'reset'): ?>
      <h2 style="margin:0 0 10px 0;font-size:18px">Resetare parolă</h2>
      <div class="muted" style="margin-bottom:12px">Introduceți adresa de e-mail, iar noi vă trimitem datele de autentificare.</div>
      <form method="post" autocomplete="off">
        <input type="hidden" name="action" value="reset"/>
        <input type="hidden" name="next" value="<?=h($next)?>"/>
        <div>
          <label>Adresă e-mail</label>
          <input name="email" type="email" placeholder="nume@domeniu.ro" required />
        </div>
        <div style="margin-top:12px">
          <button class="btn" type="submit">Resetare parolă</button>
        </div>
      </form>

    <?php else: ?>
      <h2 style="margin:0 0 10px 0;font-size:18px">Autentificare</h2>
      <div class="muted" style="margin-bottom:12px">Conectați-vă pentru a vedea transferurile efectuate.</div>
      <form method="post" autocomplete="off">
        <input type="hidden" name="action" value="login"/>
        <input type="hidden" name="next" value="<?=h($next)?>"/>
        <div class="row">
          <div>
            <label>Username (E-mail)</label>
            <input name="email" type="email" placeholder="nume@domeniu.ro" required />
          </div>
          <div>
            <label>Parola</label>
            <input name="password" type="password" placeholder="Parola" required />
          </div>
        </div>
        <div style="margin-top:12px">
          <button class="btn" type="submit">Autentificare</button>
          <a class="btn2" href="account.php?tab=reset&next=<?=h(urlencode($next))?>" style="margin-left:8px">Am uitat parola</a>
        </div>
      </form>
    <?php endif; ?>

  </div>
</div>
</body>
</html>
