<?php
// public/sections/mapabus/_mapa.php
// Requiere que en el include existan: $pdo, $busId
// Si no se incluyó antes el helper, lo cargamos.
if (!function_exists('entidadesPermitidasPorUsuario')) {
  require_once __DIR__ . '/../../../server/acl_entidades.php';
}

// 1) Rutas y lectura de SVG
$svgPath = __DIR__ . '/../../../public/mapa.svg';
if (!is_file($svgPath)) {
  echo "<div class='alert alert-warning'>No se encontró el mapa SVG.</div>";
  return;
}
$svg = file_get_contents($svgPath);

// 2) Helper para normalizar nombres (quita acentos y espacios extra)
function norm_es($s) {
  $s = mb_strtoupper((string)$s, 'UTF-8');
  $s = strtr($s, [
    'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N',
    'á'=>'A','é'=>'E','í'=>'I','ó'=>'O','ú'=>'U','ü'=>'U','ñ'=>'N',
  ]);
  $s = preg_replace('/\s+/', ' ', $s);
  return trim($s);
}

// 3) Mapeo ISO del path → nombre “humano” del estado (coincide con tu mapa.js)
$iso2name = [
  'MX-AGU'=>'AGUASCALIENTES','MX-BCN'=>'BAJA CALIFORNIA','MX-BCS'=>'BAJA CALIFORNIA SUR','MX-CAM'=>'CAMPECHE',
  'MX-CHP'=>'CHIAPAS','MX-CHH'=>'CHIHUAHUA','MX-CMX'=>'CIUDAD DE MEXICO','MX-COA'=>'COAHUILA','MX-COL'=>'COLIMA',
  'MX-DUR'=>'DURANGO','MX-GUA'=>'GUANAJUATO','MX-GRO'=>'GUERRERO','MX-HID'=>'HIDALGO','MX-JAL'=>'JALISCO',
  'MX-MEX'=>'ESTADO DE MEXICO','MX-MIC'=>'MICHOACAN','MX-MOR'=>'MORELOS','MX-NAY'=>'NAYARIT','MX-NLE'=>'NUEVO LEON',
  'MX-OAX'=>'OAXACA','MX-PUE'=>'PUEBLA','MX-QUE'=>'QUERETARO','MX-ROO'=>'QUINTANA ROO','MX-SLP'=>'SAN LUIS POTOSI',
  'MX-SIN'=>'SINALOA','MX-SON'=>'SONORA','MX-TAB'=>'TABASCO','MX-TAM'=>'TAMAULIPAS','MX-TLA'=>'TLAXCALA',
  'MX-VER'=>'VERACRUZ','MX-YUC'=>'YUCATAN','MX-ZAC'=>'ZACATECAS'
];

// 4) IDs de entidades permitidas para el usuario en ESTE bus (backend duro)
$usuarioId  = (int)($_SESSION['user_id'] ?? 0);
$permitidas = entidadesPermitidasPorUsuario($pdo, $usuarioId, $busId, 'mapa_bus'); // [] si ninguna
echo '<pre>'; var_dump($permitidas); echo '</pre>';
// 5) Construimos mapa nombre→ID desde la BD para cruzarlo con $iso2name
$rows = $pdo->query("SELECT ID, descripcion FROM entidad WHERE activo = 1")->fetchAll(PDO::FETCH_ASSOC);
$nombre2id = [];
foreach ($rows as $r) {
  $nombre2id[norm_es($r['descripcion'])] = (int)$r['ID'];
}

// 6) Función para añadir class="estado-bloqueado" a un <path id="MX-XXX">
$bloqueaPath = function(string $svgStr, string $idIso): string {
  // Encuentra el path con id="MX-XXX" y añade/concatena la clase
  return preg_replace_callback(
    '/(<path\b[^>]*\bid="' . preg_quote($idIso, '/') . '"[^>]*)(\/?>)/i',
    function ($m) {
      $tag = $m[1];
      if (preg_match('/\bclass="([^"]*)"/i', $tag, $cm)) {
        // ya tiene class → anexamos
        $nuevo = preg_replace(
          '/\bclass="([^"]*)"/i',
          'class="$1 estado-bloqueado"',
          $tag,
          1
        );
      } else {
        // no tiene class → la agregamos
        $nuevo = $tag . ' class="estado-bloqueado"';
      }
      return $nuevo . $m[2];
    },
    $svgStr,
    1
  );
};

// 7) Recorremos cada estado del SVG y bloqueamos los NO permitidos
foreach ($iso2name as $iso => $nombre) {
  $idEntidad = $nombre2id[norm_es($nombre)] ?? null;
  $allowed   = $idEntidad && in_array($idEntidad, $permitidas, true);

  if (!$allowed) {
    $svg = $bloqueaPath($svg, $iso);
  } else {
    // Opcional: imprime data-* útiles para tu JS (nombre/ID)
    $svg = preg_replace_callback(
      '/(<path\b[^>]*\bid="' . preg_quote($iso, '/') . '"[^>]*)(\/?>)/i',
      function ($m) use ($idEntidad, $nombre) {
        $tag = $m[1];
        // añade data-estado-id y data-estado-nombre si no existen
        if (strpos($tag, 'data-estado-id=') === false) {
          $tag .= ' data-estado-id="' . (int)$idEntidad . '"';
        }
        if (strpos($tag, 'data-estado-nombre=') === false) {
          $tag .= ' data-estado-nombre="' . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') . '"';
        }
        return $tag . $m[2];
      },
      $svg,
      1
    );
  }
}

// 8) Imprime el SVG ya “sellado” con permisos
echo $svg;
?>
<style>
  /* Estilo de bloqueado (gris, sin interacción) */
  .estado-bloqueado {
    opacity: .35;
    pointer-events: none;
    filter: grayscale(1);
  }
</style>
