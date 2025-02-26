<?php
include("conexion/conexion.php");
include("funciones/funciones.php");
require_once('fpdf/fpdf.php');

// Registrar venta
if (isset($_POST['submit'])) {
    $nombre_cliente = $_POST['nombre_cliente'];
    $direccion = $_POST['direccion'];
    $telefono = $_POST['telefono'];
    $materiales = $_POST['id_material'];
    $cantidades = $_POST['cantidad'];
    $precios_unitarios = $_POST['precio_unitario'];
    $totales = $_POST['total'];

    if (count($materiales) > 0) {
        $id_venta = registrarVentaGeneral($nombre_cliente, $direccion, $telefono);
        foreach ($materiales as $index => $id_material) {
            registrarDetalleVenta($id_venta, $id_material, $cantidades[$index], $precios_unitarios[$index], $totales[$index]);
        }
        generarPDF($id_venta);
        header("Location: ventas.php?message=Venta%20registrada%20con%20éxito.&type=success");
        exit();
    }
}

// Eliminar venta
if (isset($_GET['delete'])) {
    $id_venta = intval($_GET['delete']);
    eliminarVenta($id_venta);
    header("Location: ventas.php?message=Venta%20eliminada%20con%20éxito.&type=success");
    exit();
}

// Obtener ventas
$ventas = obtenerVentas();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="estilos/style.css?v=<?php echo time(); ?>">
    <title>Ventas de Materiales</title>
</head>
<body>
<header>
    <span>Venta de materiales</span>
    <a href="cargaMateriales.php" class="btn">Cargar Material</a>
    <a href="compra_materiales.php" class="btn">Comprar Materiales</a>
    <a href="ganancias.php" class="btn">Ganancias</a>
</header>

<!-- Mensaje de éxito o error -->
<?php 
if (isset($_GET['message']) && isset($_GET['type'])) {
    $tipoMensaje = ($_GET['type'] == 'success') ? 'mensaje_verde' : 'mensaje_rojo';
    echo "<p class='$tipoMensaje'>" . urldecode($_GET['message']) . "</p>";
}
?>

<section>
    <form action="" method="post" id="ventaForm">
        <label for="nombre_cliente">Nombre del cliente</label>
        <input type="text" name="nombre_cliente" placeholder="Nombre del cliente" required pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+" title="Solo letras y espacios permitidos">

        <label for="direccion">Dirección</label>
        <input type="text" name="direccion" placeholder="Dirección del cliente" required>

        <label for="telefono">Teléfono</label>
        <input type="tel" name="telefono" placeholder="Teléfono del cliente" required pattern="[0-9]+" title="Solo números permitidos">

        <label for="id_material">Material</label>
        <select name="id_material[]" class="id_material" required>
            <option value="" disabled selected>Seleccione un material</option>
            <?php 
            $materiales = obtenerMaterialesDisponibles();
            while ($material = mysqli_fetch_assoc($materiales)) {
                echo "<option value='{$material['id_material']}'
                             data-precio='{$material['precio_unitario']}'
                             data-stock='{$material['cantidad']}'>
                       {$material['nombre']} ({$material['medida']}) (Stock: {$material['cantidad']})
                     </option>";
            }
            ?>
    </select>

        <label for="cantidad">Cantidad</label>
        <input type="number" name="cantidad[]" class="cantidad" placeholder="Ingrese la cantidad" required>

        <label for="precio_unitario">Precio unitario</label>
        <input type="number" name="precio_unitario[]" class="precio_unitario" placeholder="0.00" required>

        <label for="total">Total</label>
        <input type="number" name="total[]" class="total" placeholder="0.00" readonly required>

        <button type="button" id="agregarProducto">Agregar Producto</button>
        <button type="submit" class="btn" name="submit">Registrar venta</button>
    </form>

    <table>
    <thead>
        <tr>
            <th>ID Venta</th>
            <th>Fecha</th>
            <th>Cliente</th>
            <th>Dirección</th>
            <th>Teléfono</th>
            <th>Total</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        while ($venta = mysqli_fetch_assoc($ventas)) {
            echo "<tr>
                <td>{$venta['id_venta']}</td>
                <td>{$venta['fecha']}</td>
                <td>{$venta['nombre_cliente']}</td>
                <td>{$venta['direccion']}</td>
                <td>{$venta['telefono']}</td>
                <td>{$venta['total_general']}</td>
                <td>
                    <a href='ventas.php?delete={$venta['id_venta']}' class='btn-rojo' onclick='return confirm(\"¿Estás seguro de que deseas eliminar esta venta?\")'>Eliminar</a>
                    <a href='generarPdfVenta.php?id_venta={$venta['id_venta']}' class='btn-verde'>Generar PDF</a>
                </td>
            </tr>";
        }
        ?>
    </tbody>
    </table>
