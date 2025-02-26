<?php
include("conexion/conexion.php");
include("funciones/funciones.php");
require_once('fpdf/fpdf.php');
//controla que se genere el pdf despues de generar la venta por si lo lo guarde
if (isset($_GET['id_venta'])) {
    $id_venta = intval($_GET['id_venta']);
    generarPDF($id_venta);
} else {
    die("ID de venta no proporcionado.");
}
?>

