<?php
// CLI worker: flush event_queue table by sending payloads to producer

require __DIR__ . '/../api/config.php';

// simple HTTP POST helper using curl
function http_post_json(string $url, array $data, int $timeout = 5): array {
    $ch = curl_init($url);
    $payload = json_encode($data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    $resp = curl_exec($ch);
    $errno = curl_errno($ch);
    $err = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['status' => $status, 'body' => $resp, 'errno' => $errno, 'error' => $err];
}

$producer = getenv('PRODUCER_URL') ?: ($PRODUCER_URL ?? 'http://producer:4000');
$url = rtrim($producer, '/') . '/api/send';

echo "Worker starting, producer endpoint: $url\n";

// run loop
// configuration
$MAX_ATTEMPTS = intval(getenv('WORKER_MAX_ATTEMPTS') ?: 5);
$LEASE_SECONDS = intval(getenv('WORKER_LEASE_SECONDS') ?: 30);
$WORKER_ID = getmypid() ?: uniqid('worker_', true);

// run loop with claim/lease pattern
while (true) {
    try {
        // try to atomically claim one row
        $db->begin_transaction();

        $claimStmt = $db->prepare(
            "UPDATE event_queue SET locked_by = ?, locked_at = NOW() 
             WHERE id = (
               SELECT id FROM (
                 SELECT id FROM event_queue 
                 WHERE processed = 0 AND (locked_at IS NULL OR locked_at < (NOW() - INTERVAL ? SECOND))
                 ORDER BY ts LIMIT 1
               ) _tmp
             )"
        );
        if ($claimStmt) {
            $claimStmt->bind_param('si', $WORKER_ID, $LEASE_SECONDS);
            $claimStmt->execute();
        }

        // select the claimed row
        $res = $db->query("SELECT id, attempts, payload FROM event_queue WHERE locked_by = '" . $db->real_escape_string($WORKER_ID) . "' ORDER BY locked_at DESC LIMIT 1 FOR UPDATE");

        if (!$res || $res->num_rows === 0) {
            $db->commit();
            // sleep briefly when no work
            sleep(2);
            continue;
        }

        $row = $res->fetch_assoc();
        $id = (int)$row['id'];
        $attempts = (int)$row['attempts'];
        $payload = json_decode($row['payload'], true);

        // attempt send
        $out = http_post_json($url, $payload, 5);
        if ($out['errno'] === 0 && ($out['status'] >= 200 && $out['status'] < 300)) {
            // mark processed and release lock
            $stmt = $db->prepare('DELETE FROM event_queue WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $db->commit();
            echo "Processed event $id\n";
        } else {
            $err = $out['error'] ?: ('HTTP ' . $out['status']);
            $attempts++;

            if ($attempts >= $MAX_ATTEMPTS) {
                // move to dead_letter
                $dlStmt = $db->prepare('INSERT INTO dead_letter (event_id, attempts, payload, last_error, failed_at) VALUES (?, ?, ?, ?, NOW())');
                $json = json_encode($payload);
                $dlStmt->bind_param('iiss', $id, $attempts, $json, $err);
                $dlStmt->execute();

                // remove from queue
                $del = $db->prepare('DELETE FROM event_queue WHERE id = ?');
                $del->bind_param('i', $id);
                $del->execute();
                $db->commit();
                echo "Moved event $id to dead_letter after $attempts attempts\n";
            } else {
                // update attempts and clear lock so it can be retried later
                $stmt = $db->prepare('UPDATE event_queue SET attempts = ?, last_error = ?, locked_by = NULL, locked_at = NULL WHERE id = ?');
                $stmt->bind_param('isi', $attempts, $err, $id);
                $stmt->execute();
                $db->commit();
                echo "Failed event $id attempt=$attempts err=$err\n";
            }
        }

    } catch (Throwable $e) {
        try { $db->rollback(); } catch (Exception $_) {}
        echo "Worker error: " . $e->getMessage() . "\n";
        sleep(2);
    }
}
