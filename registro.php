<?php
require_once 'conexion.php';
require_once 'auditoria.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Obtener datos del formulario
    $nombre = $_POST['nombre'] ?? '';
    $apellido = $_POST['apellido'] ?? '';
    $documento_identidad = $_POST['documento_identidad'] ?? '';
    $email = $_POST['correo'] ?? '';
    $contrasena = $_POST['contrasena'] ?? '';
    $tipo_usuario = $_POST['tipo_usuario'] ?? 'normal';

    // Validar campos obligatorios
    if (empty($nombre) || empty($apellido) || empty($documento_identidad) || empty($email) || empty($contrasena)) {
        echo "Por favor complete todos los campos obligatorios.";
        exit;
    }

    // Validar formato de email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "El formato del correo electrónico no es válido.";
        exit;
    }

    // Hash de contraseña
    $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);

    // Verificar si el email o documento ya existen
    try {
        $sql_verificar = "SELECT COUNT(*) FROM usuarios WHERE email = :email OR documento_identidad = :documento";
        $stmt_verificar = $conexion->prepare($sql_verificar);
        $stmt_verificar->execute([
            ':email' => $email,
            ':documento' => $documento_identidad
        ]);
        
        if ($stmt_verificar->fetchColumn() > 0) {
            echo "El correo electrónico o documento de identidad ya están registrados.";
            exit;
        }
    } catch (PDOException $e) {
        echo "Error al verificar usuario: " . $e->getMessage();
        exit;
    }

    // Insertar nuevo usuario
    $sql = "INSERT INTO usuarios (
                nombre, 
                apellido, 
                documento_identidad, 
                tipo_usuario, 
                email, 
                contrasena,
                estado,
                fecha_registro
            ) VALUES (
                :nombre, 
                :apellido, 
                :documento_identidad, 
                :tipo_usuario, 
                :email, 
                :contrasena,
                'activo',
                NOW()
            )";

    try {
        $stmt = $conexion->prepare($sql);
        $stmt->execute([
            ':nombre' => $nombre,
            ':apellido' => $apellido,
            ':documento_identidad' => $documento_identidad,
            ':tipo_usuario' => $tipo_usuario,
            ':email' => $email,
            ':contrasena' => $contrasena_hash
        ]);

        // Registrar auditoría
        registrar_auditoria($conexion, $nombre, "Registro", "Usuario registrado como $tipo_usuario");
        
        // Redirigir al login con mensaje de éxito
        header("Location: login.html?registro=exito");
        exit;
    } catch (PDOException $e) {
        echo "Error al registrar: " . $e->getMessage();
    }
} else {
    // Si alguien intenta acceder directamente al script
    header("Location: registro.html");
    exit;
}
?>