<?php
// /final/mapa/server/acl.php
require_once __DIR__ . '/config.php';
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

if (!function_exists('acl_build_from_db')) {
  function acl_build_from_db($userId, $nivel){
    if ((int)$nivel >= 3) return ['all'=>true, 'mods'=>[]];

    $sql = "SELECT m.descripcion AS modulo, pu.accion, pu.FK_entidad, pu.FK_bus
            FROM permiso_usuario pu
            JOIN modulo m ON m.ID=pu.Fk_modulo AND m.activo=1
            WHERE pu.Fk_usuario=? AND pu.activo=1";
    $st = $GLOBALS['pdo']->prepare($sql);
    $st->execute([(int)$userId]);

    $acl = ['all'=>false, 'mods'=>[]];
    while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
      $mod = (string)$r['modulo'];
      $acc = strtoupper($r['accion']);
      if (!isset($acl['mods'][$mod]))        { $acl['mods'][$mod] = []; }
      if (!isset($acl['mods'][$mod][$acc]))  { $acl['mods'][$mod][$acc] = []; }
      $acl['mods'][$mod][$acc][] = [
        'entidad' => array_key_exists('FK_entidad',$r) && $r['FK_entidad']!==null ? (string)$r['FK_entidad'] : null,
        'bus'     => array_key_exists('FK_bus',$r)     && $r['FK_bus']!==null     ? (int)$r['FK_bus']       : null,
      ];
    }
    return $acl;
  }
}

if (!function_exists('acl_can')) {
  function acl_can($modulo, $accion='READ', $entidadKey=null, $busId=null){
    $acl = $_SESSION['acl'] ?? null;
    if (!$acl) return false;
    if (!empty($acl['all'])) return true;

    $needs = (strtoupper($accion)==='READ')
      ? ['READ','CREATE','UPDATE','DELETE','COMMENT','EXPORT']
      : [strtoupper($accion)];

    $mods = $acl['mods'][$modulo] ?? [];
    foreach ($needs as $need) {
      foreach (($mods[$need] ?? []) as $perm) {
        $okE = is_null($perm['entidad']) || (string)$perm['entidad'] === (string)$entidadKey;
        $okB = is_null($perm['bus'])     || (int)$perm['bus']       === (int)$busId;
        if ($okE && $okB) return true;
      }
    }
    return false;
  }
}

if (!function_exists('acl_require')) {
  function acl_require($modulo, $accion='READ', $entidadKey=null, $busId=null){
    if (!acl_can($modulo,$accion,$entidadKey,$busId)) {
      http_response_code(403);
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['ok'=>false,'msg'=>'Sin permiso'], JSON_UNESCAPED_UNICODE);
      exit;
    }
  }
}

if (!function_exists('acl_can_some_entity')) {
  function acl_can_some_entity($modulo, $accion='READ', $busId=null){
    $acl = $_SESSION['acl'] ?? null;
    if (!$acl) return false;
    if (!empty($acl['all'])) return true;

    $needs = (strtoupper($accion)==='READ')
      ? ['READ','CREATE','UPDATE','DELETE','COMMENT','EXPORT']
      : [strtoupper($accion)];

    $mods = $acl['mods'][$modulo] ?? [];
    foreach ($needs as $need) {
      foreach (($mods[$need] ?? []) as $perm) {
        // Global por bus (FK_bus NULL) o coincide el bus → true (independiente de entidad)
        $okBus = is_null($perm['bus']) || (int)$perm['bus'] === (int)$busId;
        if ($okBus) return true;
      }
    }
    return false;
  }
}

if (!function_exists('acl_require_some_entity')) {
  function acl_require_some_entity($modulo, $accion='READ', $busId=null){
    if (!acl_can_some_entity($modulo, $accion, $busId)) {
      http_response_code(403);
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['ok'=>false,'msg'=>'Sin permiso'], JSON_UNESCAPED_UNICODE);
      exit;
    }
  }
}
