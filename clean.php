<?php
require_once __DIR__ . '/util.php';

header('Content-Type: text/plain; charset=utf-8');

// 1) Cheie obligatorie (doar HTTP)
$key = $_GET['key'] ?? '';
if (!defined('CRON_HTTP_KEY') || $key !== CRON_HTTP_KEY) {
    http_response_code(403);
    echo "403 Forbidden - cheie lipsa sau incorecta\n";
    exit;
}

// 2) Ruleaza cleanup si raporteaza clar ce s-a intamplat
try {
    ensure_dirs();

    $pdo = db();
    $now = now();

    $st = $pdo->prepare("SELECT id FROM transfers WHERE expires_at <= :now");
    $st->execute([':now' => $now]);
    $expired = $st->fetchAll();

    $deleted = 0;

    foreach ($expired as $t) {
        $tid = $t['id'];

        // sterge fisierele transferului
        $dir = TRANSFERS_PATH . '/' . $tid;
        if (is_dir($dir)) {
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($it as $f) {
                $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
            }
            @rmdir($dir);
        }

        // sterge tmp-urile
        $tmp = TMP_PATH . '/' . $tid;
        if (is_dir($tmp)) {
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($tmp, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($it as $f) {
                $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
            }
            @rmdir($tmp);
        }

        // sterge DB entries
        $pdo->prepare("DELETE FROM download_logs WHERE transfer_id=:tid")->execute([':tid' => $tid]);
        $pdo->prepare("DELETE FROM files WHERE transfer_id=:tid")->execute([':tid' => $tid]);
        $pdo->prepare("DELETE FROM transfers WHERE id=:tid")->execute([':tid' => $tid]);

        $deleted++;
    }

    echo "OK - deleted_transfers={$deleted}\n";
    exit;

} catch (Throwable $e) {
    // log local (ca sa vezi dupa pe hosting)
    ensure_dirs();
    $msg = date('c') . " ERROR: " . $e->getMessage() . "\n" .
           "FILE: " . $e->getFile() . ":" . $e->getLine() . "\n" .
           "TRACE:\n" . $e->getTraceAsString() . "\n\n";
    @file_put_contents(LOG_PATH . '/cron_http_errors.log', $msg, FILE_APPEND);

    http_response_code(500);
    echo "500 Cron failed\n";
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "AT: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit;
}