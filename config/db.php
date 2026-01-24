<?php
// config/db.php

$host = 'sql308.infinityfree.com';
$db_name = 'if0_40983741_destellodeoro18k';
$username = 'if0_40983741';
$password = 'SdT2vqAaxmr'; // Por defecto en XAMPP es vacío

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    // Configurar el modo de error de PDO a excepción
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // En producción, no mostrar el error detallado
    die("Error de conexión: " . $e->getMessage());
}
?>
