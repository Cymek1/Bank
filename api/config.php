<?php
// BANK_API/api/config.php

$DB_HOST = "localhost";
$DB_NAME = "bankdb";   // <-- dokładnie taka nazwa jak w dumpie
$DB_USER = "root";
$DB_PASS = "";         // jeśli masz hasło do roota, wpisz je tu

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
