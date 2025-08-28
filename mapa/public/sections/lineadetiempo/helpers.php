<?php
// /mapa/public/sections/lineadetiempo/helpers.php

/* ========================== UTILIDADES GENERALES ========================== */

if (!function_exists('h')) {
  function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

if (!function_exists('respond_json')) {
  function respond_json(array $payload, int $status=200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
  }
}

/* ========================= COLORES Y ESTILOS DE ETAPA ========================= */

if (!function_exists('etapa_icon_slug')) {
  function etapa_icon_slug(string $s): string {
    $tmp = @iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$s);
    if ($tmp !== false) $s = $tmp;
    $s = strtolower($s);
    $s = preg_replace('/[^a-z0-9]+/','_', $s);
    return trim($s,'_');
  }
}

if (!function_exists('etapa_color_hex')) {
  function etapa_color_hex(string $desc): string {
    $slug = etapa_icon_slug($desc);
    $map = [
      'kit'               => '#a6a6a6',
      'conectividad'      => '#ff0505',
      'base_de_datos'     => '#c0504d',
      'concepto'          => '#ffc000',
      'pruebas_unitarias' => '#202231',
      'pruebas_masivas'   => '#2d7fcf',
      'produccion'        => '#00b050',
      'implementado'      => '#394cf2',
    ];
    return $map[$slug] ?? '#198754';
  }
}

if (!function_exists('etapa_text_color')) {
  function etapa_text_color(string $bg): string {
    $hex = ltrim($bg, '#');
    if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) return '#fff';
    $r = hexdec(substr($hex,0,2));
    $g = hexdec(substr($hex,2,2));
    $b = hexdec(substr($hex,4,2));
    $yiq = (($r*299)+($g*587)+($b*114))/1000;
    return ($yiq >= 128) ? '#000' : '#fff';
  }
}

if (!function_exists('dotClassFromColor')) {
  function dotClassFromColor(?string $c): string {
    $c = strtolower(trim((string)$c));
    return match (true) {
      str_contains($c,'danger') || $c==='rojo'      => 'dot-danger',
      str_contains($c,'warning')|| $c==='amarillo'  => 'dot-warning',
      str_contains($c,'success')|| $c==='verde'     => 'dot-success',
      str_contains($c,'secondary')                  => 'dot-secondary',
      default                                       => 'dot-primary'
    };
  }
}

/* ======================= RESOLUCIÓN ROBUSTA DE RUTAS ======================= */

if (!function_exists('lt_norm_slash')) {
  function lt_norm_slash(string $p): string {
    return str_replace('\\','/', $p);
  }
}

