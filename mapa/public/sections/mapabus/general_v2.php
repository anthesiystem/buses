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
    .contenedor-mapa-general-v2 {
      display: flex !important;
      flex-direction: row !important;
      width: 100%;
      height: 75vh;
      min-height: 450px;
      gap: 15px;
      padding: 15px;
      margin-top: 80px; /* Espacio para header */
      margin-left: 60px; /* Espacio para sidebar */
      box-sizing: border-box;
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    }
    
    #mapa-v2 {
      flex: 0 0 69% !important;
      width: 70% !important;
      background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
      border-radius: 12px;
      padding: 15px;
      box-shadow: 
        0 8px 25px rgba(0,0,0,0.15),
        inset 0 1px 0 rgba(255,255,255,0.8);
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100%;
      position: relative;
      border: 2px solid #e3f2fd;
    }
    
    #mapa-v2::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: 
        radial-gradient(circle at 20% 20%, rgba(25, 118, 210, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(139, 195, 74, 0.1) 0%, transparent 50%);
      pointer-events: none;
      border-radius: 10px;
    }
    
    #mapa-v2 svg {
      max-width: 95%;
      max-height: 95%;
      width: auto;
      height: auto;
      filter: drop-shadow(0 4px 8px rgba(0,0,0,0.1));
      position: relative;
      z-index: 1;
    }
    
    /* Estados mejorados con transiciones suaves */
    #mapa-v2 path {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      cursor: pointer;
    }
    
    #mapa-v2 path:hover {
      transform: scale(1.02);
      filter: brightness(1.1) saturate(1.2);
      stroke: #2196f3;
      stroke-width: 2px;
    }
    
    #info-v2 {
      flex: 0 0 30% !important;
      width: 30% !important;
      background: linear-gradient(145deg, #ffffff 0%, #fafafa 100%);
      border-radius: 12px;
      padding: 15px;
      box-shadow: 
        0 8px 25px rgba(0,0,0,0.15),
        inset 0 1px 0 rgba(255,255,255,0.8);
      overflow-y: auto;
      height: 100%;
      font-size: 0.8rem;
      border: 2px solid #e8f5e8;
    }
    
    #info-v2 h2 {
      font-size: 1.2rem;
      font-weight: 700;
      background: linear-gradient(135deg, #1976d2, #42a5f5);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 1rem;
      text-align: center;
      text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    /* Estilos para hacer las tablas más compactas */
    #info-v2 table {
      font-size: 0.75rem !important;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    #info-v2 .card-estado {
      padding: 12px !important;
      margin-bottom: 15px !important;
      border-radius: 10px;
      background: linear-gradient(145deg, #f8f9fa, #ffffff);
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      border: 1px solid #e3f2fd;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    #info-v2 .card-estado:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    }
    
    #info-v2 .estado-header {
      gap: 10px !important;
      align-items: center;
    }
    
    #info-v2 .estado-icon {
      width: 65px !important;
      height: 65px !important;
      font-size: 18px !important;
      border-radius: 50%;
      background: linear-gradient(135deg, #42a5f5, #1976d2);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 12px rgba(25, 118, 210, 0.3);
    }
    
    #info-v2 .estado-info h3 {
      font-size: 1.1rem !important;
      margin: 0 !important;
      color: #1976d2;
      font-weight: 600;
    }
    
    #info-v2 .estado-info h5 {
      font-size: 0.85rem !important;
      margin: 0.3rem 0 0 !important;
      color: #666;
    }
    
    #info-v2 .estado-kv {
      font-size: 0.8rem !important;
      margin-top: 6px !important;
      padding: 8px;
      background: rgba(25, 118, 210, 0.05);
      border-radius: 6px;
      border-left: 3px solid #2196f3;
    }
    
    /* Hacer tablas más compactas */
    #info-v2 .m1c table {
      font-size: 0.7rem !important;
      background: white;
    }
    
    #info-v2 .m1c thead th {
      padding: 0.5rem 0.4rem !important;
      font-size: 0.7rem !important;
      background: linear-gradient(135deg, #2196f3, #42a5f5);
      color: white;
    }
    
    #info-v2 .m1c tbody td {
      padding: 0.4rem 0.4rem !important;
      font-size: 0.7rem !important;
      border-bottom: 1px solid #f0f0f0;
    }
    
    #info-v2 .m1c tbody tr:hover {
      background: rgba(25, 118, 210, 0.05);
    }
    
    #info-v2 .chip {
      padding: 0.2rem 0.5rem !important;
      font-size: 0.65rem !important;
      border-radius: 15px;
      background: linear-gradient(135deg, #4caf50, #66bb6a);
      color: white;
      box-shadow: 0 2px 4px rgba(76, 175, 80, 0.3);
    }
    
    #info-v2 .badge {
      padding: 0.25rem 0.4rem !important;
      font-size: 0.65rem !important;
      border-radius: 12px;
      background: linear-gradient(135deg, #ff9800, #ffb74d);
      color: white;
      box-shadow: 0 2px 4px rgba(255, 152, 0, 0.3);
    }
    
    /* Borde animado mejorado para estado seleccionado */
    .estado-seleccionado-v2 {
      stroke: #ff6b6b !important;
      stroke-width: 3 !important;
      stroke-dasharray: 12,6 !important;
      stroke-dashoffset: 0;
      animation: dashMoveV2 2s linear infinite;
      filter: brightness(1.2) saturate(1.3) drop-shadow(0 0 10px rgba(255, 107, 107, 0.5));
    }
    
    @keyframes dashMoveV2 {
      0% {
        stroke-dashoffset: 0;
      }
      100% {
        stroke-dashoffset: -36;
      }
    }
    
    /* Indicador de versión */
    .version-indicator {
      position: absolute;
      top: 10px;
      right: 10px;
      background: linear-gradient(135deg, #4caf50, #66bb6a);
      color: white;
      padding: 5px 12px;
      border-radius: 15px;
      font-size: 0.75rem;
      font-weight: 600;
      box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
      z-index: 10;
    }
    
    /* Panel de controles mejorado */
    .controls-panel-v2 {
      position: absolute;
      bottom: 15px;
      left: 15px;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border-radius: 10px;
      padding: 10px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      display: flex;
      gap: 8px;
      align-items: center;
      z-index: 10;
    }
    
    .controls-panel-v2 button {
      background: linear-gradient(135deg, #2196f3, #42a5f5);
      color: white;
      border: none;
      border-radius: 6px;
      padding: 6px 12px;
      font-size: 0.7rem;
      cursor: pointer;
      transition: all 0.2s ease;
      box-shadow: 0 2px 4px rgba(33, 150, 243, 0.3);
    }
    
    .controls-panel-v2 button:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 8px rgba(33, 150, 243, 0.4);
    }
    
    /* Solo cambiar a vertical en móviles */
    @media (max-width: 768px) {
      .contenedor-mapa-general-v2 {
        flex-direction: column !important;
        height: auto !important;
        padding: 10px;
        gap: 10px;
      }
      
      #mapa-v2 {
        flex: none !important;
        width: 100% !important;
        height: 400px;
        margin-bottom: 10px;
      }
      
      #info-v2 {
        flex: none !important;
        width: 100% !important;
        height: auto;
        padding: 12px;
      }
      
      .table thead { display:none; }
      .tabla-responsive-fila{ display:block; margin-bottom:1rem; border:1px solid #ccc; border-radius:6px; padding:.5rem; }
      .tabla-responsive-fila td{ display:flex; justify-content:space-between; padding:6px 12px; border:none; border-bottom:1px solid #ddd; }
      .tabla-responsive-fila td::before{ content:attr(data-label); font-weight:bold; flex-basis:40%; color:#333; }
      .tabla-responsive-fila td:last-child{ border-bottom:none; }
    }
    
    /* Reset para evitar interferencias */
    .contenedor-mapa-general-v2 {
      margin: 0 !important;
      padding: 15px !important;
    }
    #main-content {
      padding-top: 5%;
    }
    
    /* Estadísticas mejoradas */
    .stats-panel {
      background: linear-gradient(135deg, #e3f2fd, #f3e5f5);
      border-radius: 10px;
      padding: 12px;
      margin-bottom: 15px;
      border: 1px solid #bbdefb;
    }
    
    .stats-panel h4 {
      color: #1976d2;
      font-size: 0.9rem;
      margin-bottom: 8px;
      text-align: center;
    }
    
    .stats-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px;
      font-size: 0.7rem;
    }
    
    .stat-item {
      background: rgba(255, 255, 255, 0.7);
      padding: 6px;
      border-radius: 6px;
      text-align: center;
      border: 1px solid rgba(25, 118, 210, 0.2);
    }
    
    .stat-value {
      font-weight: bold;
      color: #1976d2;
      font-size: 0.8rem;
    }
  </style>
  <base href="/final/mapa/public/">
</head>

<div class="contenedor-mapa-general-v2">
  <div id="mapa-v2">
    <div class="version-indicator">Versión 2.0 - Enhanced</div>
    
    <!-- Intentar cargar img-map-enhanced.svg, fallback a img-map.svg -->
    <?php 
    $fallbackSvgPath = "../../img-map.svg";

    
    // Debug de rutas
    error_log("Verificando rutas SVG para V2:");

    error_log("Fallback: " . realpath($fallbackSvgPath));

    
    if (file_exists($enhancedSvgPath)) {
        echo "<!-- Usando SVG mejorado V2 -->\n";
        echo file_get_contents($enhancedSvgPath);
        error_log("V2: Cargando img-map-enhanced.svg");
    } elseif (file_exists($fallbackSvgPath)) {
        echo "<!-- Usando SVG img-map como fallback V2 -->\n";
        echo file_get_contents($fallbackSvgPath);
        error_log("V2: Cargando img-map.svg como fallback");
    } elseif (file_exists($originalSvgPath)) {
        echo "<!-- Usando SVG original como último recurso V2 -->\n";
        echo file_get_contents($originalSvgPath);
        error_log("V2: Cargando mapa.svg como último recurso");
    } else {
        echo "<!-- Error: No se encontró ningún archivo SVG -->\n";
        echo '<div style="padding: 40px; text-align: center; color: #dc3545; border: 2px dashed #dc3545; border-radius: 10px; background: #f8d7da;">';
        echo '<h3>⚠️ Error de Carga SVG</h3>';
        echo '<p>No se encontró ningún archivo SVG válido.</p>';
        echo '<p><strong>Rutas verificadas:</strong></p>';
        echo '<ul style="text-align: left; display: inline-block;">';
        echo '<li>' . $enhancedSvgPath . '</li>';
        echo '<li>' . $fallbackSvgPath . '</li>';
        echo '<li>' . $originalSvgPath . '</li>';
        echo '</ul>';
        echo '</div>';
        error_log("V2 ERROR: No se encontró ningún archivo SVG");
    }
    ?>
    
    <div class="controls-panel-v2">
      <button onclick="resetViewV2()" title="Restablecer vista">🏠</button>
      <button onclick="toggleStatsPanel()" title="Estadísticas">📊</button>
      <button onclick="highlightAllStates()" title="Resaltar estados">🎯</button>
      <button onclick="exportMapV2()" title="Exportar mapa">💾</button>
    </div>
  </div>

  <div id="info-v2">
    <h2 id="estadoNombreV2">🗺️ Información del Estado</h2>
    
    <!-- Panel de estadísticas -->
    <div class="stats-panel" id="statsPanel" style="display: none;">
      <h4>📈 Estadísticas del Mapa</h4>
      <div class="stats-grid">
        <div class="stat-item">
          <div class="stat-value" id="totalStatesV2">32</div>
          <div>Estados</div>
        </div>
        <div class="stat-item">
          <div class="stat-value" id="loadedStatesV2">0</div>
          <div>Cargados</div>
        </div>
        <div class="stat-item">
          <div class="stat-value" id="activeStatesV2">0</div>
          <div>Activos</div>
        </div>
        <div class="stat-item">
          <div class="stat-value" id="versionTypeV2">Enhanced</div>
          <div>Versión</div>
        </div>
      </div>
    </div>
    
    <div id="detalle-v2" data-estado=""></div>
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

<!-- Mapa de claves de estados -->
<script src="/final/mapa/server/mapag/estadomap.js"></script>

<?php
// Debug permisos
error_log("DEBUG V2 - Usuario: " . $userId);
error_log("DEBUG V2 - Nivel: " . $nivel);
error_log("DEBUG V2 - Entidades permitidas: " . implode(',', $permisos["entidades"]));
error_log("DEBUG V2 - Buses permitidos: " . implode(',', $permisos["buses"]));
?>

<!-- Inicializar permisos globales -->
<script>
window.__ACL_GENERAL_V2__ = {
  entidades: <?= json_encode(array_map('intval', $permisos["entidades"]), JSON_UNESCAPED_UNICODE) ?>,
  buses: <?= json_encode(array_map('intval', $permisos["buses"]), JSON_UNESCAPED_UNICODE) ?>
};

// Debug de permisos
console.log('🔧 Permisos V2 inicializados:', window.__ACL_GENERAL_V2__);
</script>

<!-- Script principal mejorado -->
<script
  id="mapaScriptV2"
  src="/final/mapa/server/mapag/mapageneral_v2.js?v=1"
  data-color-concluido="#4caf50"
  data-color-sin-ejecutar="#bdbdbd"
  data-color-otro="#f44336"
  data-url-datos="/final/mapa/server/mapag/generalindex.php"
  data-url-detalle="/final/mapa/server/mapag/detalle.php"
  data-catalogo-buses='<?= json_encode($catalogoBuses, JSON_UNESCAPED_UNICODE) ?>'
  data-permisos-entidades='<?= json_encode($permisos["entidades"], JSON_UNESCAPED_UNICODE) ?>'
  data-permisos-buses='<?= json_encode($permisos["buses"], JSON_UNESCAPED_UNICODE) ?>'>
</script>

<!-- Script de funciones adicionales V2 -->
<script>
// Variables globales para V2
let mapViewV2 = {
  zoom: 1,
  translateX: 0,
  translateY: 0,
  isDragging: false
};

// Funciones mejoradas para V2
function resetViewV2() {
  mapViewV2 = { zoom: 1, translateX: 0, translateY: 0, isDragging: false };
  const mapContainer = document.getElementById('mapa-v2');
  const svg = mapContainer.querySelector('svg');
  if (svg) {
    svg.style.transform = 'scale(1) translate(0px, 0px)';
  }
  console.log('🏠 Vista restablecida en V2');
}

function toggleStatsPanel() {
  const panel = document.getElementById('statsPanel');
  if (panel) {
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
    console.log('📊 Panel de estadísticas:', panel.style.display);
  }
}

function highlightAllStates() {
  const svg = document.querySelector('#mapa-v2 svg');
  if (!svg) return;
  
  const paths = svg.querySelectorAll('path');
  let highlighted = false;
  
  paths.forEach(path => {
    if (path.classList.contains('estado-seleccionado-v2')) {
      path.classList.remove('estado-seleccionado-v2');
    } else {
      path.classList.add('estado-seleccionado-v2');
      highlighted = true;
    }
  });
  
  console.log('🎯 Estados', highlighted ? 'resaltados' : 'sin resaltar');
  
  // Quitar el resaltado después de 3 segundos
  if (highlighted) {
    setTimeout(() => {
      paths.forEach(path => path.classList.remove('estado-seleccionado-v2'));
    }, 3000);
  }
}

function exportMapV2() {
  const svg = document.querySelector('#mapa-v2 svg');
  if (!svg) {
    alert('❌ No se encontró el SVG para exportar');
    return;
  }
  
  // Crear una copia del SVG
  const svgCopy = svg.cloneNode(true);
  const serializer = new XMLSerializer();
  const svgString = serializer.serializeToString(svgCopy);
  
  // Crear blob y descargar
  const blob = new Blob([svgString], { type: 'image/svg+xml' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = `mapa-general-v2-${new Date().toISOString().split('T')[0]}.svg`;
  link.click();
  URL.revokeObjectURL(url);
  
  console.log('💾 Mapa V2 exportado');
}

function updateStatsV2() {
  const svg = document.querySelector('#mapa-v2 svg');
  if (!svg) return;
  
  const paths = svg.querySelectorAll('path');
  const pathsWithIds = svg.querySelectorAll('path[id]');
  const pathsWithClasses = svg.querySelectorAll('path[class]');
  const pathsWithData = svg.querySelectorAll('path[data-entidad-id]');
  
  // Actualizar contadores
  document.getElementById('loadedStatesV2').textContent = paths.length;
  document.getElementById('activeStatesV2').textContent = pathsWithData.length;
  
  // Determinar tipo de versión
  let versionType = 'Basic';
  if (pathsWithData.length > 0) versionType = 'Enhanced';
  else if (pathsWithIds.length > 0) versionType = 'Standard';
  
  document.getElementById('versionTypeV2').textContent = versionType;
  
  console.log(`📊 Stats V2: ${paths.length} paths, ${pathsWithData.length} enhanced, ${versionType}`);
}

// Mejorar interacciones del mapa
function enhanceMapInteractionsV2() {
  const mapContainer = document.getElementById('mapa-v2');
  const svg = mapContainer.querySelector('svg');
  
  if (!svg) return;
  
  // Añadir eventos de zoom con rueda del mouse
  mapContainer.addEventListener('wheel', (e) => {
    e.preventDefault();
    const delta = e.deltaY * -0.001;
    mapViewV2.zoom = Math.max(0.5, Math.min(3, mapViewV2.zoom + delta));
    
    svg.style.transform = `scale(${mapViewV2.zoom}) translate(${mapViewV2.translateX}px, ${mapViewV2.translateY}px)`;
  });
  
  // Añadir arrastre mejorado
  let lastMouseX = 0, lastMouseY = 0;
  
  mapContainer.addEventListener('mousedown', (e) => {
    mapViewV2.isDragging = true;
    lastMouseX = e.clientX;
    lastMouseY = e.clientY;
    mapContainer.style.cursor = 'grabbing';
  });
  
  mapContainer.addEventListener('mousemove', (e) => {
    if (!mapViewV2.isDragging) return;
    
    const deltaX = (e.clientX - lastMouseX) / mapViewV2.zoom;
    const deltaY = (e.clientY - lastMouseY) / mapViewV2.zoom;
    
    mapViewV2.translateX += deltaX;
    mapViewV2.translateY += deltaY;
    
    svg.style.transform = `scale(${mapViewV2.zoom}) translate(${mapViewV2.translateX}px, ${mapViewV2.translateY}px)`;
    
    lastMouseX = e.clientX;
    lastMouseY = e.clientY;
  });
  
  mapContainer.addEventListener('mouseup', () => {
    mapViewV2.isDragging = false;
    mapContainer.style.cursor = 'grab';
  });
  
  mapContainer.style.cursor = 'grab';
  
  console.log('🎮 Interacciones mejoradas V2 configuradas');
}

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
  console.log('🚀 Inicializando Mapa General V2...');
  
  // Esperar un poco para que el SVG se cargue
  setTimeout(() => {
    updateStatsV2();
    enhanceMapInteractionsV2();
    
    // Verificar qué tipo de SVG se cargó
    const svg = document.querySelector('#mapa-v2 svg');
    if (svg) {
      const pathsWithData = svg.querySelectorAll('path[data-entidad-id]');
      if (pathsWithData.length > 0) {
        console.log('✅ SVG Enhanced detectado con', pathsWithData.length, 'estados con datos');
      } else {
        console.log('ℹ️ SVG estándar detectado, considera usar el SVG enhanced');
      }
    }
  }, 1000);
});

// Repintar leyenda con colores V2
document.addEventListener("DOMContentLoaded", function () {
  const iv = setInterval(() => {
    const a = document.getElementById("legendConcluido");
    const b = document.getElementById("legendPruebas");
    const c = document.getElementById("legendSinEjecutar");
    if (a) a.setAttribute("fill", "#4caf50");
    if (b) b.setAttribute("fill", "#f44336");
    if (c) c.setAttribute("fill", "#bdbdbd");
    if (a && b && c) clearInterval(iv);
  }, 120);
});
</script>

<!-- Modal de Comentarios (heredado de V1) -->
<div class="modal fade" id="modalComentarios" tabindex="-1"
     data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-xl"></div>
</div>

<!-- Bootstrap Icons para el modal -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="/final/mapa/public/sections/lineadetiempo/stylelineatiempo.css">

<!-- Script para el Modal de Comentarios (heredado de V1) -->
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
      
      dlg.innerHTML = html;
      console.log('✅ Modal actualizado exitosamente');
      
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

  /** Fallback de guardado por fetch */
  async function guardarComentarioFetch(form) {
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
    
    console.log('🖱️ Click en botón modalbitacora V2');
    const id = btn.getAttribute('data-bs-id');
    console.log('📌 ID del registro:', id);
    
    if (id) {
      e.preventDefault();
      await cargarComentariosModal(id);
      
      const modal = document.getElementById('modalComentarios');
      const bsModal = bootstrap.Modal.getOrCreateInstance(modal);
      bsModal.show();
    }
  });

  /** Delegación: interceptar submit dentro del modal */
  document.addEventListener('submit', function (ev) {
    const form = ev.target;
    const modal = document.getElementById('modalComentarios');
    if (!modal || !modal.contains(form) || form.id !== 'formComentario') return;

    ev.preventDefault();
    if (window.guardarComentario) {
      window.guardarComentario(form);
    } else {
      guardarComentarioFetch(form);
    }
  });

  window.cargarComentariosModal = cargarComentariosModal;
  
  console.log('✅ Script de modal V2 inicializado completamente');
})();
</script>

