<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../session_acl.php';

$estado = $_GET['estado'] ?? '';
$estado = trim($estado);

// Definir permisos para comentarios
$puedeVerComentarios = estaAutenticado() && tienePermiso('read', $estado);
$puedeCrearComentarios = estaAutenticado() && tienePermiso('create', $estado);

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$sql = "
SELECT 
    r.ID,
    r.Fk_motor_base,
    r.Fk_dependencia,
    r.Fk_entidad,
    r.Fk_bus,
    r.Fk_estado_bus,
    r.Fk_categoria,
    r.Fk_tecnologia,
    r.Fk_etapa,
    r.fecha_inicio,
    r.fecha_migracion,
    r.fecha_creacion,

    b.descripcion  AS bus_nombre,
    eb.descripcion AS estado_nombre,
    t.numero_version AS version,          -- ✔ viene de tecnologia
    t.descripcion   AS tecnologia,        -- ✔ viene de tecnologia
    COALESCE(NULLIF(TRIM(d.siglas),''), d.descripcion) AS dependencia,  -- ← aquí las SIGLAS
    e.descripcion AS entidad,
    c.descripcion AS categoria,
    en.descripcion AS motor_base_nombre,
    et.descripcion AS etapa,
    et.avance      AS avance              -- ✔ porcentaje desde etapa
FROM registro r
INNER JOIN entidad     e  ON e.ID  = r.Fk_entidad
INNER JOIN estado_bus  eb ON eb.ID = r.Fk_estado_bus
LEFT  JOIN bus         b  ON b.ID  = r.Fk_bus 
                         AND b.activo = 1                  -- 🔹 sólo buses activos
LEFT  JOIN dependencia d  ON d.ID  = r.Fk_dependencia
INNER JOIN categoria   c  ON c.ID  = r.Fk_categoria
INNER JOIN motor_base  en ON en.ID = r.Fk_motor_base
INNER JOIN tecnologia  t  ON t.ID  = r.Fk_tecnologia
LEFT  JOIN etapa       et ON et.ID = r.Fk_etapa
WHERE UPPER(TRIM(e.descripcion)) = UPPER(TRIM(:estado))
  AND r.activo = 1                                         -- 🔹 sólo registros activos
ORDER BY FIELD(c.descripcion, 'Productivos','Centrales','Migraciones','Pruebas','PRUEBAS-MIGRADOS'),
         c.descripcion,
         b.descripcion
";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':estado', $estado, PDO::PARAM_STR);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$registros = [];
$total = 0;

foreach ($rows as $row) {
  $cat = ucfirst(strtolower($row['categoria'] ?? 'Otro'));
  $registros[$cat][] = $row;
  $total++;
}

// Encabezado
echo '<div class="float-end">';
echo '<button class="btn btn-outline-dark btn-sm" data-bs-toggle="modal" data-bs-target="#modalDetalles">
        <i class="bi bi-table"></i> VER DETALLES
      </button>';
echo '</div>';
echo "<h3><strong>TOTAL DE BUSES:</strong> $total</h3>";

// Orden preferido de categorías
$orden = ['Productivos','Centrales','Migraciones','Pruebas'];

// Colores/acento por catálogo (puedes ajustar)
$catAccents = [
  'Productivos' => '#22c55e',
  'Centrales'   => '#0ea5e9',
  'Migraciones' => '#f59e0b',
  'Pruebas'     => '#ef4444',
  'Otro'        => '#6b7280',
];

// Helper de badge por estatus (todo por catálogo, sin IDs mágicos)
$estatusClass = function($tx) {
  $tx = strtoupper(trim($tx ?? ''));
  if ($tx === 'IMPLEMENTADO') return 'b-ok';
  if ($tx === 'PRUEBAS')      return 'b-warn';
  if ($tx === 'SIN IMPLEMENTAR') return 'b-off';
  return 'b-off';
};
?>

