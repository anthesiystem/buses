<?php
// 0) Núcleo: conexión y sesión (DEBE ir antes del debug)
require_once __DIR__ . '/../../../server/config.php';
require_once __DIR__ . '/../../../server/auth.php';
require_login_or_redirect();
// (opcional) ACL si la usas en la vista
require_once __DIR__ . '/../../../server/acl.php';

// 1) DEBUG DE PERMISOS: activar con  ?bus=XX&debug=permisos
if (isset($_GET['debug']) && $_GET['debug'] === 'permisos') {
  header('Content-Type: text/html; charset=utf-8');

  $userId = (int)($_SESSION['user_id'] ?? $_SESSION['usuario']['ID'] ?? 0);
  $nivel  = (int)($_SESSION['nivel'] ?? 0);
  $busId  = isset($_GET['bus']) ? (int)$_GET['bus'] : null;

  // Resolver ID del módulo "mapa_bus" (fallback a 9)
  $modId = 9;
  try {
    $stmMod = $pdo->prepare("SELECT ID FROM modulo WHERE descripcion = 'mapa_bus' LIMIT 1");
    if ($stmMod->execute() && ($row = $stmMod->fetch(PDO::FETCH_ASSOC))) {
      $modId = (int)$row['ID'];
    }
  } catch (\Throwable $e) { /* ignorar */ }

  // Cargar entidades activas (para mapa nombre->ID)
  $rowsEnt = $pdo->query("SELECT ID, descripcion FROM entidad WHERE activo = 1")->fetchAll(PDO::FETCH_ASSOC);
  $allIds  = array_map('intval', array_column($rowsEnt, 'ID'));
  $nameById = [];
  foreach ($rowsEnt as $r) $nameById[(int)$r['ID']] = $r['descripcion'];

  // Admin (nivel >=3) => todas
  if ($nivel >= 3) {
    $permitidas = $allIds;
  } else {
    // Unir TODAS las filas READ aplicables (comodines de bus y acción)
    $cond = "Fk_usuario = :u AND Fk_modulo = :m AND activo = 1 AND (accion IS NULL OR accion = 'READ')";
    $params = [':u' => $userId, ':m' => $modId];

    if ($busId === null) {
      $cond .= " AND (FK_bus IS NULL OR FK_bus = 0)";
    } else {
      $cond .= " AND (FK_bus IS NULL OR FK_bus = 0 OR FK_bus = :b)";
      $params[':b'] = $busId;
    }

    $st = $pdo->prepare("SELECT FK_entidad FROM permiso_usuario WHERE $cond");
    $st->execute($params);
    $perms = $st->fetchAll(PDO::FETCH_ASSOC);

    $ids = [];
    $todas = false;
    foreach ($perms as $p) {
      $val = $p['FK_entidad'];
      if ($val === null) { $todas = true; break; }

      $tok = trim((string)$val);
      $up  = strtoupper($tok);
      if ($tok === '0' || $tok === '*' || $up === 'ALL' || $up === 'TODAS') { $todas = true; break; }

      foreach (preg_split('/\s*,\s*/', $tok, -1, PREG_SPLIT_NO_EMPTY) as $t) {
        if (ctype_digit($t)) {
          $id = (int)$t;
          if (in_array($id, $allIds, true)) $ids[] = $id;
        } else {
          // Intento por nombre exacto si guardaron nombres
          $needle = mb_strtoupper($t, 'UTF-8');
          foreach ($rowsEnt as $r) {
            if (mb_strtoupper($r['descripcion'], 'UTF-8') === $needle) { $ids[] = (int)$r['ID']; break; }
          }
        }
      }
    }
    $permitidas = $todas ? $allIds : array_values(array_unique($ids));
  }

  // Info del bus (opcional)
  $busNombre = '(desconocido)';
  if ($busId) {
    $stb = $pdo->prepare("SELECT descripcion FROM bus WHERE ID = ? LIMIT 1");
    if ($stb->execute([$busId]) && ($rb = $stb->fetch(PDO::FETCH_ASSOC))) {
      $busNombre = $rb['descripcion'];
    }
  }

  // Render de salida amigable + JSON
  echo "<h2>Debug permisos &mdash; mapa_bus</h2>";
  echo "<p><b>Usuario:</b> {$userId} | <b>Nivel:</b> {$nivel}</p>";
  echo "<p><b>Bus:</b> {$busId} &middot; <i>{$busNombre}</i></p>";

  echo "<h3>Entidades permitidas (".count($permitidas).")</h3>";
  if ($permitidas) {
    echo "<ul>";
    foreach ($permitidas as $id) {
      $nom = htmlspecialchars($nameById[$id] ?? "(ID {$id})", ENT_QUOTES, 'UTF-8');
      echo "<li><b>{$id}</b> &mdash; {$nom}</li>";
    }
    echo "</ul>";
  } else {
    echo "<p><i>Sin permisos de lectura para este bus.</i></p>";
  }

  echo "<h3>JSON</h3>";
  echo "<pre>".json_encode(['permitidas' => $permitidas], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."</pre>";

  echo "<hr>";
  $base = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');
  $qBus = $busId ? "?bus={$busId}" : "";
  echo "<p><a href='{$base}{$qBus}'>Ir a la vista normal</a></p>";
  exit; // Evita que siga renderizando la vista
}

// 2) ----- A partir de aquí, tu lógica normal de la vista -----

// Param bus
$busId = (int)($_GET['bus'] ?? 0);
if ($busId <= 0) { echo "<div class='alert alert-danger'>Bus no válido</div>"; exit; }

// Permiso para ver este bus
acl_require_some_entity('mapa_bus', 'READ', $busId);

// Obtener datos del bus
$stmt = $pdo->prepare("SELECT * FROM bus WHERE ID = ? AND activo = 1 LIMIT 1");
$stmt->execute([$busId]);
$bus = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$bus) { echo "<div class='alert alert-warning'>No se encontró el bus especificado.</div>"; exit; }

