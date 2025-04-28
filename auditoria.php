<?php
function registrar_auditoria($conexion, $usuario, $accion, $detalle = "") {
    $sql = "INSERT INTO auditorias (usuario, accion, fecha, detalle) VALUES (:usuario, :accion, NOW(), :detalle)";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([
        ':usuario' => $usuario,
        ':accion' => $accion,
        ':detalle' => $detalle
    ]);
}
?>