<!-- ESTILOS Opción C -->
<style>
  .m1c{--border:#e8ebf1;--muted:#f7f9fc;--thead:#fff7fb;--thead-txt:#b1124b;
       --ok:#0f6b3f;--okbg:#e7f6ec;--warn:#7a5a00;--warnbg:#fff6da;--off:#475569;--offbg:#eef1f4;--accent:#ec407a;}
  .m1c .wrap{border:1px solid var(--border);border-radius:14px;background:#fff;padding:12px}
  .m1c .tablebox{overflow:auto;border-radius:10px}
  .m1c table{width:100%;border-collapse:separate;border-spacing:0;font-size:.9rem}
  .m1c thead th{position:sticky;top:0;background:var(--thead);color:var(--thead-txt);
                font-weight:900;border-bottom:1px solid var(--border);padding:.65rem .6rem;text-align:center;white-space:nowrap}
  .m1c tbody td{padding:.55rem .6rem;border-bottom:1px solid var(--border);background:#fff;vertical-align:middle}
  .m1c tbody tr:nth-child(even) td{background:var(--muted)}
  .m1c tbody tr:hover td{background:#fff2f7}

  /* Acento izquierdo por categoría (usa variable por fila) */
  .m1c tr.accent td:first-child{position:relative}
  .m1c tr.accent td:first-child::before{
    content:"";position:absolute;left:-1px;top:-1px;bottom:-1px;width:4px;border-radius:4px 0 0 4px;background:var(--accent-color,#ec407a)
  }

  /* Chips y badges */
  .m1c .chip{padding:.25rem .6rem;border-radius:999px;background:#f0f3f9;font-weight:800;font-size:.82rem;color:#334155}
  .m1c .bus{font-weight:900;letter-spacing:.2px}
  .m1c .ver{font-family:ui-monospace,monospace;background:#f2f4f9;border-radius:.4rem;padding:.22rem .45rem;font-weight:700}
  .m1c .badge{padding:.3rem .5rem;border-radius:.45rem;font-weight:800;font-size:.82rem}
  .m1c .b-ok{background:var(--okbg);color:var(--ok)}
  .m1c .b-warn{background:var(--warnbg);color:var(--warn)}
  .m1c .b-off{background:var(--offbg);color:var(--off)}

  /* Botón de comentarios */
  .m1c .btn-comentarios {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #0d6efd;
    padding: 0.25rem;
    line-height: 1;
    border: 1px solid #0d6efd;
    background: white;
    border-radius: 4px;
    width: 32px;
    height: 32px;
    cursor: pointer;
    transition: all 0.2s ease;
  }
  .m1c .btn-comentarios:hover {
    transform: scale(1.1);
    background: #0d6efd;
    color: white;
  }
  .m1c .btn-comentarios i {
    font-size: 1rem;
  }

  /* Avance */
  .m1c .progress{height:7px;background:#edf0f6;border-radius:999px;overflow:hidden}
  .m1c .bar{height:100%;background:var(--accent)}
  <!-- MODO COMPACTO (Opción C) -->
<style>
  /* Ensancha un poco el modal en desktop */
  @media (min-width: 1200px){
    #modalDetalles .modal-dialog.modal-xl{ max-width:95%; }
  }

  /* Compacta tipografía, paddings y controles */
  .m1c.m1c-compact .wrap{ padding:8px; }
  .m1c.m1c-compact table{ font-size:.82rem; }
  .m1c.m1c-compact thead th{
    padding:.45rem .45rem;
    font-weight:800;
  }
  .m1c.m1c-compact tbody td{
    padding:.38rem .45rem;
  }
  .m1c.m1c-compact .chip{
    padding:.18rem .45rem;
    font-size:.74rem;
    font-weight:800;
  }
  .m1c.m1c-compact .ver{
    padding:.16rem .35rem;
    font-size:.82rem;
    font-weight:700;
  }
  .m1c.m1c-compact .badge{
    padding:.25rem .40rem;
    font-size:.78rem;
    font-weight:800;
  }
  .m1c.m1c-compact .progress{ height:5px; }
  .m1c.m1c-compact th:last-child{ min-width:120px; } /* columna Avance */
  
  /* Opcional: evita desbordes en nombres largos de BUS */
  .m1c.m1c-compact td:nth-child(2){
    text-overflow:ellipsis; white-space:nowrap; overflow:hidden; max-width:320px;
  }
  @media (max-width: 992px){
    .m1c.m1c-compact td:nth-child(2){ max-width:220px; }
  }
</style>

</style>

<div class="m1c m1c-compact mt-3">
  <div class="wrap">
    <div class="tablebox">
      <table class="w-100">
        <thead>
          <tr>
            <th>Categoría</th>
            <th>Bus</th>
            <th>Tecnología</th>
            <th>Dependencia</th>
            <th style="min-width:150px">Avance</th>
            <th style="width:50px">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php
          // 1) Categorías en orden preferido
          foreach ($orden as $cat) {
            if (!isset($registros[$cat])) continue;
            foreach ($registros[$cat] as $row) {
              $accent = $catAccents[$cat] ?? $catAccents['Otro'];
              $tec = trim((string)($row['tecnologia'] ?? ''));
              $por = max(0, min(100, (int)($row['avance'] ?? 0)));
              $dep = $estatusClass($row['dependencia'] ?? '');
              
              // Botón de comentarios HTML
              $btnComentarios = $puedeVerComentarios ? 
                  "<button class='btn-comentarios modalbitacora' 
                          data-bs-toggle='modal' 
                          data-bs-target='#modalComentarios' 
                          data-bs-id='" . (int)$row['ID'] . "'
                          data-bus-nombre='" . h($row['bus_nombre']) . "'
                          data-estado='" . h($estado) . "'
                          data-puede-crear='1'
                          title='Ver comentarios'>
                      <i class='bi bi-chat-dots-fill'></i>
                  </button>" : "";

              echo "<tr class='accent' style='--accent-color: {$accent}'>
                      <td><span class='chip'>".h($cat)."</span></td>
                      <td class='bus'>".h($row['bus_nombre'])."</td>
                      <td><span class='ver'>".h($tec)."</span></td>
                      <td><span class='badge {$dep}'>".h($row['dependencia'])."</span></td>
                      <td>
                        <div class='progress mb-1'><div class='bar' style='width: {$por}%'></div></div>
                        <small class='text-body-secondary fw-bold'>{$por}%</small>
                      </td>
                      <td class='text-center'>{$btnComentarios}</td>
                    </tr>";
            }
          }

          // 2) Resto de categorías
          foreach ($registros as $cat => $filas) {
            if (in_array($cat, $orden, true)) continue;
            foreach ($filas as $row) {
              $accent = $catAccents['Otro'];
              $tec = trim((string)($row['tecnologia'] ?? ''));
              $por = max(0, min(100, (int)($row['avance'] ?? 0)));
              $dep = $estatusClass($row['dependencia'] ?? '');
              echo "<tr class='accent' style='--accent-color: {$accent}'>
                      <td><span class='chip'>".h($cat)."</span></td>
                      <td class='bus'>".h($row['bus_nombre'])."</td>
                      <td><span class='ver'>".h($tec)."</span></td>
                      <td><span class='badge {$dep}'>".h($row['dependencia'])."</span></td>
                      <td>
                        <div class='progress mb-1'><div class='bar' style='width: {$por}%'></div></div>
                        <small class='text-body-secondary fw-bold'>{$por}%</small>
                      </td>
                      <td class='text-center'>
                        <button type='button' class='btn-comentarios modalbitacora'
                                title='Ver comentarios'
                                data-bs-toggle='modal'
                                data-bs-target='#modalComentarios'
                                data-bs-id='". (int)$row['ID'] ."'
                                data-bus-nombre='" . h($row['bus_nombre']) . "'
                                data-estado='" . h($estado) . "'
                                data-puede-crear='" . ($puedeCrearComentarios ? '1' : '0') . "'>
                            <i class='bi bi-chat-dots-fill'></i>
                        </button>
                      </td>
                    </tr>";
            }
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<!-- MODAL DETALLE DE REGISTROS -->
<!-- ESTILOS PARA EL MODAL DETALLES -->
<style>
  /* Modal */
  .modal-content.detalles {
    border: 0;
    border-radius: 1rem;
    box-shadow: 0 20px 45px rgba(0,0,0,.15);
    overflow: hidden;
  }
  .modal-header.detalles {
    background: linear-gradient(135deg, #911f1f, #fd0d39);
    color: #fff;
    border: 0;
  }
  .modal-header.detalles .btn-close {
    filter: invert(1) grayscale(100%);
  }

  /* Tabla */
  .detalles-table {
    --row-pad-y: .6rem;
    --row-pad-x: .75rem;
    --font-size: .95rem;
    --radius: .75rem;
    --thead-bg: #ffcfcf;
    --thead-color: #fd0d59;
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
    font-size: var(--font-size);
    overflow: hidden;
    border-radius: var(--radius);
  }
  .detalles-table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: var(--thead-bg);
    color: var(--thead-color);
    font-weight: 700;
    border-bottom: 1px solid var(--bs-border-color);
    padding: .7rem var(--row-pad-x);
    white-space: nowrap;
    text-align: center;
  }
  .detalles-table tbody td {
    padding: var(--row-pad-y) var(--row-pad-x);
    vertical-align: middle;
    border-bottom: 1px solid var(--bs-border-color);
    background: var(--bs-body-bg);
  }
  .detalles-table tbody tr:hover td {
    background: #f6e4e492;
  }
  .detalles-table tr:nth-child(even) td {
    background: #f7f4f499;
  }
  .detalles-table .badge-soft {
    background: rgba(125, 108, 111, 0.12);
    color: #6c757d;
    font-weight: 600;
    padding: .35rem .55rem;
    border-radius: .5rem;
  }
  .detalles-table .text-strong {
    font-weight: 700;
    color: var(--bs-emphasis-color);
  }
  .detalles-table .text-accent {
    font-weight: 600;
    color: #e90e45;
  }
  /* Ensanchar modal-xl solo para Detalles */
@media (min-width: 1200px) {
  #modalDetalles .modal-dialog.modal-xl {
    max-width: 95%; /* antes ~1140px, ahora hasta 95% del viewport */
  }
}
#modalDetalles .detalles-table {
  font-size: 0.85rem;   /* antes 0.95rem */
}

#modalDetalles .detalles-table thead th,
#modalDetalles .detalles-table tbody td {
  padding: .45rem .5rem; /* menos padding */
}
#modalDetalles .table-responsive {
  overflow-x: auto;
}

</style>

<!-- MODAL DETALLES -->
<div class="modal fade" id="modalDetalles" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content detalles">
      <div class="modal-header detalles d-flex justify-content-between align-items-center">
        <h5 class="modal-title">Detalle de Registros</h5>
        <div class="d-flex align-items-center gap-2">
          <button type="button" class="btn btn-light btn-sm" onclick="generarPDF()">
            <i class="bi bi-file-earmark-pdf me-1"></i> Descargar PDF
          </button>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
      </div>

      <div class="modal-body">
        <div class="table-responsive">
          <table class="detalles-table w-100">
            <thead>
              <tr>
                <th>Categoría</th>
                <th>Bus</th>
                <th>Motor Base</th>
                <th>Tecnología</th>
                <th>Versión</th>
                <th>Dependencia</th>
                <th>Fecha Inicio</th>
                <th>Fecha Migración</th>
                <th>Etapa</th>
                <th>Avance</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($registros as $grupo): ?>
                <?php foreach ($grupo as $row): ?>
                  <tr>
                    <td><span class="badge-soft"><?= h($row['categoria']) ?></span></td>
                    <td class="text-strong"><?= h($row['bus_nombre']) ?></td>
                    <td><?= h($row['motor_base_nombre']) ?></td>
                    <td class="text-accent"><?= h($row['tecnologia']) ?></td>
                    <td><?= h($row['version']) ?></td>
                    <td><?= h($row['dependencia']) ?></td>
                    <td><span class="text-body-secondary small"><?= h($row['fecha_inicio']) ?></span></td>
                    <td><span class="text-body-secondary small"><?= h($row['fecha_migracion']) ?></span></td>
                    <td><?= h($row['etapa']) ?></td>
                    <td><?= (int)($row['avance'] ?? 0) ?>%</td>
                  </tr>
                <?php endforeach ?>
              <?php endforeach ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<!-- Modal de Comentarios -->
<link rel="stylesheet" href="/final/mapa/public/sections/lineadetiempo/stylelineatiempo.css">

<!-- Modal global, se rellena dinámicamente -->
<div class="modal fade" id="modalComentarios" tabindex="-1" role="dialog" aria-modal="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Comentarios</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="text-center">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Cargando...</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Script de comentarios -->
<script src="/final/mapa/public/sections/lineadetiempo/comentarios_ui.js"></script>

<script>
(function () {
  const modalComentarios = document.getElementById('modalComentarios');
  
  if (modalComentarios) {
    modalComentarios.addEventListener('show.bs.modal', async function (event) {
      const button = event.relatedTarget;
      const id = button.getAttribute('data-bs-id');
      const busNombre = button.getAttribute('data-bus-nombre');
      const estado = button.getAttribute('data-estado');
      const puedeCrear = button.getAttribute('data-puede-crear');
      
      const modalDialog = this.querySelector('.modal-dialog');
      modalDialog.innerHTML = '<div class="modal-content"><div class="modal-body text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div></div>';
      
      try {
        const url = '/final/mapa/public/sections/lineadetiempo/comentarios_modal.php';
        const puedeCrear = button.getAttribute('data-puede-crear') === '1';
        const params = new URLSearchParams({
          id: id,
          estado: estado,
          nombre: busNombre,
          puede_crear: puedeCrear ? '1' : '0'
        });
        
        const response = await fetch(`${url}?${params.toString()}`);
        if (!response.ok) throw new Error('Network response was not ok');
        
        const html = await response.text();
        modalDialog.innerHTML = html;
        
        // Inicializar cualquier componente adicional si es necesario
        if (window.initComentariosModal) {
          window.initComentariosModal(modalDialog);
        }
      } catch (error) {
        console.error('Error loading modal content:', error);
        modalDialog.innerHTML = `
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Error</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-danger">
              Error al cargar los comentarios. Por favor, intente nuevamente.
            </div>
          </div>`;
      }
    });
  }
})();
    // No necesitamos más código aquí ya que la funcionalidad
    // está manejada por el event listener de arriba
});
</script>


</div>
</body>
</html>
