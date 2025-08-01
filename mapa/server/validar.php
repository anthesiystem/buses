<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';


if (isset($_POST['usuario']) && isset($_POST['password'])) {
    $usuario = $_POST['usuario'];
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT ID, cuenta, contrasena, nivel FROM usuario WHERE cuenta = ? ");
        $stmt->execute([$usuario]);
        $row = $stmt->fetch();

        if ($row) {
            // Comparar contraseñas (sin hash por ahora)

            echo 'Input usuario: ' . $usuario . '<br>';
            echo 'Input pass: ' . $password . '<br>';
            echo 'DB pass: ' . $row['contrasena'] . '<br>';


            if (trim($password) === trim($row['contrasena'])) {
                $_SESSION['usuario'] = $row['cuenta'];
                $_SESSION['usuario_id'] = $row['ID'];
                $_SESSION['fk_perfiles'] = $row['nivel']; // Aquí se asigna correctamente el nivel
                $_SESSION['ultima_actividad'] = time();

                // Cargar permisos del usuario
                require_once 'permiso.php';
                cargarPermisos($row['ID'], $pdo);

                
                // Registrar login
                $stmtLog = $pdo->prepare("INSERT INTO sesion (Fk_usuario, Tipo_evento) VALUES (?, 'LOGIN')");
                $stmtLog->execute([$row['ID']]);

                header("Location: ../public/index.php");
                exit();
            } else {
                header("Location: ../public/login.php?error=1"); // Contraseña incorrecta
                exit();
            }
        } else {
            header("Location: ../public/login.php?error=1"); // Usuario no encontrado
            exit();
        }
    } catch (PDOException $e) {
 echo "❌ Error de conexión o consulta: " . $e->getMessage();
    exit;
    }
} else {
    header("Location: ../public/login.php?error=2"); // Datos incompletos
    exit();
}
