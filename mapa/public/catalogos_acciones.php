<?php
session_start();
require_once '../server/config.php';

$tabla = $_POST['tabla'] ?? $_GET['tabla'] ?? 'bus';

// seguridad
$permitidas = ['bus','dependencia','entidad','tecnologia'];
if (!in_array($tabla, $permitidas)) die('Tabla inválida');

// función para registrar en la bitácora
function registrarBitacora($pdo, $usuarioId, $tabla, $idRegistro, $accion, $descripcion) {
    $stmt = $pdo->prepare("INSERT INTO bitacora (Fk_Id_Usuarios, Tabla_Afectada, Id_Registro_Afectado, Tipo_Accion, Descripcion) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$usuarioId, $tabla, $idRegistro, $accion, $descripcion]);
}

// agregar
if (isset($_POST['agregar'])) {
    $nombre = $_POST['nombre'];
    $stmt = $pdo->prepare("INSERT INTO $tabla (Nombre,Activo) VALUES (?,1)");
    $stmt->execute([$nombre]);

    // bitácora
    $idInsertado = $pdo->lastInsertId();
    registrarBitacora($pdo, $_SESSION['usuario_id'], $tabla, $idInsertado, 'INSERT', "El usuario {$_SESSION['usuario_nombre']} agregó '$nombre' en $tabla");

    header("Location: catalogos.php?tabla=$tabla");
    exit;
}

// actualizar
if (isset($_POST['actualizar'])) {
    $id = $_POST['id'];
    $nombre = $_POST['nombre'];
    $stmt = $pdo->prepare("UPDATE $tabla SET Nombre=? WHERE Id=?");
    $stmt->execute([$nombre, $id]);

    // bitácora
    registrarBitacora($pdo, $_SESSION['usuario_id'], $tabla, $id, 'UPDATE', "El usuario {$_SESSION['usuario_nombre']} actualizó el nombre a '$nombre' en $tabla");

    header("Location: catalogos.php?tabla=$tabla");
    exit;
}

// activar/desactivar
if (isset($_GET['cambiar'])) {
    $id = $_GET['id'];
    $estado = $_GET['estado'];
    $stmt = $pdo->prepare("UPDATE $tabla SET Activo=? WHERE Id=?");
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
