<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'];
    $contraseña = $_POST['contraseña'];
    
    // Verificar admin
    if ($usuario === 'Lautaro' && $contraseña === '2233') {
        $_SESSION['usuario'] = $usuario; 
        header('Location: cargaMateriales.php');
        exit();
    } else {
        $error = 'Usuario o contraseña incorrectos';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="estilos/style.css">
    <title>Corralon - Iniciar Sesion</title>
</head>
<body>
    <section>
        <h1>Iniciar Sesión</h1>
        <form action="" method="post">
            <label for="usuario">Clave corralon:</label>
            <input type="text" id="usuario" name="usuario" placeholder="Ingresa clave del corralon" required>
            
            <label for="contraseña">Contraseña:</label>
            <input type="password" id="contraseña" name="contraseña" placeholder="Ingresa la contraseña" required>
            
            <button type="submit">Iniciar Sesion</button>
        </form>
        
        <?php if (isset($error)) { echo "<p style='color: red;'>$error</p>"; } ?>
    </section>
</body>
</html>
