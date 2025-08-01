<?php
session_start();
require_once '../../server/config.php';

// Función para registrar en bitacora
function registrarBitacora($pdo, $usuarioId, $tabla, $idRegistro, $accion, $descripcion) {
    $stmt = $pdo->prepare("INSERT INTO bitacora (Fk_Id_Usuarios, Tabla_Afectada, Id_Registro_Afectado, Tipo_Accion, Descripcion)
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$usuarioId, $tabla, $idRegistro, $accion, $descripcion]);
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Desactivar el registro
    $stmt = $pdo->prepare("UPDATE registro SET Activo = 0 WHERE Id = ?");
    $stmt->execute([$id]);

    // Registrar en la bitácora
    registrarBitacora(
        $pdo,
        $_SESSION['usuario_id'],     // asegúrate que esta variable está disponible
        'registro',
        $id,
        'DESACTIVAR',
        "El usuario {$_SESSION['usuario']} desactivó el registro $id"
    );

    header("Location: ../../public/sections/registros.php");
    exit;
}
