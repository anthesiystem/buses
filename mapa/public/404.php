<?php
session_start();
http_response_code(404);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Error 404 - Página no encontrada</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../server/style.css">
  <style>
    .contenido404 {
      display: flex;
      justify-content: center;
      align-items: center;
      height: calc(100vh - 70px);
      text-align: center;
    }
    .contenido404 h1 {
      font-size: 5rem;
    }
    .contenido404 p {
      font-size: 1.2rem;
    }
  </style>
</head>
<body class="bg-light">

  <?php include 'navbar.php'; ?>
  <?php include 'sidebar.php'; ?>

  <div class="contenedor contenido404">
    <div>
      <h1>404</h1>
      <p>La página que buscas no existe o fue movida.</p>
      <a href="index.php" class="btn btn-primary mt-3">Volver al inicio</a>
    </div>
  </div>

</body>
</html>
