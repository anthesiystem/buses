<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


require_once __DIR__ . '/config.php';

function registrarBitacora($pdo, $usuarioId, $tabla, $idRegistro, $accion, $descripcion) {
    $stmt = $pdo->prepare("INSERT INTO bitacora (Fk_Id_Usuarios, Tabla_Afectada, Id_Registro_Afectado, Tipo_Accion, Descripcion) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$usuarioId, $tabla, $idRegistro, $accion, $descripcion]);
}

if (isset($_GET['activar']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $estado = $_GET['activar']; // 1 = activar, 0 = desactivar

    $stmt = $pdo->prepare("UPDATE registro SET Activo = ? WHERE Id = ?");
    $stmt->execute([$estado, $id]);

    // Registro en bitácora
    $accion = $estado == 1 ? 'ACTIVAR' : 'DESACTIVAR';
    registrarBitacora(
        $pdo,
        $_SESSION['usuario_id'],
        'registro',
        $id,
        $accion,
        "El usuario {$_SESSION['usuario_id']} realizó $accion sobre el registro $id"
    );

    header("Location: ../public/registros.php");
    exit;
}


$fecha_modificacion = date('Y-m-d H:i:s');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fk_engine = $_POST['fk_engine'];
    $fk_tecnologia = $_POST['fk_tecnologia'];
    $fk_dependencia = $_POST['fk_dependencia'];
    $fk_entidad = $_POST['fk_entidad'];
    $fk_bus = $_POST['fk_bus'];
    $fk_estatus = $_POST['fk_estatus'];
    $fk_categoria = $_POST['fk_categoria'];
    $avance = $_POST['avance'];
    $version = $_POST['version'];
    $fecha_inicio = $_POST['fecha_inicio'];
    $migracion = empty($_POST['Migracion']) ? null : $_POST['Migracion'];

    // Validar avance si estatus es concluido
    if ((int)$fk_estatus === 2) {
        $avance = 100;
    } else {
        $avance = min((int)$avance, 99);
    }

    if (!empty($_POST['id'])) {
        // Actualizar registro
        $id = $_POST['id'];
        $sql = "UPDATE registro SET 
            Fk_Id_Engine = ?, 
            Fk_Id_Tecnologia = ?, 
            Fk_Id_Dependencia = ?, 
            Fk_Id_Entidad = ?, 
            Fk_Id_Bus = ?, 
            Fk_Id_Estatus = ?, 
            Fk_Id_Categoria = ?, 
            Avance = ?, 
            Version = ?, 
            Fecha_Inicio = ?, 
            Migracion = ?, 
            Fecha_Modificacion = ?
        WHERE Id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $fk_engine, $fk_tecnologia, $fk_dependencia, $fk_entidad, $fk_bus,
            $fk_estatus, $fk_categoria, $avance, $version, $fecha_inicio, $migracion,
            $fecha_modificacion, $id
        ]);

        registrarBitacora(
            $pdo,
            $_SESSION['usuario_id'],
            'registro',
            $id,
            'UPDATE',
            "Actualizó registro: versión=$version, avance=$avance"
        );

    } else {
        // Insertar nuevo registro
        $sql = "INSERT INTO registro 
        (Fk_Id_Engine, Fk_Id_Tecnologia, Fk_Id_Dependencia, Fk_Id_Entidad, Fk_Id_Bus, Fk_Id_Estatus, Fk_Id_Categoria, Avance, Version, Fecha_Inicio, Migracion, Fecha_Creacion, Fecha_Modificacion, Activo)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, 1)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $fk_engine, $fk_tecnologia, $fk_dependencia, $fk_entidad, $fk_bus, $fk_estatus, $fk_categoria,
            $avance, $version, $fecha_inicio, $migracion, $fecha_modificacion
        ]);

        $idInsertado = $pdo->lastInsertId();
        registrarBitacora(
            $pdo,
            $_SESSION['usuario_id'],
            'registro',
            $idInsertado,
            'INSERT',
            "Creó nuevo registro: versión=$version, avance=$avance"
        );


    }

    header("Location: ../public/registros.php");
    exit;
}
?>
