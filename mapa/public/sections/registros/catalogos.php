<?php
// catalogos.php - Obtención de datos para catálogos
require_once 'config.php';

// Catálogos básicos
$dependencias = catalogo($pdo, 'dependencia');
$entidades    = catalogo($pdo, 'entidad');
$buses        = catalogo($pdo, 'bus');
$engines      = catalogo($pdo, 'motor_base');
$estatuses    = catalogo($pdo, 'estado_bus');
$categorias   = catalogo($pdo, 'categoria');

// Tecnologías con formato especial
$tecnologias = $pdo->query("
  SELECT ID, CONCAT(numero_version, ' - ', descripcion) AS descripcion
  FROM tecnologia
  WHERE activo = 1
  ORDER BY numero_version, descripcion
")->fetchAll(PDO::FETCH_ASSOC);

// Etapas con porcentaje
$etapas = $pdo->query("
  SELECT ID, descripcion, avance
  FROM etapa
  WHERE activo = 1
  ORDER BY ID
")->fetchAll(PDO::FETCH_ASSOC);
?>
