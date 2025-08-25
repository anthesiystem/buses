<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../../server/config.php';
try {
  $u = $pdo->query("SELECT COUNT(*) c FROM usuario")->fetch(PDO::FETCH_ASSOC)['c'] ?? 0;
  $p = $pdo->query("SELECT COUNT(*) c FROM persona")->fetch(PDO::FETCH_ASSOC)['c'] ?? 0;
  echo json_encode(['ok'=>true,'usuarios'=>$u,'personas'=>$p]);
} catch (Throwable $e) {
  echo json_encode(['ok'=>false,'err'=>$e->getMessage()]);
}
