<?php

$dataSourceName = 'mysql:host=localhost;port=3306;dbname=payroll;charset=utf8mb4';

$username = 'root';
$password = '';

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
];

try {
    $pdo = new PDO(
        $dataSourceName,
        $username,
        $password,
        $options
    );

    $pdo->exec('SET time_zone = "Asia/Manila"');

} catch (PDOException $exception) {
    error_log('Database Connection Error: Unable to connect to the database. ' .
              'Exception Message: ' . $exception->getMessage());
}
