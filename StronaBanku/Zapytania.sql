CREATE DATABASE BankDB;

USE BankDB;

CREATE TABLE Users (
    id INT IDENTITY(1,1) PRIMARY KEY,
    nazwa_uzytkownika VARCHAR(255),
    email VARCHAR(255),
    haslo VARCHAR(255),
    stan_konta DECIMAL(10, 2) DEFAULT 0,
    nr_konta VARCHAR(20) DEFAULT (CONCAT('PL', RIGHT('0000000000' + CAST(ABS(CHECKSUM(NEWID())) AS VARCHAR(10)), 10)))
);

CREATE TABLE TransactionHistory (
    id INT IDENTITY(1,1) PRIMARY KEY,
    user_id INT,
    kwota DECIMAL(10, 2),
    typ_transakcji VARCHAR(255),
    data_transakcji DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(id)
);

CREATE TABLE UserActivity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nr_konta CHAR(4) NOT NULL,
    status_konta ENUM('Aktywny', 'Zablokowany') NOT NULL,
    status_platnosci ENUM('Aktywny', 'Zablokowany') NOT NULL,
    FOREIGN KEY (user_id) REFERENCES Users(id)
);

