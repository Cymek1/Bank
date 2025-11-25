<?php
require __DIR__ . "/config.php";
require __DIR__ . "/helpers.php";

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    json_response(["error" => "METHOD_NOT_ALLOWED"], 405);
}

$user = require_auth($db);

json_response([
    "balance" => (float)$user["stan_konta"],
    "account_number" => $user["nr_konta"],
]);
