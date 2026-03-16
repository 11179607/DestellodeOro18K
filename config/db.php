<?php
// config/db.php

// Cargar variables locales si existen (no versionadas)
@include __DIR__ . '/env.php';

$host     = getenv('DB_HOST') ?: ($ENV_DB_HOST ?? null);
$db_name  = getenv('DB_NAME') ?: ($ENV_DB_NAME ?? null);
$username = getenv('DB_USER') ?: ($ENV_DB_USER ?? null);
$password = getenv('DB_PASSWORD') ?: ($ENV_DB_PASSWORD ?? null);

if (!$host || !$db_name || !$username || $password === null) {
    die("Error de configuración: define DB_HOST, DB_NAME, DB_USER y DB_PASSWORD en variables de entorno.");
}

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    // Configurar el modo de error de PDO a excepción
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Configurar zona horaria de Colombia (UTC-5)
    date_default_timezone_set('America/Bogota');
    // Configurar zona horaria en MySQL
    $conn->exec("SET time_zone = '-05:00'");
} catch(PDOException $e) {
    // En producción, no mostrar el error detallado
    die("Error de conexión: " . $e->getMessage());
}
?>
