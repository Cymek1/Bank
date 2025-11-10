<?php
// Rozpoczynamy sesję
session_start();

// Sprawdzenie, czy użytkownik jest zalogowany
if (!isset($_SESSION['user_id'])) {
    // Jeśli nie, przekierowanie na stronę logowania
    header("Location: login.html");
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

// Pobranie id użytkownika z sesji
$user_id = $_SESSION['user_id'];

// Zapytanie do bazy danych w celu pobrania danych użytkownika
$sql = "SELECT nazwa_uzytkownika, nr_konta, stan_konta FROM Users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id); // przypisanie zmiennej do zapytania
$stmt->execute();
$result = $stmt->get_result();

// Sprawdzenie, czy zapytanie zwróciło jakiekolwiek dane
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    // Przypisanie wartości do zmiennych
    $nazwa_uzytkownika = $user['nazwa_uzytkownika'];
    $nr_konta = $user['nr_konta'];
    $stan_konta = $user['stan_konta'];
} else {
    // Jeśli użytkownik nie istnieje, przekierowanie na stronę logowania
    echo "Brak wyników dla tego użytkownika";
    exit();
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
    <title>Menu</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Stylizacja listy rozwijanej */
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #f1f1f1;
            min-width: 160px;
            box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
            z-index: 1;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }

        .dropdown-content a:hover {
            background-color: #ddd;
        }
    </style>
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
            <!-- Przycisk wylogowania -->
            <form action="logout.php" method="GET">
                <button type="submit">Wyloguj</button>
            </form>
        </div>
    </div>

    <!-- Nagłówek -->
    <div class="header">
        Pulpit
    </div>

 <!-- Przyciski nawigacyjne -->
 <div class="buttons-container">
        <button class="button" onclick="window.location.href='menu.php'">Panel</button>

        <!-- Dropdown dla przycisku Transakcje -->
        <div class="dropdown">
            <button class="button">Transakcje</button>
            <div class="dropdown-content">
                <a href="przelew.html">Przelew</a>
                <a href="wypłata.html">Wypłata</a>
            </div>
        </div>

        <!-- Dropdown dla przycisku Pomoc -->
        <div class="dropdown">
            <button class="button">Pomoc</button>
            <div class="dropdown-content">
                <a href="pomoc.php">Chatbot</a>
                <a href="zmiana_hasla.php">Zmień hasło</a>
            </div>
        </div>
    </div>

    <!-- Okienko z danymi konta -->
    <div class="account-card">
        <h3>Dane Konta</h3>
        <p><strong>Nazwa użytkownika:</strong> <?php echo htmlspecialchars($nazwa_uzytkownika); ?></p>
        <p><strong>Nr Konta:</strong> <?php echo htmlspecialchars($nr_konta); ?></p>
        <p><strong>Saldo:</strong> <?php echo number_format($stan_konta, 2, ',', ' ') . ' PLN'; ?></p>
    </div>

    <!-- Historia transakcji -->
    <div class="transactions">
        <h3>Historia Transakcji</h3>
        <button class="button" onclick="window.location.href='historia.php'">Pokaż historię</button>
    </div>

        <!-- Stopka -->
        <footer>
        <div class="container">
            <p>&copy; Wszystkie prawa zastrzeżone.</p>
        </div>
    </footer>
</body>
</html>
