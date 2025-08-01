<?php
session_start();
require_once '../../server/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("UPDATE registro SET 
        Fk_Id_Engine = ?, Fk_Id_Tecnologia = ?, Fk_Id_Dependencia = ?, Fk_Id_Entidad = ?, Fk_Id_Bus = ?, Fk_Id_Estatus = ?, Version = ?, Fecha_Inicio = ?, Migracion = ?, Avance = ?, Fecha_Modificacion = NOW(), Fk_Id_usuarios = ? 
        WHERE Id = ?");

    $avance = ($_POST['Fk_Id_Estatus'] == 3) ? 100 : min((int)$_POST['Avance'], 99);

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
        $_SESSION['id_usuario'],
        $_POST['Id']
    ]);
    header("Location: ../../public/sections/registros.php");
}
?>