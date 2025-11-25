<?php
// Return counts and sample rows from event_queue and dead_letter for debugging/tests
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

// Simple admin guard: allow from localhost or when ADMIN_KEY matches
$adminKey = getenv('ADMIN_KEY') ?: '';
$provided = $_GET['admin_key'] ?? '';
if (!empty($adminKey) && $provided !== $adminKey) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

try {
    $res = [];

    $r = $db->query('SELECT COUNT(*) as c FROM event_queue');
    $row = $r->fetch_assoc();
    $res['event_queue_count'] = (int)$row['c'];

    $r = $db->query('SELECT COUNT(*) as c FROM dead_letter');
    $row = $r->fetch_assoc();
    $res['dead_letter_count'] = (int)$row['c'];

    // sample rows
    $res['event_queue_sample'] = [];
    $q = $db->query('SELECT id, payload, attempts, last_error, created_at FROM event_queue ORDER BY created_at DESC LIMIT 5');
    while ($r = $q->fetch_assoc()) {
        $r['payload'] = json_decode($r['payload'], true);
        $res['event_queue_sample'][] = $r;
    }

    $res['dead_letter_sample'] = [];
    $q = $db->query('SELECT id, payload, attempts, error, created_at FROM dead_letter ORDER BY created_at DESC LIMIT 5');
    while ($r = $q->fetch_assoc()) {
        $r['payload'] = json_decode($r['payload'], true);
        $res['dead_letter_sample'][] = $r;
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($res);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'db_error', 'message' => $e->getMessage()]);
    exit;
}
