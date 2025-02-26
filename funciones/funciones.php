<?php
//carga de materiales
include("conexion/conexion.php");


// muestro los materiales no eliminados

function obtenerMateriales($conexion) {
    $query = "SELECT * FROM materiales WHERE eliminado = 0";
    return mysqli_query($conexion, $query);
}


//Verificar si un material ya existe y no esta eliminado.

function materialExiste($conexion, $nombre) {
    $query = "SELECT nombre FROM materiales WHERE nombre = '$nombre' AND eliminado = 0";
    $result = mysqli_query($conexion, $query);
    return mysqli_num_rows($result) > 0;
}


//Ingreso el material a la bd

function registrarMaterial($conexion, $nombre, $marca, $cantidad, $medida, $precio_compra, $precio_unitario) {
    $query = "INSERT INTO materiales (nombre, marca, cantidad, medida, precio_compra, precio_unitario, activo, eliminado) VALUES ('$nombre', '$marca', '$cantidad', '$medida', '$precio_compra', '$precio_unitario', 1, 0)";
    return mysqli_query($conexion, $query);
}

 //Actualizo un material
function actualizarMaterial($conexion, $id_material, $nombre, $marca, $cantidad, $medida, $precio_compra, $precio_unitario, $activo) {
    $query = "UPDATE materiales SET nombre = '$nombre', marca = '$marca', cantidad = '$cantidad', medida = '$medida', precio_compra = '$precio_compra', precio_unitario = '$precio_unitario', activo = '$activo' WHERE id_material = '$id_material'";
    return mysqli_query($conexion, $query);
}

//marco como eliminado en la bd el material elegido
function eliminarMaterial($conexion, $id_material) {
    $query = "UPDATE materiales SET eliminado = 1 WHERE id_material = '$id_material'";
    return mysqli_query($conexion, $query);
}

function obtenerMaterialPorId($conexion, $id_material) {
    $query = "SELECT * FROM materiales WHERE id_material = '$id_material'";
    $result = mysqli_query($conexion, $query);
    return mysqli_fetch_assoc($result);
}
?>

<?php
// Registrar la venta general (solo el total de precio)
function registrarVentaGeneral($nombre_cliente, $direccion, $telefono) {
    global $conexion;
    $query = "INSERT INTO ventas (fecha, nombre_cliente, direccion, telefono) VALUES (NOW(), '$nombre_cliente', '$direccion', '$telefono')";
    if (mysqli_query($conexion, $query)) {
        return mysqli_insert_id($conexion);
    } else {
        die("Error al registrar la venta: " . mysqli_error($conexion));
    }
}


// Reducir el stock del material al registrar una venta
function reducirStockMaterial($id_material, $cantidad_vendida) {
    global $conexion;
    $query = "UPDATE materiales SET cantidad = cantidad - $cantidad_vendida WHERE id_material = $id_material AND cantidad >= $cantidad_vendida";
    if (!mysqli_query($conexion, $query)) {
        die("Error al reducir el stock del material: " . mysqli_error($conexion));
    }
}


// Registrar todos los detalles de cada producto de la venta (materia,precio_unitario,etc)
function registrarDetalleVenta($id_venta, $id_material, $cantidad, $precio_unitario, $total) {
    global $conexion;
    $stmt = $conexion->prepare("INSERT INTO detalles_ventas (id_venta, id_material, cantidad, precio_unitario, total) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        die("Error al preparar la consulta: " . $conexion->error);
    }
    $stmt->bind_param("iidds", $id_venta, $id_material, $cantidad, $precio_unitario, $total);
    $stmt->execute();
    $stmt->close();

    // Reducir el stock del material
    reducirStockMaterial($id_material, $cantidad);
}


// Obtener materiales disponibles para seleccionar en el formulario
function obtenerMaterialesDisponibles() {
    global $conexion;
    $query = "SELECT id_material, nombre, medida, precio_unitario, precio_compra, cantidad 
              FROM materiales 
              WHERE cantidad > 0 AND activo = 1 AND eliminado = 0";
    return mysqli_query($conexion, $query);
}


