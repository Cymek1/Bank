<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wypłata</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        #message-container {
            display: none;
            margin: 20px;
            padding: 10px;
            border: 1px solid #ccc;
            background-color: #f9f9f9;
            color: #333;
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
        Wypłata
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

    <!-- Sekcja komunikatów -->
    <div id="message-container"></div>

    <!-- Formularz wypłaty -->
    <div class="login-form">
        <form id="withdraw-form">
            <label for="kwota">Kwota do wypłaty:</label>
            <input
                type="number"
                id="kwota"
                name="kwota"
                placeholder="Wprowadź kwotę"
                step="0.01"
                min="0.01"
                required
            ><br><br>

            <button type="submit">Wypłać</button>
        </form>

        <p id="info"></p>
    </div>

    <!-- Stopka -->
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

        const form   = document.getElementById("withdraw-form");
        const info   = document.getElementById("info");
        const msgBox = document.getElementById("message-container");

        form.addEventListener("submit", async (e) => {
            e.preventDefault();
            info.style.color = "black";
            info.textContent = "Przetwarzanie wypłaty...";
            msgBox.style.display = "none";
            msgBox.textContent = "";

            const kwotaStr = document.getElementById("kwota").value;
            const kwota    = parseFloat(kwotaStr.toString().replace(",", "."));

            if (isNaN(kwota) || kwota <= 0) {
                info.style.color = "red";
                info.textContent = "Podaj poprawną kwotę.";
                return;
            }

            try {
                const data = await ApiClient.withdraw(kwota);
                console.log("Odpowiedź z /transactions_withdraw.php:", data);

                info.style.color = "green";
                if (data.message) {
                    info.textContent = data.message;
                } else {
                    info.textContent = "Wypłata zakończona pomyślnie.";
                }

                msgBox.style.display = "block";
                msgBox.textContent = "Środki zostały wypłacone z konta.";
                form.reset();
            } catch (err) {
                console.error(err);
                info.style.color = "red";
                info.textContent = err.message || "Nie udało się wykonać wypłaty.";

                msgBox.style.display = "block";
                msgBox.textContent = "Wystąpił błąd podczas wypłaty.";
            }
        });
    </script>
</body>
</html>
