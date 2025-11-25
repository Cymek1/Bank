<?php
// dead_letter.php — admin endpoint to list and retry dead-lettered events

require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

// Basic protection: require a query param ?admin=1 or set ADMIN_KEY env var
$adminKey = getenv('ADMIN_KEY') ?: null;
$isAdmin = false;
if ($adminKey) {
    $provided = $_GET['admin_key'] ?? '';
    $isAdmin = hash_equals($adminKey, $provided);
} else {
    // fallback: allow if caller provides ?admin=1 (only for local/dev)
    $isAdmin = (isset($_GET['admin']) && $_GET['admin'] === '1');
}

if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET') {
    // list recent dead letter entries
    $limit = intval($_GET['limit'] ?? 50);
    $limit = max(1, min(500, $limit));
    $q = $db->prepare('SELECT id, event_id, attempts, payload, last_error, failed_at FROM dead_letter ORDER BY failed_at DESC LIMIT ?');
    $q->bind_param('i', $limit);
    $q->execute();
    $res = $q->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $r['payload'] = json_decode($r['payload'], true);
        $rows[] = $r;
    }
    echo json_encode(['rows' => $rows]);
    exit;
}

if ($method === 'POST') {
    // retry a dead-letter entry by id: move back to event_queue
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data) || !isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'missing id']);
        exit;
    }
    $id = (int)$data['id'];

    // fetch the dead_letter row
    $s = $db->prepare('SELECT payload FROM dead_letter WHERE id = ?');
    $s->bind_param('i', $id);
    $s->execute();
    $r = $s->get_result()->fetch_assoc();
    if (!$r) {
        http_response_code(404);
        echo json_encode(['error' => 'not_found']);
        exit;
    }

    $payload = $r['payload'];

    // insert back to queue and delete from dead_letter in a transaction
    $db->begin_transaction();
    $ins = $db->prepare('INSERT INTO event_queue (payload, attempts, processed) VALUES (?, 0, 0)');
    $ins->bind_param('s', $payload);
    $ins->execute();
    $del = $db->prepare('DELETE FROM dead_letter WHERE id = ?');
    $del->bind_param('i', $id);
    $del->execute();
    $db->commit();

    echo json_encode(['status' => 'requeued']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'method_not_allowed']);
