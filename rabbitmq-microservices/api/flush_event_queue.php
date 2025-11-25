<?php
// CLI script: flush_event_queue.php
// Usage: php flush_event_queue.php
require __DIR__ . '/config.php';
require __DIR__ . '/helpers.php';

$file = __DIR__ . '/event_queue.jsonl';
if (!file_exists($file)) {
    echo "No queue file found.\n";
    exit(0);
}

$lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if (!$lines) {
    echo "Queue empty.\n";
    unlink($file);
    exit(0);
}

$out = [];
$changed = false;
foreach ($lines as $line) {
    $obj = json_decode($line, true);
    if (!is_array($obj) || !isset($obj['payload'])) continue;
    $attempts = (int)($obj['attempts'] ?? 0);
    $payload = $obj['payload'];

    // try publish
    $ok = publish_event($payload);
    if ($ok) {
        echo "Published queued event\n";
        $changed = true;
        // small pause to avoid hammering
        usleep(200000);
    } else {
        // increase attempts and keep
        $obj['attempts'] = $attempts + 1;
        $out[] = json_encode($obj);
    }
}

if (count($out) === 0) {
    unlink($file);
    echo "All queued events flushed.\n";
} else {
    file_put_contents($file, implode("\n", $out) . "\n", LOCK_EX);
    echo "Some events remain queued (" . count($out) . ").\n";
}

exit(0);
