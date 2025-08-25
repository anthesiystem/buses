<?php
// public/sections/mapabus/index.php

// Conexión y sesión
require_once __DIR__ . '/../../../server/config.php';
require_once __DIR__ . '/../../../server/auth.php';
require_login_or_redirect();

// ACL base (por módulo/bus) y helper de entidades permitidas
require_once __DIR__ . '/../../../server/acl.php';
require_once __DIR__ . '/../../../server/acl_entidades.php';

// -------------------- Parámetros y validación --------------------
$busId = (int)($_GET['bus'] ?? 0);
// Validación más estricta del ID del bus
if ($busId <= 0 || $busId > 10000) { // Ajusta el límite máximo según tu caso
  echo "<div class='alert alert-danger m-3'>ID de bus no válido</div>";
  exit;
}

// Verificar si el bus existe antes de continuar
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bus WHERE ID = ? AND activo = 1");
$stmt->execute([$busId]);
if (!$stmt->fetchColumn()) {
    echo "<div class='alert alert-danger m-3'>Bus no encontrado o inactivo</div>";
    exit;
}

// Exige que el usuario tenga al menos UNA entidad para este bus/módulo
acl_require_some_entity('mapa_bus', 'READ', $busId);

// -------------------- Datos del bus --------------------
$stmt = $pdo->prepare("SELECT * FROM bus WHERE ID = ? AND activo = 1 LIMIT 1");
$stmt->execute([$busId]);
$bus = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$bus) {
  echo "<div class='alert alert-warning m-3'>No se encontró el bus especificado.</div>";
  exit;
}

// Paths base
$BASE_URL  = '/final/mapa';
$PUBLIC    = $BASE_URL . '/public';
$SERVER    = $BASE_URL . '/server';

// Colores configurados en la tabla bus (con fallback)
$busNombre           = $bus['descripcion'];
$colorImplementado   = $bus['color_implementado']    ?? '#4CAF50';
$colorSinImplementar = $bus['color_sin_implementar'] ?? '#9E9E9E';
$colorPruebas        = $bus['pruebas']               ?? '#FFC107';

// Normaliza icono (por si viene con ruta)
$imagenBD  = $bus['imagen'] ?: 'icons/default.png';
$iconoName = basename($imagenBD);
$iconoURL  = $PUBLIC . '/icons/' . $iconoName;

// -------------------- UserId + ModId + Permisos --------------------
function ses_get(array $keys, $default=null) {
  foreach ($keys as $k) if (isset($_SESSION[$k])) return $_SESSION[$k];
  if (isset($_SESSION['usuario']) && is_array($_SESSION['usuario'])) {
    foreach (['ID','id','user_id','usuario_id'] as $k) if (isset($_SESSION['usuario'][$k])) return $_SESSION['usuario'][$k];
  }
  return $default;
}
$userId = (int) ses_get(['user_id','usuario_id','ID','id'], 0);

// Busca ID del módulo "mapa_bus" (fallback a 9 si ya lo tienes fijo)
$st = $pdo->prepare("SELECT ID FROM modulo WHERE descripcion = ? LIMIT 1");
$st->execute(['mapa_bus']);
$modId = (int)($st->fetchColumn() ?: 9);

// IMPORTANTÍSIMO: Usa el helper con **ID** de módulo (no con el string)
$permitidas = entidadesPermitidasPorUsuario($pdo, $userId, $busId, $modId);
$permitidas = array_values(array_unique(array_map('intval', (array)$permitidas)));
?>
<link rel="stylesheet" href="<?= $PUBLIC ?>/sections/lineadetiempo/stylelineatiempo.css">

