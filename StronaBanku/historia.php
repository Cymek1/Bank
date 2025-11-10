<?php
session_start(); // Rozpoczynamy sesję

// Sprawdzenie, czy użytkownik jest zalogowany
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

// Ustawienia połączenia z bazą danych
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "BankDB";

// Połączenie z bazą danych
$conn = new mysqli($servername, $username, $password, $dbname);

// Sprawdzenie połączenia
if ($conn->connect_error) {
    die("Błąd połączenia: " . $conn->connect_error);
}

// Pobranie ID użytkownika z sesji
$user_id = $_SESSION['user_id'];

// Pobranie numeru konta zalogowanego użytkownika
$sql_user_account = "SELECT nr_konta FROM Users WHERE id = ?";
$stmt_user_account = $conn->prepare($sql_user_account);
$stmt_user_account->bind_param("i", $user_id);
$stmt_user_account->execute();
$result_user_account = $stmt_user_account->get_result();
$row_user_account = $result_user_account->fetch_assoc();
$numer_konta_uzytkownika = $row_user_account['nr_konta'];

// Zapytanie do historii transakcji
$sql = "
    SELECT 
        kwota, 
        typ_transakcji, 
        data_transakcji, 
        opis
    FROM TransactionHistory
    WHERE user_id = ?
    UNION ALL
    SELECT 
        kwota, 
        'Przelew otrzymany' AS 'typ_transakcji', 
        data_transakcji, 
        opis
    FROM TransactionHistory
    WHERE numer_konta_odbiorcy = ?
    ORDER BY data_transakcji DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $user_id, $numer_konta_uzytkownika);
$stmt->execute();
$result = $stmt->get_result();

// Pobranie wyników do tablicy
$transactions = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
}

// Zamknięcie połączenia
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historia Transakcji</title>
    <link rel="stylesheet" href="style.css"> <!-- Link do pliku CSS -->
</head>
<body>

    <!-- Pasek nawigacyjny -->
    <div class="navbar">
        <div class="logo">
            <a href="bank.html">
                <img src="logo2.png" alt="BankSystem" style="height: 50px;">
            </a>
        </div>
        <div class="navbar-right">
            <form action="logout.php" method="GET">
                <button type="submit">Wyloguj</button>
            </form>
        </div>
    </div>

    <!-- Nagłówek -->
    <div class="header">
        Historia Transakcji
    </div>

    <!-- Historia transakcji -->
    <div class="transactions">
        <?php if (count($transactions) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Kwota</th>
                        <th>Typ Transakcji</th>
                        <th>Data Transakcji</th>
                        <th>Opis</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?php echo number_format($transaction['kwota'], 2, ',', ' ') . ' PLN'; ?></td>
                            <td><?php echo htmlspecialchars($transaction['typ_transakcji']); ?></td>
                            <td><?php echo date("d-m-Y H:i:s", strtotime($transaction['data_transakcji'])); ?></td>
                            <td><?php echo htmlspecialchars($transaction['opis']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Brak transakcji w historii.</p>
        <?php endif; ?>
    </div>

    <!-- Przycisk powrotu -->
    <div class="back-button-container" style="text-align: center; margin-top: 30px;">
        <a href="menu.php">
            <button class="button">Powrót do menu</button>
        </a>
    </div>

    <!-- Stopka -->
    <footer>
        <div class="container">
            <p>&copy; Wszystkie prawa zastrzeżone.</p>
        </div>
    </footer>
</body>
</html>
