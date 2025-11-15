<?php
require __DIR__ . "/config.php";
require __DIR__ . "/helpers.php";

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    json_response(["error" => "METHOD_NOT_ALLOWED"], 405);
}

$user = require_auth($db);

$page = max(1, (int)($_GET["page"] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$from = $_GET["from"] ?? null;
$to   = $_GET["to"] ?? null;
$type = $_GET["type"] ?? null; // "transfer" / "withdraw"

$where = ["user_id = ?"];
$params = [$user["id"]];
$types  = "i";

// mapowanie typu
if ($type === "transfer") {
    $where[] = "typ_transakcji = 'Przelew'";
} elseif ($type === "withdraw") {
    $where[] = "typ_transakcji = 'Wypłata'";
}

if ($from) {
    $where[] = "DATE(data_transakcji) >= ?";
    $params[] = $from;
    $types   .= "s";
}
if ($to) {
    $where[] = "DATE(data_transakcji) <= ?";
    $params[] = $to;
    $types   .= "s";
}

$sql = "SELECT id, kwota, typ_transakcji, data_transakcji, opis, numer_konta_odbiorcy
        FROM transactionhistory
        WHERE " . implode(" AND ", $where) . "
        ORDER BY data_transakcji DESC
        LIMIT $limit OFFSET $offset";

$stmt = $db->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$items = [];
while ($row = $res->fetch_assoc()) {
    $typeApi = strtolower($row["typ_transakcji"]) === "przelew" ? "transfer" : "withdraw";
    $items[] = [
        "id" => (int)$row["id"],
        "created_at" => $row["data_transakcji"],
        "type" => $typeApi,
        "amount" => (float)$row["kwota"],
        "title" => $row["opis"],
        "counterparty" => $row["numer_konta_odbiorcy"],
    ];
}

json_response([
    "items" => $items,
    "hasMore" => count($items) === $limit,
]);