</section>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Función para calcular el total según índice (fila)
    function calcularTotal(index) {
        let materialSelect = document.getElementsByName('id_material[]')[index];
        let cantidadInput = document.getElementsByName('cantidad[]')[index];
        let precioUnitarioInput = document.getElementsByName('precio_unitario[]')[index];
        let totalInput = document.getElementsByName('total[]')[index];

        let selectedOption = materialSelect.options[materialSelect.selectedIndex];
        let precioUnitario = parseFloat(precioUnitarioInput.value) || parseFloat(selectedOption.getAttribute('data-precio')) || 0;
        let cantidad = parseInt(cantidadInput.value) || 0;

        totalInput.value = (precioUnitario * cantidad).toFixed(2);
    }

    // Función para verificar si la cantidad ingresada excede el stock
    function checkStock(materialSelect, quantityInput) {
        let selectedOption = materialSelect.options[materialSelect.selectedIndex];
        let stock = parseInt(selectedOption.getAttribute('data-stock')) || 0;
        let cantidadIngresada = parseInt(quantityInput.value) || 0;

        if (cantidadIngresada > stock) {
            quantityInput.setCustomValidity(`La cantidad ingresada (${cantidadIngresada}) supera el stock disponible (${stock}).`);
        } else {
            quantityInput.setCustomValidity("");
        }
    }

    // --- Primera fila del formulario ---
    document.querySelector("select[name='id_material[]']").addEventListener('change', function () {
        let selectedOption = this.options[this.selectedIndex];
        document.querySelector("input[name='precio_unitario[]']").value = selectedOption.getAttribute('data-precio');

        // Verificamos stock en tiempo real
        let quantityInput = document.querySelector("input[name='cantidad[]']");
        checkStock(this, quantityInput);

        // Calculamos total
        calcularTotal(0);
    });

    document.querySelector("input[name='cantidad[]']").addEventListener('input', function() {
        let materialSelect = document.querySelector("select[name='id_material[]']");
        
        // Verificamos stock
        checkStock(materialSelect, this);

        // Calculamos total
        calcularTotal(0);
    });

    document.querySelector("input[name='precio_unitario[]']").addEventListener('input', () => calcularTotal(0));


    // --- Agregar más productos dinámicamente ---
    document.getElementById('agregarProducto').addEventListener('click', () => {
        const container = document.createElement('div');
        container.innerHTML = `
            <label for="id_material">Material</label>
            <select name="id_material[]" class="id_material" required>
                ${document.querySelector("select[name='id_material[]']").innerHTML}
            </select>

            <label for="cantidad">Cantidad</label>
            <input type="number" name="cantidad[]" class="cantidad" required>

            <label for="precio_unitario">Precio unitario</label>
            <input type="number" name="precio_unitario[]" class="precio_unitario" required>

            <label for="total">Total</label>
            <input type="number" name="total[]" class="total" readonly required>

            <button type="button" class="eliminarProducto">Eliminar</button>
        `;

        document.getElementById('ventaForm').appendChild(container);

        let index = document.getElementsByName('id_material[]').length - 1;

        // Cambiar material en nueva fila
        container.querySelector('.id_material').addEventListener('change', function () {
            let selectedOption = this.options[this.selectedIndex];
            container.querySelector('.precio_unitario').value = selectedOption.getAttribute('data-precio');
            
            // Verificar stock
            let quantityInput = container.querySelector('.cantidad');
            checkStock(this, quantityInput);

            // Calcular total
            calcularTotal(index);
        });

        // Ingresar cantidad en nueva fila
        container.querySelector('.cantidad').addEventListener('input', function() {
            let materialSelect = container.querySelector('.id_material');
            
            // Verificar stock
            checkStock(materialSelect, this);

            // Calcular total
            calcularTotal(index);
        });

        // Ingresar precio unitario en nueva fila
        container.querySelector('.precio_unitario').addEventListener('input', () => calcularTotal(index));

        // Eliminar fila
        container.querySelector('.eliminarProducto').addEventListener('click', () => {
            container.remove();
        });
    });
});
</script>

</body>
</html>
