<?php
// BANK_API/api/transactions_withdraw.php
require __DIR__ . "/config.php";
require __DIR__ . "/helpers.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    json_response(["error" => "METHOD_NOT_ALLOWED"], 405);
}

$user = require_auth($db);

$input  = json_decode(file_get_contents("php://input"), true) ?? [];
$amount = (float)($input["amount"] ?? 0);

if ($amount <= 0) {
    json_response(["error" => "INVALID_DATA", "message" => "Kwota musi być > 0"], 400);
}

// status płatności
$stmt = $db->prepare("SELECT status_konta, status_platnosci FROM useractivity WHERE user_id = ?");
$stmt->bind_param("i", $user["id"]);
$stmt->execute();
$res = $stmt->get_result();
$activity = $res->fetch_assoc();

if ($activity && ($activity["status_konta"] !== "Aktywny" || $activity["status_platnosci"] !== "Aktywny")) {
    json_response(["error" => "BLOCKED", "message" => "Konto lub płatności są zablokowane"], 403);
}

$db->begin_transaction();

try {
    $stmt = $db->prepare("SELECT stan_konta FROM users WHERE id = ? FOR UPDATE");
    $stmt->bind_param("i", $user["id"]);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $balance = (float)$row["stan_konta"];

    if ($balance < $amount) {
        $db->rollback();
        json_response(["error" => "NO_FUNDS", "message" => "Brak środków"], 400);
    }

    $newBalance = $balance - $amount;

    $stmt = $db->prepare("UPDATE users SET stan_konta = ? WHERE id = ?");
    $stmt->bind_param("di", $newBalance, $user["id"]);
    $stmt->execute();

    $stmt = $db->prepare("
        INSERT INTO transactionhistory (user_id, kwota, typ_transakcji, opis, numer_konta_odbiorcy)
        VALUES (?, ?, 'Wypłata', 'Wypłata z aplikacji', '')
    ");
    $stmt->bind_param("id", $user["id"], $amount);
    $stmt->execute();

    $db->commit();

    // ---- ALERTY ----
    $userId = (int)$user["id"];

    add_alert(
        $db,
        $userId,
        "Wypłata z konta: {$amount} PLN. Nowe saldo: {$newBalance} PLN."
    );

    if ($amount > 1000) {
        add_alert(
            $db,
            $userId,
            "Uwaga: wykonano dużą wypłatę ({$amount} PLN). Sprawdź, czy to na pewno Ty."
        );
    }

    // publish event to producer (best-effort)
    $now = (new DateTime())->format(DateTime::ATOM);
    $msg = [
        'id' => (string)$userId,
        'balance' => $newBalance,
        'metadata' => ['type' => 'withdraw'],
        'updated_at' => $now
    ];
    publish_event($msg);

    json_response(["status" => "OK"]);

} catch (Throwable $e) {
    $db->rollback();
    json_response(["error" => "TX_ERROR", "message" => $e->getMessage()], 500);
}
