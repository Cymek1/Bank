<?php
// BANK_API/api/transactions_transfer.php
require __DIR__ . "/config.php";
require __DIR__ . "/helpers.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    json_response(["error" => "METHOD_NOT_ALLOWED"], 405);
}

$user = require_auth($db);  // ['id', 'nr_konta', itd.]

$input = json_decode(file_get_contents("php://input"), true) ?? [];
$counterparty = trim($input["counterparty"] ?? "");
$title        = trim($input["title"] ?? "");
$amount       = (float)($input["amount"] ?? 0);

if ($counterparty === "" || $title === "" || $amount <= 0) {
    json_response(["error" => "INVALID_DATA", "message" => "Uzupełnij wszystkie pola"], 400);
}

// sprawdź status płatności
$stmt = $db->prepare("SELECT status_konta, status_platnosci FROM useractivity WHERE user_id = ?");
$stmt->bind_param("i", $user["id"]);
$stmt->execute();
$res = $stmt->get_result();
$activity = $res->fetch_assoc();

if ($activity && ($activity["status_konta"] !== "Aktywny" || $activity["status_platnosci"] !== "Aktywny")) {
    json_response(["error" => "BLOCKED", "message" => "Konto lub płatności są zablokowane"], 403);
}

// znajdź odbiorcę po nr_konta
$stmt = $db->prepare("SELECT id FROM users WHERE nr_konta = ?");
$stmt->bind_param("s", $counterparty);
$stmt->execute();
$res = $stmt->get_result();
$target = $res->fetch_assoc();

if (!$target) {
    json_response(["error" => "NO_SUCH_ACCOUNT", "message" => "Nie znaleziono konta odbiorcy"], 404);
}

$db->begin_transaction();

try {
    // sprawdź saldo nadawcy
    $stmt = $db->prepare("SELECT stan_konta FROM users WHERE id = ? FOR UPDATE");
    $stmt->bind_param("i", $user["id"]);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $balance = (float)$row["stan_konta"];

    if ($balance < $amount) {
        $db->rollback();
        json_response(["error" => "NO_FUNDS", "message" => "Brak wystarczających środków"], 400);
    }

    // odejmij od nadawcy
    $stmt = $db->prepare("UPDATE users SET stan_konta = stan_konta - ? WHERE id = ?");
    $stmt->bind_param("di", $amount, $user["id"]);
    $stmt->execute();

    // dodaj odbiorcy
    $stmt = $db->prepare("UPDATE users SET stan_konta = stan_konta + ? WHERE id = ?");
    $stmt->bind_param("di", $amount, $target["id"]);
    $stmt->execute();

    // wpisz do historii nadawcy
    $stmt = $db->prepare("
        INSERT INTO transactionhistory (user_id, kwota, typ_transakcji, opis, numer_konta_odbiorcy)
        VALUES (?, ?, 'Przelew', ?, ?)
    ");
    $stmt->bind_param("idss", $user["id"], $amount, $title, $counterparty);
    $stmt->execute();

    $db->commit();

    // ---- ALERTY ----
    $senderId    = (int)$user["id"];
    $receiverId  = (int)$target["id"];
    $senderAcct  = $user["nr_konta"] ?? "NIEZNANY";

    // alert dla nadawcy
    add_alert(
        $db,
        $senderId,
        "Wykonano przelew {$amount} PLN na konto {$counterparty}. Tytuł: {$title}."
    );

    // alert dla odbiorcy
    add_alert(
        $db,
        $receiverId,
        "Otrzymano przelew {$amount} PLN z konta {$senderAcct}. Tytuł: {$title}."
    );

    json_response(["status" => "OK"]);

} catch (Throwable $e) {
    $db->rollback();
    json_response(["error" => "TX_ERROR", "message" => $e->getMessage()], 500);
}
