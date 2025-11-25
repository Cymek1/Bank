<?php
// BANK_API/api/recommendations_read.php
require __DIR__ . "/config.php";
require __DIR__ . "/helpers.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    json_response(["error" => "METHOD_NOT_ALLOWED"], 405);
}

$user = require_auth($db);
$userId = (int)$user["id"];

$input = json_decode(file_get_contents("php://input"), true) ?? [];
$id    = isset($input["id"]) ? (int)$input["id"] : 0;

if ($id <= 0) {
    json_response(["error" => "INVALID_ID", "message" => "Podaj poprawne id alertu"], 400);
}

// oznaczamy alert jako przeczytany, ale TYLKO jeśli należy do tego usera
$stmt = $db->prepare("
    UPDATE recommendations
    SET read_at = NOW()
    WHERE id = ? AND user_id = ?
");
$stmt->bind_param("ii", $id, $userId);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    json_response([
        "status"  => "NO_CHANGE",
        "message" => "Nie znaleziono takiego alertu dla tego użytkownika"
    ]);
}

json_response([
    "status"  => "OK",
    "message" => "Alert oznaczony jako przeczytany",
]);
