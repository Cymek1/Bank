<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerty</title>
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

        table {
            width: 90%;
            margin: 20px auto;
            border-collapse: collapse;
        }

        table th, table td {
            padding: 8px 10px;
            border: 1px solid #ccc;
            text-align: left;
        }

        table th {
            background-color: #f2f2f2;
        }

        .alert-unread {
            font-weight: bold;
            background-color: #fffbe6;
        }

        .filters {
            width: 90%;
            margin: 10px auto;
            text-align: right;
        }

        .filters button {
            margin-left: 5px;
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
        Alerty
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

    <p id="info"></p>

    <div class="filters">
        <span>Filtr:</span>
        <button id="btn-all" class="button">Wszystkie</button>
        <button id="btn-unread" class="button">Nieprzeczytane</button>
    </div>

    <table>
        <thead>
            <tr>
                <th>Wiadomość</th>
                <th>Data utworzenia</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody id="alerts-body">
            <!-- wiersze wstrzykiwane z JS -->
        </tbody>
    </table>

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

        const info      = document.getElementById("info");
        const tbody     = document.getElementById("alerts-body");
        const btnAll    = document.getElementById("btn-all");
        const btnUnread = document.getElementById("btn-unread");

        let currentFilter = "all"; // all | unread

        async function loadAlerts() {
            info.style.color = "black";
            info.textContent = "Ładowanie alertów...";
            tbody.innerHTML = "";

            try {
                const onlyUnread = currentFilter === "unread";
                const data = await ApiClient.listRecommendations(onlyUnread);
                console.log("Dane z /recommendations.php:", data);

                const items = data.items || [];

                if (!items.length) {
                    tbody.innerHTML = "<tr><td colspan='3'>Brak alertów.</td></tr>";
                    info.textContent = "";
                    return;
                }

                for (const row of items) {
                    const tr = document.createElement("tr");

                    const isUnread = !row.read_at;

                    if (isUnread) {
                        tr.classList.add("alert-unread");
                    }

                    const tdMsg = document.createElement("td");
                    tdMsg.textContent = row.message || "";

                    const tdCreated = document.createElement("td");
                    tdCreated.textContent = row.created_at || "";

                    const tdStatus = document.createElement("td");
                    tdStatus.textContent = isUnread ? "Nieprzeczytany" : "Przeczytany";

                    tr.appendChild(tdMsg);
                    tr.appendChild(tdCreated);
                    tr.appendChild(tdStatus);

                    // kliknięcie w nieprzeczytany alert → oznacz jako przeczytany
                    if (isUnread && row.id) {
                        tr.style.cursor = "pointer";
                        tr.title = "Kliknij, aby oznaczyć jako przeczytany";

                        tr.addEventListener("click", async () => {
                            try {
                                await ApiClient.markRecommendationRead(row.id);
                                await loadAlerts();
                            } catch (err) {
                                console.error(err);
                                info.style.color = "red";
                                info.textContent = err.message || "Nie udało się oznaczyć alertu jako przeczytany.";
                            }
                        });
                    }

                    tbody.appendChild(tr);
                }

                info.textContent = "";
            } catch (err) {
                console.error(err);
                info.style.color = "red";
                info.textContent = err.message || "Błąd podczas ładowania alertów.";
                tbody.innerHTML = "<tr><td colspan='3'>Nie udało się pobrać alertów.</td></tr>";
            }
        }

        btnAll.addEventListener("click", () => {
            currentFilter = "all";
            loadAlerts();
        });

        btnUnread.addEventListener("click", () => {
            currentFilter = "unread";
            loadAlerts();
        });

        loadAlerts();
    </script>
</body>
</html>
