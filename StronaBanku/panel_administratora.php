<?php
session_start();

// Sprawdzenie, czy użytkownik jest administratorem
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] !== 'admin') {
    header("Location: login.html"); // Przekierowanie na stronę logowania, jeśli nie jest administratorem
    exit();
}

// Ustawienia połączenia z bazą danych
$servername = "localhost";
$username = "root"; // domyślny użytkownik XAMPP
$password = ""; // domyślne hasło w XAMPP
$dbname = "BankDB";

// Utworzenie połączenia
$conn = new mysqli($servername, $username, $password, $dbname);

// Sprawdzenie połączenia
if ($conn->connect_error) {
    die("Błąd połączenia: " . $conn->connect_error);
}

// Obsługa formularza po kliknięciu "Zatwierdź zmiany"
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_statuses'])) {
    // Pobieranie zaktualizowanych statusów i wartości konta
    foreach ($_POST['user_status'] as $user_id => $status) {
        $status_konta = $status['status_konta'];
        $status_platnosci = $status['status_platnosci'];

        // Aktualizacja statusów w bazie danych
        $sql = "UPDATE UserActivity 
                SET status_konta = ?, status_platnosci = ? 
                WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $status_konta, $status_platnosci, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    // Aktualizacja stanu konta
    foreach ($_POST['user_balance'] as $user_id => $new_balance) {
        $new_balance = (float) $new_balance;

        // Aktualizacja stanu konta w bazie danych
        $sql = "UPDATE Users SET stan_konta = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("di", $new_balance, $user_id);
        $stmt->execute();
        $stmt->close();

        // Dodanie wpisu do historii transakcji
        $transaction_type = "Aktualizacja stanu konta";
        $sql = "INSERT INTO TransactionHistory (user_id, kwota, typ_transakcji) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ids", $user_id, $new_balance, $transaction_type);
        $stmt->execute();
        $stmt->close();
    }

    // Przekierowanie z potwierdzeniem zmian
    echo "<script type='text/javascript'>alert('Zmiany zostały zapisane pomyślnie.'); window.location.href = 'panel_administratora.php';</script>";
    exit();
}

// Zapytanie do bazy danych w celu pobrania danych wszystkich użytkowników
$sql = "SELECT u.id, u.nazwa_uzytkownika, u.nr_konta, u.stan_konta, u.email, a.status_konta, a.status_platnosci 
        FROM Users u 
        JOIN UserActivity a ON u.id = a.user_id";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administratora</title>
    <link rel="stylesheet" href="style.css">
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
<br><br><br><br><br><br>
    <!-- Tabela użytkowników -->
    <form method="POST">
        <table border="1">
            <tr>
                <th>Nazwa użytkownika</th>
                <th>Numer konta</th>
                <th>Stan konta</th>
                <th>Email</th>
                <th>Status konta</th>
                <th>Status płatności</th>
            </tr>

            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>" . htmlspecialchars($row['nazwa_uzytkownika']) . "</td>
                            <td>" . htmlspecialchars($row['nr_konta']) . "</td>
                            <td>
                                <input type='number' step='0.01' name='user_balance[" . $row['id'] . "]' value='" . htmlspecialchars($row['stan_konta']) . "' required>
                            </td>
                            <td>" . htmlspecialchars($row['email']) . "</td>
                            <td>
                                <select name='user_status[" . $row['id'] . "][status_konta]'>
                                    <option value='Aktywny'" . ($row['status_konta'] == 'Aktywny' ? ' selected' : '') . ">Aktywny</option>
                                    <option value='Zablokowany'" . ($row['status_konta'] == 'Zablokowany' ? ' selected' : '') . ">Zablokowany</option>
                                </select>
                            </td>
                            <td>
                                <select name='user_status[" . $row['id'] . "][status_platnosci]'>
                                    <option value='Aktywny'" . ($row['status_platnosci'] == 'Aktywny' ? ' selected' : '') . ">Aktywny</option>
                                    <option value='Zablokowany'" . ($row['status_platnosci'] == 'Zablokowany' ? ' selected' : '') . ">Zablokowany</option>
                                </select>
                            </td>
                        </tr>";
                }
            } else {
                echo "<tr><td colspan='6'>Brak danych</td></tr>";
            }
            ?>

        </table>
<br><br><br>
<div class="button-container">
        <button type="submit" name="update_statuses" class="btn-submit">Zatwierdź zmiany</button>
</div>
    </form>

    <!-- Stopka -->
    <footer>
        <div class="container">
            <p>&copy; Wszystkie prawa zastrzeżone.</p>
        </div>
    </footer>

</body>
</html>

<?php
$conn->close();
?>
