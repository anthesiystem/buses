<?php
// tools/patch_map_svg.php
// Parchea mapa.svg y añade class + data-entidad-id/nombre a cada <path id="MX-XXX">

$src = __DIR__ . 'mapa.svg';           // C:\Wemp\nginx\html\final\mapa\public\mapa.svg
$dst = __DIR__ . 'mapa.patched.svg';   // salida

$svg = file_get_contents($src);
if ($svg === false) { die("No pude leer $src\n"); }

// Mapa: código → [ID, NOMBRE]
$ENT = [
  'AGU'=>[ 1,'AGUASCALIENTES'],
  'BCN'=>[ 2,'BAJA CALIFORNIA'],
  'BCS'=>[ 3,'BAJA CALIFORNIA SUR'],
  'CAM'=>[ 4,'CAMPECHE'],
  'CHP'=>[ 5,'CHIAPAS'],
  'CHH'=>[ 6,'CHIHUAHUA'],
  'CMX'=>[ 7,'CIUDAD DE MEXICO'],
  'COA'=>[ 8,'COAHUILA'],
  'COL'=>[ 9,'COLIMA'],
  'DUR'=>[10,'DURANGO'],
  'GUA'=>[11,'GUANAJUATO'],
  'GRO'=>[12,'GUERRERO'],
  'HID'=>[13,'HIDALGO'],
  'JAL'=>[14,'JALISCO'],
  'MEX'=>[15,'ESTADO DE MEXICO'],
  'MIC'=>[16,'MICHOACAN'],
  'MOR'=>[17,'MORELOS'],
  'NAY'=>[18,'NAYARIT'],
  'NLE'=>[19,'NUEVO LEON'],
  'OAX'=>[20,'OAXACA'],
  'PUE'=>[21,'PUEBLA'],
  'QUE'=>[22,'QUERETARO'],
  'ROO'=>[23,'QUINTANA ROO'],
  'SLP'=>[24,'SAN LUIS POTOSI'],
  'SIN'=>[25,'SINALOA'],
  'SON'=>[26,'SONORA'],
  'TAB'=>[27,'TABASCO'],
  'TAM'=>[28,'TAMAULIPAS'],
  'TLA'=>[29,'TLAXCALA'],
  'VER'=>[30,'VERACRUZ'],
  'YUC'=>[31,'YUCATAN'],
  'ZAC'=>[32,'ZACATECAS'],
];

$patched = preg_replace_callback(
  '/<path\b([^>]*\bid="MX-([A-Z]{3})"[^>]*)>/u',
  function($m) use ($ENT){
    $attrs = $m[1];
    $code  = $m[2];
    if (!isset($ENT[$code])) return $m[0];

    [$id,$name] = $ENT[$code];

    // --- class: añade mx-state/is-blocked, quita mx-state-disabled si existiera
    if (preg_match('/\bclass="([^"]*)"/u', $attrs, $mc)) {
      $classes = preg_split('/\s+/', trim($mc[1]));
      $classes = array_values(array_unique(array_filter($classes, fn($c)=>$c!=='mx-state-disabled' && $c!=='')));
      if (!in_array('mx-state', $classes, true))   $classes[] = 'mx-state';
      if (!in_array('is-blocked', $classes, true)) $classes[] = 'is-blocked';
      $attrs = preg_replace('/\bclass="[^"]*"/u', 'class="'.implode(' ', $classes).'"', $attrs);
    } else {
      $attrs .= ' class="mx-state is-blocked"';
    }

    // --- data-entidad-id
    if (!preg_match('/\bdata-entidad-id="/u', $attrs)) {
      $attrs .= ' data-entidad-id="'.$id.'"';
    } else {
      $attrs = preg_replace('/\bdata-entidad-id="[^"]*"/u', 'data-entidad-id="'.$id.'"', $attrs);
    }

    // --- data-entidad-nombre
    if (!preg_match('/\bdata-entidad-nombre="/u', $attrs)) {
      $attrs .= ' data-entidad-nombre="'.htmlspecialchars($name, ENT_QUOTES, 'UTF-8').'"';
    } else {
      $attrs = preg_replace('/\bdata-entidad-nombre="[^"]*"/u', 'data-entidad-nombre="'.htmlspecialchars($name, ENT_QUOTES, 'UTF-8').'"', $attrs);
    }

    return '<path'.$attrs.'>';
  },
  $svg
);

if ($patched === null) { die("Regex error\n"); }

file_put_contents($dst, $patched) || die("No pude escribir $dst\n");
echo "OK -> $dst\n";