// Obtener todas las ventas registradas para mostrar en la tabla
function obtenerVentas() {
    global $conexion;
    $query = "SELECT v.id_venta, v.fecha, v.nombre_cliente, v.direccion AS direccion, v.telefono AS telefono, SUM(dv.total) AS total_general FROM ventas v JOIN detalles_ventas dv ON v.id_venta = dv.id_venta GROUP BY v.id_venta";
    return mysqli_query($conexion, $query);
}



// Obtener los detalles de una venta especifica
function obtenerDetallesVenta($id_venta) {
    global $conexion;
    $query = "SELECT m.nombre AS material, dv.cantidad, dv.precio_unitario, dv.total FROM detalles_ventas dv JOIN materiales m ON dv.id_material = m.id_material WHERE dv.id_venta = $id_venta";
    $resultado = mysqli_query($conexion, $query);
    $detalles = [];
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $detalles[] = $fila;
    }
    return $detalles;
}

// Eliminar una venta y sus detalles
function eliminarVenta($id_venta) {
    global $conexion;
    $queryDetalles = "DELETE FROM detalles_ventas WHERE id_venta = $id_venta";
    mysqli_query($conexion, $queryDetalles);

    $queryVenta = "DELETE FROM ventas WHERE id_venta = $id_venta";
    mysqli_query($conexion, $queryVenta);
}
?>

<?php
require('fpdf/fpdf.php');

// Funcion para generar el pdf de una venta
function generarPDF($id_venta) {
    global $conexion;

    $queryVenta = "SELECT v.id_venta, v.fecha, v.nombre_cliente, v.direccion, v.telefono, SUM(dv.total) AS total_general FROM ventas v JOIN detalles_ventas dv ON v.id_venta = dv.id_venta WHERE v.id_venta = $id_venta GROUP BY v.id_venta";
    $resultadoVenta = mysqli_query($conexion, $queryVenta);
    $venta = mysqli_fetch_assoc($resultadoVenta);

    $queryDetalles = "SELECT m.nombre AS material, dv.cantidad, dv.precio_unitario, dv.total FROM detalles_ventas dv JOIN materiales m ON dv.id_material = m.id_material WHERE dv.id_venta = $id_venta";
    $resultDetalles = mysqli_query($conexion, $queryDetalles);

    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, "Factura de Venta - ID: {$venta['id_venta']}", 0, 1, 'C');
    $pdf->Ln(10);

    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, "Cliente: {$venta['nombre_cliente']}", 0, 1);
    $pdf->Cell(0, 10, "Direccion: {$venta['direccion']}", 0, 1);
    $pdf->Cell(0, 10, "Telefono: {$venta['telefono']}", 0, 1);
    $pdf->Cell(0, 10, "Fecha: {$venta['fecha']}", 0, 1);
    $pdf->Ln(5);

    // Encabezado de la tabla
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(80, 10, 'Material', 1);
    $pdf->Cell(30, 10, 'Cantidad', 1);
    $pdf->Cell(40, 10, 'Precio Unitario', 1);
    $pdf->Cell(40, 10, 'Total', 1);
    $pdf->Ln();

    // Detalles de la venta
    $pdf->SetFont('Arial', '', 12);
    while ($detalle = mysqli_fetch_assoc($resultDetalles)) {
        $pdf->Cell(80, 10, $detalle['material'], 1);
        $pdf->Cell(30, 10, $detalle['cantidad'], 1);
        $pdf->Cell(40, 10, number_format($detalle['precio_unitario'], 2), 1);
        $pdf->Cell(40, 10, number_format($detalle['total'], 2), 1);
        $pdf->Ln();
    }

    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(150, 10, 'Total General', 1);
    $pdf->Cell(40, 10, number_format($venta['total_general'], 2), 1);
    $pdf->Ln();

    $nombreArchivo = "Factura_Venta_{$venta['id_venta']}.pdf";
    $pdf->Output('D', $nombreArchivo);
}