<style>
  .contenedor-mapa { display: grid; gap: 16px; grid-template-columns: 2fr 1fr; }
  @media (max-width: 992px){ .contenedor-mapa{ grid-template-columns: 1fr; } }
  .card-estado{border-radius:18px;box-shadow:0 8px 24px rgba(0,0,0,.06);padding:18px;background:#fff}
  .estado-header{display:flex;align-items:center;gap:14px}
  .estado-icon{width:86px;height:86px;border-radius:18px;overflow:hidden}
  .estado-icon img{width:100%;height:100%;object-fit:cover}
  .estado-info h3{font-size:20px;font-weight:800;margin:0;color:#111827}
  .estado-info h5{font-size:14px;font-weight:700;margin:.15rem 0 0;color:#374151}
  #detalle{margin-top:10px}

  /* Estados habilitado/bloqueado (por ACL) */
  .mx-state { transition: fill .2s, opacity .2s; outline: none; }
  .mx-state.is-allowed { cursor: pointer; pointer-events: auto; }
  .mx-state.is-blocked { pointer-events: none; opacity: .45; }

  /* Tooltip simple opcional para el mapa */
  #tooltipMapa{position:absolute;padding:4px 8px;background:rgba(0,0,0,.75);color:#fff;border-radius:6px;font-size:12px;pointer-events:none;display:none;z-index:1000}
</style>

<!-- Inyectamos permisos y datos básicos ANTES de cargar mapa.js -->
<script>
  window.MAPA_BUS = {
    userId:  <?= json_encode($userId) ?>,
    busId:   <?= json_encode($busId) ?>,
    modulo:  "mapa_bus",
    permitidas: <?= json_encode($permitidas) ?> // ← IDs de entidad permitidas
  };
  console.log('[MAPA_BUS]', window.MAPA_BUS);
</script>

<div class="contenedor-mapa">
  <div>
    <?php include __DIR__ . '/_mapa.php'; ?>
    <?php include __DIR__ . '/_legend.php'; ?>
  </div>

  <?php include __DIR__ . '/_panel_info.php'; ?>
</div>

<?php include __DIR__ . '/_modal_comentarios.php'; ?>

<!-- Variables de configuración seguras -->
<?php
$scriptVersion = defined('APP_VERSION') ? APP_VERSION : date('Ymd');
$scriptPath = $SERVER . '/mapabus/mapa.js?v=' . $scriptVersion;
?>

<!-- Script principal del Mapa -->
<script
  id="mapaScript"
  src="<?= htmlspecialchars($scriptPath, ENT_QUOTES, 'UTF-8') ?>"
  data-bus-id="<?= (int)$busId ?>"
  data-url-conteos="<?= htmlspecialchars($SERVER . '/mapabus/conteos.php', ENT_QUOTES, 'UTF-8') ?>"
  data-url-detalle="<?= htmlspecialchars($SERVER . '/mapabus/detalle.php', ENT_QUOTES, 'UTF-8') ?>"
  data-url-entidades="<?= htmlspecialchars($SERVER . '/mapabus/entidades.php', ENT_QUOTES, 'UTF-8') ?>"
  data-color-concluido="<?= htmlspecialchars($colorImplementado, ENT_QUOTES, 'UTF-8') ?>"
  data-color-sin-ejecutar="<?= htmlspecialchars($colorSinImplementar, ENT_QUOTES, 'UTF-8') ?>"
  data-color-otro="<?= htmlspecialchars($colorPruebas, ENT_QUOTES, 'UTF-8') ?>"
  data-max-intentos="3"
  data-timeout="30000"
></script>

<!-- Colores de la leyenda -->
<script>
document.addEventListener("DOMContentLoaded", function () {
  const ds = document.getElementById('mapaScript').dataset;
  const a = document.getElementById("legendConcluido");
  const b = document.getElementById("legendPruebas");
  const c = document.getElementById("legendSinEjecutar");
  if (a && ds.colorConcluido)   a.setAttribute("fill", ds.colorConcluido);
  if (b && ds.colorOtro)        b.setAttribute("fill", ds.colorOtro);
  if (c && ds.colorSinEjecutar) c.setAttribute("fill", ds.colorSinEjecutar);
});
</script>

<!-- UI de comentarios -->
<script src="<?= $PUBLIC ?>/sections/lineadetiempo/comentarios_ui.js"></script>
