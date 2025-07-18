<head>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

 <link rel="icon" type="image/png" sizes="32x32" href="/favicon.png">
 <style>
  .dropdown-menu.show {
    position: absolute !important;
    z-index: 3000 !important;
}

 </style>

</head>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="tablero.php">TABLERO</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarOpciones" aria-controls="navbarOpciones" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarOpciones">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="index.php">Bus Prod</a></li>
        <li class="nav-item"><a class="nav-link" href="vryr.php">VRyR</a></li>
        <li class="nav-item"><a class="nav-link" href="rnl.php">LC</a></li>
        <li class="nav-item"><a class="nav-link" href="rnip.php">RNIP</a></li>
        <li class="nav-item"><a class="nav-link" href="mj.php">MJ</a></li>
        <li class="nav-item"><a class="nav-link" href="cup.php">CUP</a></li>
        <li class="nav-item"><a class="nav-link" href="911.php">911</a></li>
        <li class="nav-item"><a class="nav-link" href="lpr.php">LPR</a></li>
        <li class="nav-item"><a class="nav-link" href="rnae.php">RNAE</a></li>
        <li class="nav-item"><a class="nav-link" href="eo.php">EO</a></li>
        <li class="nav-item"><a class="nav-link" href="vo.php">VO</a></li>

        <?php if (isset($_SESSION['nivel']) && ($_SESSION['nivel'] == 2 || $_SESSION['nivel'] == 3)): ?>
        <li class="nav-item"><a class="nav-link" href="vo.php">REGISTROS</a></li>
        <li class="nav-item"><a class="nav-link" href="vo.php">CATALOGOS</a></li>
        <?php endif; ?>

        <li class="nav-item">
          <a class="nav-link" href="logout.php">Salir</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>


