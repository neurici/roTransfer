<?php
require_once __DIR__ . '/util.php';
ensure_dirs();

$transferId = $_POST['transfer_id'] ?? '';
$fileId     = $_POST['file_id'] ?? '';
$chunkIndex = isset($_POST['chunk_index']) ? (int)$_POST['chunk_index'] : -1;
$totalChunks= isset($_POST['total_chunks']) ? (int)$_POST['total_chunks'] : -1;

if ($transferId==='' || $fileId==='' || $chunkIndex<0 || $totalChunks<1) { http_response_code(400); echo "bad params"; exit; }
if (!isset($_FILES['chunk'])) { http_response_code(400); echo "no chunk"; exit; }
if ($_FILES['chunk']['error'] !== UPLOAD_ERR_OK) { http_response_code(400); echo "upload err=".(int)$_FILES['chunk']['error']; exit; }

$pdo = db();
$st = $pdo->prepare("SELECT * FROM transfers WHERE id = :id LIMIT 1");
$st->execute([':id'=>$transferId]);
$t = $st->fetch();
if (!$t) { http_response_code(404); echo "no transfer"; exit; }
if (is_expired($t)) { http_response_code(410); echo "expired"; exit; }

$tmpDir = TMP_PATH . "/{$transferId}/{$fileId}";
if (!is_dir($tmpDir)) mkdir($tmpDir, 0775, true);
$dst = $tmpDir . "/" . str_pad((string)$chunkIndex, 8, '0', STR_PAD_LEFT) . ".part";
if (!move_uploaded_file($_FILES['chunk']['tmp_name'], $dst)) { http_response_code(500); echo "move failed"; exit; }

echo "ok";
