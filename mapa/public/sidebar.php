<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  .sidebar {
    background: #212529;
    position: fixed;
    top: 71px;
    bottom: 0;
    width: 60px;
    overflow: hidden;
    transition: width 0.2s ease-in-out;
    z-index: 1000;
  }

  .sidebar:hover {
    width: 220px;
  }

  .sidebar ul {
    list-style: none;
    padding: 0;
    margin: 0;
  }

  .sidebar li {
    display: flex;
    align-items: center;
    height: 50px;
  }

  .sidebar a {
    color: #aaa;
    text-decoration: none;
    display: flex;
    align-items: center;
    width: 100%;
    padding: 10px 15px;
    transition: background 0.2s;
  }

  .sidebar a:hover {
    background-color: #000;
    color: #fff;
  }

  .sidebar i {
    width: 30px;
    font-size: 18px;
    text-align: center;
  }

  .nav-text {
    font-size: 14px;
    font-family: 'Arial', sans-serif;
    white-space: nowrap;
    opacity: 0;
    transition: opacity 0.2s;
    padding-left: 13px;
  }

  .sidebar:hover .nav-text {
    opacity: 1;
  }

  .logout {
    position: absolute;
    bottom: 0;
    width: 100%;
  }

   .imgsdb {

  }

  .imgsdb.hover {
    margin: 2px;
    padding-left: 2px;
  }
 
</style>

<div class="sidebar">
  <ul>
    <li><a href="tablero.php"><img class="imgsdb" src="../icons/tab.png" style="width: 1.3vw; height: 2.8vh;;;"/><span class="nav-text">TABLERO</span></a></li>
    <li><a href="index.php"><img class="imgsdb" src="../icons/map.png" style="width: 1.3vw; height: 2.8vh;;;"/><span class="nav-text">BUS-PROD</span></a></li>
    <li><a href="vryr.php"><img class="imgsdb" src="../icons/vryr.png" style="width: 1.3vw; height: 2.8vh;;;"/><span class="nav-text"> VRYR</span></a></li>
    <li><a href="rnl.php"><img class="imgsdb" src="../icons/rnl.png" style="width: 1.3vw; height: 2.8vh;"/><span class="nav-text">RNL</span></a></li>
    <li><a href="rnip.php"><img class="imgsdb" src="../icons/rnip.png" style="width: 1.3vw; height: 2.8vh;;"/><span class="nav-text">RNIP</span></a></li>
    <li><a href="mj.php"><img class="imgsdb" src="../icons/mj.png" style="width: 1.3vw; height: 2.8vh;"/><span class="nav-text">MJ</span></a></li>
    <li><a href="cup.php"><img class="imgsdb" src="../icons/cup.png" style="width: 1.3vw; height: 2.8vh;"/><span class="nav-text">CUP</span></a></li>
    <li><a href="911.php"><img class="imgsdb" src="../icons/911.png" style="width: 1.3vw; height: 2.8vh;"/><span class="nav-text"> 911</span></a></li>
    <li><a href="lpr.php"><img class="imgsdb" src="../icons/lpr.png" style="width: 1.3vw; height: 2.8vh;"/><span class="nav-text">LPR</span></a></li>
    <li><a href="rnae.php"><img class="imgsdb" src="../icons/rnae.png" style="width: 1.3vw; height: 2.8vh;"/><span class="nav-text">RNAE</span></a></li>
    <li><a href="eo.php"><img class="imgsdb" src="../icons/eo.png" style="width: 1.3vw; height: 2.8vh;"/><span class="nav-text">EO</span></a></li>
    <li><a href="vo.php"><img class="imgsdb" src="../icons/vo.png" style="width: 1.3vw; height: 2.8vh;"/><span class="nav-text">VO</span></a></li>

    <?php if (isset($_SESSION['fk_id_perfiles']) && ($_SESSION['fk_id_perfiles'] == 2 || $_SESSION['fk_id_perfiles'] == 4)): ?>
      <li><a href="registros.php"><img class="imgsdb" src="../icons/reg.png" style="width: 1.3vw; height: 2.8vh;"/><span class="nav-text">REGISTROS</span></a></li>
      <li><a href="catalogos.php"><img class="imgsdb" src="../icons/cat.png" style="width: 1.3vw; height: 2.8vh;"/><span class="nav-text">CATALOGOS</span></a></li>
      <li><a href="bitacora.php"><img class="imgsdb" src="../icons/bit.png" style="width: 1.3vw; height: 2.8vh;"/><span class="nav-text">BITACORA</span></a></li>
      <?php endif; ?>
  </ul>

  <ul class="logout">
    <li><a href="logout.php"><img class="imgsdb" src="../icons/lg.png" style="width: 1.3vw; height: 2.8vh;"/><span class="nav-text">Salir</span></a></li>
  </ul>
</div>
