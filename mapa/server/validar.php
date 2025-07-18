<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$db = 'busmap';
$user = 'admin';
$pass = 'admin1234';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

if (isset($_POST['usuario']) && isset($_POST['password'])) {
    $usuario = $_POST['usuario'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT Id, User, Password, fk_id_perfiles FROM usuarios WHERE User = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        // ← Comparación directa si no usas hash (para desarrollo)
        if ($password === $row['Password']) {
            $_SESSION['usuario'] = $row['User'];
            $_SESSION['fk_id_perfiles'] = $row['fk_id_perfiles'];
            $_SESSION['usuario_id'] = $row['Id']; // ✅ ESTA ES LA CLAVE
            $_SESSION['ultima_actividad'] = time();
            


            // Guardar en logsesion
            $stmt = $conn->prepare("INSERT INTO logsesion (Fk_Id_User, Tipo_Evento) VALUES (?, 'LOGIN')");
            $stmt->bind_param("i", $row['Id']); // Suponiendo que $row contiene al usuario validado
            $stmt->execute();



             header("Location: ../public/tablero.php");
                            exit();
                        } else {
                            header("Location: ../public/login.php?error=1");
                            exit();
                        }
                    } else {
                        header("Location: ../public/login.php?error=1");
                        exit();
                    }

                    $stmt->close();
                } else {
                    header("Location: ../public/login.php?error=2");
                    exit();
                }

$conn->close();
?>
