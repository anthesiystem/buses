<?php
session_start();
require_once __DIR__ . '/../server/config.php';
require_once __DIR__ . '/../server/bitacora_helper.php';

// Verificar que el usuario tenga una sesión válida y deba cambiar contraseña
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['debe_cambiar_pass'])) {
    header('Location: login.php');
    exit;
}

$msg = '';
$tipoMsg = 'danger';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_SESSION['usuario_id'];
    $pass1 = $_POST['nueva_contrasenia'] ?? '';
    $pass2 = $_POST['confirmar_contrasenia'] ?? '';
    
    if (strlen($pass1) < 6) {
        $msg = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($pass1 !== $pass2) {
        $msg = 'Las contraseñas no coinciden.';
    } elseif ($pass1 === 'admin') {
        $msg = 'No puede usar "admin" como nueva contraseña.';
    } else {
        try {
            $hash = password_hash($pass1, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('UPDATE usuario SET contrasenia=?, fecha_modificacion=NOW() WHERE ID=?');
            $ok = $stmt->execute([$hash, $id]);
            
            if ($ok) {
                // Registrar en bitácora
                $usuario_session = $_SESSION['cuenta'] ?? 'Usuario';
                $descripcion = "Usuario cambió contraseña temporal por una nueva";
                registrarBitacora($pdo, $usuario_session, 'usuario', 'cambio_password_temporal', $descripcion, $id);
                
                // Limpiar variables de sesión temporal
                unset($_SESSION['debe_cambiar_pass']);
                
                // Establecer sesión completa
                $sqlUser = "SELECT u.ID, u.cuenta, u.nivel, CAST(u.activo AS UNSIGNED) AS activo,
                                   p.nombre, p.apaterno, p.amaterno
                            FROM usuario u
                            INNER JOIN persona p ON u.Fk_persona = p.ID
                            WHERE u.ID = ?";
                $stmtUser = $pdo->prepare($sqlUser);
                $stmtUser->execute([$id]);
                $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);
                
                if ($userData) {
                    $_SESSION['usuario'] = [
                        'ID'      => (int)$userData['ID'],
                        'cuenta'  => $userData['cuenta'],
                        'nivel'   => (int)$userData['nivel'],
                        'nombre'  => $userData['nombre'],
                        'apaterno'=> $userData['apaterno'],
                        'amaterno'=> $userData['amaterno'],
                    ];
                    $_SESSION['fk_perfiles'] = (int)$userData['nivel'];
                    $_SESSION['nombre_completo'] = $userData['nombre'].' '.$userData['apaterno'].' '.$userData['amaterno'];
                    $_SESSION['ultima_actividad'] = time();
                }
                
                header('Location: index.php?msg=Contraseña actualizada correctamente');
                exit;
            } else {
                $msg = 'Error al actualizar la contraseña.';
            }
        } catch (Exception $e) {
            $msg = 'Error del sistema. Intente nuevamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar contraseña - Sistema</title>
    <link href="../server/style/bootstrap.min.css" rel="stylesheet">
    <link href="../server/style/all.min.css" rel="stylesheet">
    <style>
        body {
            background: url(img/fondo2.png) center center / contain, linear-gradient(135deg, #eaeaeaff 0%, #d8d8d8ff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .card-header {
            background: linear-gradient(135deg, #c53131ff 0%, #851616ff 100%);
            border-radius: 15px 15px 0 0 !important;
            color: white;
            text-align: center;
            padding: 1.5rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #c53131ff 0%, #851616ff 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 500;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-lock me-2"></i>
                        Cambiar Contraseña
                    </h4>
                    <p class="mb-0 mt-2 opacity-75">
                        Debe cambiar su contraseña temporal antes de continuar
                    </p>
                </div>
                <div class="card-body p-4">
                    <?php if ($msg): ?>
                        <div class="alert alert-<?= $tipoMsg ?> d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?= htmlspecialchars($msg) ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" id="formCambiarPass">
                        <div class="mb-3">
                            <label for="nueva_contrasenia" class="form-label">
                                <i class="fas fa-key me-1"></i>
                                Nueva contraseña
                            </label>
                            <input type="password" 
                                   class="form-control" 
                                   name="nueva_contrasenia" 
                                   id="nueva_contrasenia" 
                                   required 
                                   minlength="6"
                                   placeholder="Mínimo 6 caracteres">
                            <div class="form-text">
                                <small>La contraseña debe tener al menos 6 caracteres</small>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirmar_contrasenia" class="form-label">
                                <i class="fas fa-check-double me-1"></i>
                                Confirmar contraseña
                            </label>
                            <input type="password" 
                                   class="form-control" 
                                   name="confirmar_contrasenia" 
                                   id="confirmar_contrasenia" 
                                   required 
                                   minlength="6"
                                   placeholder="Repita la nueva contraseña">
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-2"></i>
                            Actualizar Contraseña
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../server/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('formCambiarPass').addEventListener('submit', function(e) {
    const pass1 = document.getElementById('nueva_contrasenia').value;
    const pass2 = document.getElementById('confirmar_contrasenia').value;
    
    if (pass1 !== pass2) {
        e.preventDefault();
        alert('Las contraseñas no coinciden');
        return false;
    }
    
    if (pass1.length < 6) {
        e.preventDefault();
        alert('La contraseña debe tener al menos 6 caracteres');
        return false;
    }
    
    if (pass1.toLowerCase() === 'admin') {
        e.preventDefault();
        alert('No puede usar "admin" como nueva contraseña');
        return false;
    }
    
    // Deshabilitar botón para evitar múltiples envíos
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Actualizando...';
});
</script>
</body>
</html>
