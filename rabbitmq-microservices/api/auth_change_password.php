<?php
// BANK_API/api/auth_change_password.php
require __DIR__ . "/config.php";
require __DIR__ . "/helpers.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    json_response(["error" => "METHOD_NOT_ALLOWED"], 405);
}

$user   = require_auth($db);
$userId = (int)$user["id"];

$input = json_decode(file_get_contents("php://input"), true) ?? [];
$old   = trim($input["old_password"] ?? "");
$new   = trim($input["new_password"] ?? "");

if ($old === "" || $new === "") {
    json_response([
        "error"   => "VALIDATION_ERROR",
        "message" => "Podaj stare i nowe hasło",
    ], 400);
}

if (strlen($new) < 3) {
    json_response([
        "error"   => "VALIDATION_ERROR",
        "message" => "Nowe hasło musi mieć co najmniej 3 znaki",
    ], 400);
}

// pobierz aktualne hasło
$stmt = $db->prepare("SELECT haslo FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res  = $stmt->get_result();
$row  = $res->fetch_assoc();

if (!$row) {
    json_response([
        "error"   => "USER_NOT_FOUND",
        "message" => "Użytkownik nie istnieje",
    ], 404);
}

$currentPassword = $row["haslo"];

// plain-text porównanie (na projekt)
if ($old !== $currentPassword) {
    json_response([
        "error"   => "INVALID_OLD_PASSWORD",
        "message" => "Stare hasło jest nieprawidłowe",
    ], 400);
}

// aktualizacja hasła
$stmt = $db->prepare("UPDATE users SET haslo = ? WHERE id = ?");
$stmt->bind_param("si", $new, $userId);
$stmt->execute();

// ALERT
add_alert($db, $userId, "Twoje hasło zostało zmienione.");

// publish event (best-effort)
$now = (new DateTime())->format(DateTime::ATOM);
$msg = [
    'id' => (string)$userId,
    'balance' => (float)$user['stan_konta'],
    'metadata' => ['type' => 'change_password'],
    'updated_at' => $now
];
publish_event($msg);

json_response([
    "status"  => "OK",
    "message" => "Hasło zostało zmienione",
]);
