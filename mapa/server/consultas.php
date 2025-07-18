<?php
require_once __DIR__ . '/config.php';

// catálogos
$tecnologias = $pdo->query("SELECT Id, Nombre FROM tecnologia WHERE Activo=1")->fetchAll();
$dependencias = $pdo->query("SELECT Id, Nombre FROM dependencia WHERE Activo=1")->fetchAll();
$entidades = $pdo->query("SELECT Id, Nombre FROM entidad WHERE Activo=1")->fetchAll();
$buses = $pdo->query("SELECT Id, Nombre FROM bus WHERE Activo=1")->fetchAll();
$estatuses = $pdo->query("SELECT Id, Valor FROM estatus WHERE Activo=1")->fetchAll();
$categorias = $pdo->query("SELECT Id, Nombre FROM categoria WHERE Activo=1")->fetchAll();


// filtro de registros
$filtro_entidad = isset($_GET['filtro_entidad']) ? $_GET['filtro_entidad'] : null;

$query = "
    SELECT r.*, 
        t.Nombre AS Tecnologia, 
        d.Nombre AS Dependencia, 
        e.Nombre AS Entidad, 
        b.Nombre AS Bus,
        es.Valor AS Estatus
        c.Valor AS Categoria
    FROM registro r
    LEFT JOIN tecnologia t ON r.Fk_Id_Tecnologia = t.Id
    LEFT JOIN dependencia d ON r.Fk_Id_Dependencia = d.Id
    LEFT JOIN entidad e ON r.Fk_Id_Entidad = e.Id
    LEFT JOIN bus b ON r.Fk_Id_Bus = b.Id
    LEFT JOIN estatus es ON r.Fk_Id_Estatus = es.Id
    LEFT JOIN categoria c ON r.Fk_Id_Categoria = c.Id

";
if ($filtro_entidad) {
    $query .= " WHERE r.Fk_Id_Entidad = " . intval($filtro_entidad);
}

$query .= " ORDER BY r.Id DESC";  // aquí la ordenación final

$registros = $pdo->query($query)->fetchAll();

?>
