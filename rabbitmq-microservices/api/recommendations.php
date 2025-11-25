<?php
// BANK_API/api/recommendations.php
require __DIR__ . "/config.php";
require __DIR__ . "/helpers.php";

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    json_response(["error" => "METHOD_NOT_ALLOWED"], 405);
}

$user = require_auth($db);
$userId = (int)$user["id"];

$readFilter = $_GET["read"] ?? null;

// budujemy prostego SELECT-a
$sql = "SELECT id, message, created_at, read_at FROM recommendations WHERE user_id = ?";
$params = [$userId];
$types = "i";

if ($readFilter === "false" || $readFilter === "0") {
    $sql .= " AND read_at IS NULL";
} elseif ($readFilter === "true" || $readFilter === "1") {
    $sql .= " AND read_at IS NOT NULL";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $db->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$items = [];
while ($row = $res->fetch_assoc()) {
    $items[] = $row;
}

json_response([
    "items" => $items,
]);
