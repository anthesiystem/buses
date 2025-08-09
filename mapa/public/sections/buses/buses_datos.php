<?php
require_once '../../../server/config.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$stmt = $pdo->query("SELECT * FROM bus ORDER BY ID");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
