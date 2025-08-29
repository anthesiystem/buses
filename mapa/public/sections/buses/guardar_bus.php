<?php
session_start();
require_once '../../../server/config.php';
require_once '../../../server/bitacora_helper.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$id          = $_POST['ID'] ?? null;
$descripcion = $_POST['descripcion'];
$color1      = $_POST['color_implementado'];
$color2      = $_POST['color_sin_implementar'];
$pruebas     = $_POST['pruebas'];
$imagenRuta  = null;

// Ruta real en el sistema
$carpetaDestino = '../../icons/';

if (!empty($_FILES['imagen']['name'])) {
  $nombreOriginal = basename($_FILES['imagen']['name']);
  $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

  if (in_array($extension, ['png', 'jpg', 'jpeg'])) {
    $nombreFinal = uniqid('bus_') . '.' . $extension;
    $rutaCompleta = $carpetaDestino . $nombreFinal;

    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaCompleta)) {
      // Ruta accesible desde el navegador
      $imagenRuta = $nombreFinal; // solo el nombre, sin prefijo "/mapa/public/icons/"
    } else {
      echo json_encode(['success' => false, 'message' => 'Error al subir imagen']);
      exit;
    }
  } else {
    echo json_encode(['success' => false, 'message' => 'Formato de imagen inválido']);
    exit;
  }
}

try {
  $usuario_info = obtenerUsuarioSession();
  $accion = $id ? 'UPDATE' : 'INSERT';
  $accion_texto = $id ? 'actualizado' : 'creado';
  
  if ($id) {
    if ($imagenRuta) {
      $stmt = $pdo->prepare("UPDATE bus SET descripcion=?, color_implementado=?, color_sin_implementar=?, pruebas=?, imagen=? WHERE ID=?");
      $stmt->execute([$descripcion, $color1, $color2, $pruebas, $imagenRuta, $id]);
    } else {
      $stmt = $pdo->prepare("UPDATE bus SET descripcion=?, color_implementado=?, color_sin_implementar=?, pruebas=? WHERE ID=?");
      $stmt->execute([$descripcion, $color1, $color2, $pruebas, $id]);
    }
    $bus_id = $id;
  } else {
    $stmt = $pdo->prepare("INSERT INTO bus (descripcion, color_implementado, color_sin_implementar, pruebas, imagen) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$descripcion, $color1, $color2, $pruebas, $imagenRuta]);
    $bus_id = $pdo->lastInsertId();
  }

  // Registrar en bitácora
  $descripcion_bitacora = "Bus '$descripcion' $accion_texto";
  if ($imagenRuta) {
    $descripcion_bitacora .= " - Imagen: $imagenRuta";
  }
  
  registrarBitacora(
    $pdo, 
    $usuario_info['user_id'], 
    'bus', 
    $accion, 
    $descripcion_bitacora, 
    $bus_id
  );

  echo json_encode(['success' => true, 'message' => "Bus $accion_texto correctamente"]);
} catch (Exception $e) {
  error_log("Error en guardar_bus.php: " . $e->getMessage());
  echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
