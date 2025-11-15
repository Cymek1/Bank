<?php
// BANK_API/api/auth_2fa_verify.php
require __DIR__ . "/config.php";
require __DIR__ . "/helpers.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    json_response(["error" => "METHOD_NOT_ALLOWED"], 405);
}

$input = json_decode(file_get_contents("php://input"), true) ?? [];
$email = trim($input["email"] ?? "");
$code  = trim($input["code"] ?? "");

if ($email === "" || $code === "") {
    json_response(["error" => "INVALID_DATA", "message" => "Podaj email i kod 2FA"], 400);
}

// szukamy użytkownika
$stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

if (!$user) {
    json_response(["error" => "USER_NOT_FOUND"], 404);
}

$userId = (int)$user["id"];

// najnowszy nieużyty kod dla tego użytkownika
$stmt = $db->prepare("
    SELECT id, code, created_at
    FROM user_2fa_codes
    WHERE user_id = ? AND used = 0
    ORDER BY created_at DESC
    LIMIT 1
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

if (!$row) {
    json_response(["error" => "NO_CODE", "message" => "Brak aktywnego kodu 2FA"], 400);
}

// sprawdzamy ważność – np. 5 minut od utworzenia
$created = new DateTime($row["created_at"]);
$now = new DateTime();
$diff = $now->getTimestamp() - $created->getTimestamp();
$validSeconds = 5 * 60; // 5 minut

if ($diff > $validSeconds) {
    $upd = $db->prepare("UPDATE user_2fa_codes SET used = 1, success = 0 WHERE id = ?");
    $upd->bind_param("i", $row["id"]);
    $upd->execute();
    json_response(["error" => "CODE_EXPIRED", "message" => "Kod wygasł"], 400);
}

if ($row["code"] !== $code) {
    json_response(["error" => "INVALID_CODE", "message" => "Nieprawidłowy kod 2FA"], 400);
}

// sukces – oznaczamy kod jako wykorzystany
$upd = $db->prepare("UPDATE user_2fa_codes SET used = 1, success = 1 WHERE id = ?");
$upd->bind_param("i", $row["id"]);
$upd->execute();

// generujemy tokeny jak przy logowaniu
$access = generate_token(["user_id" => $userId, "type" => "access"], 3600);
$refresh = generate_token(["user_id" => $userId, "type" => "refresh"], 86400);

json_response([
    "status" => "OK",
    "access" => $access,
    "refresh" => $refresh,
]);
