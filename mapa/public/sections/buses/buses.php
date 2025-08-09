<?php require_once '../../../server/config.php'; ?>
<!-- Bootstrap JS Bundle (incluye Popper) -->






<div class="container mt-4">
  <h4 class="mb-3">Administración de Buses</h4>
  <button class="btn btn-success mb-3" onclick="abrirModalBus()">➕ Agregar Bus</button>

  <table class="table table-bordered table-hover align-middle text-center">
    <thead class="table-dark">
      <tr>
        <th>ID</th>
        <th>Descripción</th>
        <th>Color Implementado</th>
        <th>Color Sin Implementar</th>
        <th>Pruebas</th>
        <th>Imagen</th>
        <th>Estado</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody id="tablaBuses"></tbody>
  </table>
</div>

<?php include 'modal_bus.php'; ?>

<script src="buses.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>