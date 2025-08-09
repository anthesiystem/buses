<?php
require_once '../../../server/config.php';
$registros = $pdo->query("
  SELECT r.*, 
         e.descripcion AS Entidad, 
         d.descripcion AS Dependencia, 
         b.descripcion AS Bus, 
         mb.descripcion AS Motor_Base, 
         v.descripcion AS Version, 
         eb.descripcion AS Estado
  FROM registro r
  LEFT JOIN entidad e ON e.ID = r.Fk_entidad
  LEFT JOIN dependencia d ON d.ID = r.Fk_dependencia
  LEFT JOIN bus b ON b.ID = r.Fk_bus
  LEFT JOIN motor_base mb ON mb.ID = r.Fk_motor_base
  LEFT JOIN version v ON v.ID = r.Fk_version
  LEFT JOIN estado_bus eb ON eb.ID = r.Fk_estado_bus
  WHERE r.activo = 1
")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es" class="h-100">
<head>
  <meta charset="UTF-8">
  <title>Registros</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex flex-column h-100">

<main class="flex-shrink-0">
  <div class="container">
    <h3 class="my-3" id="titulo">Registros</h3>

    <a href="nuevo.php" class="btn btn-success">Agregar</a>

    <table class="table table-bordered table-hover my-3">
      <thead class="table-dark">
        <tr>
          <th>ID</th>
          <th>Entidad</th>
          <th>Dependencia</th>
          <th>Bus</th>
          <th>Engine</th>
          <th>Versión</th>
          <th>Estado</th>
          <th>Inicio</th>
          <th>Migración</th>
          <th>Avance</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($registros as $r): ?>
          <tr>
            <td><?= $r['ID'] ?></td>
            <td><?= $r['Entidad'] ?></td>
            <td><?= $r['Dependencia'] ?></td>
            <td><?= $r['Bus'] ?></td>
            <td><?= $r['Motor_Base'] ?></td>
            <td><?= $r['Version'] ?></td>
            <td><?= $r['Estado'] ?></td>
            <td><?= $r['fecha_inicio'] ?></td>
            <td><?= $r['fecha_migracion'] ?></td>
            <td><?= $r['avance'] ?>%</td>
            <td>
              <a href="sections/busreg/edita.php?id=<?= $r['ID'] ?>" class="btn btn-warning"><img src="/mapa/public/icons/edita.png" alt="Comentarios" width="20" height="20"></a>
              <button type="button" class="btn btn-info btn-sm"
        data-bs-toggle="modal"
        data-bs-target="#modalComentarios"
        data-bs-id="<?= $r['ID'] ?>">
  <img src="/mapa/public/icons/bitacora.png" alt="Comentarios" width="20" height="20">
</button>

              <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal"
                      data-bs-target="#eliminaModal" data-bs-id="<?= $r['ID'] ?>">
                    <img src="/mapa/public/icons/elimina.png" alt="Comentarios" width="20" height="20">
                    </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>




<footer class="footer mt-auto py-3 bg-body-tertiary">
  <div class="container">
    <span class="text-body-secondary">2025 | Sistema de Seguimiento</span>
  </div>
</footer>

<!-- Modal de eliminación -->
<div class="modal fade" id="eliminaModal" tabindex="-1" aria-labelledby="eliminaModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="elimina.php" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="eliminaModalLabel">Confirmar eliminación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        ¿Estás seguro de que deseas eliminar este registro?
      </div>
      <div class="modal-footer">
        <input type="hidden" name="id" id="idEliminar">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-danger">Eliminar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal de comentarios (contenedor) -->
<div class="modal fade" id="modalComentarios" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content" id="contenedorComentarios">
      <div class="modal-body text-center p-5">Cargando…</div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
  const modalComentarios = document.getElementById('modalComentarios');
  const contenedorComentarios = document.getElementById('contenedorComentarios');

  modalComentarios.addEventListener('show.bs.modal', async (event) => {
    const button = event.relatedTarget;
    const id = button.getAttribute('data-bs-id');
    await cargarComentarios(id);
  });

  async function cargarComentarios(idRegistro, fase = null) {
    contenedorComentarios.innerHTML = '<div class="modal-body text-center p-5">Cargando…</div>';

    const url = new URL('/mapa/public/sections/lineadetiempo/comentarios_modal.php', location.origin);
    url.searchParams.set('id', idRegistro);
    if (fase && fase !== '__ALL__') url.searchParams.set('fase', fase); // opcional: filtro por servidor

    const res  = await fetch(url.toString(), { cache: 'no-store' });
    const html = await res.text();
    contenedorComentarios.innerHTML = html;

    // IMPORTANTÍSIMO: volver a enganchar eventos cada vez que reemplazas HTML
    wireUpComentarioForm();
    wireUpFiltroFase(idRegistro);
  }

  function wireUpComentarioForm() {
    const form = contenedorComentarios.querySelector('#formComentario');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd  = new FormData(form);
      const btn = form.querySelector('button[type="submit"]');
      const txt = btn.textContent;
      btn.disabled = true; btn.textContent = 'Guardando…';
      try {
        // OJO: ruta real de guardar_comentario.php
        const resp = await fetch('/mapa/public/sections/lineadetiempo/guardar_comentario.php', { method: 'POST', body: fd });
        const data = await resp.json();
        if (!data.ok) throw new Error(data.msg || 'No se pudo guardar');
        // recargar modal (misma fase seleccionada si existe)
        const faseActiva = contenedorComentarios.querySelector('#faseBar .active')?.getAttribute('data-fase') || '__ALL__';
        await cargarComentarios(fd.get('Fk_registro'), faseActiva);
      } catch (err) {
        alert('Error: ' + err.message);
      } finally {
        btn.disabled = false; btn.textContent = txt;
      }
    });
  }

  function wireUpFiltroFase(idRegistro) {
    const bar = contenedorComentarios.querySelector('#faseBar');
    if (!bar) return;

    bar.addEventListener('click', async (ev) => {
      const btn = ev.target.closest('button[data-fase]');
      if (!btn) return;

      // visual
      [...bar.querySelectorAll('button')].forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const fase = btn.getAttribute('data-fase');

      // ---- Opción A: filtrar en el cliente (sin pedir al servidor) ----
      const items = contenedorComentarios.querySelectorAll('.tl-item');
      if (fase === '__ALL__') {
        items.forEach(el => el.classList.remove('d-none'));
      } else {
        items.forEach(el => el.classList.toggle('d-none', (el.getAttribute('data-fase') || '').trim() !== fase));
      }

      // ---- Opción B (alternativa): pedir al servidor ya filtrado ----
      // await cargarComentarios(idRegistro, fase);
    });
  }
</script>


</body>
</html>