if (!function_exists('lt_docroot')) {
  function lt_docroot(): string {
    return rtrim(lt_norm_slash((string)($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
  }
}

/**
 * Verifica si un *URL path* (que debe empezar con "/") existe físicamente en el servidor.
 * Convierte /algo/ruta.png -> C:\...\document_root\algo\ruta.png y hace is_file().
 */
if (!function_exists('lt_url_exists')) {
  function lt_url_exists(string $urlPath): bool {
    $urlPath = lt_norm_slash($urlPath);
    if ($urlPath === '' || $urlPath[0] !== '/') return false;
    $fs = lt_docroot() . $urlPath;
    return is_file($fs);
  }
}

/**
 * Base URL hacia /public, calculada desde la ruta del script actual.
 * Ej: /final/mapa/public/sections/lineadetiempo/comentarios_modal.php -> /final/mapa/public
 */
if (!function_exists('lt_public_base_url')) {
  function lt_public_base_url(): string {
    $self = lt_norm_slash((string)($_SERVER['PHP_SELF'] ?? ''));
    $base = rtrim(dirname(dirname(dirname($self))), '/');
    return $base !== '' ? $base : '/';
  }
}

/** Carpeta URL del script actual (modal) */
if (!function_exists('lt_modal_base_url')) {
  function lt_modal_base_url(): string {
    $self = lt_norm_slash((string)($_SERVER['PHP_SELF'] ?? ''));
    return rtrim(dirname($self), '/');
  }
}

/** URL al default.png existente (prueba en public/icons, local/icons y sin prefijo /final) */
if (!function_exists('lt_default_icon_url')) {
  function lt_default_icon_url(): string {
    $c1 = lt_public_base_url() . '/icons/default.png';
    $c2 = lt_modal_base_url()  . '/icons/default.png';
    $c3 = preg_replace('#^/final#','', $c1); // por si tu host no monta /final
    $c4 = '/icons/default.png';

    if (lt_url_exists($c1)) return $c1;
    if (lt_url_exists($c2)) return $c2;
    if (lt_url_exists($c3)) return $c3;
    if (lt_url_exists($c4)) return $c4;

    // Último recurso: devuelve /public/icons aunque no exista; el <img onerror> usará lt_default_icon_url()
    return $c1;
  }
}

/**
 * Devuelve la URL del PNG de la etapa probando varias ubicaciones:
 *  1) /…/public/icons/<slug>.png
 *  2) /…/sections/lineadetiempo/icons/<slug>.png
 *  3) Igual que (1) pero sin el prefijo /final
 *  4) /icons/<slug>.png en la raíz del host
 * Si no encuentra, devuelve el candidato (1) y el <img onerror> debe apuntar a lt_default_icon_url().
 */
if (!function_exists('etapa_icon_slug')) {
  function etapa_icon_slug(?string $s): string {
    $s = (string)$s;
    $tmp = @iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$s);
    if ($tmp !== false) $s = $tmp;
    $s = strtolower($s);
    $s = preg_replace('/[^a-z0-9]+/','_', $s);
    $s = trim($s,'_');
    return $s !== '' ? $s : 'default';
  }
}

/* Rutas base + existencia en disco */
if (!function_exists('lt_norm_slash')) {
  function lt_norm_slash(string $p): string { return str_replace('\\','/', $p); }
}
if (!function_exists('lt_docroot')) {
  function lt_docroot(): string { return rtrim(lt_norm_slash((string)($_SERVER['DOCUMENT_ROOT'] ?? '')), '/'); }
}
if (!function_exists('lt_url_exists')) {
  function lt_url_exists(string $urlPath): bool {
    $urlPath = lt_norm_slash($urlPath);
    if ($urlPath === '' || $urlPath[0] !== '/') return false;
    return is_file(lt_docroot() . $urlPath);
  }
}
if (!function_exists('lt_public_base_url')) {
  function lt_public_base_url(): string {
    $self = lt_norm_slash((string)($_SERVER['PHP_SELF'] ?? ''));
    $base = rtrim(dirname(dirname(dirname($self))), '/');
    return $base !== '' ? $base : '/';
  }
}
if (!function_exists('lt_modal_base_url')) {
  function lt_modal_base_url(): string {
    $self = lt_norm_slash((string)($_SERVER['PHP_SELF'] ?? ''));
    return rtrim(dirname($self), '/');
  }
}
if (!function_exists('lt_default_icon_url')) {
  function lt_default_icon_url(): string {
    $c1 = lt_public_base_url() . '/icons/default.png';
    $c2 = lt_modal_base_url()  . '/icons/default.png';
    $c3 = preg_replace('#^/final#','', $c1);
    $c4 = '/icons/default.png';
    if (lt_url_exists($c1)) return $c1;
    if (lt_url_exists($c2)) return $c2;
    if (lt_url_exists($c3)) return $c3;
    return $c4;
  }
}

/* URL robusta del ícono de la etapa */
if (!function_exists('etapa_icon_url')) {
  function etapa_icon_url(?string $desc): string {
    $slug = etapa_icon_slug($desc);
    if ($slug === 'default') return lt_default_icon_url();

    $c1 = lt_public_base_url() . "/icons/{$slug}.png";      // /final/mapa/public/icons
    $c2 = lt_modal_base_url()  . "/icons/{$slug}.png";      // junto al modal
    $c3 = preg_replace('#^/final#','', $c1);                // sin /final
    $c4 = "/icons/{$slug}.png";                             // raíz del host

    if (lt_url_exists($c1)) return $c1;
    if (lt_url_exists($c2)) return $c2;
    if (lt_url_exists($c3)) return $c3;
    if (lt_url_exists($c4)) return $c4;

    return lt_default_icon_url(); // evita 404 y onerror
  }
}
