<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';

function registrarBitacora($pdo, $usuarioId, $tabla, $idRegistro, $accion, $descripcion) {
    $stmt = $pdo->prepare("
        INSERT INTO BITACORA (Fk_usuario, tabla_afectada, ID_registro_afectado, tipo_accion, descripcion)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$usuarioId, $tabla, $idRegistro, $accion, $descripcion]);
}

// Activar/desactivar
if (isset($_GET['activar']) && isset($_GET['id'])) {
    $id     = intval($_GET['id']);
    $estado = intval($_GET['activar']); // 1 = activar, 0 = desactivar

    $stmt = $pdo->prepare("UPDATE REGISTRO SET activo = ? WHERE ID = ?");
    $stmt->execute([$estado, $id]);

    $accion = $estado === 1 ? 'ACTIVAR' : 'DESACTIVAR';
    registrarBitacora(
        $pdo,
        $_SESSION['usuario_id'],
        'REGISTRO',
        $id,
        $accion,
        "El usuario {$_SESSION['usuario_id']} realizó $accion sobre el registro $id"
    );

    header("Location: ../public/registros.php");
    exit;
}

// Alta o modificación de registros
$fecha_modificacion = date('Y-m-d H:i:s');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fk_engine      = (int)$_POST['fk_engine'];
    $fk_version     = (int)$_POST['fk_version'];
    $fk_dependencia = (int)$_POST['fk_dependencia'];
    $fk_entidad     = (int)$_POST['fk_entidad'];
    $fk_bus         = (int)$_POST['fk_bus'];
    $fk_estado_bus  = (int)$_POST['fk_estatus']; // ahora es estado_bus
    $fk_categoria   = (int)$_POST['fk_categoria'];
    $avance         = (int)$_POST['avance'];
    $fecha_inicio   = $_POST['fecha_inicio'];
    $fecha_migracion = empty($_POST['Migracion']) ? null : $_POST['Migracion'];


    // Si el estatus representa "IMPLEMENTADO", forzar avance al 100
    if ($fk_estado_bus === 2) { // 2 = IMPLEMENTADO según tu tabla
        $avance = 100;
    } else {
        $avance = min($avance, 99);
    }

    if (!empty($_POST['id'])) {
        // Actualización
        $id = (int)$_POST['id'];
        $sql = "UPDATE REGISTRO SET 
            Fk_engine       = ?, 
            Fk_version      = ?, 
            Fk_dependencia  = ?, 
            Fk_entidad      = ?, 
            Fk_bus          = ?, 
            Fk_estado_bus   = ?, 
            Fk_categoria    = ?, 
            avance             = ?, 
            fecha_inicio       = ?, 
            fecha_migracion          = ?, 
            fecha_modificacion = ?
        WHERE ID = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $fk_engine, $fk_version, $fk_dependencia, $fk_entidad, $fk_bus,
            $fk_estado_bus, $fk_categoria, $avance, $fecha_inicio, $migracion,
            $fecha_modificacion, $id
        ]);

        registrarBitacora(
            $pdo,
            $_SESSION['usuario_id'],
            'REGISTRO',
            $id,
            'UPDATE',
            "Actualizó registro: versión=$fk_version, avance=$avance"
        );

    } else {
        // Inserción
        $sql = "INSERT INTO REGISTRO 
        (Fk_engine, Fk_version, Fk_dependencia, Fk_entidad, Fk_bus, Fk_estado_bus, Fk_categoria, avance, fecha_inicio, fecha_migracion, fecha_creacion, fecha_modificacion, activo)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, 1)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $fk_engine, $fk_version, $fk_dependencia, $fk_entidad, $fk_bus,
            $fk_estado_bus, $fk_categoria, $avance, $fecha_inicio, $fecha_migracion, $fecha_modificacion
        ]);

        $idInsertado = $pdo->lastInsertId();
        registrarBitacora(
            $pdo,
            $_SESSION['usuario_id'],
            'REGISTRO',
            $idInsertado,
            'INSERT',
            "Creó nuevo registro: versión=$fk_version, avance=$avance"
        );
    }

        echo "ok";
        exit;

}
