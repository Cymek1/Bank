<?php
// historia.php – front tylko jako HTML, bez logiki PHP (możesz usunąć stare rzeczy z sesją)
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historia transakcji</title>
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

        table {
            width: 90%;
            margin: 0 auto;
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

        .pagination {
            margin-top: 20px;
            text-align: center;
        }

        .pagination button {
            margin: 0 5px;
        }

        #info {
            text-align: center;
            margin-top: 10px;
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
        Historia transakcji
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

    <!-- Tabela historii transakcji -->
    <div class="transactions">
        <table>
            <thead>
                <tr>
                    <th>Kwota</th>
                    <th>Typ transakcji</th>
                    <th>Data</th>
                    <th>Opis</th>
                </tr>
            </thead>
            <tbody id="transactions-body">
                <!-- Wiersze transakcji wstrzykujemy z JS -->
            </tbody>
        </table>

        <div class="pagination">
            <button id="prev-page" class="button">Poprzednia</button>
            <span id="page-info">Strona 1</span>
            <button id="next-page" class="button">Następna</button>
        </div>
    </div>

    <p id="info"></p>

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

        const info = document.getElementById("info");
        const tbody = document.getElementById("transactions-body");
        const prevBtn = document.getElementById("prev-page");
        const nextBtn = document.getElementById("next-page");
        const pageInfo = document.getElementById("page-info");

        let currentPage = 1;
        let hasMore = true;

        async function loadTransactions(page = 1) {
            info.style.color = "black";
            info.textContent = "Ładowanie historii...";
            tbody.innerHTML = "";

            try {
                const data = await ApiClient.getTransactions(page);
                console.log("Dane z /transactions.php:", data);

                // backend: { items: [...], hasMore: bool }
                const items = data.items || data.transactions || [];

                if (!items.length) {
                    tbody.innerHTML = "<tr><td colspan='4'>Brak transakcji.</td></tr>";
                    hasMore = false;
                } else {
                    hasMore = true;
                    for (const tx of items) {
                        const amount = tx.kwota ?? tx.amount ?? 0;
                        const type = tx.typ_transakcji ?? tx.type ?? "";
                        const date =
                            tx.data_transakcji ??
                            tx.created_at ??
                            tx.date ??
                            "";
                        const desc =
                            tx.opis ??
                            tx.title ??
                            tx.description ??
                            "";

                        const tr = document.createElement("tr");

                        const tdAmount = document.createElement("td");
                        tdAmount.textContent = parseFloat(amount).toFixed(2) + " PLN";

                        const tdType = document.createElement("td");
                        tdType.textContent = type;

                        const tdDate = document.createElement("td");
                        tdDate.textContent = date;

                        const tdDesc = document.createElement("td");
                        tdDesc.textContent = desc;

                        tr.appendChild(tdAmount);
                        tr.appendChild(tdType);
                        tr.appendChild(tdDate);
                        tr.appendChild(tdDesc);

                        tbody.appendChild(tr);
                    }
                }

                currentPage = page;
                pageInfo.textContent = "Strona " + currentPage;
                info.textContent = "";

                if (typeof data.has_more !== "undefined") {
                    hasMore = !!data.has_more;
                } else if (typeof data.hasMore !== "undefined") {
                    hasMore = !!data.hasMore;
                }

            } catch (err) {
                console.error(err);
                tbody.innerHTML = "<tr><td colspan='4'>Błąd ładowania historii.</td></tr>";
                info.style.color = "red";
                info.textContent = err.message || "Nie udało się pobrać historii transakcji.";
            }
        }

        prevBtn.addEventListener("click", () => {
            if (currentPage > 1) {
                loadTransactions(currentPage - 1);
            }
        });

        nextBtn.addEventListener("click", () => {
            if (hasMore) {
                loadTransactions(currentPage + 1);
            }
        });

        loadTransactions(1);
    </script>
</body>
</html>