$busNombre           = $bus['descripcion'];
$colorImplementado   = $bus['color_implementado']    ?? '#4CAF50';
$colorSinImplementar = $bus['color_sin_implementar'] ?? '#9E9E9E';
$colorPruebas        = $bus['pruebas']               ?? '#FFC107';
$iconoPath           = "/final/mapa/public/icons/" . ($bus['imagen'] ?? "default.png");
?>

<link rel="stylesheet" href="/final/mapa/public/sections/lineadetiempo/stylelineatiempo.css">

<head>
  <style>
    /* Reset y layout principal: 70% mapa, 30% info */
    .contenedor-mapa {
      display: flex !important;
      flex-direction: row !important;
      width: 100%;
      height: 89vh;
      min-height: 450px;
      gap: 15px;
      padding: 15px;
      margin-top: 80px; /* Espacio para header */
      margin-left: 60px; /* Espacio para sidebar */
      box-sizing: border-box;
    }
    
    #mapa {
      flex: 1 0 60% !important;
      width: 70% !important;
      background: #e1edf880;
      border-radius: 8px;
      padding: 10px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100%;
    }
    
    #mapa svg {
      max-width: 95%;
      max-height: 95%;
      width: auto;
      height: auto;
    }
    
    #info {
      flex: 0 0 39% !important;
      width: 30% !important;
      background: #e1edf880;
      border-radius: 8px;
      padding: 10px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      overflow-y: auto;
      height: 100%;
      font-size: 0.8rem; /* Letra más pequeña */
    }
    
    #info h2 {
      font-size: 1rem;
      font-weight: 700;
      color: #374151;
      margin-bottom: 0.8rem;
      text-align: center;
    }
    
    /* Estilos para hacer las tablas más compactas */
    #info table {
      font-size: 0.75rem !important;
    }
    
    #info .card-estado {
      padding: 10px !important;
      margin-bottom: 15px !important;
    }
    
    #info .estado-header {
      gap: 8px !important;
    }
    
    #info .estado-icon {
      width: 60px !important;
      height: 60px !important;
      font-size: 16px !important;
    }
    
    #info .estado-info h3 {
      font-size: 1rem !important;
      margin: 0 !important;
    }
    
    #info .estado-info h5 {
      font-size: 0.8rem !important;
      margin: 0.2rem 0 0 !important;
    }
    
    #info .estado-kv {
      font-size: 0.8rem !important;
      margin-top: 4px !important;
    }
    
    /* Hacer tablas más compactas */
    #info .m1c table {
      font-size: 0.7rem !important;
    }
    
    #info .m1c thead th {
      padding: 0.4rem 0.3rem !important;
      font-size: 0.7rem !important;
    }
    
    #info .m1c tbody td {
      padding: 0.3rem 0.3rem !important;
      font-size: 0.7rem !important;
    }
    
    #info .chip {
      padding: 0.15rem 0.4rem !important;
      font-size: 0.65rem !important;
    }
    
    #info .badge {
      padding: 0.2rem 0.3rem !important;
      font-size: 0.65rem !important;
    }
    
    /* Borde punteado gris simple para estado seleccionado */
    .estado-seleccionado {
      stroke: #0b0b0bff !important;
      stroke-width: 1 !important;
      stroke-dasharray: 8,4 !important;
      stroke-dashoffset: 0;
      animation: dashMove 2s linear infinite;
    }
    
    @keyframes dashMove {
      0% {
        stroke-dashoffset: 0;
      }
      100% {
        stroke-dashoffset: -24;
      }
    }
    
    #detalle {
      margin-top: 1rem;
    }
    
    /* Solo cambiar a vertical en móviles */
    @media (max-width: 768px) {
      .contenedor-mapa {
        flex-direction: column !important;
        height: auto !important;
        padding: 10px;
        gap: 10px;
      }
      
      #mapa {
        flex: none !important;
        width: 100% !important;
        height: 350px;
        margin-bottom: 10px;
      }
      
      #info {
        flex: none !important;
        width: 100% !important;
        height: auto;
        padding: 10px;
      }
      
      .table thead { display:none; }
      .tabla-responsive-fila{ display:block; margin-bottom:1rem; border:1px solid #ccc; border-radius:6px; padding:.5rem; }
      .tabla-responsive-fila td{ display:flex; justify-content:space-between; padding:6px 12px; border:none; border-bottom:1px solid #ddd; }
      .tabla-responsive-fila td::before{ content:attr(data-label); font-weight:bold; flex-basis:40%; color:#333; }
      .tabla-responsive-fila td:last-child{ border-bottom:none; }
    }
    
    /* Reset para evitar interferencias */
    .contenedor-mapa {
      margin: 0 !important;
      padding: 15px !important;
    }

     #main-content {
    padding-top: 5%;}
  </style>
  <base href="/final/mapa/public/">
</head>

<script>
async function getACL(){
  const r = await fetch('../../../server/session_acl.php', {cache:'no-store'});
  const acl = await r.json();
  const can = (mod, action='READ', entidadKey=null, busId=null)=>{
    if (acl.all) return true;
    const needs = action==='READ'
      ? ['READ','CREATE','UPDATE','DELETE','COMMENT','EXPORT']
      : [action];
    const m = (acl.mods && acl.mods[mod]) || {};
    return needs.some(a => (m[a]||[]).some(p =>
      (p.entidad===null || String(p.entidad)===String(entidadKey)) &&
      (p.bus===null     || +p.bus===+busId)
    ));
  };
  const canSomeEntity = (mod, action, busId)=>{
    if (acl.all) return true;
    const needs = action==='READ'
      ? ['READ','CREATE','UPDATE','DELETE','COMMENT','EXPORT']
      : [action];
    const m = (acl.mods && acl.mods[mod]) || {};
    return needs.some(a => (m[a]||[]).some(p => (p.bus===null || +p.bus===+busId)));
  };
  return {acl, can, canSomeEntity};
}
</script>




<div class="contenedor-mapa">
  <!-- SVG del mapa -->
  <div id="mapa">
    <?php echo file_get_contents("../../../public/mapa.svg"); ?>
  </div>

  <!-- Panel de información lateral -->
  <div id="info">
    <h2 id="estadoNombre">Información del Estado</h2>
    <div class="card-estado">
      <div class="estado-header">
        <div class="estado-info">
          <!-- Información del bus seleccionado se mostrará aquí -->
        </div>
      </div>
      <div id="detalle" data-estado=""></div>
    </div>
  </div>
</div>

  <!-- Script del mapa (con datos del bus) -->
<script>
// Configuración global del mapa
window.MAPA_CONFIG = {
    busId: <?= (int)$busId ?>,
    colors: {
        concluido: <?= json_encode($colorImplementado) ?>,
        sinEjecutar: <?= json_encode($colorSinImplementar) ?>,
        otro: <?= json_encode($colorPruebas) ?>
    },
    urls: {
        conteos: '/final/mapa/server/mapabus/datos.php',
        detalle: '/final/mapa/server/mapabus/busvista.php',
        entidades: '/final/mapa/public/sections/mapabus/entidades_permitidas.php'
    }
};
</script>

<script
  id="mapaScript"
  src="/final/mapa/server/mapabus/mapa.js?v=<?= time() ?>"
  data-bus-id="<?= (int)$busId ?>"
  data-color-concluido="<?= htmlspecialchars($colorImplementado, ENT_QUOTES) ?>"
  data-color-sin-ejecutar="<?= htmlspecialchars($colorSinImplementar, ENT_QUOTES) ?>"
  data-color-otro="<?= htmlspecialchars($colorPruebas, ENT_QUOTES) ?>"
  data-url-conteos="/final/mapa/server/mapabus/datos.php"
  data-url-detalle="/final/mapa/server/mapabus/busvista.php"
  data-url-entidades="/final/mapa/public/sections/mapabus/entidades_permitidas.php">
</script>



  <!-- Leyenda de colores -->
<script>
document.addEventListener("DOMContentLoaded", function () {
  const interval = setInterval(() => {
    const rectConcluido  = document.getElementById("legendConcluido");
    const rectPruebas    = document.getElementById("legendPruebas");
    const rectSinEjecutar= document.getElementById("legendSinEjecutar");
    if (rectConcluido && rectPruebas && rectSinEjecutar) {
      rectConcluido.setAttribute("fill", "<?= $colorImplementado ?>");
      rectPruebas.setAttribute("fill", "<?= $colorPruebas ?>");
      rectSinEjecutar.setAttribute("fill", "<?= $colorSinImplementar ?>");
      clearInterval(interval);
    }
  }, 100);
});
</script>



<!-- Modal global, se rellena dinámicamente -->
<div class="modal fade" id="modalComentarios" tabindex="-1"
     data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-xl"></div>
</div>

<!-- Estilos para el Modal de Comentarios -->
<style>
.modal-overlay {
  background: rgba(0, 0, 0, 0.5);
}
.modal-xl {
  max-width: 95%;
  margin: 1.75rem auto;
}
.modal-content {
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}
.modal-header {
  border-bottom: 1px solid #dee2e6;
  background: #f8f9fa;
}
.modal-body {
  padding: 1.5rem;
}
</style>

<!-- Bootstrap Icons para el modal -->
<link rel="stylesheet" href="../server/font/bootstrap-icons.css">









<!-- Debe cargarse UNA sola vez en la página -->
<script src="/final/mapa/public/sections/lineadetiempo/comentarios_ui.js"></script>

<script>
(function () {
  /** Carga/recarga del modal con el HTML generado por PHP */
async function cargarComentariosModal(id) {
  console.log('📝 Cargando modal para ID:', id);

  const modal = document.getElementById('modalComentarios');
  const dlg   = modal ? modal.querySelector('.modal-dialog') : null;
  if (!dlg) {
    console.error('❌ No se encontró el modal o su dialog');
    return;
  }

  dlg.innerHTML = '<div class="modal-content"><div class="modal-body text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div></div>';

  // 🔒 Usa SIEMPRE la ruta absoluta válida a tu proyecto:
  const url = '/final/mapa/public/sections/lineadetiempo/comentarios_modal.php?id='
            + encodeURIComponent(id) + '&_=' + Date.now();

  console.log('🌐 Realizando fetch a:', url);

  try {
    const res  = await fetch(url, { cache: 'no-store' });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    const html = await res.text();
    
    console.log('📄 Longitud del HTML recibido:', html.length);
    
    if (html.trim().length === 0) {
      throw new Error('La respuesta está vacía');
    }
    
    dlg.innerHTML = html; // Debe empezar con <div class="modal-content">…
    console.log('✅ Modal actualizado exitosamente');
    
    // re-inits opcionales, si los usas:
    if (window.initTimelineModal) window.initTimelineModal();
  } catch (e) {
    console.error('❌ Error cargando modal:', e);
    dlg.innerHTML = `
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Error</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-danger">
          Error al cargar los comentarios: ${e.message}
        </div>
      </div>`;
  }
}

  /** Fallback de guardado por fetch (en caso de que no exista window.guardarComentario) */
  async function guardarComentarioFetch(form) {
    // Anti doble click
    if (form.dataset.submitting === '1') return false;
    form.dataset.submitting = '1';

    const btn = form.querySelector('button[type="submit"]');
    const original = btn ? btn.innerHTML : '';
    if (btn) { btn.disabled = true; btn.innerHTML = 'Guardando...'; }

    try {
      const res = await fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        cache: 'no-store'
      });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const json = await res.json();
      if (json.success) {
        const registroId = form.querySelector('[name="Fk_registro"]')?.value;
        if (registroId) {
          await cargarComentariosModal(registroId);
        }
        form.reset();
        return false;
      } else {
        throw new Error(json.error || 'Error desconocido');
      }
    } catch (err) {
      console.error('Error guardando:', err);
      alert('Error al guardar: ' + err.message);
      return false;
    } finally {
      if (btn) { btn.disabled = false; btn.innerHTML = original; }
      form.dataset.submitting = '';
    }
  }

  /** Delegación: abrir el modal y cargar su contenido */
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-bs-target="#modalComentarios"][data-bs-id]');
    if (!btn) return;
    
    console.log('🖱️ Click en botón modalbitacora');
    const id = btn.getAttribute('data-bs-id');
    console.log('📌 ID del registro:', id);
    
    if (id) {
      e.preventDefault();
      await cargarComentariosModal(id);
      
      // Mostrar el modal usando Bootstrap
      const modal = document.getElementById('modalComentarios');
      const bsModal = bootstrap.Modal.getOrCreateInstance(modal);
      bsModal.show();
    }
  });

  /** Delegación: interceptar submit dentro del modal SIEMPRE (evita navegar al JSON) */
  document.addEventListener('submit', function (ev) {
    const form = ev.target;
    const modal = document.getElementById('modalComentarios');
    if (!modal || !modal.contains(form) || form.id !== 'formComentario') return;

    ev.preventDefault(); // ← clave para que no navegue
    if (window.guardarComentario) {
      // Si tienes una versión propia, úsala
      window.guardarComentario(form);
    } else {
      // Si no, usa el fallback local
      guardarComentarioFetch(form);
    }
  });

  /** Delegación: filtro del stepper por etapa (funciona tras cada recarga) */
  document.addEventListener('click', function (e) {
    const modal = document.getElementById('modalComentarios');
    if (!modal) return;

    const btnAll = e.target.closest('#btnAll');
    const stepLi = e.target.closest('#barEtapas li.step[data-id]');
    if (!btnAll && !stepLi) return;

    const list  = modal.querySelector('#listaComentarios');
    const items = list ? list.querySelectorAll('.tl-item') : [];
    if (!list || !items.length) return;

    if (btnAll) {
      items.forEach(it => it.style.display = '');
      modal.querySelectorAll('#barEtapas li.step').forEach(li => li.classList.remove('current'));
      toggleEmptyMessage(list, false);
      return;
    }

    const target = String(stepLi.dataset.id || '');
    modal.querySelectorAll('#barEtapas li.step').forEach(li => li.classList.remove('current'));
    stepLi.classList.add('current');

    let visibles = 0;
    items.forEach(it => {
      const id = String(it.dataset.etapaId || it.getAttribute('data-etapa-id') || '');
      const show = (id === target);
      it.style.display = show ? '' : 'none';
      if (show) visibles++;
    });
    toggleEmptyMessage(list, visibles === 0);
  });

  function toggleEmptyMessage(listEl, show) {
    let msg = listEl.querySelector('#noItemsMsg');
    if (!msg) {
      msg = document.createElement('div');
      msg.id = 'noItemsMsg';
      msg.className = 'text-muted text-center p-3';
      msg.textContent = 'Sin comentarios en esta etapa.';
      msg.style.display = 'none';
      listEl.appendChild(msg);
    }
    msg.style.display = show ? '' : 'none';
  }

  // Exporta función para reutilizarla desde otros scripts si hace falta
  window.cargarComentariosModal = cargarComentariosModal;
  
  console.log('✅ Script de modal inicializado completamente');
})();
</script>