?>

<?php
// Registrar la compra general (solo el total y la fecha)
function registrarCompraGeneral() {
    global $conexion;
    $query = "INSERT INTO compras (fecha) VALUES (NOW())";
    mysqli_query($conexion, $query);
    return mysqli_insert_id($conexion); 
}


// Registrar todos los detalles de cada material de la compra (material, precio_compra, cantidad, etc.)
function registrarDetalleCompra($id_compra, $id_material, $cantidad, $precio_compra, $total) {
    global $conexion;
    
    // Insertar el detalle de la compra
    $query = "INSERT INTO detalles_compras (id_compra, id_material, cantidad, precio_compra, total) VALUES ($id_compra, $id_material, $cantidad, $precio_compra, $total)";
    mysqli_query($conexion, $query);

    // Aumentar el stock del material
    $queryStock = "UPDATE materiales SET cantidad = cantidad + $cantidad WHERE id_material = $id_material";
    mysqli_query($conexion, $queryStock);
}


// Actualizar el stock del material al registrar una compra
function actualizarStockMaterial($id_material, $cantidad) {
    global $conexion;
    $query = "UPDATE materiales SET cantidad = cantidad + $cantidad WHERE id_material = $id_material";
    mysqli_query($conexion, $query);
}

// Obtener materiales disponibles para seleccionar en el formulario
function materialesDisponiblesCompra() {
    global $conexion;
    $query = "SELECT id_material, nombre, precio_compra, cantidad FROM materiales WHERE activo = 1 AND eliminado = 0"; // Solo materiales activos y no eliminados
    return mysqli_query($conexion, $query);
}

// Obtener todas las compras registradas para mostrar en la tabla
function obtenerCompras() {
    global $conexion;
    $query = "SELECT c.id_compra, c.fecha, SUM(dc.total) AS total_general FROM compras c JOIN detalles_compras dc ON c.id_compra = dc.id_compra GROUP BY c.id_compra";
    return mysqli_query($conexion, $query);
}

// Eliminar una compra y sus detalles
function eliminarCompra($id_compra) {
    global $conexion;

    // Obtener los detalles de la compra antes de eliminarla
    $queryDetalles = "SELECT id_material, cantidad FROM detalles_compras WHERE id_compra = $id_compra";
    $result = mysqli_query($conexion, $queryDetalles);

    // Restablecer el stock de los materiales
    while ($row = mysqli_fetch_assoc($result)) {
        $id_material = $row['id_material'];
        $cantidad = $row['cantidad'];

        // Reducir el stock del material
        $queryStock = "UPDATE materiales SET cantidad = cantidad - $cantidad WHERE id_material = $id_material";
        if (!mysqli_query($conexion, $queryStock)) {
            echo "Error actualizando el stock: " . mysqli_error($conexion);
            return false; // Salir si ocurre un error
        }
    }

    // Eliminar los detalles de la compra
    $queryEliminarDetalles = "DELETE FROM detalles_compras WHERE id_compra = $id_compra";
    if (!mysqli_query($conexion, $queryEliminarDetalles)) {
        echo "Error eliminando detalles de compra: " . mysqli_error($conexion);
        return false;
    }

    // Eliminar la compra principal
    $queryEliminarCompra = "DELETE FROM compras WHERE id_compra = $id_compra";
    if (!mysqli_query($conexion, $queryEliminarCompra)) {
        echo "Error eliminando compra: " . mysqli_error($conexion);
        return false;
    }

    return true;
}



