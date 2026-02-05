<?php
require_once __DIR__ . '/util.php';
require_once __DIR__ . '/admin_core.php';

start_session();

if (empty($_SESSION['admin_ok'])) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "403 Forbidden - not logged in\n";
    exit;
}

$action = (string)($_POST['action'] ?? $_GET['action'] ?? '');
header('Content-Type: text/plain; charset=utf-8');

try {
    if ($action === 'cron') {
        $r = core_cleanup_expired();
        echo "OK cleanup\n";
        echo "deleted_transfers=".(int)$r['deleted_transfers']."\n";
        exit;
    }

    if ($action === 'nuke') {
        $r = core_nuke_all();
        echo "OK NUKE\n";
        echo "transfers: deleted_files=".(int)$r['transfers_deleted_files'].", deleted_dirs=".(int)$r['transfers_deleted_dirs']."\n";
        echo "tmp:       deleted_files=".(int)$r['tmp_deleted_files'].", deleted_dirs=".(int)$r['tmp_deleted_dirs']."\n";
        exit;
    }

    if ($action === 'delete_transfer') {
        $tid = (string)($_POST['transfer_id'] ?? $_GET['transfer_id'] ?? '');
        $tid = trim($tid);
        if ($tid === '') {
            http_response_code(400);
            echo "EROARE: Lipsește ID-ul transferului\n";
            exit;
        }
        // Basic allowlist: uuid-ish + safe chars
        if (!preg_match('/^[a-zA-Z0-9\-]{8,80}$/', $tid)) {
            http_response_code(400);
            echo "EROARE: ID-ul transferului este invalid\n";
            exit;
        }
        $r = core_delete_transfer($tid);
        if (empty($r['deleted'])) {
            http_response_code(404);
            echo "EROARE: Transferul nu există\n";
            exit;
        }
        echo "A fost șters transferul cu ";
        echo "id: ".$tid."\n";
        exit;
    }

    http_response_code(400);
    echo "400 Bad Request - unknown action\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "500 Failed\n";
    echo "ERROR: ".$e->getMessage()."\n";
    echo "AT: ".$e->getFile().":".$e->getLine()."\n";
}