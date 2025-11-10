<?php
session_start(); // Rozpoczęcie sesji

// Połączenie z bazą danych
$servername = "localhost";
$username = "root"; // Użyj swojego loginu do bazy
$password = ""; // Użyj swojego hasła do bazy
$dbname = "BankDB"; // Nazwa bazy danych

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Połączenie z bazą danych nie powiodło się: " . $conn->connect_error);
}

// Sprawdzanie, czy użytkownik jest zalogowany
if (!isset($_SESSION['user_id'])) {
    echo "Musisz być zalogowany, aby wykonać przelew.";
    exit;
}

// Odczytujemy dane z formularza
$numer_konta = $_POST['numer_konta'];
$kwota = $_POST['kwota'];
$opis = $_POST['opis']; // Odczytujemy opis przelewu
$user_id = $_SESSION['user_id']; // ID zalogowanego użytkownika

// Sprawdzamy status płatności użytkownika
$sql_status = "SELECT status_platnosci FROM UserActivity WHERE user_id = ?";
$stmt_status = $conn->prepare($sql_status);
$stmt_status->bind_param("i", $user_id);
$stmt_status->execute();
$result_status = $stmt_status->get_result();
if ($result_status->num_rows > 0) {
    $row_status = $result_status->fetch_assoc();
    $status_platnosci = $row_status['status_platnosci'];

    // Jeśli status płatności nie jest 'Aktywny', zablokuj przelew
    if ($status_platnosci != 'Aktywny') {
        echo "<script>
                alert('Twoje konto jest zablokowane do wykonania transakcji.');
                window.location.href = 'przelew.html';
              </script>";
        exit;
    }
} else {
    echo "Błąd w odczycie statusu płatności.";
    exit;
}

// Sprawdzamy, czy konto odbiorcy istnieje
$sql = "SELECT * FROM Users WHERE nr_konta = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $numer_konta);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Jeśli konto nie istnieje, wyświetl komunikat o błędzie
    echo "<script>
            alert('Podane konto odbiorcy nie istnieje.');
            window.location.href = 'przelew.html';
          </script>";
} else {
    // Sprawdzamy status płatności odbiorcy
    $row_receiver = $result->fetch_assoc();
    $receiver_id = $row_receiver['id'];

    $sql_receiver_status = "SELECT status_platnosci FROM UserActivity WHERE user_id = ?";
    $stmt_receiver_status = $conn->prepare($sql_receiver_status);
    $stmt_receiver_status->bind_param("i", $receiver_id);
    $stmt_receiver_status->execute();
    $result_receiver_status = $stmt_receiver_status->get_result();

    if ($result_receiver_status->num_rows > 0) {
        $row_receiver_status = $result_receiver_status->fetch_assoc();
        $receiver_status = $row_receiver_status['status_platnosci'];

        if ($receiver_status == 'Zablokowany') {
            echo "<script>
                    alert('Nie można wykonać przelewu. Konto odbiorcy jest zablokowane.');
                    window.location.href = 'przelew.html';
                  </script>";
            exit;
        }
    } else {
        echo "Błąd w odczycie statusu płatności odbiorcy.";
        exit;
    }

    // Sprawdzamy saldo aktualnego użytkownika
    $sql_sender = "SELECT * FROM Users WHERE id = ?";
    $stmt_sender = $conn->prepare($sql_sender);
    $stmt_sender->bind_param("i", $user_id);
    $stmt_sender->execute();
    $result_sender = $stmt_sender->get_result();
    $row_sender = $result_sender->fetch_assoc();
    $current_balance_sender = $row_sender['stan_konta'];

    if ($kwota > $current_balance_sender) {
        // Jeśli saldo jest niewystarczające
        echo "<script>
                alert('Brak wystarczających środków na koncie.');
                window.location.href = 'przelew.html';
              </script>";
    } else {
        // Zmniejszamy saldo nadawcy
        $new_balance_sender = $current_balance_sender - $kwota;
        $sql_update_sender = "UPDATE Users SET stan_konta = ? WHERE id = ?";
        $stmt_update_sender = $conn->prepare($sql_update_sender);
        $stmt_update_sender->bind_param("di", $new_balance_sender, $user_id);
        $stmt_update_sender->execute();

        // Zwiększamy saldo odbiorcy
        $new_balance_receiver = $row_receiver['stan_konta'] + $kwota;

        $sql_update_receiver = "UPDATE Users SET stan_konta = ? WHERE nr_konta = ?";
        $stmt_update_receiver = $conn->prepare($sql_update_receiver);
        $stmt_update_receiver->bind_param("ds", $new_balance_receiver, $numer_konta);
        $stmt_update_receiver->execute();

        // Zapisujemy transakcję z opisem i numerem konta odbiorcy
        $sql_transaction = "INSERT INTO TransactionHistory (user_id, kwota, typ_transakcji, opis, numer_konta_odbiorcy) VALUES (?, ?, 'Przelew', ?, ?)";
        $stmt_transaction = $conn->prepare($sql_transaction);
        $stmt_transaction->bind_param("idsd", $user_id, $kwota, $opis, $numer_konta);
        $stmt_transaction->execute();

        // Potwierdzenie przelewu
        echo "<script>
                alert('Przelew został wykonany pomyślnie.');
                window.location.href = 'menu.php';
              </script>";
    }
}

$conn->close();
?>
