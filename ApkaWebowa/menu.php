<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu</title>

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

        .account-card {
            max-width: 500px;
            margin: 30px auto;
            background-color: #ffffff;
            padding: 20px 25px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .account-card h3 {
            margin-top: 0;
            margin-bottom: 15px;
        }

        .account-card p {
            margin: 6px 0;
        }

        .error-box {
            max-width: 500px;
            margin: 20px auto;
            padding: 12px 16px;
            background: #ffe5e5;
            color: #a30000;
            border-radius: 8px;
            border: 1px solid #ff9999;
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
        Pulpit
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

    <!-- Błędy / info -->
    <div id="error-box" class="error-box" style="display:none;"></div>
    <p id="info"></p>

    <!-- Okienko z danymi konta -->
    <div class="account-card">
        <h3>Dane Konta</h3>
        <p><strong>Nazwa użytkownika:</strong> <span id="user-name">Ładowanie...</span></p>
        <p><strong>Nr Konta:</strong> <span id="account-number">Ładowanie...</span></p>
        <p><strong>Saldo:</strong> <span id="balance">Ładowanie...</span></p>
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

        const info        = document.getElementById("info");
        const errorBox    = document.getElementById("error-box");
        const nameSpan    = document.getElementById("user-name");
        const accountSpan = document.getElementById("account-number");
        const balanceSpan = document.getElementById("balance");

        async function loadDashboard() {
            info.style.color = "black";
            info.textContent = "Ładowanie danych konta...";
            errorBox.style.display = "none";
            errorBox.textContent = "";

            try {
                const data = await ApiClient.getProfile();
                console.log("Dane z /users_me.php:", data);

                const username =
                    data.name ||
                    data.nazwa_uzytkownika ||
                    data.username ||
                    "Użytkownik";

                const accountNumber =
                    data.account_number ||
                    data.nr_konta ||
                    data.accountNumber ||
                    "----";

                const balanceRaw =
                    typeof data.balance === "number"
                        ? data.balance
                        : (data.stan_konta || data.saldo || 0);

                const balance = parseFloat(balanceRaw).toFixed(2) + " PLN";

                nameSpan.textContent    = username;
                accountSpan.textContent = accountNumber;
                balanceSpan.textContent = balance;

                info.textContent = "";
            } catch (err) {
                console.error(err);
                info.style.color = "red";
                info.textContent = "Nie udało się załadować danych konta.";
                nameSpan.textContent    = "Błąd";
                accountSpan.textContent = "Błąd";
                balanceSpan.textContent = "Błąd";

                errorBox.textContent = err.message || "Błąd połączenia z API.";
                errorBox.style.display = "block";
            }
        }

        loadDashboard();
    </script>
</body>
</html>
