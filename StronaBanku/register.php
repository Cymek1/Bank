<?php
// Dane do połączenia z bazą danych
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

// Flaga błędu, jeśli e-mail lub nazwa użytkownika już istnieje
$email_exists = false;
$username_exists = false;

// Sprawdzenie, czy formularz został wysłany metodą POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Pobranie danych z formularza
    $nazwa_uzytkownika = $_POST['nazwa_uzytkownika'];
    $email = $_POST['email'];
    $haslo = $_POST['haslo']; // Hasło bez szyfrowania

    // Sprawdzenie, czy e-mail już istnieje w bazie danych
    $sql = "SELECT id FROM Users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Ustawienie flagi błędu dla e-maila
        $email_exists = true;
    }

    // Sprawdzenie, czy nazwa użytkownika już istnieje w bazie danych
    $sql = "SELECT id FROM Users WHERE nazwa_uzytkownika = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $nazwa_uzytkownika);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Ustawienie flagi błędu dla nazwy użytkownika
        $username_exists = true;
    }

    // Jeśli nie znaleziono błędów, wykonaj rejestrację
    if (!$email_exists && !$username_exists) {
        // Przygotowanie zapytania SQL do wstawienia danych
        $sql = "INSERT INTO Users (nazwa_uzytkownika, email, haslo) VALUES (?, ?, ?)";

        // Przygotowanie i wykonanie zapytania
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $nazwa_uzytkownika, $email, $haslo);

        if ($stmt->execute()) {
            // Pobranie ID nowo zarejestrowanego użytkownika
            $user_id = $stmt->insert_id;

            // Pobranie numeru konta z tabeli Users
            $sql_account = "SELECT nr_konta FROM Users WHERE id = ?";
            $stmt_account = $conn->prepare($sql_account);
            $stmt_account->bind_param("i", $user_id);
            $stmt_account->execute();
            $stmt_account->bind_result($nr_konta);
            $stmt_account->fetch();
            $stmt_account->close();

            // Dodanie wpisu do tabeli UserActivity z domyślnymi statusami i nr_konta
            $status_konta = 'Aktywny';
            $status_platnosci = 'Aktywny';

            $sql_activity = "INSERT INTO UserActivity (user_id, nr_konta, status_konta, status_platnosci) VALUES (?, ?, ?, ?)";
            $stmt_activity = $conn->prepare($sql_activity);
            $stmt_activity->bind_param("isss", $user_id, $nr_konta, $status_konta, $status_platnosci);

            if ($stmt_activity->execute()) {
                // Rejestracja zakończona sukcesem
                echo "<script>alert('Rejestracja zakończona sukcesem.'); window.location.href = 'login.html';</script>";
                exit();
            } else {
                echo "Błąd podczas dodawania do UserActivity: " . $stmt_activity->error;
            }
        } else {
            echo "Błąd podczas rejestracji: " . $stmt->error;
        }
    }

    $stmt->close();
    
    // Sprawdzenie, czy $stmt_activity zostało zdefiniowane, zanim wywołasz close()
    if (isset($stmt_activity)) {
        $stmt_activity->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejestracja</title>
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
            <button><a href="login.html">Logowanie</a></button>
            <button><a href="register.php">Rejestracja</a></button>
        </div>
    </div>

    <!-- Nagłówek -->
    <div class="header">
        Zarejestruj się
    </div>

    <!-- Formularz rejestracji -->
    <div class="register-form">
        <h2>Formularz rejestracyjny</h2>

        <!-- Pokazanie błędów -->
        <?php if ($email_exists): ?>
            <script>
                alert("E-mail jest już zarejestrowany w systemie. Proszę podać inny adres e-mail.");
            </script>
        <?php endif; ?>

        <?php if ($username_exists): ?>
            <script>
                alert("Nazwa użytkownika jest już zajęta. Proszę wybrać inną nazwę.");
            </script>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <label for="nazwa_uzytkownika">Nazwa użytkownika:</label><br>
            <input type="text" id="nazwa_uzytkownika" name="nazwa_uzytkownika" required><br><br>
    
            <label for="email">E-mail:</label><br>
            <input type="email" id="email" name="email" required><br><br>
    
            <label for="haslo">Hasło:</label><br>
            <input type="password" id="haslo" name="haslo" required><br><br>
    
            <input type="submit" value="Zarejestruj">
        </form>
        
        <p>Masz już konto? <a href="login.html">Zaloguj się</a></p>
<br><br>
    <!-- Stopka -->
    <footer>
        <div class="container">
            <p>&copy; Wszystkie prawa zastrzeżone.</p>
        </div>
    </footer>

</body>
</html>
