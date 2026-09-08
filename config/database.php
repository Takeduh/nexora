<?php

function getConnection(): PDO
{
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $host = 'localhost';
    $dbname = 'nexora_db';
    $username = 'root';
    $password = '';

    try {
        $connection = new PDO(
            "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (PDOException) {
        http_response_code(500);
        exit('Database connection failed. Check XAMPP MySQL and config/database.php.');
    }

    return $connection;
}

$pdo = getConnection();
