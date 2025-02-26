<?php

$servername = "localhost";
$username = "root";        
$password = "";           
$dbname = "corralon";      

$conexion = new mysqli($servername, $username, $password, $dbname);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}
?>
