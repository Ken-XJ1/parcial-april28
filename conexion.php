<?php
$host = "localhost";
$usuario = "root";
$contrasena = "";
$basedatos = "parciala";

try {
    $conexion = new PDO("mysql:host=$host;dbname=$basedatos", $usuario, $contrasena);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Conexión fallida: " . $e->getMessage());
}
?>
