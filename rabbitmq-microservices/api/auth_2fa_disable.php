<?php
// BANK_API/api/auth_2fa_disable.php
require __DIR__ . "/config.php";
require __DIR__ . "/helpers.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    json_response(["error" => "METHOD_NOT_ALLOWED"], 405);
}

$user   = require_auth($db);
$userId = (int)$user["id"];

// 1) wyłącz 2FA
$stmt = $db->prepare("UPDATE users SET twofa_enabled = 0 WHERE id = ?");
if (!$stmt) {
    json_response(["error" => "DB_PREPARE_FAILED", "details" => $db->error], 500);
}
$stmt->bind_param("i", $userId);
if (!$stmt->execute()) {
    json_response(["error" => "DB_EXEC_FAILED", "details" => $stmt->error], 500);
}

// 2) usuń kody
$stmt = $db->prepare("DELETE FROM user_2fa_codes WHERE user_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
}

// ALERT
add_alert($db, $userId, "Wyłączyłeś uwierzytelnianie dwuskładnikowe (2FA).");

json_response([
    "status"  => "OK",
    "message" => "2FA zostało wyłączone, kody usunięte",
]);
