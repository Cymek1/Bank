<?php
// BANK_API/api/config.php

$DB_HOST = getenv('DB_HOST') ?: "mysql";
$DB_NAME = getenv('DB_NAME') ?: "bank_db";
$DB_USER = getenv('DB_USER') ?: "bank";
$DB_PASS = getenv('DB_PASS') ?: "bankpass";

// URL producenta microservice (Producer). Jeśli uruchamiasz producer lokalnie przez docker-compose
// i chcesz wywoływać go z hosta, użyj http://localhost:4000
$PRODUCER_URL = getenv('PRODUCER_URL') ?: 'http://localhost:4000';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    $db->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode([
        "error" => "DB_CONNECTION_FAILED",
        "message" => $e->getMessage(),
    ]);
    exit;
}
