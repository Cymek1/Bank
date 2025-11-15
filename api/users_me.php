<?php
require __DIR__ . "/config.php";
require __DIR__ . "/helpers.php";

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    json_response(["error" => "METHOD_NOT_ALLOWED"], 405);
}

$user = require_auth($db);

json_response([
    "id" => (int)$user["id"],
    "email" => $user["email"],
    "name" => $user["nazwa_uzytkownika"],
    "account_number" => $user["nr_konta"],
    "balance" => (float)$user["stan_konta"],
]);
