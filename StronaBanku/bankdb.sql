-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Lis 28, 2024 at 06:57 PM
-- Wersja serwera: 10.4.32-MariaDB
-- Wersja PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bankdb`
--

DELIMITER $$
--
-- Procedury
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `GenerateUniqueAccountNumber` (OUT `accountNumber` CHAR(4))   BEGIN
    DECLARE isUnique BOOLEAN DEFAULT FALSE;
    DECLARE generatedNumber CHAR(4);
    
    WHILE NOT isUnique DO
        SET generatedNumber = LPAD(FLOOR(RAND() * 10000), 4, '0'); -- Losowy numer 4-cyfrowy
        IF NOT EXISTS (SELECT 1 FROM Users WHERE nr_konta = generatedNumber) THEN
            SET isUnique = TRUE;
            SET accountNumber = generatedNumber;
        END IF;
    END WHILE;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `transactionhistory`
--

CREATE TABLE `transactionhistory` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `kwota` decimal(10,2) NOT NULL,
  `typ_transakcji` varchar(255) NOT NULL,
  `data_transakcji` datetime DEFAULT current_timestamp(),
  `opis` varchar(255) NOT NULL,
  `numer_konta_odbiorcy` varchar(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactionhistory`
--

INSERT INTO `transactionhistory` (`id`, `user_id`, `kwota`, `typ_transakcji`, `data_transakcji`, `opis`, `numer_konta_odbiorcy`) VALUES
(75, 19, 12.00, 'Przelew', '2024-11-28 17:44:08', 'Testowy przelew', '2311'),
(76, 19, 4000.00, 'Wypłata', '2024-11-28 17:44:15', '', '');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `useractivity`
--

CREATE TABLE `useractivity` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nr_konta` char(4) NOT NULL,
  `status_konta` enum('Aktywny','Zablokowany') NOT NULL,
  `status_platnosci` enum('Aktywny','Zablokowany') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `useractivity`
--

INSERT INTO `useractivity` (`id`, `user_id`, `nr_konta`, `status_konta`, `status_platnosci`) VALUES
(1, 1, '0001', 'Aktywny', 'Zablokowany'),
(7, 19, '7876', 'Aktywny', 'Aktywny'),
(13, 25, '2311', 'Aktywny', 'Aktywny');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nazwa_uzytkownika` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `haslo` varchar(255) NOT NULL,
  `stan_konta` decimal(10,2) DEFAULT 0.00,
  `nr_konta` char(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nazwa_uzytkownika`, `email`, `haslo`, `stan_konta`, `nr_konta`) VALUES
(1, 'Admin', 'admin@example.com', 'admin', 0.00, '0001'),
(19, 'Test', 'test-email@wp.pl', 'test', 93380.88, '7876'),
(25, 'Krystian', 'krycha@wp.pl', '12345', 412.78, '2311');

--
-- Wyzwalacze `users`
--
DELIMITER $$
CREATE TRIGGER `BeforeInsertUser` BEFORE INSERT ON `users` FOR EACH ROW BEGIN
    CALL GenerateUniqueAccountNumber(@newAccountNumber);
    SET NEW.nr_konta = @newAccountNumber;
END
$$
DELIMITER ;

--
-- Indeksy dla zrzutów tabel
--

--
-- Indeksy dla tabeli `transactionhistory`
--
ALTER TABLE `transactionhistory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeksy dla tabeli `useractivity`
--
ALTER TABLE `useractivity`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeksy dla tabeli `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nr_konta` (`nr_konta`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `transactionhistory`
--
ALTER TABLE `transactionhistory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT for table `useractivity`
--
ALTER TABLE `useractivity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `transactionhistory`
--
ALTER TABLE `transactionhistory`
  ADD CONSTRAINT `transactionhistory_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `useractivity`
--
ALTER TABLE `useractivity`
  ADD CONSTRAINT `useractivity_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
