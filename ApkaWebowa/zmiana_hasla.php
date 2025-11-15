<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zmiana hasła</title>
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
        Zmiana hasła
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

    <!-- Formularz zmiany hasła -->
    <div class="login-form">
        <form id="change-password-form">
            <label for="old_password">Stare hasło:</label>
            <input
                type="password"
                id="old_password"
                name="old_password"
                placeholder="Wprowadź aktualne hasło"
                required
            ><br><br>

            <label for="new_password">Nowe hasło:</label>
            <input
                type="password"
                id="new_password"
                name="new_password"
                placeholder="Wprowadź nowe hasło"
                required
            ><br><br>

            <label for="new_password_repeat">Powtórz nowe hasło:</label>
            <input
                type="password"
                id="new_password_repeat"
                name="new_password_repeat"
                placeholder="Powtórz nowe hasło"
                required
            ><br><br>

            <button type="submit">Zmień hasło</button>
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
        // Brak tokena → do logowania
        if (!ApiClient.getAccessToken()) {
            window.location.href = "login.html";
        }

        function handleLogout() {
            ApiClient.logout();
        }

        const form = document.getElementById("change-password-form");
        const info = document.getElementById("info");

        form.addEventListener("submit", async (e) => {
            e.preventDefault();
            info.style.color = "black";
            info.textContent = "Przetwarzanie zmiany hasła...";

            const oldPassword = document.getElementById("old_password").value;
            const newPassword = document.getElementById("new_password").value;
            const newPasswordRepeat = document.getElementById("new_password_repeat").value;

            if (!oldPassword || !newPassword || !newPasswordRepeat) {
                info.style.color = "red";
                info.textContent = "Uzupełnij wszystkie pola.";
                return;
            }

            if (newPassword !== newPasswordRepeat) {
                info.style.color = "red";
                info.textContent = "Nowe hasła nie są takie same.";
                return;
            }

            if (newPassword.length < 6) {
                info.style.color = "red";
                info.textContent = "Nowe hasło powinno mieć co najmniej 6 znaków.";
                return;
            }

            try {
                const data = await ApiClient.changePassword(oldPassword, newPassword);
                console.log("Odpowiedź z /auth_change_password.php:", data);

                info.style.color = "green";
                if (data.message) {
                    info.textContent = data.message;
                } else {
                    info.textContent = "Hasło zostało zmienione.";
                }

                // (opcjonalnie) wylogowanie po zmianie hasła:
                // setTimeout(() => ApiClient.logout(), 2000);

                form.reset();
            } catch (err) {
                console.error(err);
                info.style.color = "red";
                info.textContent = err.message || "Nie udało się zmienić hasła.";
            }
        });
    </script>
</body>
</html>
