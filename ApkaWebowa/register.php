<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejestracja</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
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
            <button><a href="login.html">Logowanie</a></button>
            <button><a href="register.php">Rejestracja</a></button>
        </div>
    </div>

    <!-- Nagłówek -->
    <div class="header">
        Zarejestruj się
    </div>

    <!-- Formularz rejestracji -->
    <div class="register-form">
        <h2>Formularz rejestracyjny</h2>

        <form id="register-form">
            <label for="nazwa_uzytkownika">Nazwa użytkownika:</label><br>
            <input type="text" id="nazwa_uzytkownika" name="nazwa_uzytkownika" required><br><br>

            <label for="email">E-mail:</label><br>
            <input type="email" id="email" name="email" required><br><br>

            <label for="haslo">Hasło:</label><br>
            <input type="password" id="haslo" name="haslo" required><br><br>

            <label for="haslo_repeat">Powtórz hasło:</label><br>
            <input type="password" id="haslo_repeat" name="haslo_repeat" required><br><br>

            <input type="submit" value="Zarejestruj">
        </form>

        <p id="info"></p>

        <p>Masz już konto? <a href="login.html">Zaloguj się</a></p>
    </div>

    <!-- Stopka -->
    <footer>
        <div class="container">
            <p>&copy; Wszystkie prawa zastrzeżone.</p>
        </div>
    </footer>

    <script src="js/apiClient.js"></script>
    <script>
        const form = document.getElementById("register-form");
        const info = document.getElementById("info");

        form.addEventListener("submit", async (e) => {
            e.preventDefault();
            info.style.color = "black";
            info.textContent = "Przetwarzanie rejestracji...";

            const username = document.getElementById("nazwa_uzytkownika").value.trim();
            const email = document.getElementById("email").value.trim();
            const password = document.getElementById("haslo").value;
            const passwordRepeat = document.getElementById("haslo_repeat").value;

            if (!username || !email || !password || !passwordRepeat) {
                info.style.color = "red";
                info.textContent = "Uzupełnij wszystkie pola.";
                return;
            }

            if (password !== passwordRepeat) {
                info.style.color = "red";
                info.textContent = "Hasła nie są takie same.";
                return;
            }

            if (password.length < 6) {
                info.style.color = "red";
                info.textContent = "Hasło powinno mieć co najmniej 6 znaków.";
                return;
            }

            try {
                const data = await ApiClient.register(username, email, password);
                console.log("Odpowiedź z /auth_register.php:", data);

                info.style.color = "green";
                info.textContent = data.message || "Rejestracja zakończona sukcesem. Możesz się teraz zalogować.";

                setTimeout(() => {
                    window.location.href = "login.html";
                }, 2000);

                form.reset();
            } catch (err) {
                console.error(err);
                info.style.color = "red";
                info.textContent = err.message || "Nie udało się zarejestrować użytkownika.";
            }
        });
    </script>
</body>
</html>
