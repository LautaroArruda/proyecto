<?php
include("conexion/conexion.php");
include("funciones/funciones.php");
require_once('fpdf/fpdf.php');

$message = "";

// Registrar compra
if (isset($_POST['submit'])) {
    $materiales = $_POST['id_material'];
    $cantidades = $_POST['cantidad'];
    $precios_unitarios = $_POST['precio_compra'];
    $totales = $_POST['total'];

    if (count($materiales) > 0) {
        $id_compra = registrarCompraGeneral();
        if ($id_compra) {
            foreach ($materiales as $index => $id_material) {
                registrarDetalleCompra($id_compra, $id_material, $cantidades[$index], $precios_unitarios[$index], $totales[$index]);
            }
            generarPDFCompra($id_compra);
            header("Location: compra_materiales.php?message=Compra%20registrada%20con%20éxito.&type=success");
            exit();
        } else {
            header("Location: compra_materiales.php?message=Error%20al%20registrar%20la%20compra.&type=error");
            exit();
        }
    }
}

// Eliminar compra
if (isset($_GET['delete'])) {
    $id_compra = intval($_GET['delete']);
    if (eliminarCompra($id_compra)) {
        header("Location: compra_materiales.php?message=Compra%20eliminada%20con%20éxito.&type=success");
        exit();
    } else {
        header("Location: compra_materiales.php?message=Error%20al%20eliminar%20la%20compra.&type=error");
        exit();
    }
}

// Obtener compras
$compras = obtenerCompras();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="estilos/style.css?v=<?php echo time(); ?>">
    <title>Compras de Materiales</title>
</head>
<body>
<header>
    <span>Compra de materiales</span>
    <a href="cargaMateriales.php" class="btn">Cargar Material</a>
    <a href="ventas.php" class="btn">Vender</a>
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
    <form action="" method="post" id="compraForm">
        <label for="id_material">Material</label>
        <select name="id_material[]" class="id_material" required>
            <option value="" disabled selected>Seleccione un material</option>
            <?php 
            $materiales = obtenerMaterialesDisponibles();
            while ($material = mysqli_fetch_assoc($materiales)) {
                echo "<option value='{$material['id_material']}' 
                    data-precio='{$material['precio_compra']}'>
                    {$material['nombre']} ({$material['medida']}) (Stock: {$material['cantidad']})
                    </option>";
            }
            ?>
        </select>

        <label for="cantidad">Cantidad</label>
        <input type="number" name="cantidad[]" class="cantidad" placeholder="Ingrese la cantidad" required>

        <label for="precio_compra">Precio compra</label>
        <input type="number" name="precio_compra[]" class="precio_compra" placeholder="0.00" required>

        <label for="total">Total</label>
        <input type="number" name="total[]" class="total" placeholder="0.00" readonly required>

        <button type="button" id="agregarProducto">Agregar Material</button>
        <button type="submit" class="btn" name="submit">Registrar compra</button>
    </form>

    <table>
    <thead>
        <tr>
            <th>ID Compra</th>
            <th>Fecha</th>
            <th>Total</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        while ($compra = mysqli_fetch_assoc($compras)) {
            echo "<tr>
                <td>{$compra['id_compra']}</td>
                <td>{$compra['fecha']}</td>
                <td>{$compra['total_general']}</td>
                <td>
                    <a href='compra_materiales.php?delete={$compra['id_compra']}' class='btn-rojo' onclick='return confirm(\"¿Estás seguro de eliminar esta compra?\")'>Eliminar</a>
                    <a href='generarPdfCompra.php?id_compra={$compra['id_compra']}' class='btn-verde'>Generar PDF</a>
                </td>
            </tr>";
        }
        ?>
    </tbody>
    </table>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    function calcularTotal(index) {
        let materialSelect = document.getElementsByName('id_material[]')[index];
        let cantidadInput = document.getElementsByName('cantidad[]')[index];
        let precioUnitarioInput = document.getElementsByName('precio_compra[]')[index];
        let totalInput = document.getElementsByName('total[]')[index];

        let selectedOption = materialSelect.options[materialSelect.selectedIndex];
        let precioUnitario = parseFloat(precioUnitarioInput.value) || parseFloat(selectedOption.getAttribute('data-precio')) || 0;
        let cantidad = parseInt(cantidadInput.value) || 0;

        totalInput.value = (precioUnitario * cantidad).toFixed(2);
    }

    document.querySelector("select[name='id_material[]']").addEventListener('change', function () {
        let selectedOption = this.options[this.selectedIndex];
        document.querySelector("input[name='precio_compra[]']").value = selectedOption.getAttribute('data-precio');
        calcularTotal(0);
    });

    document.querySelector("input[name='cantidad[]']").addEventListener('input', () => calcularTotal(0));
    document.querySelector("input[name='precio_compra[]']").addEventListener('input', () => calcularTotal(0));

    document.getElementById('agregarProducto').addEventListener('click', () => {
        const container = document.createElement('div');
        container.innerHTML = `
            <label for="id_material">Material</label>
            <select name="id_material[]" class="id_material" required>
                ${document.querySelector("select[name='id_material[]']").innerHTML}
            </select>

            <label for="cantidad">Cantidad</label>
            <input type="number" name="cantidad[]" class="cantidad" required>

            <label for="precio_compra">Precio compra</label>
            <input type="number" name="precio_compra[]" class="precio_compra" required>

            <label for="total">Total</label>
            <input type="number" name="total[]" class="total" readonly required>

            <button type="button" class="eliminarProducto">Eliminar</button>
        `;

        document.getElementById('compraForm').appendChild(container);

        let index = document.getElementsByName('id_material[]').length - 1;
        container.querySelector('.id_material').addEventListener('change', function () {
            let selectedOption = this.options[this.selectedIndex];
            container.querySelector('.precio_compra').value = selectedOption.getAttribute('data-precio');
            calcularTotal(index);
        });

        container.querySelector('.cantidad').addEventListener('input', () => calcularTotal(index));
        container.querySelector('.precio_compra').addEventListener('input', () => calcularTotal(index));

        container.querySelector('.eliminarProducto').addEventListener('click', () => {
            container.remove();
        });
    });
});
</script>
</body>
</html>
