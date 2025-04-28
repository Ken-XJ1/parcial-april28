<?php

session_start();
include 'conexion.php';

// Verificar si el usuario es admin
if (!isset($_SESSION['usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Agregar un nuevo usuario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agregar_usuario'])) {
    $nombre = $_POST['usuario']; // Cambiado a 'nombre'
    $contrasena = password_hash($_POST['password'], PASSWORD_DEFAULT); // Cambiado a 'contrasena'
    $tipo_usuario = $_POST['tipo_usuario'];
    $correo = $_POST['correo'];

    // Validar que no exista el usuario
    $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE nombre = :nombre"); // Cambiado a 'nombre'
    $stmt->execute([':nombre' => $nombre]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo "El usuario ya existe.";
    } else {
        $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, contrasena, tipo_usuario, email) VALUES (:nombre, :contrasena, :tipo_usuario, :correo)"); // Cambiado a 'contrasena'
        if ($stmt->execute([':nombre' => $nombre, ':contrasena' => $contrasena, ':tipo_usuario' => $tipo_usuario, ':correo' => $correo])) {
            echo "Usuario agregado correctamente.";
        } else {
            echo "Error al agregar usuario.";
        }
    }
}

// Editar un usuario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editar_usuario'])) {
    $id_usuario = $_POST['id_usuario'];
    $nombre = $_POST['usuario']; // Cambiado a 'nombre'
    $contrasena = $_POST['password'] ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null; // Cambiado a 'contrasena'
    $tipo_usuario = $_POST['tipo_usuario'];
    $correo = $_POST['correo'];

    // Actualizar el usuario
    if ($contrasena) {
        $query = "UPDATE usuarios SET nombre = :nombre, contrasena = :contrasena, tipo_usuario = :tipo_usuario, email = :correo WHERE id_usuario = :id_usuario"; // Cambiado a 'contrasena'
        $params = [':nombre' => $nombre, ':contrasena' => $contrasena, ':tipo_usuario' => $tipo_usuario, ':correo' => $correo, ':id_usuario' => $id_usuario];
    } else {
        $query = "UPDATE usuarios SET nombre = :nombre, tipo_usuario = :tipo_usuario, email = :correo WHERE id_usuario = :id_usuario"; // Cambiado a 'nombre' y 'email'
        $params = [':nombre' => $nombre, ':tipo_usuario' => $tipo_usuario, ':correo' => $correo, ':id_usuario' => $id_usuario];
    }

    $stmt = $conexion->prepare($query);
    if ($stmt->execute($params)) {
        echo "Usuario actualizado correctamente.";
    } else {
        echo "Error al actualizar el usuario.";
    }
}

// Eliminar un usuario
if (isset($_GET['eliminar_usuario'])) {
    $id_usuario = $_GET['eliminar_usuario'];

    // Eliminar el usuario
    $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id_usuario = :id_usuario");
    if ($stmt->execute([':id_usuario' => $id_usuario])) {
        echo "Usuario eliminado correctamente.";
    } else {
        echo "Error al eliminar el usuario.";
    }
}

// Obtener todos los usuarios
$stmt = $conexion->prepare("SELECT * FROM usuarios");
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" width="device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
</head>
<body>
    <h1>Bienvenido, Administrador</h1>
    
    <h2>Agregar Usuario</h2>
    <form method="POST">
        <label for="usuario">Usuario:</label><br>
        <input type="text" id="usuario" name="usuario" required><br><br>
        
        <label for="password">Contraseña:</label><br>
        <input type="password" id="password" name="password" required><br><br>

        <label for="tipo_usuario">Tipo de usuario:</label><br>
        <select id="tipo_usuario" name="tipo_usuario" required>
            <option value="normal">Normal</option>
            <option value="admin">Admin</option>
        </select><br><br>

        <label for="correo">Correo:</label><br>
        <input type="email" id="correo" name="correo" required><br><br>

        <input type="submit" name="agregar_usuario" value="Agregar Usuario">
    </form>

    <h2>Editar o Eliminar Usuarios</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Usuario</th>
            <th>Tipo de Usuario</th>
            <th>Correo</th>
            <th>Acciones</th>
        </tr>

        <?php foreach ($result as $row): ?>
            <tr>
                <td><?php echo $row['id_usuario']; ?></td>
                <td><?php echo $row['nombre']; ?></td> <!-- Cambiado a 'nombre' -->
                <td><?php echo $row['tipo_usuario']; ?></td>
                <td><?php echo $row['email']; ?></td> <!-- Cambiado a 'email' -->
                <td>
                    <!-- Editar -->
                    <a href="admin_panel.php?editar_usuario=<?php echo $row['id_usuario']; ?>">Editar</a> | 
                    <!-- Eliminar -->
                    <a href="admin_panel.php?eliminar_usuario=<?php echo $row['id_usuario']; ?>" onclick="return confirm('¿Estás seguro de que quieres eliminar este usuario?')">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <?php
    // Si el admin quiere editar un usuario, mostramos un formulario de edición
    if (isset($_GET['editar_usuario'])) {
        $id_usuario = $_GET['editar_usuario'];
        $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE id_usuario = :id_usuario");
        $stmt->execute([':id_usuario' => $id_usuario]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        ?>

        <h2>Editar Usuario</h2>
        <form method="POST">
            <input type="hidden" name="id_usuario" value="<?php echo $user['id_usuario']; ?>">
            
            <label for="usuario">Usuario:</label><br>
            <input type="text" id="usuario" name="usuario" value="<?php echo $user['nombre']; ?>" required><br><br> <!-- Cambiado a 'nombre' -->
            
            <label for="password">Contraseña:</label><br>
            <input type="password" id="password" name="password"><br><br>

            <label for="tipo_usuario">Tipo de usuario:</label><br>
            <select id="tipo_usuario" name="tipo_usuario" required>
                <option value="normal" <?php echo $user['tipo_usuario'] == 'normal' ? 'selected' : ''; ?>>Normal</option>
                <option value="admin" <?php echo $user['tipo_usuario'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
            </select><br><br>

            <label for="correo">Correo:</label><br>
            <input type="email" id="correo" name="correo" value="<?php echo $user['email']; ?>" required><br><br> <!-- Cambiado a 'email' -->

            <input type="submit" name="editar_usuario" value="Actualizar Usuario">
        </form>
        <?php
    }
    ?>

</body>
</html>
