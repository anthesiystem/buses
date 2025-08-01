<?php
session_start();
require_once '../../server/config.php';

$tabla = $_POST['tabla'] ?? $_GET['tabla'] ?? 'bus';

// seguridad
$permitidas = ['bus','dependencia','entidad','tecnologia', 'engine', 'categoria'];
if (!in_array($tabla, $permitidas)) die('Tabla inválida');

// función para registrar en la bitácora
function registrarBitacora($pdo, $usuarioId, $tabla, $idRegistro, $accion, $descripcion) {
    $stmt = $pdo->prepare("INSERT INTO bitacora (Fk_Usuario, Tabla_Afectada, Id_Registro_Afectado, Tipo_Accion, Descripcion) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$usuarioId, $tabla, $idRegistro, $accion, $descripcion]);
}

// agregar
if (isset($_POST['agregar'])) {
    $descripcion = $_POST['descripcion'];
    $stmt = $pdo->prepare("INSERT INTO $tabla (descripcion, activo) VALUES (?, 1)");
    $stmt->execute([$descripcion]);

    // bitácora
    $idInsertado = $pdo->lastInsertId();
    registrarBitacora($pdo, $_SESSION['usuario_id'], $tabla, $idInsertado, 'INSERT', "El usuario {$_SESSION['usuario_nombre']} agregó '$descripcion' en $tabla");

    header("Location: ../../public/sections/catalogos.php?tabla=$tabla");
    exit;
}

// actualizar
if (isset($_POST['actualizar'])) {
    $id = $_POST['ID'];
    $descripcion = $_POST['descripcion'];
    $stmt = $pdo->prepare("UPDATE $tabla SET descripcion=? WHERE ID=?");
    $stmt->execute([$descripcion, $id]);

    // bitácora
    registrarBitacora($pdo, $_SESSION['usuario_id'], $tabla, $id, 'UPDATE', "El usuario {$_SESSION['usuario_nombre']} actualizó a '$descripcion' en $tabla");

    header("Location: ../../public/sections/catalogos.php?tabla=$tabla");
    exit;
}

// activar/desactivar
if (isset($_GET['cambiar'])) {
    $id = $_GET['id'];  // corregido: era ID (mayúscula) y se envía en minúscula
    $estado = $_GET['estado'];
    $stmt = $pdo->prepare("UPDATE $tabla SET activo=? WHERE ID=?");
    $stmt->execute([$estado, $id]);

    // bitácora
    if (isset($_SESSION['usuario_id'])) {
        $accion = $estado == 1 ? 'ACTIVAR' : 'DESACTIVAR';
        $descripcion = "El usuario {$_SESSION['usuario_nombre']} realizó $accion sobre el registro $id en $tabla";
        registrarBitacora($pdo, $_SESSION['usuario_id'], $tabla, $id, $accion, $descripcion);
    }

    echo json_encode(['success' => true]);
    exit;
}
