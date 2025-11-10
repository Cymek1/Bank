<?php
// Połączenie z bazą danych
include 'db_connect.php'; // Plik z połączeniem do bazy danych

// Zakładamy, że użytkownik jest zalogowany i jego ID jest przechowywane w sesji
session_start();
$userId = $_SESSION['user_id']; // ID użytkownika z sesji

// Pobranie imienia użytkownika z bazy
$query = "SELECT nazwa_uzytkownika FROM Users WHERE id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$userId]);
$userName = $stmt->fetchColumn(); // Pobranie imienia użytkownika
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pomoc</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Stylizacja dla chatbota */
        .chatbox-container {
            margin: 20px auto;
            max-width: 600px;
            width: 100%;
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .chatbox {
            height: 300px;
            overflow-y: auto;
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            background-color: #f9f9f9;
        }

        .chatbox .message {
            margin: 10px 0;
            padding: 8px;
            border-radius: 5px;
            display: inline-block;
            max-width: 80%;
        }

        .user-message {
            background-color: #007bff; /* Nowy kolor dla wiadomości użytkownika */
            color: white;
            text-align: right;
        }

        .bot-message {
            background-color: #333; /* Nowy kolor dla wiadomości chatbota */
            color: white;
        }

        .chatbox select, .chatbox button {
            width: 60%;
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .chatbox button {
            padding: 8px 15px;
            background-color: #4da6ff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .chatbox button:hover {
            background-color: #1a8cff;
        }

        /* Stylizacja formularza zmiany hasła */
        .change-password-form {
            margin: 20px auto;
            width: 100%;
            max-width: 400px;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .change-password-form label {
            font-size: 16px;
            margin-bottom: 8px;
        }

        .change-password-form input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .change-password-form button {
            background-color: #ff4d4d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .change-password-form button:hover {
            background-color: #ff3333;
        }

        /* Modal */
        .modal {
            display: none; /* Ukryte na początku */
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
            padding-top: 60px;
        }

        /* Modal content */
        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 20px;
            border-radius: 10px;
            width: 300px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        /* Close button */
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        /* Styl dla wiadomości użytkownika */
.message-user {
    padding: 10px;
    margin: 10px 0;
    background-color: #f0f0f0; /* Kolor tła dla wiadomości użytkownika */
    border-radius: 10px; /* Zaokrąglenie rogów */
    text-align: left;
    color: black; /* Kolor tekstu na czarny */
    font-size: 16px;
}

/* Styl dla wiadomości chatbota */
.message-chatbot {
    padding: 10px;
    margin: 10px 0;
    background-color: #d6e4ff; /* Kolor tła dla wiadomości chatbota */
    border-radius: 10px; /* Zaokrąglenie rogów */
    text-align: left;
    color: black; /* Kolor tekstu na czarny */
    font-size: 16px;
}

/* Styl dla okna chatu */
.chat-container {
    max-height: 400px;
    overflow-y: auto;
    padding: 10px;
    background-color: #ffffff;
    border-radius: 10px;
}
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
            <form action="bank.html" method="GET">
                <button type="submit">Wyloguj</button>
            </form>
        </div>
    </div>

    <!-- Nagłówek -->
    <div class="header">
        Pomoc
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

    <!-- Chatbot -->
    <div class="chatbox-container">
        <div class="chatbox" id="chatbox">
            <!-- Wiadomości będą wyświetlane tutaj -->
        </div>
        <div class="">
            <!-- Lista rozwijana z pytaniami -->
            <select id="userInput">
                <option value="">Wybierz pytanie...</option>
                <option value="Jak wykonać przelew">Jak wykonać przelew?</option>
                <option value="Zmiana hasła">Jak zmienić hasło?</option>
                <option value="Gdzie jest pomoc">Gdzie znaleźć pomoc?</option>
                <option value="Czym jest saldo">Co to jest saldo?</option>
                <option value="Co to jest przelew">Co to jest przelew?</option>
                <option value="Co zrobić po błędzie">Co zrobić po błędzie przy przelewie?</option>
                <option value="Jak dodać odbiorcę">Jak dodać odbiorcę przelewu?</option>
                <option value="Gdzie sprawdzić historię">Gdzie mogę sprawdzić historię transakcji?</option>
            </select>
            <button class="button" onclick="sendMessage()">Wyślij</button>
        </div>
    </div>

    <script>
        // Ustawienie imienia użytkownika z PHP
        const userName = "<?php echo htmlspecialchars($userName); ?>"; // Zastąp tym imieniem zalogowanego użytkownika

        // Prosty chatbot - odpowiedzi na wybrane pytania
        function sendMessage() {
            const userInput = document.getElementById('userInput').value;
            const chatbox = document.getElementById('chatbox');

            if (!userInput) return; // Jeśli nie wybrano pytania, nic nie robić

            // Dodaj wiadomość użytkownika
            const userMessage = document.createElement('div');
            userMessage.classList.add('message', 'user-message');
            userMessage.textContent = `${userName}: ${userInput}`;
            chatbox.appendChild(userMessage);

            // Odpowiedź chatbota
            const botMessage = document.createElement('div');
            botMessage.classList.add('message', 'bot-message');

            if (userInput === 'Jak wykonać przelew') {
                botMessage.textContent = 'Chatbot: Aby wykonać przelew, przejdź do sekcji "Przelew" w menu.';
            } else if (userInput === 'Zmiana hasła') {
                botMessage.textContent = 'Chatbot: Aby zmienić hasło, kliknij przycisk "Zmień hasło" i wprowadź nowe hasło.';
            } else if (userInput === 'Gdzie jest pomoc') {
                botMessage.textContent = 'Chatbot: Możesz znaleźć pomoc klikając na przycisk "Pomoc" w górnym menu.';
            } else if (userInput === 'Czym jest saldo') {
                botMessage.textContent = 'Chatbot: Saldo to suma środków dostępnych na Twoim koncie bankowym.';
            } else if (userInput === 'Co to jest przelew') {
                botMessage.textContent = 'Chatbot: Przelew to transfer środków z jednego konta na inne w celu zapłacenia za usługę lub towar.';
            } else if (userInput === 'Co zrobić po błędzie') {
                botMessage.textContent = 'Chatbot: Jeśli wystąpił błąd przy przelewie, skontaktuj się z obsługą klienta.';
            } else if (userInput === 'Jak dodać odbiorcę') {
                botMessage.textContent = 'Chatbot: Aby dodać odbiorcę, przejdź do sekcji "Odbiorcy" i wprowadź dane odbiorcy.';
            } else if (userInput === 'Gdzie sprawdzić historię') {
                botMessage.textContent = 'Chatbot: Historię transakcji możesz sprawdzić w sekcji "Historia transakcji" w swoim panelu.';
            } else {
                botMessage.textContent = 'Chatbot: Przepraszam, nie rozumiem tego pytania.';
            }

            chatbox.appendChild(botMessage);
            chatbox.scrollTop = chatbox.scrollHeight; // Przewijanie do najnowszej wiadomości

            // Czyszczenie inputa
            document.getElementById('userInput').value = '';
        }

    </script>
    <!-- Stopka -->
    <footer>
        <div class="container">
            <p>&copy; Wszystkie prawa zastrzeżone.</p>
        </div>
    </footer>
</body>
</html>
