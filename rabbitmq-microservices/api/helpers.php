<?php
// BANK_API/api/helpers.php

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Authorization, Content-Type");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

if (!function_exists("str_starts_with")) {
    function str_starts_with(string $haystack, string $needle): bool {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }
}

function json_response($data, int $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

const JWT_SECRET = "jakis_bardzo_tajny_klucz_na_projekt"; // zmień na swój

function generate_token(array $payload, int $ttlSeconds = 3600): string {
    $header = base64_encode(json_encode(["alg" => "HS256", "typ" => "JWT"]));
    $payload["exp"] = time() + $ttlSeconds;
    $payloadB64 = base64_encode(json_encode($payload));
    $signature = hash_hmac("sha256", "$header.$payloadB64", JWT_SECRET, true);
    $signatureB64 = base64_encode($signature);
    return "$header.$payloadB64.$signatureB64";
}

function verify_token(string $token): ?array {
    $parts = explode(".", $token);
    if (count($parts) !== 3) return null;
    [$h, $p, $s] = $parts;

    $expected = base64_encode(hash_hmac("sha256", "$h.$p", JWT_SECRET, true));
    if (!hash_equals($expected, $s)) return null;

    $payload = json_decode(base64_decode($p), true);
    if (!is_array($payload)) return null;
    if (($payload["exp"] ?? 0) < time()) return null;

    return $payload;
}

/**
 * Zwraca zalogowanego użytkownika na podstawie Bearera.
 * Wynik: ['id' => ..., 'email' => ..., 'nazwa_uzytkownika' => ..., 'nr_konta' => ..., 'stan_konta' => ...]
 */
function require_auth(mysqli $db): array {
    $headers = getallheaders();
    $auth = $headers["Authorization"] ?? $headers["authorization"] ?? "";

    if (!str_starts_with($auth, "Bearer ")) {
        json_response(["error" => "NO_TOKEN"], 401);
    }

    $token = substr($auth, 7);
    $payload = verify_token($token);
    if (!$payload || !isset($payload["user_id"])) {
        json_response(["error" => "INVALID_TOKEN"], 401);
    }

    $id = (int)$payload["user_id"];
    $stmt = $db->prepare("SELECT id, email, nazwa_uzytkownika, nr_konta, stan_konta FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();

    if (!$user) {
        json_response(["error" => "USER_NOT_FOUND"], 401);
    }

    return $user;
}

/**
 * Tworzy alert / rekomendację dla użytkownika
 * - zakładamy tabelę:
 *   recommendations(id INT AI PK, user_id INT, message TEXT, created_at DATETIME, read_at DATETIME NULL)
 */
function add_alert(mysqli $db, int $userId, string $message): void {
    $stmt = $db->prepare("
        INSERT INTO recommendations (user_id, message, created_at)
        VALUES (?, ?, NOW())
    ");
    if (!$stmt) {
        error_log("add_alert prepare error: " . $db->error);
        return;
    }
    $stmt->bind_param("is", $userId, $message);
    if (!$stmt->execute()) {
        error_log("add_alert execute error: " . $stmt->error);
    }
}

/**
 * Publish an event to the producer microservice HTTP API (/api/send).
 * Returns true on success, false otherwise. Non-blocking for the caller.
 */
function publish_event(array $payload): bool {
    global $PRODUCER_URL;

    if (empty($PRODUCER_URL)) return false;

    $url = rtrim($PRODUCER_URL, '/') . '/api/send';
    $data = json_encode($payload);

    $opts = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\nContent-Length: " . strlen($data) . "\r\n",
            'content' => $data,
            'timeout' => 3,
        ]
    ];

    $context = stream_context_create($opts);
    try {
        $result = @file_get_contents($url, false, $context);
        if ($result === false) {
            // enqueue for retry (DB-backed preferred)
            enqueue_event($payload);
            return false;
        }
        return true;
    } catch (Throwable $e) {
        error_log('publish_event failed: ' . $e->getMessage());
        enqueue_event($payload);
        return false;
    }
}

function enqueue_event(array $payload): void {
    // Prefer DB-backed queue when available, fallback to file
    try {
        if (isset($GLOBALS['db']) && $GLOBALS['db'] instanceof mysqli) {
            $json = json_encode($payload);
            $stmt = $GLOBALS['db']->prepare("INSERT INTO event_queue (payload, attempts) VALUES (?, 0)");
            if ($stmt) {
                $stmt->bind_param('s', $json);
                $stmt->execute();
                return;
            }
        }
    } catch (Throwable $e) {
        error_log('enqueue_event DB failed: ' . $e->getMessage());
        // fall through to file fallback
    }

    $file = __DIR__ . '/event_queue.jsonl';
    $entry = json_encode(['ts' => time(), 'attempts' => 0, 'payload' => $payload]);
    // try to append (best-effort)
    @file_put_contents($file, $entry . "\n", FILE_APPEND | LOCK_EX);
}

