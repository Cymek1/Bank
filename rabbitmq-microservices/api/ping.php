<?php
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    "ok" => true,
    "method" => $_SERVER["REQUEST_METHOD"],
    "time" => date('Y-m-d H:i:s')
]);
