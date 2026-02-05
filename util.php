<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function ensure_dirs(): void {
    foreach ([STORAGE_PATH, TRANSFERS_PATH, TMP_PATH, LOG_PATH] as $d) {
        if (!is_dir($d)) @mkdir($d, 0775, true);
    }
}

function start_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            ini_set('session.cookie_secure', '1');
        }
        session_start();
    }
}

function json_out($data, int $code=200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function now(): int { return time(); }

function uuidv4(): string {
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function token32(): string { return bin2hex(random_bytes(16)); }
function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function render_error_page(string $message, int $code=400, string $title='Eroare'): void {
    http_response_code($code);
    header('Content-Type: text/html; charset=utf-8');

    $safeTitle = h($title);
    $safeMsg   = h($message);

    echo "<!doctype html><html lang=\"ro\"><head>" .
         "<meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">" .
         "<title>{$safeTitle} - " . h(APP_NAME) . "</title>" .
         "<style>
            body{font-family:system-ui,Segoe UI,Roboto,Arial;background:#0b1020;color:#e8ecff;margin:0}
            .wrap{max-width:900px;margin:0 auto;padding:28px}
            .card{background:#111a33;border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:18px}
            .err-card{border-color:rgba(255,91,91,.55)}
            .err{color:#ff5b5b}
            .btn{display:inline-block;background:#6d5efc;border:none;color:white;padding:12px 14px;border-radius:12px;cursor:pointer;font-weight:600;text-decoration:none}
            .btn-secondary{display:inline-block;background:rgba(255,255,255,.10);border:1px solid rgba(255,255,255,.12);color:#e8ecff;padding:12px 14px;border-radius:12px;cursor:pointer;font-weight:700;text-decoration:none}
            a{color:#9bd1ff}
            .top{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;gap:12px;flex-wrap:wrap}
            .badge{font-size:12px;padding:6px 10px;border-radius:999px;background:rgba(255,255,255,.08)}
         </style></head><body>";

    echo "<div class=\"wrap\">";
    echo "<div class=\"top\"><div class=\"badge\">" . h(APP_NAME) . "</div></div>";
    echo "<div class=\"card err-card\">";
    echo "<h2 class=\"err\" style=\"margin:0 0 10px\">🚫 {$safeTitle}</h2>";
    echo "<div class=\"err\" style=\"font-size:15px;line-height:1.5\">{$safeMsg}</div>";
    echo "<div style=\"margin-top:16px;display:flex;gap:10px;flex-wrap:wrap\">";
    echo "<a class=\"btn-secondary\" href=\"index.php\">Înapoi la pagina principală</a>";
    echo "</div>";
    echo "</div></div></body></html>";
    exit;
}


function clean_filename(string $name): string {
    $name = preg_replace('/[^\w\s\.\-\(\)\[\]]+/u', '_', $name);
    $name = preg_replace('/\s+/', ' ', $name);
    $name = trim($name);
    return $name === '' ? 'fisier' : $name;
}

function transfer_dir(string $transferId): string {
    ensure_dirs();
    $dir = TRANSFERS_PATH . '/' . $transferId;
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    return $dir;
}

function is_expired(array $t): bool { return (int)$t['expires_at'] <= now(); }

function require_transfer_by_token(string $token): array {
    $pdo = db();
    $st = $pdo->prepare("SELECT * FROM transfers WHERE token = :token LIMIT 1");
    $st->execute([':token' => $token]);
    $t = $st->fetch();
    if (!$t) { render_error_page("Transfer inexistent.", 404, "Eroare"); }
    if (is_expired($t)) { render_error_page("Link expirat.", 410, "Eroare"); }
    if ($t['max_downloads'] !== null && (int)$t['downloads_count'] >= (int)$t['max_downloads']) {
        render_error_page("Link expirat (limită descărcări atinsă).", 410, "Eroare");
    }
    return $t;
}

function list_files(string $transferId): array {
    $pdo = db();
    $st = $pdo->prepare("SELECT * FROM files WHERE transfer_id = :tid ORDER BY created_at ASC");
    $st->execute([':tid' => $transferId]);
    return $st->fetchAll();
}

function client_ip(): string {
    foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $k) {
        if (!empty($_SERVER[$k])) {
            $v = $_SERVER[$k];
            if ($k === 'HTTP_X_FORWARDED_FOR') $v = explode(',', $v)[0];
            return trim($v);
        }
    }
    return 'unknown';
}

function log_download(string $transferId, ?string $fileId, string $kind): void {
    ensure_dirs();
    $pdo = db();
    $st = $pdo->prepare("INSERT INTO download_logs(transfer_id, file_id, kind, ip, user_agent, created_at)
                         VALUES(:tid, :fid, :kind, :ip, :ua, :ts)");
    $st->execute([
        ':tid' => $transferId,
        ':fid' => $fileId,
        ':kind'=> $kind,
        ':ip'  => client_ip(),
        ':ua'  => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ':ts'  => now()
    ]);
}

function bump_downloads(string $transferId): void {
    $pdo = db();
    $pdo->prepare("UPDATE transfers SET downloads_count = downloads_count + 1 WHERE id = :id")->execute([':id'=>$transferId]);
}

function expires_in_text(int $expiresAt, ?int $nowTs = null): string {
    $nowTs = $nowTs ?? now();
    $diff = $expiresAt - $nowTs;
    if ($diff <= 0) return 'expirat';
    $days = intdiv($diff, 86400); $diff -= $days*86400;
    $hours = intdiv($diff, 3600); $diff -= $hours*3600;
    $mins = intdiv($diff, 60);
    return $days . ' ' . ($days === 1 ? 'zi' : 'zile') . ' ' .
           $hours . ' ' . ($hours === 1 ? 'oră' : 'ore') . ' ' .
           $mins . ' min';
}

// ====== AUTH PAROLA ======
function transfer_auth_key(string $token): string { return 'rt_ok_' . $token; }

function is_transfer_authed(string $token): bool {
    start_session();
    $k = transfer_auth_key($token);
    if (empty($_SESSION[$k])) return false;
    $ts = (int)$_SESSION[$k];
    if ($ts <= 0) return false;
    if (now() - $ts > TRANSFER_AUTH_TTL) { unset($_SESSION[$k]); return false; }
    return true;
}

function set_transfer_authed(string $token): void {
    start_session();
    $_SESSION[transfer_auth_key($token)] = now();
}

function require_transfer_password_ok(array $t): void {
    if (empty($t['password_hash'])) return;
    if (!is_transfer_authed((string)$t['token'])) {
        http_response_code(403);
        echo "Parolă necesară. Deschide pagina linkului și introdu parola.";
        exit;
    }
}

// ====== AUTH UTILIZATOR (conturi) ======
function user_session_key(): string { return 'rt_user'; }

function current_user(): ?array {
    start_session();
    if (empty($_SESSION[user_session_key()]) || !is_array($_SESSION[user_session_key()])) return null;
    $u = $_SESSION[user_session_key()];
    if (empty($u['id']) || empty($u['email'])) return null;
    return ['id'=>(string)$u['id'], 'email'=>(string)$u['email']];
}

function login_user(array $userRow): void {
    start_session();
    $_SESSION[user_session_key()] = ['id'=>(string)$userRow['id'], 'email'=>(string)$userRow['email']];
}

function logout_user(): void {
    start_session();
    unset($_SESSION[user_session_key()]);
}

function require_login(): array {
    $u = current_user();
    if (!$u) {
        $to = $_SERVER['REQUEST_URI'] ?? 'index.php';
        header('Location: account.php?next=' . urlencode($to));
        exit;
    }
    return $u;
}


// Log DOAR pentru emailurile trimise catre destinatar (fara copia catre initiator)
function log_email_transfer(string $initiator, string $recipient, string $transferId): void {
    ensure_dirs();
    $logFile = LOG_PATH . '/mail.log';

    $transferPathReal = realpath(TRANSFERS_PATH . '/' . $transferId);
    if ($transferPathReal === false) {
        $transferPathReal = TRANSFERS_PATH . '/' . $transferId;
    }

    $line = sprintf(
        "[%s] initiator=%s recipient=%s transfer_id=%s path=%s\n",
        date('d-m-Y H:i:s'),
        $initiator,
        $recipient,
        $transferId,
        $transferPathReal
    );

    if (@file_put_contents($logFile, $line, FILE_APPEND) === false) {
        error_log("roTransfer MAIL LOG FAIL: " . trim($line));
    }
}
