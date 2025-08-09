<?php
session_start();

// Mostrar mensaje si se viene de logout o error
$mensaje = '';
$tipoMensaje = 'error'; // 'error' o 'success'

if (isset($_GET['error'])) {
    switch($_GET['error']) {
        case '1':
            $mensaje = 'Usuario o contraseña incorrectos.';
            break;
        case '2':
            $mensaje = 'Por favor, complete todos los campos.';
            break;
        default:
            $mensaje = 'Error al intentar iniciar sesión.';
    }
    $tipoMensaje = 'error';
} elseif (isset($_GET['logout'])) {
    $mensaje = 'Sesión finalizada correctamente.';
    $tipoMensaje = 'success';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Iniciar Sesión</title>
  <style>
    * {
  font-family: -apple-system, BlinkMacSystemFont, "San Francisco", Helvetica, Arial, sans-serif;
  font-weight: 300;
  margin: 0;
  box-sizing: border-box;
}

html, body {
  height: 100vh;
  width: 100vw;
  margin: 0;
  display: flex;
  align-items: flex-start;
  justify-content: flex-start;
  background: url(img/fondo2.png) center center / contain, linear-gradient(244deg, #ffffff 0%, #ffffff 50%, #c7c7c7 100%)
}

h4 {
  font-size: 26px;
  font-weight: 600;
  color: #000;
  opacity: 0.85;
  margin-bottom: 20px;
}

label {
  font-size: 12.5px;
  color: #000;
  opacity: 0.8;
  font-weight: 400;
}

form {
  padding: 40px 30px 20px 30px;
  background: #fefefe;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

form p {
  line-height: 155%;
  margin-bottom: 40px;
  font-size: 14px;
  color: #000;
  opacity: 0.65;
  font-weight: 400;
  max-width: 200px;
}

a.discrete {
  color: rgba(0, 0, 0, 0.4);
  font-size: 14px;
  border-bottom: 1px solid rgba(0, 0, 0, 0);
  padding-bottom: 4px;
  margin-left: auto;
  font-weight: 300;
  transition: all 0.3s ease;
  margin-top: 40px;
  text-decoration: none;
}

a.discrete:hover {
  border-bottom: 1px solid rgba(0, 0, 0, 0.2);
}

button {
  -webkit-appearance: none;
  width: auto;
  min-width: 100px;
  border-radius: 24px;
  text-align: center;
  padding: 15px 40px;
  margin-top: 5px;
  background-color: #9b2247;
  color: #fff;
  font-size: 14px;
  margin: auto;
  font-weight: 500;
  box-shadow: 0px 2px 6px -1px rgba(0, 0, 0, 0.13);
  border: none;
  transition: all 0.3s ease;
  outline: 0;
}

button:hover {
  transform: translateY(-3px);
  box-shadow: 0 2px 6px -1px rgba(124, 27, 56, 0.65);
}

button:active {
  transform: scale(0.99);
}

input {
  font-size: 16px;
  padding: 20px 0px;
  height: 56px;
  border: none;
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
  background: #fff;
  min-width: 280px;
  transition: all 0.3s linear;
  color: #000;
  font-weight: 400;
  -webkit-appearance: none;
}

input:focus {
  border-bottom: 1px solid rgba(230, 157, 157, 1);
  outline: 0;
  box-shadow: 0 2px 6px -8px rgba(230, 157, 174, 0.45);
}

.floating-label {
  position: relative;
  margin-bottom: 10px;
}

.floating-label label {
  position: absolute;
  top: calc(50% - 7px);
  left: 0;
  opacity: 0;
  transition: all 0.3s ease;
}

.floating-label input:not(:placeholder-shown) {
  padding: 28px 0px 12px 0px;
}

.floating-label input:not(:placeholder-shown) + label {
  transform: translateY(-10px);
  opacity: 0.7;
}

.session {
  display: flex;
  flex-direction: row;
  width: auto;
  height: auto;
  margin: auto auto;
  background: #ffffff;
  border-radius: 4px;
  box-shadow: 0px 2px 6px -1px rgba(0, 0, 0, 0.12);
}

.left {
  width: 220px;
  min-height: 100%;
  position: relative;
  background-image: url("img/login.jpg");
  background-size: cover;
  border-top-left-radius: 4px;
  border-bottom-left-radius: 4px;
}

.left svg {
  height: 40px;
  width: auto;
  margin: 20px;
}
.iconuser{
  margin:0 auto;
  display: flex;

  padding: 0;
}
  </style>
</head>
<body>
  <div class="session">
    <div class="left">

    </div>
    <form action="../server/validar.php" method="POST" class="log-in" autocomplete="off"> 
      <h4><span>Seguimiento de Buses</span></h4>
      <div id="iconuser" style="margin: auto;"><img src="img/iconuser.png" alt=""></div>

    <?php if ($mensaje): ?>
      <div style="
        background: <?php echo $tipoMensaje === 'error' ? '#f8d7da' : '#d4edda'; ?>;
        color: <?php echo $tipoMensaje === 'error' ? '#842029' : '#155724'; ?>;
        border: 1px solid <?php echo $tipoMensaje === 'error' ? '#f5c2c7' : '#c3e6cb'; ?>;
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 20px;
        font-size: 14px;
        width: 100%;
        text-align: center;
      ">
        <?= htmlspecialchars($mensaje) ?>
      </div>
    <?php endif; ?>


      <div class="floating-label">
        <input placeholder="Usuario" type="text" name="usuario" id="usuario" required autocomplete="off">
        <label for="usuario">Usuario:</label>
      </div>

      <div class="floating-label">
        <input placeholder="Contraseña" type="password" name="password" id="password" required autocomplete="off">
        <label for="password">Contraseña:</label>
      </div>

      <button type="submit">Ingresar</button>
    </form>
  </div>
<script>
  document.querySelector('form').addEventListener('submit', function(e) {
    const usuario = document.getElementById('usuario').value.trim();
    const password = document.getElementById('password').value.trim();
    
    if (!usuario || !password) {
      e.preventDefault();
      window.location.href = 'login.php?error=2';
      return;
    }
    
    // Deshabilitar el botón de envío para prevenir múltiples submissions
    const submitButton = this.querySelector('button[type="submit"]');
    if (submitButton) {
      submitButton.disabled = true;
      submitButton.textContent = 'Ingresando...';
    }
  });
</script>


</body>
</html>