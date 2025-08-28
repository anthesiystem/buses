<?php
require_once '../../../server/config.php';
require_once '../../../server/auth.php';
require_once '../../../server/acl.php';

// Verificar si está autenticado
if (!estaAutenticado()) {
    header('Location: /final/mapa/public/login.php');
    exit;
}

// Obtener módulo mapa_general
$modId = 10; // valor por defecto
try {
    $stmMod = $pdo->prepare("SELECT ID FROM modulo WHERE descripcion = 'mapa_general' LIMIT 1");
    if ($stmMod->execute() && ($row = $stmMod->fetch(PDO::FETCH_ASSOC))) {
        $modId = (int)$row['ID'];
    }
} catch (\Throwable $e) { 
    error_log("Error al obtener ID del módulo mapa_general: " . $e->getMessage());
}

// Verificar que tenga al menos un permiso para este módulo
$userId = (int)($_SESSION['user_id'] ?? $_SESSION['usuario']['ID'] ?? 0);
$stmtCheck = $pdo->prepare("
    SELECT COUNT(*) 
    FROM permiso_usuario 
    WHERE Fk_usuario = ? 
    AND Fk_modulo = ? 
    AND activo = 1
");
$stmtCheck->execute([$userId, $modId]);
$tieneAlgunPermiso = (int)$stmtCheck->fetchColumn() > 0;

// Obtener nivel del usuario (admin = nivel 3 o superior)
$nivel = (int)($_SESSION['usuario']['nivel'] ?? $_SESSION['nivel'] ?? 0);

// Si no es admin y no tiene permisos, redirigir
if ($nivel < 3 && !$tieneAlgunPermiso) {
    header('Location: /final/mapa/public/login.php');
    exit;
}
$nivel = (int)($_SESSION['usuario']['nivel'] ?? $_SESSION['nivel'] ?? 0);
$userId = (int)($_SESSION['user_id'] ?? $_SESSION['usuario']['ID'] ?? 0);

// Para admins, permitir todo
if ($nivel >= 3) {
    $entidades = array_map('intval', array_column($pdo->query("SELECT ID FROM entidad WHERE activo = 1")->fetchAll(), 'ID'));
    $buses = array_map('intval', array_column($pdo->query("SELECT ID FROM bus WHERE activo = 1")->fetchAll(), 'ID'));
} else {
    // Para otros usuarios, obtener permisos específicos
    $modId = 10; // ID por defecto para mapa_general
    try {
        $stmMod = $pdo->prepare("SELECT ID FROM modulo WHERE descripcion = 'mapa_general' LIMIT 1");
        if ($stmMod->execute() && ($row = $stmMod->fetch(PDO::FETCH_ASSOC))) {
            $modId = (int)$row['ID'];
        }
    } catch (\Throwable $e) { /* ignorar */ }

    // Obtener las entidades y buses específicamente asignados
    $stmt = $pdo->prepare("
        SELECT DISTINCT FK_entidad, FK_bus
        FROM permiso_usuario 
        WHERE Fk_usuario = ? 
        AND Fk_modulo = ? 
        AND activo = 1
    ");
    $stmt->execute([$userId, $modId]);
    $permisos_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $entidades = [];
    $buses = [];
    $tiene_permiso_total = false;

    foreach ($permisos_raw as $p) {
        $ent_val = $p['FK_entidad'];
        $bus_val = $p['FK_bus'];

        // Verificar si tiene permiso total (null o comodines)
        if ($ent_val === null || $bus_val === null) {
            $tiene_permiso_total = true;
            break;
        }

        // Convertir y validar entidad
        $ent_id = (int)$ent_val;
        if ($ent_id > 0) {
            $entidades[] = $ent_id;
        }

        // Convertir y validar bus
        $bus_id = (int)$bus_val;
        if ($bus_id > 0) {
            $buses[] = $bus_id;
        }
    }

    if (!$tiene_permiso_total) {
        $entidades = array_values(array_unique($entidades));
        $buses = array_values(array_unique($buses));
    } else {
        // Si tiene permiso total, obtener todas las entidades y buses activos
        $entidades = array_map('intval', array_column($pdo->query("SELECT ID FROM entidad WHERE activo = 1")->fetchAll(), 'ID'));
        $buses = array_map('intval', array_column($pdo->query("SELECT ID FROM bus WHERE activo = 1")->fetchAll(), 'ID'));
    }
}

// Debug
error_log("Usuario ID: $userId, Nivel: $nivel");
error_log("Entidades permitidas: " . implode(',', $entidades));
error_log("Buses permitidos: " . implode(',', $buses));

// Exponer permisos a JavaScript
$permisos = [
    'entidades' => array_values(array_filter($entidades)),
    'buses' => array_values(array_filter($buses))
];
?>
<head>
  <style>
    /* Reset y layout principal: 70% mapa, 30% info */
    .contenedor-mapa-general {
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
      margin-bottom: 1rem;
      text-align: center;
    }
    
    #detalle {
      margin-top: 1rem;
    }
    
    /* Solo cambiar a vertical en móviles */
    @media (max-width: 768px) {
      .contenedor-mapa-general {
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
    .contenedor-mapa-general {
      margin: 0 !important;
      padding: 15px !important;
    }
    #main-content {
    padding-top: 5%;
}
  </style>
  <base href="/final/mapa/public/">
</head>

<div class="contenedor-mapa-general">
  <!-- ojo: sin comilla extra -->
  <div id="mapa">
    <?php echo file_get_contents("../../../public/mapa.svg"); ?>
  </div>

  <div id="info">
    <h2 id="estadoNombre">Información del Estado</h2>
    <div id="detalle" data-estado=""></div>
  </div>
</div>

<?php
$catalogoBuses = [];

if (!empty($buses)) {
    $placeholders = str_repeat('?,', count($buses) - 1) . '?';
    $stmt = $pdo->prepare("
        SELECT UPPER(TRIM(descripcion)) AS descripcion
        FROM bus
        WHERE activo = 1
          AND descripcion <> 'VACIA'
          AND ID IN ($placeholders)
        ORDER BY ID
    ");
    $stmt->execute($buses);
} else {
    $stmt = $pdo->query("SELECT UPPER(TRIM(descripcion)) AS descripcion FROM bus WHERE activo = 1 AND descripcion <> 'VACIA' ORDER BY ID");
}

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $catalogoBuses[] = $row['descripcion'];
}
?>

<!-- primero el mapa de claves de estados -->
<script src="/final/mapa/server/mapag/estadomap.js"></script>

<!-- script principal con endpoints bien puestos -->
  <?php
  // Debug permisos
  error_log("DEBUG - Usuario: " . $userId);
  error_log("DEBUG - Nivel: " . $nivel);
  error_log("DEBUG - Entidades permitidas: " . implode(',', $permisos["entidades"]));
  error_log("DEBUG - Buses permitidos: " . implode(',', $permisos["buses"]));
  ?>

  <!-- Debug de variables PHP -->
  <?php
  error_log("Debug - Permisos antes de JSON: " . print_r($permisos, true));
  ?>

  <!-- Inicializar permisos globales -->
  <script>
    window.__ACL_GENERAL__ = {
      entidades: <?= json_encode(array_map('intval', $permisos["entidades"]), JSON_UNESCAPED_UNICODE) ?>,
      buses: <?= json_encode(array_map('intval', $permisos["buses"]), JSON_UNESCAPED_UNICODE) ?>
    };
    
    // Debug de permisos
    console.log('Permisos inicializados:', window.__ACL_GENERAL__);
  </script>

  <script
    id="mapaScript"
    src="/final/mapa/server/mapag/mapageneral.js?v=2"
    data-color-concluido="#04a404b6"
    data-color-sin-ejecutar="#B0B0B0"
    data-color-otro="#e1d071ff"
    data-url-datos="/final/mapa/server/mapag/generalindex.php"
    data-url-detalle="/final/mapa/server/mapag/detalle.php"
    data-catalogo-buses='<?= json_encode($catalogoBuses, JSON_UNESCAPED_UNICODE) ?>'
    data-permisos-entidades='<?= json_encode($permisos["entidades"], JSON_UNESCAPED_UNICODE) ?>'
    data-permisos-buses='<?= json_encode($permisos["buses"], JSON_UNESCAPED_UNICODE) ?>'>
  </script><!-- (opcional) repintar leyenda una vez que exista -->
<script>
document.addEventListener("DOMContentLoaded", function () {
  const iv = setInterval(() => {
    const a = document.getElementById("legendConcluido");
    const b = document.getElementById("legendPruebas");
    const c = document.getElementById("legendSinEjecutar");
    if (a) a.setAttribute("fill", "#04a404b6");
    if (b) b.setAttribute("fill", "#258d19");
    if (c) c.setAttribute("fill", "#B0B0B0");
    if (a && b && c) clearInterval(iv);
  }, 120);
});
</script>

<!-- Modal de Comentarios -->
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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="/final/mapa/public/sections/lineadetiempo/stylelineatiempo.css">

<!-- Script para el Modal de Comentarios -->
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

    // 🔒 Usa comentarios_general_modal.php para el mapa general:
    const url = '/final/mapa/public/sections/lineadetiempo/comentarios_general_modal.php?id='
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
      window.guardarComentario(form);
    } else {
      guardarComentarioFetch(form);
    }
  });

  // Exporta función para reutilizarla desde otros scripts si hace falta
  window.cargarComentariosModal = cargarComentariosModal;
  
  console.log('✅ Script de modal inicializado completamente');
})();
</script>

