<?php
session_start();
include('db_connect.php'); // Połączenie z bazą danych

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kwota = $_POST['kwota'];
    $haslo = $_POST['haslo'];
    $user_id = $_SESSION['user_id']; // ID zalogowanego użytkownika

    try {
        // Pobranie danych użytkownika
        $query = "SELECT haslo, stan_konta FROM Users WHERE id = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['user_id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo "<script>alert('Nie znaleziono użytkownika.'); window.location.href = 'wypłata.html';</script>";
            exit();
        }

        // Sprawdzenie statusu płatności użytkownika
        $status_query = "SELECT status_platnosci FROM useractivity WHERE user_id = :user_id";
        $status_stmt = $pdo->prepare($status_query);
        $status_stmt->execute(['user_id' => $user_id]);
        $status = $status_stmt->fetch(PDO::FETCH_ASSOC);

        if ($status && $status['status_platnosci'] !== 'Aktywny') {
            echo "<script>alert('Transakcje są zablokowane dla tego użytkownika.'); window.location.href = 'wypłata.html';</script>";
            exit();
        }

        // Sprawdzenie poprawności hasła
        if ($haslo === $user['haslo']) {
            if ($user['stan_konta'] >= $kwota) {
                // Aktualizacja stanu konta
                $new_balance = $user['stan_konta'] - $kwota;
                $update_query = "UPDATE Users SET stan_konta = :new_balance WHERE id = :user_id";
                $update_stmt = $pdo->prepare($update_query);
                $update_stmt->execute(['new_balance' => $new_balance, 'user_id' => $user_id]);

                // Zapis transakcji
                $transaction_query = "INSERT INTO TransactionHistory (user_id, kwota, typ_transakcji) VALUES (:user_id, :kwota, 'Wypłata')";
                $transaction_stmt = $pdo->prepare($transaction_query);
                $transaction_stmt->execute(['user_id' => $user_id, 'kwota' => $kwota]);

                echo "<script>alert('Wypłata zakończona pomyślnie.'); window.location.href = 'wypłata.html';</script>";
                exit();
            } else {
                echo "<script>alert('Nie masz wystarczająco dużo środków na koncie.'); window.location.href = 'wypłata.html';</script>";
                exit();
            }
        } else {
            echo "<script>alert('Niepoprawne hasło.'); window.location.href = 'wypłata.html';</script>";
            exit();
        }
    } catch (PDOException $e) {
        echo "<script>alert('Wystąpił błąd: " . addslashes($e->getMessage()) . "'); window.location.href = 'wypłata.html';</script>";
        exit();
    }
}
?>
