<?php
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

// Sprawdzanie, czy formularz został wysłany
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Pobieranie danych z formularza
    $nazwa_uzytkownika = $_POST['nazwa_uzytkownika'];
    $haslo = $_POST['haslo'];

    // Sprawdzenie, czy użytkownik to administrator
    if ($nazwa_uzytkownika == "Admin" && $haslo == "admin") {
        // Rozpoczęcie sesji dla administratora
        session_start();
        $_SESSION['user_id'] = 'admin'; // Ustawiamy specjalne ID dla administratora

        // Przekierowanie do panelu administratora
        header("Location: panel_administratora.php");
        exit();
    }

    // Zapytanie do bazy danych w celu sprawdzenia użytkownika oraz statusu konta
    $sql = "SELECT u.*, a.status_konta 
            FROM Users u 
            JOIN UserActivity a ON u.id = a.user_id 
            WHERE u.nazwa_uzytkownika = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $nazwa_uzytkownika); // przypisanie zmiennej do zapytania
    $stmt->execute();
    $result = $stmt->get_result();

    // Sprawdzanie, czy użytkownik istnieje
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Sprawdzanie poprawności hasła
        if ($haslo == $user['haslo']) { 
            // Sprawdzanie, czy status konta jest "Aktywny"
            if ($user['status_konta'] == 'Aktywny') {
                // Rozpoczęcie sesji i zapisanie user_id w sesji
                session_start();
                $_SESSION['user_id'] = $user['id']; // Zapisanie ID użytkownika w sesji

                // Przekierowanie do strony menu.php
                header("Location: menu.php");
                exit();
            } else {
                // Wysłanie komunikatu o błędzie, gdy konto jest zablokowane
                header("Location: login.html?error=account-blocked");
                exit();
            }
        } else {
            // Wysłanie komunikatu o błędzie do login.html
            header("Location: login.html?error=wrong-password");
            exit();
        }
    } else {
        // Wysłanie komunikatu o błędzie do login.html
        header("Location: login.html?error=user-not-found");
        exit();
    }

    // Zamknięcie połączenia
    $stmt->close();
}

$conn->close();
?>
