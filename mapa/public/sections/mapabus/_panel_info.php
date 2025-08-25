<?php
// public/sections/mapabus/_panel_info.php
?>
<div id="info" class="card-estado">
  <div class="estado-header">
    <div class="estado-icon">
      <img src="<?= htmlspecialchars($iconoURL, ENT_QUOTES, 'UTF-8') ?>" alt="icono">
    </div>
    <div class="estado-info">
      <h3 id="tituloBus"><?= htmlspecialchars(strtoupper($busNombre), ENT_QUOTES, 'UTF-8') ?></h3>
      <h5 id="subtituloEstado" class="text-muted"></h5>
    </div>
  </div>
  <div id="detalle"></div>
</div>
