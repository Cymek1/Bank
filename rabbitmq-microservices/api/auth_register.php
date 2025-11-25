<?php
// auth_register.php

require __DIR__ . "/config.php";
require __DIR__ . "/helpers.php";

// --- CORS / preflight jak w auth_login.php ---
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    http_response_code(204);
    exit;
}

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// --- tylko POST ---
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    json_response(["error" => "METHOD_NOT_ALLOWED"], 405);
}

// --- wczytanie JSON-a ---
$raw  = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!is_array($data)) {
    json_response([
        "error"   => "INVALID_JSON",
        "message" => "Body musi być JSON-em",
    ], 400);
}

$username = trim($data["username"] ?? "");
$email    = trim($data["email"] ?? "");
$password = trim($data["password"] ?? "");

// --- walidacja ---
if ($username === "" || $email === "" || $password === "") {
    json_response([
        "error"   => "VALIDATION_ERROR",
        "message" => "Uzupełnij nazwę użytkownika, email i hasło",
    ], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response([
        "error"   => "VALIDATION_ERROR",
        "message" => "Nieprawidłowy adres email",
    ], 400);
}

if (strlen($password) < 3) {
    json_response([
        "error"   => "VALIDATION_ERROR",
        "message" => "Hasło musi mieć co najmniej 3 znaki",
    ], 400);
}

try {
    // UŻYWAMY TEGO SAMEGO CO W auth_login.php: $db (mysqli)
    // $db pochodzi z config.php

    // 1. Sprawdź, czy email już istnieje
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $db->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->fetch_assoc()) {
        json_response([
            "error"   => "EMAIL_EXISTS",
            "message" => "Użytkownik z takim adresem email już istnieje",
        ], 409);
    }
    $stmt->close();

    // 2. Wygeneruj nr_konta (4 cyfry) i stan_konta
    $nr_konta   = str_pad((string)random_int(0, 9999), 4, "0", STR_PAD_LEFT);
    $stan_konta = 0.0;

    // 3. Wstaw użytkownika
    $insert = $db->prepare("
        INSERT INTO users (nazwa_uzytkownika, email, haslo, stan_konta, nr_konta)
        VALUES (?, ?, ?, ?, ?)
    ");
    if (!$insert) {
        throw new Exception("Prepare failed: " . $db->error);
    }

    // typy: s (string), s, s, d (double/numeryczne), s
    $insert->bind_param("sssds", $username, $email, $password, $stan_konta, $nr_konta);
    $insert->execute();

    if ($insert->errno) {
        throw new Exception("Insert error: " . $insert->error);
    }

    $userId = (int)$db->insert_id;
    $insert->close();

    // Publish initial account event to microservices (best-effort)
    $now = (new DateTime())->format(DateTime::ATOM);
    $msg = [
        'id' => (string)$userId,
        'balance' => $stan_konta,
        'metadata' => ['type' => 'register'],
        'updated_at' => $now
    ];
    publish_event($msg);

    json_response([
        "status"   => "OK",
        "message"  => "Konto utworzone poprawnie",
        "user_id"  => $userId,
        "nr_konta" => $nr_konta,
    ], 201);

} catch (Throwable $e) {
    json_response([
        "error"   => "SERVER_ERROR",
        "message" => "Wystąpił błąd serwera przy rejestracji",
        "debug"   => $e->getMessage(),  // możesz potem wyłączyć
    ], 500);
}
