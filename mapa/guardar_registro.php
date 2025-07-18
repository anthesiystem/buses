<?php
session_start();
require_once '../server/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("INSERT INTO registro 
        (Fk_Id_Engine, Fk_Id_Tecnologia, Fk_Id_Dependencia, Fk_Id_Entidad, Fk_Id_Bus, Fk_Id_Estatus, Version, Fecha_Inicio, Migracion, Avance, Fecha_Creacion, Fecha_Modificacion, Fk_Id_usuarios, Activo) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?, 1)");

    $avance = ($_POST['Fk_Id_Estatus'] == 3) ? 100 : min((int)$_POST['Avance'], 99); // 3 = Concluido

    $stmt->execute([
        $_POST['Fk_Id_Engine'],
        $_POST['Fk_Id_Tecnologia'],
        $_POST['Fk_Id_Dependencia'],
        $_POST['Fk_Id_Entidad'],
        $_POST['Fk_Id_Bus'],
        $_POST['Fk_Id_Estatus'],
        $_POST['Version'],
        $_POST['Fecha_Inicio'],
        $_POST['Migracion'],
        $avance,
        $_SESSION['id_usuario']
    ]);
    header("Location: registros.php");
}
?>