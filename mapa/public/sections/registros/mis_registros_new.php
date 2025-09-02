<?php
require_once '../../server/config.php';
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

try {
    // parámetros de paginación
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $rowsPerPage = isset($_GET['rowsPerPage']) ? (int)$_GET['rowsPerPage'] : 15;
    $offset = ($page - 1) * $rowsPerPage;

    // parámetros de filtros
    $filtroEstado = isset($_GET['estado']) && $_GET['estado'] !== '' ? (int)$_GET['estado'] : null;
    $filtroEntidad = isset($_GET['entidad']) && $_GET['entidad'] !== '' ? (int)$_GET['entidad'] : null;
    $filtroCategoria = isset($_GET['categoria']) && $_GET['categoria'] !== '' ? (int)$_GET['categoria'] : null;

    // construir condiciones WHERE
    $whereConditions = ['r.activo = 1'];
    $params = [];

    if ($filtroEstado !== null) {
        $whereConditions[] = "r.Fk_estado_bus = ?";
        $params[] = $filtroEstado;
    }

    if ($filtroEntidad !== null) {
        $whereConditions[] = "r.Fk_entidad = ?";
        $params[] = $filtroEntidad;
    }

    if ($filtroCategoria !== null) {
        $whereConditions[] = "r.Fk_categoria = ?";
        $params[] = $filtroCategoria;
    }

    $whereClause = implode(' AND ', $whereConditions);

    // contar total de registros
    $countSql = "SELECT COUNT(*) FROM registro r WHERE $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();
    $totalPages = ceil($total / $rowsPerPage);

    // consulta principal
    $sql = "
    SELECT
      r.ID,
      r.Fk_dependencia, r.Fk_entidad, r.Fk_bus, r.Fk_motor_base, r.Fk_tecnologia,
      r.Fk_estado_bus, r.Fk_categoria, r.Fk_etapa,
      r.fecha_inicio, r.fecha_migracion, r.fecha_creacion, r.fecha_modificacion,

      COALESCE(d.descripcion,'—')  AS Dependencia,
      e.descripcion                AS Entidad,
      COALESCE(b.descripcion,'—')  AS Bus,
      en.descripcion               AS Engine,
      CONCAT(t.numero_version, ' - ', t.descripcion) AS Tecnologia,
      c.descripcion                AS Categoria,
      eb.descripcion               AS Estado,
      COALESCE(et.descripcion,'—') AS Etapa,
      COALESCE(et.avance, 0)       AS EtapaPorcentaje
    FROM registro r
    LEFT JOIN dependencia d ON d.ID = r.Fk_dependencia        
    JOIN entidad e       ON e.ID = r.Fk_entidad
    LEFT JOIN bus b      ON b.ID = r.Fk_bus
    JOIN motor_base en   ON en.ID = r.Fk_motor_base
    JOIN tecnologia t    ON t.ID  = r.Fk_tecnologia
    JOIN categoria c     ON c.ID  = r.Fk_categoria
    JOIN estado_bus eb   ON eb.ID = r.Fk_estado_bus
    LEFT JOIN etapa et   ON et.ID = r.Fk_etapa
    WHERE $whereClause
    ORDER BY r.fecha_creacion DESC, r.ID DESC
    LIMIT ? OFFSET ?
    ";

    $stmt = $pdo->prepare($sql);
    $allParams = array_merge($params, [$rowsPerPage, $offset]);
    $stmt->execute($allParams);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // construir filas HTML
    $html = '';
    foreach ($registros as $r) {
      $id   = (int)$r['ID'];
      $ent  = h($r['Entidad'] ?? '');
      $dep  = h($r['Dependencia'] ?? '');
      $bus  = h($r['Bus'] ?? '');
      $eng  = h($r['Engine'] ?? '');
      $tec  = h($r['Tecnologia'] ?? '');
      $est  = h($r['Estado'] ?? '');
      $etap = h($r['Etapa'] ?? '—');
      $pct  = isset($r['EtapaPorcentaje']) ? (int)$r['EtapaPorcentaje'] : 0;
      $fini = h($r['fecha_inicio'] ?? '');
      $fmig = h($r['fecha_migracion'] ?? '');

      $badgeClass = 'badge-soft';
      $estTxt = mb_strtolower($est, 'UTF-8');
      if (preg_match('/sin\s*implement/i', $estTxt)) {
        $badgeClass = 'text-bg-secondary';
      } elseif (preg_match('/\bimplementado\b/i', $estTxt)) {
        $badgeClass = 'badge-implementado';
      } elseif (preg_match('/prueba/i', $estTxt)) {
        $badgeClass = 'badge-pruebas';
      }

      $json = json_encode([
        'ID'             => $id,
        'Fk_dependencia' => $r['Fk_dependencia'],
        'Fk_entidad'     => $r['Fk_entidad'],
        'Fk_bus'         => $r['Fk_bus'],
        'Fk_motor_base'  => $r['Fk_motor_base'],
        'Fk_tecnologia'  => $r['Fk_tecnologia'],
        'Fk_estado_bus'  => $r['Fk_estado_bus'],
        'Fk_categoria'   => $r['Fk_categoria'],
        'Fk_etapa'       => $r['Fk_etapa'],
        'fecha_inicio'   => $r['fecha_inicio'],
        'fecha_migracion'=> $r['fecha_migracion'],
      ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

      $html .= "
      <tr>
        <td><input class='row-check form-check-input' type='checkbox'></td>
        <td>$id</td>
        <td>$ent</td>
        <td class='col-sm-hide'>$dep</td>
        <td>$bus</td>
        <td>$eng</td>
        <td class='col-sm-hide'>$tec</td>
        <td><span class='badge $badgeClass'>$est</span></td>
        <td>
          <div class='d-flex align-items-center gap-2'>
            <div class='progress flex-fill'>
              <div class='progress-bar brand' role='progressbar' style='width: ".max(0,min(100,$pct))."%'></div>
            </div>
            <small class='text-muted'>$pct%</small>
          </div>
          <div class='small text-muted'>$etap</div>
        </td>
        <td class='col-sm-hide'>$fini</td>
        <td class='col-sm-hide'>$fmig</td>
        <td class='actions text-end'>
          <div class='btn-group'>
            <button class='btn btn-outline-brand btn-sm' onclick='editar($json)'>
              <i class='bi bi-pencil'></i><span class='text ms-1'>Editar</span>
            </button>
          </div>
        </td>
      </tr>";
    }

    echo json_encode([
      'html' => $html,
      'total' => (int)$total,
      'totalPages' => (int)$totalPages
    ]);

} catch (Exception $e) {
    echo json_encode([
      'html' => '<tr><td colspan="12" class="text-center text-danger">Error: ' . $e->getMessage() . '</td></tr>',
      'total' => 0,
      'totalPages' => 1
    ]);
}
?>
