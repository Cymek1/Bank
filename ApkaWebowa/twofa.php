<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uwierzytelnianie 2FA</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
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

        .twofa-card {
            max-width: 500px;
            margin: 30px auto;
            background-color: #fff;
            padding: 20px 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .twofa-card h3 {
            margin-top: 0;
            margin-bottom: 15px;
            text-align: center;
        }

        .twofa-row {
            margin: 10px 0;
        }

        .twofa-row strong {
            display: inline-block;
            width: 120px;
        }

        .twofa-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }

        .twofa-buttons button {
            min-width: 120px;
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
            <button type="button" class="button" onclick="handleLogout()">Wyloguj</button>
        </div>
    </div>

    <!-- Nagłówek -->
    <div class="header">
        Uwierzytelnianie dwuskładnikowe (2FA)
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

    <!-- Karta 2FA -->
    <div class="twofa-card">
        <h3>Ustawienia uwierzytelniania 2FA</h3>

        <p>
            Uwierzytelnianie dwuskładnikowe (2FA) dodaje dodatkową warstwę zabezpieczeń
            do Twojego konta. Po włączeniu, logowanie będzie wymagało dodatkowego kodu.
        </p>

        <div class="twofa-row">
            <strong>Status 2FA:</strong>
            <span id="twofa-status">Nieznany</span>
        </div>

        <div class="twofa-buttons">
            <button id="btn-enable" class="button">Włącz 2FA</button>
            <button id="btn-disable" class="button">Wyłącz 2FA</button>
        </div>

        <p id="info"></p>
    </div>

    <!-- Stopka -->
    <footer>
        <div class="container">
            <p>&copy; Wszystkie prawa zastrzeżone.</p>
        </div>
    </footer>

    <!-- Skrypt API -->
    <script src="js/apiClient.js"></script>

    <script>
        // Jeśli nie ma tokena – do logowania
        if (!ApiClient.getAccessToken()) {
            window.location.href = "login.html";
        }

        function handleLogout() {
            ApiClient.logout();
        }

        const info       = document.getElementById("info");
        const statusSpan = document.getElementById("twofa-status");
        const btnEnable  = document.getElementById("btn-enable");
        const btnDisable = document.getElementById("btn-disable");

        // Na start – status nieznany (nie mamy osobnego endpointu statusu 2FA)
        statusSpan.textContent = "Nieznany";

        // Używamy istniejącej globalnej stałej z apiClient.js
        const BASE = typeof API_BASE_URL !== "undefined"
            ? API_BASE_URL
            : "http://localhost/bank_api/api"; // awaryjnie

        async function callTwofaEndpoint(endpoint, mode) {
            info.style.color = "black";
            info.textContent = "Przetwarzanie...";

            const token = ApiClient.getAccessToken();
            if (!token) {
                window.location.href = "login.html";
                return;
            }

            try {
                const response = await fetch(BASE + endpoint, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Authorization": "Bearer " + token
                    },
                    body: JSON.stringify({})
                });

                let data = {};
                try {
                    data = await response.json();
                } catch (e) {
                    data = {};
                }

                if (!response.ok) {
                    const msg = data.message || data.error || "Błąd połączenia z API";
                    throw new Error(msg);
                }

                info.style.color = "green";
                info.textContent = data.message || "Operacja zakończona pomyślnie.";

                if (mode === "enable") {
                    statusSpan.textContent = "Włączone";
                } else if (mode === "disable") {
                    statusSpan.textContent = "Wyłączone";
                }

            } catch (err) {
                console.error(err);
                info.style.color = "red";
                info.textContent = err.message || "Nie udało się wykonać operacji.";
            }
        }

        btnEnable.addEventListener("click", () => {
            callTwofaEndpoint("/auth_2fa_enable.php", "enable");
        });

        btnDisable.addEventListener("click", () => {
            callTwofaEndpoint("/auth_2fa_disable.php", "disable");
        });
    </script>

</body>
</html>
