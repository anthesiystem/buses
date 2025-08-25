<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../../server/config.php';

function jerr($m){ echo json_encode(['ok'=>false,'msg'=>$m], JSON_UNESCAPED_UNICODE); exit; }
$id   = $_POST['ID'] ?? '';
$desc = trim($_POST['descripcion'] ?? '');
$act  = isset($_POST['activo']) && $_POST['activo']=='1' ? 1 : 0;

if ($desc === '') jerr('La descripción (slug) es obligatoria.');
// Valida slug sencillo (minúsculas, números, guiones bajos)
if (!preg_match('/^[a-z0-9_]+$/', $desc)) jerr('Usa solo minúsculas, números y "_" (sin espacios ni acentos).');

try{
  if ($id===''){
    $sql = "INSERT INTO modulo (descripcion, activo) VALUES (?, ?)";
    $st  = $pdo->prepare($sql);
    $st->bindValue(1, $desc);
    $st->bindValue(2, (int)$act, PDO::PARAM_INT);
    $ok  = $st->execute();
  } else {
    $sql = "UPDATE modulo SET descripcion=?, activo=?, fecha_modificacion=NOW() WHERE ID=?";
    $st  = $pdo->prepare($sql);
    $st->bindValue(1, $desc);
    $st->bindValue(2, (int)$act, PDO::PARAM_INT);
    $st->bindValue(3, (int)$id, PDO::PARAM_INT);
    $ok  = $st->execute();
  }
  echo json_encode(['ok'=>(bool)$ok]);
} catch(PDOException $e){
  if (strpos($e->getMessage(),'1062')!==false) jerr('Ya existe un módulo con ese slug.');
  echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
