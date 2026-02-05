<?php
require_once __DIR__ . '/util.php';

function rrmdir_contents(string $dir): array {
    $deletedFiles = 0;
    $deletedDirs  = 0;

    if (!is_dir($dir)) return [$deletedFiles, $deletedDirs];

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($it as $f) {
        if ($f->isDir()) {
            if (@rmdir($f->getPathname())) $deletedDirs++;
        } else {
            if (@unlink($f->getPathname())) $deletedFiles++;
        }
    }
    return [$deletedFiles, $deletedDirs];
}

/**
 * Sterge recursiv un director (inclusiv directorul). Silently ignores errors.
 */
function rrmdir_dir(string $dir): void {
    if (!is_dir($dir)) return;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $f) {
        $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
    }
    @rmdir($dir);
}

/**
 * Sterge complet un transfer (fisier(e) + tmp + DB).
 * Returneaza: ['deleted'=>bool]
 */
function core_delete_transfer(string $transferId): array {
    ensure_dirs();
    $pdo = db();

    // Verifica existenta (ca sa nu raporteze "OK" la ceva inexistent)
    $st = $pdo->prepare("SELECT id FROM transfers WHERE id = :id LIMIT 1");
    $st->execute([':id' => $transferId]);
    $exists = (bool)$st->fetch();
    if (!$exists) return ['deleted' => false];

    // transfers/<id>
    rrmdir_dir(TRANSFERS_PATH . '/' . $transferId);

    // tmp/<id>
    rrmdir_dir(TMP_PATH . '/' . $transferId);

    // tmp/zip_<id>_*.zip (download_zip.php)
    foreach (glob(TMP_PATH . '/zip_' . $transferId . '_*.zip') ?: [] as $zip) {
        @unlink($zip);
    }

    // DB
    $pdo->prepare("DELETE FROM download_logs WHERE transfer_id=:tid")->execute([':tid'=>$transferId]);
    $pdo->prepare("DELETE FROM files WHERE transfer_id=:tid")->execute([':tid'=>$transferId]);
    $pdo->prepare("DELETE FROM transfers WHERE id=:tid")->execute([':tid'=>$transferId]);

    return ['deleted' => true];
}

/**
 * Cleanup: sterge transferurile expirate (din DB + fisiere)
 * Returneaza: ['deleted_transfers'=>int]
 */
function core_cleanup_expired(): array {
    ensure_dirs();
    $pdo = db();
    $now = now();

    $st = $pdo->prepare("SELECT id FROM transfers WHERE expires_at <= :now");
    $st->execute([':now'=>$now]);
    $expired = $st->fetchAll();

    $deleted = 0;
    foreach ($expired as $t) {
        $tid = $t['id'];

        $dir = TRANSFERS_PATH . '/' . $tid;
        if (is_dir($dir)) {
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($it as $f) { $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname()); }
            @rmdir($dir);
        }

        $tmp = TMP_PATH . '/' . $tid;
        if (is_dir($tmp)) {
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($tmp, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($it as $f) { $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname()); }
            @rmdir($tmp);
        }

        $pdo->prepare("DELETE FROM download_logs WHERE transfer_id=:tid")->execute([':tid'=>$tid]);
        $pdo->prepare("DELETE FROM files WHERE transfer_id=:tid")->execute([':tid'=>$tid]);
        $pdo->prepare("DELETE FROM transfers WHERE id=:tid")->execute([':tid'=>$tid]);
        $deleted++;
    }

    return ['deleted_transfers' => $deleted];
}

/**
 * NUKE: sterge TOT (transfers + tmp) + goleste DB
 * Returneaza: stats
 */
function core_nuke_all(): array {
    ensure_dirs();

    [$tf, $td] = rrmdir_contents(TRANSFERS_PATH);
    [$pf, $pd] = rrmdir_contents(TMP_PATH);

    $pdo = db();
    $pdo->exec("DELETE FROM download_logs;");
    $pdo->exec("DELETE FROM files;");
    $pdo->exec("DELETE FROM transfers;");
    $pdo->exec("VACUUM;");

    return [
        'transfers_deleted_files' => $tf,
        'transfers_deleted_dirs'  => $td,
        'tmp_deleted_files'       => $pf,
        'tmp_deleted_dirs'        => $pd,
    ];
}