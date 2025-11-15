<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pomoc</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
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
            background-color: #007bff;
            color: white;
            text-align: right;
        }

        .bot-message {
            background-color: #333;
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

        .chatbox.button:hover {
            background-color: #1a8cff;
        }

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

        .chat-container {
            max-height: 400px;
            overflow-y: auto;
            padding: 10px;
            background-color: #ffffff;
            border-radius: 10px;
        }

        #info {
            margin-top: 10px;
            text-align: center;
        }
    </style>
</head>
<body>

    <!-- Pasek nawigacyjny -->
    <div class="navbar">
        <div class="logo">
            <a href="bank.html">
                <img src="assets/logo2.png" alt="BankSystem" style="height: 50px;">
            </a>
        </div>
        <div class="navbar-right">
            <button type="button" onclick="handleLogout()">Wyloguj</button>
        </div>
    </div>

    <!-- Nagłówek -->
    <div class="header">
        Pomoc
    </div>

    <!-- Przyciski nawigacyjne -->
    <div class="buttons-container">
        <button class="button" onclick="window.location.href='menu.php'">Panel</button>

        <!-- Dropdown Transakcje -->
        <div class="dropdown">
            <button class="button">Transakcje</button>
            <div class="dropdown-content">
                <a href="przelew.php">Przelew</a>
                <a href="wyplata.php">Wypłata</a>
            </div>
        </div>

        <!-- Dropdown Pomoc -->
        <div class="dropdown">
            <button class="button">Pomoc</button>
            <div class="dropdown-content">
                <a href="pomoc.php">Chatbot</a>
                <a href="zmiana_hasla.php">Zmień hasło</a>
            </div>
        </div>

        <!-- Dropdown Bezpieczeństwo -->
        <div class="dropdown">
            <button class="button">Bezpieczeństwo</button>
            <div class="dropdown-content">
                <a href="alerty.php">Alerty</a>
                <a href="twofa.php">2FA – bezpieczeństwo logowania</a>
            </div>
        </div>

        <!-- Historia -->
        <button class="button" onclick="window.location.href='historia.php'">Historia transakcji</button>
    </div>

    <!-- Chatbot -->
    <div class="chatbox-container">
        <div class="chatbox" id="chatbox">
        </div>
        <div>
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
        <p id="info"></p>
    </div>

    <footer>
        <div class="container">
            <p>&copy; Wszystkie prawa zastrzeżone.</p>
        </div>
    </footer>

    <script src="js/apiClient.js"></script>
    <script>
        if (!ApiClient.getAccessToken()) {
            window.location.href = "login.html";
        }

        function handleLogout() {
            ApiClient.logout();
        }

        const info = document.getElementById("info");
        const chatbox = document.getElementById("chatbox");
        const selectInput = document.getElementById("userInput");

        let userName = "Użytkownik";

        async function loadUserName() {
            try {
                const data = await ApiClient.getProfile();
                const username =
                    data.name ||
                    data.username ||
                    data.nazwa_uzytkownika ||
                    null;

                if (username) {
                    userName = username;
                }
            } catch (err) {
                console.error("Błąd podczas pobierania imienia użytkownika:", err);
            }
        }

        loadUserName();

        function appendMessage(text, isUser = false) {
            const msg = document.createElement("div");
            msg.classList.add("message");
            msg.classList.add(isUser ? "user-message" : "bot-message");
            text = text.replace(/\s+/g, " ");
            msg.textContent = text;
            chatbox.appendChild(msg);
            chatbox.scrollTop = chatbox.scrollHeight;
        }

        function sendMessage() {
            const userInput = selectInput.value;
            if (!userInput) return;

            appendMessage(`${userName}: ${userInput}`, true);

            let botText = "Chatbot: Przepraszam, nie rozumiem tego pytania.";

            if (userInput === "Jak wykonać przelew") {
                botText = 'Chatbot: Aby wykonać przelew, przejdź do sekcji "Przelew" w menu i wypełnij formularz.';
            } else if (userInput === "Zmiana hasła") {
                botText = 'Chatbot: Aby zmienić hasło, kliknij "Zmień hasło" w sekcji "Pomoc" i podaj stare oraz nowe hasło.';
            } else if (userInput === "Gdzie jest pomoc") {
                botText = 'Chatbot: Znajdziesz pomoc klikając w zakładkę "Pomoc" na pasku nawigacyjnym.';
            } else if (userInput === "Czym jest saldo") {
                botText = "Chatbot: Saldo to kwota środków dostępnych aktualnie na Twoim koncie.";
            } else if (userInput === "Co to jest przelew") {
                botText = "Chatbot: Przelew to operacja polegająca na przesłaniu pieniędzy z Twojego konta na inne konto.";
            } else if (userInput === "Co zrobić po błędzie") {
                botText = "Chatbot: Jeśli wystąpi błąd przy przelewie, sprawdź dane odbiorcy, historię operacji i skontaktuj się z bankiem, jeśli problem się powtarza.";
            } else if (userInput === "Jak dodać odbiorcę") {
                botText = 'Chatbot: W tej wersji aplikacji odbiorca jest podawany ręcznie przy każdym przelewie – wpisz numer konta w formularzu "Przelew".';
            } else if (userInput === "Gdzie sprawdzić historię") {
                botText = 'Chatbot: Historię transakcji znajdziesz w sekcji "Historia transakcji" dostępnej z pulpitu.';
            }

            appendMessage(botText, false);
            selectInput.value = "";
        }
    </script>
</body>
</html>
