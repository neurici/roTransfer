<?php
require_once __DIR__ . '/util.php';
require_once __DIR__ . '/smtp.php';

$raw = file_get_contents('php://input');
$in = json_decode($raw, true);
if (!is_array($in)) json_out(['error'=>'JSON invalid'], 400);

$transferId = (string)($in['transfer_id'] ?? '');
if ($transferId==='') json_out(['error'=>'params'], 400);

$pdo = db();
$st = $pdo->prepare("SELECT * FROM transfers WHERE id=:id LIMIT 1");
$st->execute([':id'=>$transferId]);
$t = $st->fetch();
if (!$t) json_out(['error'=>'transfer not found'], 404);
if (is_expired($t)) json_out(['error'=>'expired'], 410);

$files = list_files($transferId);
if (!$files) json_out(['error'=>'no files'], 400);

$link = APP_BASE_URL . '/t.php?token=' . urlencode($t['token']);

if (!empty($t['recipient_email']) || !empty($t['recipient_email2']) || !empty($t['recipient_email3'])) {
    $recipients = [];
    foreach ([(string)($t['recipient_email'] ?? ''), (string)($t['recipient_email2'] ?? ''), (string)($t['recipient_email3'] ?? '')] as $em) {
        $em = trim($em);
        if ($em !== '' && !in_array($em, $recipients, true)) $recipients[] = $em;
    }

    // Subiect (fara CR/LF)
    $title = $t['title'] ?: 'Ați primit un transfer de fișiere';
    $title = strip_tags((string)$title);
    $title = preg_replace("/[\r\n]+/", " ", $title);
    $title = trim($title);
    if ($title === '') $title = 'Ați primit un transfer de fișiere';
    if (function_exists('mb_strlen') && mb_strlen($title, 'UTF-8') > 120) $title = mb_substr($title, 0, 120, 'UTF-8');

    // Mesajul expeditorului (optional)
    $userMsg = trim((string)$t['message']);
    $msgHtml = $userMsg !== ''
        ? "<div style='margin-top:12px;padding:12px 14px;border:1px solid #e5e7eb;background:#f9fafb;border-radius:12px;color:#111827'>
             <div style='font-size:12px;letter-spacing:.06em;color:#6b7280;font-weight:700;margin-bottom:6px'>MESAJ</div>
             <div style='white-space:pre-wrap;line-height:1.45'>".nl2br(h($userMsg))."</div>
           </div>"
        : "";

    // Expirare
    $exp = date('d-m-Y H:i', (int)$t['expires_at']);
    $expIn = expires_in_text((int)$t['expires_at']);

    // Parola
    $pwNote = (!empty($t['password_hash']))
        ? "<div style='margin-top:12px;padding:12px 14px;border:1px solid #fde68a;background:#fffbeb;border-radius:12px;color:#92400e'>
             <b>Atenție:</b> Transferul este protejat cu parolă. Veți fi întrebat(ă) parola înainte de descărcare.
           </div>"
        : "";

    // Lista fișiere + total
    $filesHtml = "";
    $totalBytes = 0;
    foreach ($files as $f) {
        $name = h($f['original_name']);
        $size = (int)$f['size'];
        $totalBytes += $size;
        $mb = number_format($size / 1024 / 1024, 2);
        $filesHtml .= "
          <tr>
            <td style='padding:10px 12px;border-bottom:1px solid #eef2ff;color:#111827'>{$name}</td>
            <td style='padding:10px 12px;border-bottom:1px solid #eef2ff;color:#6b7280;white-space:nowrap;text-align:right'>{$mb} MB</td>
          </tr>";
    }
    $totalMb = number_format($totalBytes / 1024 / 1024, 2);

    // HTML email (profi)
    $html = "
  <div style='background:#f5f7fb;padding:26px 10px'>
    <div style='display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;line-height:0;mso-hide:all;'>
      Ați primit un transfer de fișiere. Deschideți e-mailul pentru detalii.
    </div>

  <div style='max-width:720px;margin:0 auto;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid rgba(255,255,255,.08)'>

    <div style='padding:18px 20px;background:linear-gradient(135deg,#6d5efc,#4c3cff);color:#ffffff'>
      <div style='font-family:system-ui,Segoe UI,Roboto,Arial;font-weight:900;font-size:18px;letter-spacing:.02em'>".h(APP_NAME)."</div>
      <div style='opacity:.9;font-family:system-ui,Segoe UI,Roboto,Arial;font-size:13px;margin-top:4px'>Transfer securizat de fișiere</div>
    </div>

    <div style='padding:18px 20px;font-family:system-ui,Segoe UI,Roboto,Arial;color:#111827'>
      <h2 style='margin:0 0 10px 0;font-size:18px'>Ați primit un transfer de fișiere</h2>

      <div style='color:#374151;font-size:14px;line-height:1.5'>
        Puteți descărca fișierele folosind butonul de mai jos.
      </div>

      {$pwNote}
      {$msgHtml}

      <div style='margin:16px 0'>
        <a href='{$link}' style='display:inline-block;background:#6d5efc;color:#ffffff;text-decoration:none;padding:12px 16px;border-radius:12px;font-weight:800'>
          Accesați transferul
        </a>
      </div>

      <div style='margin-top:10px;padding:12px 14px;border:1px solid #e5e7eb;background:#f8fafc;border-radius:12px'>
        <div style='font-size:13px;color:#111827'>
          <b>Expiră la:</b> {$exp}
          <span style='color:#6b7280'> - Aveți la dispoziție <b>{$expIn}</b> pentru a accesa transferul.</span>
        </div>
      </div>

      <div style='margin-top:14px'>
        <div style='font-size:12px;letter-spacing:.06em;color:#6b7280;font-weight:800;margin-bottom:8px'>
          FIȘIERE (".count($files)." total • {$totalMb} MB)
        </div>

        <table cellpadding='0' cellspacing='0' style='width:100%;border-collapse:collapse;border:1px solid #eef2ff;border-radius:12px;overflow:hidden'>
          <thead>
            <tr style='background:#f5f7ff'>
              <th style='text-align:left;padding:10px 12px;font-size:12px;color:#374151'>Nume fișier</th>
              <th style='text-align:right;padding:10px 12px;font-size:12px;color:#374151'>Dimensiune</th>
            </tr>
          </thead>
          <tbody>
            {$filesHtml}
          </tbody>
        </table>
      </div>

      <div style='margin-top:14px;font-size:12px;color:#6b7280'>
        Dacă butonul nu funcționează, copiați link-ul acesta în browser:
        <div style='margin-top:6px;word-break:break-all'>
          <a href='{$link}' style='font-family:ui-monospace,Menlo,Consolas,monospace;font-size:12px;color:#2563eb;text-decoration:none'>
            {$link}
          </a>
        </div>
      </div>
    </div>

    <div style='padding:14px 20px;background:#0e1324;color:#aab3d0;font-family:system-ui,Segoe UI,Roboto,Arial;font-size:12px'>
      Acest mesaj a fost trimis automat de ".h(APP_NAME).". Dacă nu ați solicitat acest transfer, puteți ignora e-mailul.
    </div>

  </div>
</div>";

    // Text body scurt (pentru preview curat)
    $textBody =
        APP_NAME . " - Transfer de fișiere\n\n" .
        "Link: " . $link . "\n" .
        "Expiră la: " . $exp . " (expiră în: " . $expIn . ")\n" .
        (!empty($t['password_hash']) ? "ATENȚIE: Transferul este protejat cu parolă.\n" : "") .
        ($userMsg !== '' ? "\nMesaj:\n" . $userMsg . "\n" : "");

    foreach ($recipients as $to) {
        try {
            smtp_send_mail($to, $title, $html, $textBody);
            // log DOAR emailul catre destinatar (fara copia catre initiator)
            log_email_transfer((string)$t['sender_email'], (string)$to, (string)$transferId);
        }
        catch (Exception $e) {
            ensure_dirs();
            file_put_contents(LOG_PATH.'/smtp_errors.log', date('c')." " .$e->getMessage()."\n", FILE_APPEND);
        }
    }


// Copie către utilizatorul autentificat (dacă există sesiune)
$u = current_user();
if ($u) {
    // Username-ul este adresa de e-mail; folosim ce avem disponibil in sesiune
    start_session();
    $sess = $_SESSION[user_session_key()] ?? [];
    $copyTo = trim((string)($sess['email'] ?? $sess['username'] ?? ($u['email'] ?? $u['username'] ?? '')));

    // Evită duplicatele (dacă expeditorul e și destinatar)
    $dupe = false;
    if (isset($recipients) && is_array($recipients) && in_array($copyTo, $recipients, true)) $dupe = true;

    if (!$dupe && $copyTo !== '') {
        // Subiect: Copie Transfer: TITLU sau ID
        $copyName = trim((string)($t['title'] ?? ''));
        if ($copyName === '') $copyName = (string)$transferId;

        $copyName = strip_tags($copyName);
        $copyName = preg_replace("/[\r\n]+/", " ", $copyName);
        $copyName = trim($copyName);
        if ($copyName === '') $copyName = (string)$transferId;
        if (function_exists('mb_strlen') && mb_strlen($copyName, 'UTF-8') > 120) $copyName = mb_substr($copyName, 0, 120, 'UTF-8');

        $copySubject = "Copie Transfer: " . $copyName;

        // Mesajul expeditorului (optional)
        $userMsg = trim((string)($t['message'] ?? ''));
        $msgHtml = $userMsg !== ''
            ? "<div style='margin-top:12px;padding:12px 14px;border:1px solid #e5e7eb;background:#f9fafb;border-radius:12px;color:#111827'>
                 <div style='font-size:12px;letter-spacing:.06em;color:#6b7280;font-weight:700;margin-bottom:6px'>MESAJ</div>
                 <div style='white-space:pre-wrap;line-height:1.45'>".nl2br(h($userMsg))."</div>
               </div>"
            : "";

        // Expirare
        $exp = date('d-m-Y H:i', (int)$t['expires_at']);
        $expIn = expires_in_text((int)$t['expires_at']);

        // Parola
        $pwNote = (!empty($t['password_hash']))
            ? "<div style='margin-top:12px;padding:12px 14px;border:1px solid #fde68a;background:#fffbeb;border-radius:12px;color:#92400e'>
                 <b>Atenție:</b> Transferul este protejat cu parolă. Veți fi întrebat(ă) parola înainte de descărcare.
               </div>"
            : "";

        // Lista fișiere + total
        $filesHtml = "";
        $totalBytes = 0;
        foreach ($files as $f) {
            $name = h($f['original_name']);
            $size = (int)$f['size'];
            $totalBytes += $size;
            $mb = number_format($size / 1024 / 1024, 2);
            $filesHtml .= "
              <tr>
                <td style='padding:10px 12px;border-bottom:1px solid #eef2ff;color:#111827'>{$name}</td>
                <td style='padding:10px 12px;border-bottom:1px solid #eef2ff;color:#6b7280;white-space:nowrap;text-align:right'>{$mb} MB</td>
              </tr>";
        }
        $totalMb = number_format($totalBytes / 1024 / 1024, 2);

        $copyHtml = "
      <div style='background:#f5f7fb;padding:26px 10px'>
        <div style='display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;line-height:0;mso-hide:all;'>
          Copie a transferului efectuat.
        </div>

      <div style='max-width:720px;margin:0 auto;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid rgba(255,255,255,.08)'>

        <div style='padding:18px 20px;background:linear-gradient(135deg,#6d5efc,#4c3cff);color:#ffffff'>
          <div style='font-family:system-ui,Segoe UI,Roboto,Arial;font-weight:900;font-size:18px;letter-spacing:.02em'>".h(APP_NAME)."</div>
          <div style='opacity:.9;font-family:system-ui,Segoe UI,Roboto,Arial;font-size:13px;margin-top:4px'>Copie transfer trimis</div>
        </div>

        <div style='padding:18px 20px;font-family:system-ui,Segoe UI,Roboto,Arial;color:#111827'>
          <h2 style='margin:0 0 10px 0;font-size:18px'>Copie a transferului efectuat</h2>

          <div style='color:#374151;font-size:14px;line-height:1.5'>
            Mai jos ai link-ul transferului (pentru evidență / retransmitere).
          </div>

          {$pwNote}
          {$msgHtml}

          <div style='margin:16px 0'>
            <a href='{$link}' style='display:inline-block;background:#6d5efc;color:#ffffff;text-decoration:none;padding:12px 16px;border-radius:12px;font-weight:800'>
              Deschide transferul
            </a>
          </div>

          <div style='margin-top:10px;padding:12px 14px;border:1px solid #e5e7eb;background:#f8fafc;border-radius:12px'>
            <div style='font-size:13px;color:#111827'>
              <b>Expiră la:</b> {$exp}
              <span style='color:#6b7280'> - Mai ai <b>{$expIn}</b> până expiră.</span>
            </div>
          </div>

          <div style='margin-top:14px'>
            <div style='font-size:12px;letter-spacing:.06em;color:#6b7280;font-weight:800;margin-bottom:8px'>
              FIȘIERE (".count($files)." total • {$totalMb} MB)
            </div>

            <table cellpadding='0' cellspacing='0' style='width:100%;border-collapse:collapse;border:1px solid #eef2ff;border-radius:12px;overflow:hidden'>
              <thead>
                <tr style='background:#f5f7ff'>
                  <th style='text-align:left;padding:10px 12px;font-size:12px;color:#374151'>Nume fișier</th>
                  <th style='text-align:right;padding:10px 12px;font-size:12px;color:#374151'>Dimensiune</th>
                </tr>
              </thead>
              <tbody>
                {$filesHtml}
              </tbody>
            </table>
          </div>

          <div style='margin-top:14px;font-size:12px;color:#6b7280'>
            Dacă butonul nu funcționează, copiați link-ul acesta în browser:
            <div style='margin-top:6px;word-break:break-all'>
              <a href='{$link}' style='font-family:ui-monospace,Menlo,Consolas,monospace;font-size:12px;color:#2563eb;text-decoration:none'>
                {$link}
              </a>
            </div>
          </div>
        </div>

        <div style='padding:14px 20px;background:#0e1324;color:#aab3d0;font-family:system-ui,Segoe UI,Roboto,Arial;font-size:12px'>
          Acest mesaj este o copie automată pentru transferul creat pe ".h(APP_NAME).".
        </div>

      </div>
    </div>";

        $copyText =
            APP_NAME . " - Copie transfer

" .
            "Link: " . $link . "\n" .
            "Expiră la: " . $exp . " (expiră în: " . $expIn . ")\n" .
            (!empty($t['password_hash']) ? "ATENȚIE: Transferul este protejat cu parolă.\n" : "") .
            ($userMsg !== '' ? "\nMesaj:\n" . $userMsg . "\n" : "");

        try { smtp_send_mail($copyTo, $copySubject, $copyHtml, $copyText); }
        catch (Exception $e) {
            ensure_dirs();
            file_put_contents(LOG_PATH.'/smtp_errors.log', date('c')." " .$e->getMessage()."\n", FILE_APPEND);
        }
    }
}



}


json_out(['ok'=>true, 'link'=>$link]);

