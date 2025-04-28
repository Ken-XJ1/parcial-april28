<?php
session_start();
require_once 'conexion.php';

// Si ya está logueado, redirigir al panel de admin
if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'admin') {
    header("Location: admin_panel.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['correo'] ?? '';
    $contrasena = $_POST['password'] ?? '';

    // Consulta PDO
    $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($contrasena, $user['contrasena'])) {
        $_SESSION['usuario'] = $user['nombre'];
        $_SESSION['tipo_usuario'] = $user['tipo_usuario'];

        if ($user['tipo_usuario'] === 'admin') {
            header("Location: admin_panel.php");
            exit();
        } else {
            header("Location: usuario_panel.php");
            exit();
        }
    } else {
        echo "Usuario o contraseña incorrectos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin</title>
</head>
<body>
    <h1>Iniciar sesión como administrador</h1>
    <form method="POST" action="">
        <label for="correo">Correo:</label><br>
        <input type="email" id="correo" name="correo" required><br><br>

        <label for="password">Contraseña:</label><br>
        <input type="password" id="password" name="password" required><br><br>

        <input type="submit" value="Iniciar sesión">
    </form>
</body>
</html>
