<?php
require_once __DIR__ . '/util.php';

$raw = file_get_contents('php://input');
$in = json_decode($raw, true);
if (!is_array($in)) json_out(['error'=>'JSON invalid'], 400);

$files = $in['files'] ?? [];
if (!is_array($files) || count($files) < 1) json_out(['error'=>'Niciun fisier'], 400);
if (count($files) > MAX_FILES_PER_TRANSFER) json_out(['error'=>'Prea multe fisiere'], 400);

$total = 0;
foreach ($files as $f) {
    $sz = (int)($f['size'] ?? 0);
    if ($sz <= 0) json_out(['error'=>'Fisier invalid'], 400);
    $total += $sz;
}
if ($total > MAX_TRANSFER_BYTES) json_out(['error'=>'Depaseste limita 2GB'], 400);

$expireDays = (int)($in['expire_days'] ?? 7);
if ($expireDays < 1) $expireDays = 1;
if ($expireDays > 30) $expireDays = 30;

$title = trim((string)($in['title'] ?? ''));
$msg   = trim((string)($in['message'] ?? ''));
$to    = trim((string)($in['recipient_email'] ?? ''));
$to2   = trim((string)($in['recipient_email2'] ?? ''));
$to3   = trim((string)($in['recipient_email3'] ?? ''));

$pw = trim((string)($in['password'] ?? ''));
$pwHash = null;
if ($pw !== '') {
    if (strlen($pw) < 3) json_out(['error'=>'Parola prea scurta (minim 3 caractere)'], 400);
    $pwHash = password_hash($pw, PASSWORD_DEFAULT);
}

$transferId = uuidv4();
$token = token32();
$createdAt = now();
$expiresAt = $createdAt + $expireDays * 86400;

$u = current_user();
$userId = $u ? (string)$u['id'] : null;
$senderEmail = $u ? (string)$u['email'] : null;

$pdo = db();
$pdo->prepare("INSERT INTO transfers(id, token, title, message, sender_email, recipient_email, recipient_email2, recipient_email3, password_hash, expires_at, total_bytes, created_at, user_id)
               VALUES(:id,:token,:title,:msg,:se,:to,:to2,:to3,:ph,:exp,:tb,:ca,:uid)")
    ->execute([
        ':id'=>$transferId, ':token'=>$token, ':title'=>$title ?: null, ':msg'=>$msg ?: null,
        ':se'=>$senderEmail, ':to'=>$to ?: null, ':to2'=>$to2 ?: null, ':to3'=>$to3 ?: null, ':ph'=>$pwHash, ':exp'=>$expiresAt, ':tb'=>$total, ':ca'=>$createdAt,
        ':uid'=>$userId
    ]);

$uploadFiles = [];
for ($i=0; $i<count($files); $i++) {
    $f = $files[$i];
    $fileId = uuidv4();
    $orig = clean_filename((string)$f['name']);
    $size = (int)$f['size'];
    $mime = (string)($f['type'] ?? 'application/octet-stream');

    $pdo->prepare("INSERT INTO files(id, transfer_id, original_name, stored_name, size, mime, created_at)
                   VALUES(:id,:tid,:on,:sn,:sz,:mime,:ca)")
        ->execute([
            ':id'=>$fileId, ':tid'=>$transferId, ':on'=>$orig, ':sn'=>'pending',
            ':sz'=>$size, ':mime'=>$mime, ':ca'=>now()
        ]);

    $tmpDir = TMP_PATH . "/{$transferId}/{$fileId}";
    if (!is_dir($tmpDir)) mkdir($tmpDir, 0775, true);

    $uploadFiles[] = ['file_id'=>$fileId, 'name'=>$orig, 'size'=>$size];
}

json_out(['transfer_id'=>$transferId,'token'=>$token,'upload_files'=>$uploadFiles]);
