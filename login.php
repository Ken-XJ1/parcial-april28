<?php
session_start();
require_once 'conexion.php';
require_once 'auditoria.php';

$email = $_POST['correo'] ?? '';
$contrasena = $_POST['contrasena'] ?? '';

// 1. Buscar usuario
$sql = "SELECT * FROM usuarios WHERE email = :email";
$stmt = $conexion->prepare($sql);
$stmt->execute([':email' => $email]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if ($usuario && password_verify($contrasena, $usuario['contrasena'])) {
    if ($usuario['tipo_usuario'] === 'admin') {
        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['tipo_usuario'] = $usuario['tipo_usuario'];

        registrar_auditoria($conexion, $usuario['nombre'], "Inicio de sesión", "Ingreso exitoso");

        header("Location: admin.php");
        exit;
    }

    // Verificar si el usuario está bloqueado (solo para usuarios normales)
    $sql_bloqueo = "SELECT * FROM dispositivos_bloqueados 
                    WHERE email = :email 
                    AND desbloqueo IS NOT NULL
                    AND desbloqueo > NOW()";
    $stmt = $conexion->prepare($sql_bloqueo);
    $stmt->execute([':email' => $email]);
    $bloqueo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($bloqueo) {
        echo "Usuario bloqueado. Por favor espera.";
        exit;
    }

    // Login exitoso - limpiar bloqueos
    $conexion->prepare("DELETE FROM dispositivos_bloqueados WHERE email = :email")
             ->execute([':email' => $email]);

    $_SESSION['id_usuario'] = $usuario['id_usuario'];
    $_SESSION['nombre'] = $usuario['nombre'];
    $_SESSION['tipo_usuario'] = $usuario['tipo_usuario'];

    registrar_auditoria($conexion, $usuario['nombre'], "Inicio de sesión", "Ingreso exitoso");

    header("Location: bienvenida.html");
    exit;
} else {
    // Login fallido - manejar intentos
    $sql_intento = "SELECT * FROM dispositivos_bloqueados WHERE email = :email";
    $stmt = $conexion->prepare($sql_intento);
    $stmt->execute([':email' => $email]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);

    $intentos = ($registro) ? $registro['intentos'] + 1 : 1;

    if ($intentos >= 3) {
        $desbloqueo = (new DateTime())->add(new DateInterval('PT3M'))->format('Y-m-d H:i:s');

        if ($registro) {
            $sql = "UPDATE dispositivos_bloqueados 
                    SET intentos = :intentos, desbloqueo = :desbloqueo 
                    WHERE email = :email";
        } else {
            $sql = "INSERT INTO dispositivos_bloqueados (email, intentos, desbloqueo) 
                    VALUES (:email, :intentos, :desbloqueo)";
        }

        $conexion->prepare($sql)->execute([
            ':intentos' => $intentos,
            ':desbloqueo' => $desbloqueo,
            ':email' => $email
        ]);

        registrar_auditoria($conexion, $email, "Bloqueo", "Bloqueado tras 3 intentos fallidos");
        echo "Has sido bloqueado por 3 minutos. Por favor espera.";
    } else {
        if ($registro) {
            $conexion->prepare("UPDATE dispositivos_bloqueados SET intentos = :intentos WHERE email = :email")
                     ->execute([':intentos' => $intentos, ':email' => $email]);
        } else {
            $conexion->prepare("INSERT INTO dispositivos_bloqueados (email, intentos) VALUES (:email, :intentos)")
                     ->execute([':email' => $email, ':intentos' => $intentos]);
        }

        echo "Credenciales incorrectas. Intento fallido $intentos de 3.";
    }
}
?>