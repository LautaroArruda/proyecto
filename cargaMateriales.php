<?php
include("funciones/funciones.php");

// Estructura para el ingreso isset ve si existe o no
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit'])) {
        $nombre = $_POST['nombre'];
        $marca = $_POST['marca'];
        $cantidad = $_POST['cantidad'];
        $medida = $_POST['medida'];
        $precio_compra = $_POST['precio_compra'];
        $precio_unitario = $_POST['precio_unitario'];

        if (materialExiste($conexion, $nombre)) {
            $message = "<p class='mensaje_rojo'>El material ya está registrado. Intente con otro.</p>";
        } else {
            if (registrarMaterial($conexion, $nombre, $marca, $cantidad, $medida, $precio_compra, $precio_unitario)) {
                $message = "<p class='mensaje_verde'>Material registrado con éxito.</p>";
            } else {
                $message = "<p class='mensaje_rojo'>Error al registrar el material.</p>";
            }
        }
    }
    // Estructura para actualizar 
    if (isset($_POST['update'])) {
        $id_material = $_POST['id_material'];
        $nombre = $_POST['nombre'];
        $marca = $_POST['marca'];
        $cantidad = $_POST['cantidad'];
        $medida = $_POST['medida'];
        $precio_compra = $_POST['precio_compra'];
        $precio_unitario = $_POST['precio_unitario'];
        $activo = $_POST['estado'];

        if (actualizarMaterial($conexion, $id_material, $nombre, $marca, $cantidad, $medida, $precio_compra, $precio_unitario, $activo)) {
            $message = "<p class='mensaje_verde'>Material actualizado con éxito.</p>";
        } else {
            $message = "<p class='mensaje_rojo'>Error al actualizar el material.</p>";
        }
    }
}

// Verificación de si se eliminó el material
if (isset($_GET['delete'])) {
    if (eliminarMaterial($conexion, $_GET['delete'])) {
        $message = "<p class='mensaje_verde'>Material eliminado con éxito.</p>";
    } else {
        $message = "<p class='mensaje_rojo'>Error al eliminar el material.</p>";
    }
}

// Mostrar materiales
$materiales = obtenerMateriales($conexion);

// Mostrar el material que se va a editar
$editMaterial = null;
if (isset($_GET['edit'])) {
    $editMaterial = obtenerMaterialPorId($conexion, $_GET['edit']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="estilos/style.css">
    <title>Carga Materiales</title>
</head>
<body>
<header>
    <span>Carga de Materiales</span>
    <a href="ventas.php" class="btn">Ventas</a>
    <a href="compra_materiales.php" class="btn">Comprar</a>
    <a href="ganancias.php" class="btn">Ganancias</a>
</header>

<!-- Mensaje de éxito o error -->
<?php if (isset($message)) echo $message; ?>

<section>
    <?php if ($editMaterial): ?>
        <!-- Editar material -->
        <form action="" method="post">
            <input type="hidden" name="id_material" value="<?php echo $editMaterial['id_material']; ?>">

            <label for="nombre">Descripción</label>
            <input type="text" name="nombre" id="nombre" value="<?php echo $editMaterial['nombre']; ?>" required>

            <label for="marca">Marca</label>
            <input type="text" name="marca" id="marca" value="<?php echo $editMaterial['marca']; ?>" required>

            <label for="cantidad">Cantidad</label>
            <input type="number" name="cantidad" id="cantidad" value="<?php echo $editMaterial['cantidad']; ?>" required>

            <label for="medida">Medida</label>
            <select name="medida" id="medida">
                <option value="Metros" <?php echo $editMaterial['medida'] == 'Metros' ? 'selected' : ''; ?>>Metros</option>
                <option value="Kilos" <?php echo $editMaterial['medida'] == 'Kilos' ? 'selected' : ''; ?>>Kilos</option>
                <option value="Unidades" <?php echo $editMaterial['medida'] == 'Unidades' ? 'selected' : ''; ?>>Unidades</option>
            </select>

            <label for="precio_compra">Precio compra</label>
            <input type="number" name="precio_compra" id="precio_compra" value="<?php echo $editMaterial['precio_compra']; ?>" min="0" step="0.01" required>

            <label for="precio_unitario">Precio unitario</label>
            <input type="number" name="precio_unitario" id="precio_unitario" value="<?php echo $editMaterial['precio_unitario']; ?>" min="0" step="0.01" required>

            <label for="estado">Estado</label>
            <select name="estado" id="estado" required>
                <option value="1" <?php echo $editMaterial['activo'] == 1 ? 'selected' : ''; ?>>Activo</option>
                <option value="0" <?php echo $editMaterial['activo'] == 0 ? 'selected' : ''; ?>>Inactivo</option>
            </select>

            <button type="submit" class="btn" name="update">Guardar Cambios</button>
            <a href="cargaMateriales.php" class="btn">Cancelar</a>
        </form>
    <?php else: ?>
        <!-- Ingresar material -->
        <form action="" method="post">
            <label for="nombre">Descripción</label>
            <input type="text" name="nombre" id="nombre" placeholder="Ingrese el nombre del material" required>

            <label for="marca">Marca</label>
            <input type="text" name="marca" id="marca" placeholder="Ingrese la marca" required>

            <label for="cantidad">Cantidad</label>
            <input type="number" name="cantidad" id="cantidad" placeholder="Ingrese el stock" required>

            <label for="medida">Medida</label>
            <select name="medida" id="medida">
                <option value="Metros">Metros</option>
                <option value="Kilos">Kilos</option>
                <option value="Unidades">Unidades</option>
            </select>

            <label for="precio_compra">Precio compra</label>
            <input type="number" name="precio_compra" id="precio_compra" placeholder="0.00" min="0" step="0.01" required>

            <label for="precio_unitario">Precio unitario</label>
            <input type="number" name="precio_unitario" id="precio_unitario" placeholder="0.00" min="0" step="0.01" required>

            <button type="submit" class="btn" name="submit">Registrar Material</button>
        </form>
    <?php endif; ?>

    <table id="tablaMateriales">
        <thead>
            <tr>
                <th>ID</th>
                <th>Descripción</th>
                <th>Marca</th>
                <th>Cantidad</th>
                <th>Medida</th>
                <th>Precio Compra</th>
                <th>Precio Unitario</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            while ($material = mysqli_fetch_assoc($materiales)) {
                $estado = $material['activo'] ? 'Activo' : 'Inactivo';
                echo "<tr>
                    <td>{$material['id_material']}</td>
                    <td>{$material['nombre']}</td>
                    <td>{$material['marca']}</td>
                    <td>{$material['cantidad']}</td>
                    <td>{$material['medida']}</td>
                    <td>{$material['precio_compra']}</td>
                    <td>{$material['precio_unitario']}</td>
                    <td>{$estado}</td>
                    <td>
                        <a href='?edit={$material['id_material']}' class='btn-verde'>Editar</a>  
                        <a href='?delete={$material['id_material']}' class='btn-rojo' onclick='return confirm(\"¿Estás seguro?\")'>Eliminar</a>
                    </td>
                </tr>";
            }
            ?>
        </tbody>
    </table>
</section>
</body>
</html>
