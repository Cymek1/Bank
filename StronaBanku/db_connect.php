<?php
$host = 'localhost'; // Adres serwera bazy danych
$dbname = 'BankDB'; // Nazwa bazy danych
$username = 'root'; // Nazwa użytkownika bazy danych
$password = ''; // Hasło użytkownika bazy danych

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Połączenie nieudane: ' . $e->getMessage();
}
?>