<!-- Scripts adicionales para funcionalidad del modal -->
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
      // Mostrar todos
      root.querySelectorAll('#barEtapas li.step').forEach(li => li.classList.remove('current'));
      items.forEach(it => it.style.display = '');
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
      const show = (String(it.dataset.etapaId || '') === target);
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
      msg.className = 'text-muted text-center p-4';
      msg.textContent = 'No hay comentarios para esta etapa.';
      listEl.appendChild(msg);
    }
    msg.style.display = show ? '' : 'none';
  }
})();
</script>

<!-- Debe cargarse UNA sola vez en la página -->
<script src="/final/mapa/public/sections/lineadetiempo/comentarios_ui.js"></script>

<!-- PDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
<script>window.jsPDF = window.jspdf.jsPDF;</script>
<script src="/final/mapa/server/generar_pdf.js"></script>

<!-- Script de prueba para verificar el borde punteado -->
<script>
// Función de prueba para aplicar el borde punteado
function probarBorde() {
  const estados = document.querySelectorAll('path[id^="MX-"]');
  console.log('🎯 Estados encontrados:', estados.length);
  
  if (estados.length > 0) {
    // Tomar el primer estado como prueba
    const estadoPrueba = estados[0];
    console.log('🧪 Aplicando borde de prueba a:', estadoPrueba.id);
    
    // Limpiar efectos anteriores
    estados.forEach(p => {
      p.classList.remove('estado-seleccionado');
    });
    
    // Aplicar el efecto
    estadoPrueba.classList.add('estado-seleccionado');
    console.log('✅ Borde punteado aplicado');
  }
}

// Esperar a que todo cargue y ejecutar prueba
setTimeout(() => {
  console.log('🚀 Iniciando prueba de borde...');
  probarBorde();
}, 3000);

// También hacer disponible la función para llamarla manualmente desde la consola
window.probarBorde = probarBorde;
console.log('💡 Ejecuta window.probarBorde() en la consola para probar el efecto');
</script>

