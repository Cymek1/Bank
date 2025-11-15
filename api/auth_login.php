<?php
// BANK_API/api/auth_login.php
require __DIR__ . "/config.php";
require __DIR__ . "/helpers.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    json_response(["error" => "METHOD_NOT_ALLOWED"], 405);
}

$input = json_decode(file_get_contents("php://input"), true) ?? [];
$email = trim($input["email"] ?? "");
$password = $input["password"] ?? "";

if ($email === "" || $password === "") {
    json_response([
        "error" => "INVALID_DATA",
        "message" => "Email i hasło są wymagane"
    ], 400);
}

// w tabeli users: id, nazwa_uzytkownika, email, haslo, stan_konta, nr_konta, twofa_enabled
$stmt = $db->prepare("
    SELECT id, email, nazwa_uzytkownika, haslo, twofa_enabled
    FROM users
    WHERE email = ?
");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

// zły email/hasło
if (!$user || $password !== $user["haslo"]) { // hasła w plain-texcie
    json_response([
        "error" => "INVALID_CREDENTIALS",
        "message" => "Błędny email lub hasło"
    ], 401);
}

// jeżeli użytkownik MA włączone 2FA → generujemy kod, NIE wydajemy tokenów
if (!empty($user["twofa_enabled"])) {
    $code = (string)random_int(100000, 999999); // 6 cyfr

    $stmt = $db->prepare("
        INSERT INTO user_2fa_codes (user_id, code, created_at, used)
        VALUES (?, ?, NOW(), 0)
    ");

    if (!$stmt) {
        json_response([
            "error" => "DB_PREPARE_FAILED",
            "details" => $db->error
        ], 500);
    }

    $stmt->bind_param("is", $user["id"], $code);

    if (!$stmt->execute()) {
        json_response([
            "error" => "DB_EXEC_FAILED",
            "details" => $stmt->error
        ], 500);
    }

    json_response([
        "twofa_required" => true,
        "message"        => "Wymagana weryfikacja 2FA. Sprawdź wiadomość z kodem.",
        "debug_code"     => $code   // NA PROJEKT – zostaw, żeby widzieć kod
    ]);
}

// jeżeli 2FA wyłączone → klasyczny login, wydajemy tokeny
$access = generate_token(["user_id" => (int)$user["id"], "type" => "access"], 3600);
$refresh = generate_token(["user_id" => (int)$user["id"], "type" => "refresh"], 86400);

json_response([
    "twofa_required" => false,
    "access"  => $access,
    "refresh" => $refresh,
]);
