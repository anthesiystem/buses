<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../../server/config.php';

$buscar = trim($_GET['buscar'] ?? '');
$sql = "SELECT ID, descripcion, CAST(activo AS UNSIGNED) AS activo
        FROM modulo
        WHERE 1";
$p = [];
if ($buscar !== '') {
  $sql .= " AND descripcion LIKE ?";
  $p[] = "%$buscar%";
}
$sql .= " ORDER BY descripcion";

$st = $pdo->prepare($sql);
$st->execute($p);
echo json_encode($st->fetchAll(PDO::FETCH_ASSOC));
