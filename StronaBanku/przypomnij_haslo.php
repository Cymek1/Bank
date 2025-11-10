<?php
require_once 'db_connect.php'; // Połączenie z bazą danych

$nazwa_uzytkownika = $email = "";

// Przetwarzanie formularza
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nazwa_uzytkownika = $_POST['nazwa_uzytkownika'];
    $email = $_POST['email'];

    // Sprawdzenie, czy użytkownik istnieje
    $sql = "SELECT * FROM Users WHERE nazwa_uzytkownika = ? AND email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nazwa_uzytkownika, $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Jeśli dane się zgadzają, wyświetl hasło
        echo "<script type='text/javascript'>alert('Twoje hasło: " . $user['haslo'] . "');</script>";
    } else {
        // Jeżeli użytkownik nie istnieje, wyświetl alert z błędem
        echo "<script type='text/javascript'>alert('Podana nazwa użytkownika lub e-mail są nieprawidłowe!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Przypomnij hasło</title>
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
        Przypomnij hasło
    </div>

    <!-- Formularz przypomnienia hasła -->
    <div class="login-form">
        <form method="POST" action="przypomnij_haslo.php">
            <label for="nazwa_uzytkownika">Nazwa użytkownika:</label>
            <input type="text" id="nazwa_uzytkownika" name="nazwa_uzytkownika" placeholder="Wprowadź nazwę użytkownika" required><br><br>

            <label for="email">E-mail:</label>
            <input type="email" id="email" name="email" placeholder="Wprowadź e-mail" required><br><br>

            <button type="submit">Przypomnij hasło</button>
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