<script>
// (OPCIONAL) Soporte legado: toolbar vieja con botones que llaman a filtrarComentariosPorEtapa()
window.filtrarComentariosPorEtapa = function(targetId, btn) {
  const modal = document.getElementById('modalComentarios');
  const bar   = modal ? modal.querySelector('#etapaBar') : document.getElementById('etapaBar');
  const items = modal ? modal.querySelectorAll('#listaComentarios .tl-item')
                      : document.querySelectorAll('#listaComentarios .tl-item');

  if (bar && btn) {
    bar.querySelectorAll('button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
  }

  const target = String(targetId);
  items.forEach(it => {
    const id = String(it.getAttribute('data-etapa-id') || '');
    it.style.display = (target === '__ALL__' || id === target) ? '' : 'none';
  });
};
</script>


<script>
// Delegación global: funciona aunque el modal se recargue con innerHTML
(function () {
  // devuelve el contenedor actual (el modal si está abierto)
  function scope() {
    const m = document.getElementById('modalComentarios');
    return m ? m : document;
  }

  // Click en "Todos" o en cualquier <li class="step" data-id="...">
  document.addEventListener('click', function (e) {
    const btnAll = e.target.closest('#btnAll');
    const stepLi = e.target.closest('#barEtapas li.step[data-id]');

    if (!btnAll && !stepLi) return;

    const root  = scope();
    const list  = root.querySelector('#listaComentarios');
    const items = list ? list.querySelectorAll('.tl-item') : [];

    if (!list || !items.length) return;

    if (btnAll) {
      // Mostrar todo
      items.forEach(it => it.style.display = '');
      root.querySelectorAll('#barEtapas li.step').forEach(li => li.classList.remove('current'));
      toggleEmptyMessage(list, false);
      return;
    }

    // Clic en un paso
    const target = String(stepLi.dataset.id || '');
    root.querySelectorAll('#barEtapas li.step').forEach(li => li.classList.remove('current'));
    stepLi.classList.add('current');

    // Filtrar por data-etapa-id del item
    let visibles = 0;
    items.forEach(it => {
      const id = String(it.dataset.etapaId || it.getAttribute('data-etapa-id') || '');
      const show = (id === target);
      it.style.display = show ? '' : 'none';
      if (show) visibles++;
    });

    toggleEmptyMessage(list, visibles === 0);
  });

  function toggleEmptyMessage(listEl, show) {
    let msg = listEl.querySelector('#noItemsMsg');
    if (!msg) {
      msg = document.createElement('div');
      msg.id = 'noItemsMsg';
      msg.className = 'text-muted text-center p-3';
      msg.textContent = 'Sin comentarios en esta etapa.';
      msg.style.display = 'none';
      listEl.appendChild(msg);
    }
    msg.style.display = show ? '' : 'none';
  }
})();
</script>
