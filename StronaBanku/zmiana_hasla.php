<?php
session_start();
include('db_connect.php'); // Połączenie z bazą danych

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stare_haslo = $_POST['stare_haslo'];
    $nowe_haslo = $_POST['nowe_haslo'];
    $user_id = $_SESSION['user_id']; // ID zalogowanego użytkownika

    try {
        // Pobranie danych użytkownika
        $query = "SELECT haslo FROM Users WHERE id = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['user_id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            header("Location: zmiana_hasla.php?message=Nie znaleziono użytkownika.");
            exit();
        }

        // Sprawdzenie poprawności starego hasła
        if ($stare_haslo === $user['haslo']) {
            // Aktualizacja hasła
            $update_query = "UPDATE Users SET haslo = :nowe_haslo WHERE id = :user_id";
            $update_stmt = $pdo->prepare($update_query);
            $update_stmt->execute(['nowe_haslo' => $nowe_haslo, 'user_id' => $user_id]);

            // Wysłanie komunikatu o pomyślnej zmianie
            header("Location: zmiana_hasla.php?message=Hasło zostało zmienione pomyślnie.");
            exit();
        } else {
            header("Location: zmiana_hasla.php?message=Niepoprawne stare hasło.");
            exit();
        }
    } catch (PDOException $e) {
        header("Location: zmiana_hasla.php?message=Wystąpił błąd: " . urlencode($e->getMessage()));
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zmiana Hasła</title>
    <link rel="stylesheet" href="style.css">
    <script>
window.onload = function () {
    const urlParams = new URLSearchParams(window.location.search);
    const message = urlParams.get('message');
    if (message) {
        // Wyświetlenie komunikatu jako alert
        alert(message);
    }
};

    </script>
    <style>
    <>
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
            <form action="logout.php" method="GET">
                <button type="submit">Wyloguj</button>
            </form>
        </div>
    </div>

    <!-- Nagłówek -->
    <div class="header">
        Zmień hasło
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

    <!-- Formularz zmiany hasła -->
    <div class="login-form">
        <form method="POST" action="zmiana_hasla.php">
            <label for="stare_haslo">Stare hasło:</label>
            <input type="password" id="stare_haslo" name="stare_haslo" placeholder="Wprowadź stare hasło" required><br><br>

            <label for="nowe_haslo">Nowe hasło:</label>
            <input type="password" id="nowe_haslo" name="nowe_haslo" placeholder="Wprowadź nowe hasło" required><br><br>

            <button type="submit">Zmień hasło</button>
        </form>
    </div>

    <!-- Stopka -->
    <footer>
        <div class="container">
            <p>&copy; Wszystkie prawa zastrzeżone.</p>
        </div>
    </footer>

</body>
</html>
