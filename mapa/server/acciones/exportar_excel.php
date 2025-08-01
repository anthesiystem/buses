<?php
require_once '../../server/config.php';
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

session_start();

if (!isset($_SESSION['usuario_id'])) {
    die("No autorizado.");
}

// Obtener registros
$stmt = $pdo->query("
  SELECT r.ID, 
    d.descripcion AS Dependencia,
    e.descripcion AS Entidad,
    b.descripcion AS Bus,
    en.descripcion AS Engine,
    v.descripcion AS Version,
    eb.descripcion AS Estatus,
    c.descripcion AS Categoria,
    r.fecha_inicio,
    r.fecha_migracion,
    r.avance
  FROM registro r
  INNER JOIN dependencia d ON r.Fk_dependencia = d.ID
  INNER JOIN entidad e ON r.Fk_entidad = e.ID
  LEFT JOIN bus b ON r.Fk_bus = b.ID
  INNER JOIN engine en ON r.Fk_engine = en.ID
  INNER JOIN version v ON r.Fk_version = v.ID
  INNER JOIN estado_bus eb ON r.Fk_estado_bus = eb.ID
  INNER JOIN categoria c ON r.Fk_categoria = c.ID
  WHERE r.activo = 1
  ORDER BY r.fecha_creacion DESC
");

$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Crear hoja
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Registros');

$encabezados = ['ID', 'Dependencia', 'Entidad', 'Bus', 'Engine', 'Versión', 'Estatus', 'Categoría', 'Inicio', 'Migración', 'Avance (%)'];
$col = 'A';
foreach ($encabezados as $h) {
    $sheet->setCellValue($col . '1', $h);
    $col++;
}

// Llenar datos
$fila = 2;
foreach ($registros as $r) {
    $col = 'A';
    foreach ($encabezados as $key) {
        $valor = $r[$key] ?? '';
        $sheet->setCellValue($col . $fila, $valor);
        $col++;
    }
    $fila++;
}

// Descargar
$nombreArchivo = 'registros_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$nombreArchivo\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