<!-- Scripts adicionales para funcionalidad del modal (heredados de V1) -->
<script>
(function () {
  function scope() {
    const m = document.getElementById('modalComentarios');
    return m ? m : document;
  }

  document.addEventListener('click', function (e) {
    const btnAll = e.target.closest('#btnAll');
    const stepLi = e.target.closest('#barEtapas li.step[data-id]');

    if (!btnAll && !stepLi) return;

    const root  = scope();
    const list  = root.querySelector('#listaComentarios');
    const items = list ? list.querySelectorAll('.tl-item') : [];

    if (!list || !items.length) return;

    if (btnAll) {
      root.querySelectorAll('#barEtapas li.step').forEach(li => li.classList.remove('current'));
      items.forEach(it => it.style.display = '');
      toggleEmptyMessage(list, false);
      return;
    }

    const target = String(stepLi.dataset.id || '');
    root.querySelectorAll('#barEtapas li.step').forEach(li => li.classList.remove('current'));
    stepLi.classList.add('current');

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

<!-- Scripts heredados de V1 -->
<script src="/final/mapa/public/sections/lineadetiempo/comentarios_ui.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
<script>window.jsPDF = window.jspdf.jsPDF;</script>
<script src="/final/mapa/server/generar_pdf_v2.js?v=<?php echo time(); ?>"></script>

<!-- Mensaje de bienvenida para V2 -->
<script>
console.log(`
🎉 ===============================================
   MAPA GENERAL V2 - ENHANCED VERSION
   ✨ Mejoras incluidas:
   - Diseño moderno con gradientes
   - Soporte para SVG enhanced (img-map-enhanced.svg)
   - Interacciones mejoradas (zoom con rueda, arrastre)
   - Panel de estadísticas en tiempo real
   - Controles adicionales
   - Animaciones y transiciones suaves
===============================================
`);
</script>
