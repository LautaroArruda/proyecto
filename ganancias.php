<?php
include('conexion/conexion.php');
require_once('funciones/funciones.php'); 

// Obtiene los datos de las funciones
$gananciasPorVenta = obtenerGanancias($conexion);
$gananciaTotal = obtenerGananciaTotal($conexion);
$productosMasVendidos = obtenerProductosMasVendidos($conexion);
$gananciasPorTipo = obtenerGananciasPorTipo($conexion);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganancias</title>
    <link rel="stylesheet" href="estilos/style.css">
</head>
<header>
    <span>Ganancias</span>
    <a href="cargaMateriales.php" class="btn">Cargar material</a></li>
    <a href="compra_materiales.php" class="btn">Comprar materiales</a></li>
    <a href="ventas.php" class="btn">Vender</a></li>
</header>
<body>
    <section>
        <h1>Ganancias de Ventas</h1>
        <table>
            <thead>
                <tr>
                    <th>ID Venta</th>
                    <th>Fecha</th>
                    <th>Ganancia de la Venta</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($fila = mysqli_fetch_assoc($gananciasPorVenta)): ?>
                    <tr>
                        <td><?php echo $fila['id_venta']; ?></td>
                        <td><?php echo $fila['fecha']; ?></td>
                        <td>$<?php echo number_format($fila['ganancia_venta'], 2); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <h3>Ganancia Total: $<?php echo number_format($gananciaTotal, 2); ?></h3>

        <h2>Productos Más Vendidos</h2>
        <table>
            <thead>
                <tr>
                    <th>Material</th>
                    <th>Cantidad Vendida</th>
                    <th>Ingresos Generados</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($fila = mysqli_fetch_assoc($productosMasVendidos)): ?>
                    <tr>
                        <td><?php echo $fila['material']; ?></td>
                        <td><?php echo $fila['cantidad_vendida']; ?></td>
                        <td>$<?php echo number_format($fila['ingresos_generados'], 2); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <h2>Ganancias por Tipo de Producto</h2>
        <table>
            <thead>
                <tr>
                    <th>Tipo de Producto</th>
                    <th>Ingresos Generados</th>
                    <th>Costo Total</th>
                    <th>Ganancia Total</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($fila = mysqli_fetch_assoc($gananciasPorTipo)): ?>
                    <tr>
                        <td><?php echo $fila['tipo_producto']; ?></td>
                        <td>$<?php echo number_format($fila['ingresos_generados'], 2); ?></td>
                        <td>$<?php echo number_format($fila['costo_total'], 2); ?></td>
                        <td>$<?php echo number_format($fila['ganancia_total'], 2); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </section>
</body>
</html>
