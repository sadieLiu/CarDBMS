<?php
function getDB(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;

    $host = getenv('DB_HOST') ?: 'db';
    $name = getenv('DB_NAME') ?: 'USEDCARS';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: 'root';

    $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}
