<?php

$path = __DIR__ . '/../../.env';
// var_dump(scandir($path));
if (!file_exists($path)) {
    throw new Exception(".env file not found");
}

$lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    if (strpos(trim($line), '#') === 0) {
        continue;
    }

    list($name, $value) = explode('=', $line, 2);
    $name = trim($name);
    $value = trim($value, "\"'");

    putenv("$name=$value");
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
}

// Agora define as constantes usando os valores do .env
define("DB_HOST_MIGRACAO", getenv('DB_HOST_MIGRACAO'));
define("DB_USERNAME_MIGRACAO", getenv('DB_USERNAME_MIGRACAO'));
define("DB_PASSWORD_MIGRACAO", getenv('DB_PASSWORD_MIGRACAO'));
define("DB_DATABASE_MIGRACAO", getenv('DB_DATABASE_MIGRACAO'));
