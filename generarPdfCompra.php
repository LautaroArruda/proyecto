<?php
include("conexion/conexion.php");
include("funciones/funciones.php");
require_once('fpdf/fpdf.php');
//controla que se genere el pdf despues de generar la compra por si no lo guarde
if (isset($_GET['id_compra'])) {
    $id_compra = intval($_GET['id_compra']);
    generarPDFCompra($id_compra);
} else {
    die("ID de compra no proporcionado.");
}
?>