// Generar PDF para una compra
function generarPDFCompra($id_compra) {
    global $conexion;

    // Obtener los detalles de la compra
    $queryCompra = "SELECT c.id_compra, c.fecha, SUM(dc.total) AS total_general FROM compras c JOIN detalles_compras dc ON c.id_compra = dc.id_compra WHERE c.id_compra = $id_compra GROUP BY c.id_compra";
    $resultadoCompra = mysqli_query($conexion, $queryCompra);
    $compra = mysqli_fetch_assoc($resultadoCompra);

    $queryDetalles = "SELECT m.nombre AS material, dc.cantidad, dc.precio_compra, dc.total FROM detalles_compras dc JOIN materiales m ON dc.id_material = m.id_material WHERE dc.id_compra = $id_compra";
    $resultDetalles = mysqli_query($conexion, $queryDetalles);

    // Crear el pdf
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, "Factura de Compra - ID: {$compra['id_compra']}", 0, 1, 'C');
    $pdf->Ln(10);

    // Informacion de la compra
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, "Fecha: {$compra['fecha']}", 0, 1);
    $pdf->Ln(5);

    // Encabezado de la tabla
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(80, 10, 'Material', 1);
    $pdf->Cell(30, 10, 'Cantidad', 1);
    $pdf->Cell(40, 10, 'Precio Compra', 1);
    $pdf->Cell(40, 10, 'Total', 1);
    $pdf->Ln();

    // Detalles de la compra
    $pdf->SetFont('Arial', '', 12);
    while ($detalle = mysqli_fetch_assoc($resultDetalles)) {
        $pdf->Cell(80, 10, $detalle['material'], 1);
        $pdf->Cell(30, 10, $detalle['cantidad'], 1);
        $pdf->Cell(40, 10, number_format($detalle['precio_compra'], 2), 1);
        $pdf->Cell(40, 10, number_format($detalle['total'], 2), 1);
        $pdf->Ln();
    }

    // Total general
    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(150, 10, 'Total General', 1);
    $pdf->Cell(40, 10, number_format($compra['total_general'], 2), 1);
    $pdf->Ln();
    
    // Guardar el archivo y enviar al navegador
    $nombreArchivo = "Factura_Compra_{$compra['id_compra']}.pdf";
    $pdf->Output('D', $nombreArchivo); // Descargar el pdf
}

// Obtiene las ganancias de cada venta
function obtenerGanancias($conexion) {
    $query = "SELECT v.id_venta, v.fecha, SUM((dv.precio_unitario - m.precio_compra) * dv.cantidad) AS ganancia_venta FROM ventas v JOIN detalles_ventas dv ON v.id_venta = dv.id_venta JOIN materiales m ON dv.id_material = m.id_material GROUP BY v.id_venta;";

    return mysqli_query($conexion, $query);
}

// Obtiene la ganancia total de todas las ventas
function obtenerGananciaTotal($conexion) {
    $query = "SELECT SUM((dv.precio_unitario - m.precio_compra) * dv.cantidad) AS ganancia_total FROM detalles_ventas dv JOIN materiales m ON dv.id_material = m.id_material;";
    $resultado = mysqli_query($conexion, $query);
    $fila = mysqli_fetch_assoc($resultado);
    return $fila['ganancia_total'];
}

function obtenerProductosMasVendidos($conexion) {
    $query = "SELECT m.nombre AS material, SUM(dv.cantidad) AS cantidad_vendida, SUM(dv.cantidad * dv.precio_unitario) AS ingresos_generados FROM detalles_ventas dv JOIN materiales m ON dv.id_material = m.id_material GROUP BY dv.id_material ORDER BY cantidad_vendida DESC LIMIT 10;";
    return mysqli_query($conexion, $query);
}

function obtenerGananciasPorTipo($conexion) {
    $query = "SELECT m.nombre AS tipo_producto, SUM(dv.cantidad * dv.precio_unitario) AS ingresos_generados, SUM(dv.cantidad * m.precio_compra) AS costo_total, SUM((dv.precio_unitario - m.precio_compra) * dv.cantidad) AS ganancia_total FROM detalles_ventas dv JOIN materiales m ON dv.id_material = m.id_material GROUP BY m.nombre ORDER BY ganancia_total DESC;";
    return mysqli_query($conexion, $query);
}
