<table class="table table-bordered table-sm align-middle text-center" id="tablaRegistros">
  <thead class="table-dark">
    <tr>
      <th>ID</th>
      <th>Dependencia</th>
      <th>Entidad</th>
      <th>Bus</th>
      <th>Engine</th>
      <th>Versión</th>
      <th>Estatus</th>
      <th>Categoría</th>
      <th>Inicio</th>
      <th>Migración</th>
      <th>Avance</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody id="cuerpoTabla">
    <!-- Los registros se cargarán dinámicamente desde registros.js -->
  </tbody>
</table>

<div id="paginacion" class="d-flex justify-content-center my-3"></div>


<!-- Indicador de carga -->
<div id="loader" class="text-center my-3 d-none">
  <div class="spinner-border text-primary" role="status"></div>
  <p class="mt-2">Cargando registros...</p>
</div>
