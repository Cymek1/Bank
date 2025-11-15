<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Przelew</title>
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
        Przelew
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


    <!-- Formularz przelewu -->
    <div class="login-form">
        <form id="transfer-form">
            <label for="numer_konta">Numer konta odbiorcy (4 cyfry):</label>
            <input
                type="text"
                id="numer_konta"
                name="numer_konta"
                placeholder="Wprowadź numer konta (4 cyfry)"
                maxlength="4"
                pattern="[0-9]{4}"
                required
            ><br><br>

            <label for="kwota">Kwota:</label>
            <input
                type="number"
                id="kwota"
                name="kwota"
                placeholder="Wprowadź kwotę"
                step="0.01"
                min="0.01"
                required
            ><br><br>

            <label for="opis">Opis przelewu:</label>
            <input
                type="text"
                id="opis"
                name="opis"
                placeholder="Wprowadź opis przelewu"
                required
            ><br><br>

            <button type="submit">Wyślij przelew</button>
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

        const form = document.getElementById("transfer-form");
        const info = document.getElementById("info");

        form.addEventListener("submit", async (e) => {
            e.preventDefault();
            info.style.color = "black";
            info.textContent = "Wysyłanie przelewu...";

            const numerKonta = document.getElementById("numer_konta").value.trim();
            const kwotaStr    = document.getElementById("kwota").value;
            const opis        = document.getElementById("opis").value.trim();

            const kwota = parseFloat(kwotaStr.toString().replace(",", "."));

            if (!numerKonta || numerKonta.length !== 4 || !/^[0-9]{4}$/.test(numerKonta)) {
                info.style.color = "red";
                info.textContent = "Podaj poprawny, 4-cyfrowy numer konta odbiorcy.";
                return;
            }

            if (isNaN(kwota) || kwota <= 0) {
                info.style.color = "red";
                info.textContent = "Podaj poprawną kwotę przelewu.";
                return;
            }

            if (!opis) {
                info.style.color = "red";
                info.textContent = "Podaj opis przelewu.";
                return;
            }

            try {
                const data = await ApiClient.transfer(numerKonta, opis, kwota);
                console.log("Odpowiedź z /transactions_transfer.php:", data);

                info.style.color = "green";
                if (data.message) {
                    info.textContent = data.message;
                } else {
                    info.textContent = "Przelew został wykonany pomyślnie.";
                }

                form.reset();
            } catch (err) {
                console.error(err);
                info.style.color = "red";
                info.textContent = err.message || "Nie udało się wykonać przelewu.";
            }
        });
    </script>
</body>
</html>
