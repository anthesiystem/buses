<?php
require_once __DIR__ . '/../server/mapag/get_estados_permitidos.php';

// Debug mode
if (isset($_GET['debug']) && $_GET['debug'] === 'permisos') {
    header('Location: /final/mapa/public/sections/mapag/debug_permisos.php');
    exit;
}

// Obtener los estados permitidos para el usuario actual
$estadosPermitidos = getEstadosPermitidos();
$idsPermitidos = array_column($estadosPermitidos, 'ID');

// Debug log
error_log("Usuario actual: " . ($_SESSION['user_id'] ?? $_SESSION['usuario']['ID'] ?? 'no-id'));
error_log("Nivel: " . ($_SESSION['usuario']['nivel'] ?? $_SESSION['nivel'] ?? 'no-nivel'));
error_log("Estados permitidos: " . print_r($estadosPermitidos, true));
error_log("IDs permitidos: " . print_r($idsPermitidos, true));

// Leer el SVG
$svgPath = __DIR__ . '/mapa.svg';
$svg = file_get_contents($svgPath);
if ($svg === false) {
    die("No se pudo leer el archivo SVG");
}

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
    function($m) use ($ENT, $idsPermitidos) {
        $attrs = $m[1];
        $code = $m[2];
        
        if (!isset($ENT[$code])) {
            return $m[0];
        }

        [$id, $name] = $ENT[$code];
        
        // Determina si el estado está permitido
        $isPermitido = in_array($id, $idsPermitidos, true);
        error_log("Estado $name (ID: $id) - Permitido: " . ($isPermitido ? 'SI' : 'NO'));
        
        // Maneja las clases CSS
        if (preg_match('/\bclass="([^"]*)"/u', $attrs, $mc)) {
            $classes = preg_split('/\s+/', trim($mc[1]));
            $classes = array_values(array_filter($classes, fn($c) => $c !== 'mx-state-disabled' && $c !== ''));
            
            // Asegura que tenga la clase mx-state
            if (!in_array('mx-state', $classes)) {
                $classes[] = 'mx-state';
            }
            
            // Maneja is-blocked según permisos
            $blockIdx = array_search('is-blocked', $classes);
            if ($isPermitido && $blockIdx !== false) {
                unset($classes[$blockIdx]);
            } elseif (!$isPermitido && $blockIdx === false) {
                $classes[] = 'is-blocked';
            }
            
            $attrs = preg_replace('/\bclass="[^"]*"/u', 'class="'.implode(' ', $classes).'"', $attrs);
        } else {
            $attrs .= ' class="mx-state'.(!$isPermitido ? ' is-blocked' : '').'"';
        }

        // Añade o actualiza los atributos data-
        if (!preg_match('/\bdata-entidad-id="/u', $attrs)) {
            $attrs .= ' data-entidad-id="'.$id.'"';
        } else {
            $attrs = preg_replace('/\bdata-entidad-id="[^"]*"/u', 'data-entidad-id="'.$id.'"', $attrs);
        }

        if (!preg_match('/\bdata-entidad-nombre="/u', $attrs)) {
            $attrs .= ' data-entidad-nombre="'.htmlspecialchars($name, ENT_QUOTES, 'UTF-8').'"';
        } else {
            $attrs = preg_replace('/\bdata-entidad-nombre="[^"]*"/u', 'data-entidad-nombre="'.htmlspecialchars($name, ENT_QUOTES, 'UTF-8').'"', $attrs);
        }

        return '<path'.$attrs.'>';
    },
    $svg
);

if ($patched === null) {
    die("Error en el procesamiento del SVG");
}

// Headers para evitar caché
header('Content-Type: image/svg+xml');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Envía el SVG modificado
echo $patched;
