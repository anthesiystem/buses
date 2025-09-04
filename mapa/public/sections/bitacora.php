<?php
session_start();
require_once dirname(__FILE__) . '/../../server/config.php';

if (!isset($_SESSION['fk_perfiles']) || $_SESSION['fk_perfiles'] < 4) {
    die('No tiene permiso para acceder a esta sección');
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: /final/mapa/public/login.php");
    exit;
}

// Filtros
$filtro_usuario = $_GET['usuario'] ?? '';
$filtro_accion = $_GET['accion'] ?? '';
$filtro_fecha  = $_GET['fecha'] ?? '';
$filtro_tabla  = $_GET['tabla'] ?? '';

// Debug temporal - remover después
// echo "<!-- Debug: usuario=$filtro_usuario, accion=$filtro_accion, fecha=$filtro_fecha, tabla=$filtro_tabla -->";

$where = [];
$params = [];

if ($filtro_usuario !== '') {
    $where[] = 'u.ID = ?';
    $params[] = $filtro_usuario;
}
if ($filtro_accion !== '') {
    $where[] = 'b.Tipo_Accion = ?';
    $params[] = $filtro_accion;
}
if ($filtro_fecha !== '') {
    $where[] = 'DATE(b.Fecha_Accion) = ?';
    $params[] = $filtro_fecha;
}
if ($filtro_tabla !== '') {
    $where[] = 'b.Tabla_Afectada = ?';
    $params[] = $filtro_tabla;
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Paginación
$registros_por_pagina = 8;
$pagina_actual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Contar total de registros para paginación
$count_sql = "
    SELECT COUNT(*) as total
    FROM bitacora b
    INNER JOIN usuario u ON u.ID = b.Fk_Usuario
    $where_sql
";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_registros = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

$sql = "
    SELECT 
        b.ID,
        u.cuenta AS Usuario,
        b.Tabla_Afectada,
        b.Id_Registro_Afectado,
        b.Tipo_Accion,
        b.Descripcion,
        b.Fecha_Accion
    FROM bitacora b
    INNER JOIN usuario u ON u.ID = b.Fk_Usuario
    $where_sql
    ORDER BY b.Fecha_Accion DESC
    LIMIT $registros_por_pagina OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener listas para filtros
$usuarios_result = $pdo->query("SELECT ID, cuenta FROM usuario ORDER BY cuenta");
$tablas_result = $pdo->query("SELECT DISTINCT Tabla_Afectada FROM bitacora ORDER BY Tabla_Afectada");

// Obtener estadísticas
$stats_sql = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN Tipo_Accion = 'DESCARGA' THEN 1 ELSE 0 END) as descargas,
        SUM(CASE WHEN Tipo_Accion = 'COMENTARIO' THEN 1 ELSE 0 END) as comentarios,
        SUM(CASE WHEN Tipo_Accion IN ('INSERT', 'UPDATE', 'DELETE') THEN 1 ELSE 0 END) as crud_operations,
        SUM(CASE WHEN Tipo_Accion IN ('usuario_crear', 'usuario_editar', 'usuario_reset', 'persona_crear', 'persona_editar', 'persona_toggle', 'permiso_crear', 'permiso_editar', 'permiso_toggle', 'modulo_crear', 'modulo_editar', 'modulo_toggle') THEN 1 ELSE 0 END) as operaciones_usuarios
    FROM bitacora b
    INNER JOIN usuario u ON u.ID = b.Fk_Usuario
    $where_sql
";
$stats_stmt = $pdo->prepare($stats_sql);
$stats_stmt->execute($params);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bitácora de Auditoría</title>
    <link href="/final/mapa/server/style/bootstrap.min.css" rel="stylesheet">
    <link href="/final/mapa/server/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root{
            --brand:#7b1e2b; --brand-600:#8e2433; --brand-700:#661822; --brand-rgb:123,30,43;
            --ink:#1f2937; --muted:#6b7280; --row-hover:rgba(var(--brand-rgb),.04); --row-selected:rgba(var(--brand-rgb),.08);
            --header-bg:#ffffff; --header-border:#e5e7eb; --table-border:#e5e7eb; --badge-bg:#f3f4f6;
        }
        body{ color:var(--ink); background:#fafafa; }
        .page-title{ font-weight:700; letter-spacing:.2px; }
        .btn-brand{
            --bs-btn-bg:var(--brand); --bs-btn-border-color:var(--brand);
            --bs-btn-hover-bg:var(--brand-600); --bs-btn-hover-border-color:var(--brand-600);
            --bs-btn-active-bg:var(--brand-700); --bs-btn-active-border-color:var(--brand-700);
            --bs-btn-color:#fff;
        }
        .btn-outline-brand{
            --bs-btn-color:var(--brand); --bs-btn-border-color:var(--brand);
            --bs-btn-hover-bg:var(--brand); --bs-btn-hover-border-color:var(--brand);
            --bs-btn-hover-color:#fff;
        }
        .table-card{
            background:#fff; border:1px solid var(--table-border);
            border-radius:14px; overflow:hidden; box-shadow:0 6px 24px rgba(0,0,0,.04);
        }
        .table-responsive{ max-height:70vh; }
        .table-brand thead th{
            background:var(--header-bg);
            border-bottom:1px solid var(--header-border); color:var(--muted);
            font-weight:700; text-transform:uppercase; font-size:.78rem; letter-spacing:.5px; cursor:pointer;
        }
        .table-brand tbody td{ vertical-align:middle; border-color:var(--table-border); }
        .table-brand tbody tr:hover{ background:var(--row-hover); }
        .table-brand tbody tr.selected{ background:var(--row-selected); box-shadow:inset 4px 0 0 var(--brand); }
        .badge-soft{ background:var(--badge-bg); color:var(--ink); border:1px solid #e5e7eb; font-weight:600; }
        .actions .btn{ padding:.25rem .5rem; }
        @media (max-width:768px){
            .col-sm-hide{ display:none; }
            .actions .btn .text{ display:none; }
        }
        
        /* Estilos específicos para bitácora */
        .table-brand th:nth-child(1) { width: 60px !important; min-width: 60px; max-width: 60px; }
        .table-brand th:nth-child(2) { width: 120px !important; min-width: 120px; max-width: 120px; }
        .table-brand th:nth-child(3) { width: 120px !important; min-width: 120px; max-width: 120px; }
        .table-brand th:nth-child(4) { width: 80px !important; min-width: 80px; max-width: 80px; }
        .table-brand th:nth-child(5) { width: 120px !important; min-width: 120px; max-width: 120px; }
        .table-brand th:nth-child(6) { width: 400px !important; min-width: 400px; }
        .table-brand th:nth-child(7) { width: 140px !important; min-width: 140px; max-width: 140px; }
        
        /* Ancho de celdas también */
        .table-brand td:nth-child(1) { width: 60px !important; max-width: 60px; }
        .table-brand td:nth-child(2) { width: 120px !important; max-width: 120px; }
        .table-brand td:nth-child(3) { width: 120px !important; max-width: 120px; }
        .table-brand td:nth-child(4) { width: 80px !important; max-width: 80px; }
        .table-brand td:nth-child(5) { width: 120px !important; max-width: 120px; }
        .table-brand td:nth-child(6) { width: 400px !important; min-width: 400px; }
        .table-brand td:nth-child(7) { width: 140px !important; max-width: 140px; }
        
        .descripcion-col { 
            max-width: 400px !important; 
            white-space: pre-wrap !important; 
            word-wrap: break-word !important; 
            line-height: 1.4 !important;
            overflow-wrap: break-word !important;
            word-break: break-word !important;
            overflow: visible !important;
            text-overflow: clip !important;
        }
        .fecha-col { 
            min-width: 140px; 
            font-size: 0.9em;
            text-align: center;
            white-space: nowrap;
        }
        .accion-col { 
            min-width: 120px; 
            text-align: center;
        }
        
        /* Forzar layout de tabla */
        .table-brand {
            table-layout: fixed !important;
            width: 100% !important;
        }

        #main-content {
            max-width: 90%;
            padding-left: 12%;
            padding-top: 3%;
        }

        /* Sin scroll interno en la tarjeta/tabla */
        .table-card { max-height: none !important; overflow: visible !important; }
        .table-responsive { overflow: visible !important; }
    </style>
</head>
<body class="bg-light">
    <div id="bitacora-root">
<div class="container mt-4">
    <h2 class="mb-4">Bitácora de Auditoría</h2>

    <!-- Tarjetas de estadísticas -->
    <div class="row mb-4 justify-content-center">
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card text-center border-primary">
                <div class="card-body py-2">
                    <h5 class="card-title text-primary mb-1"><?= number_format($stats['total']) ?></h5>
                    <p class="card-text small mb-0">Total Acciones</p>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card text-center border-info">
                <div class="card-body py-2">
                    <h5 class="card-title text-info mb-1"><?= number_format($stats['descargas']) ?></h5>
                    <p class="card-text small mb-0">Descargas PDF</p>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card text-center border-success">
                <div class="card-body py-2">
                    <h5 class="card-title text-success mb-1"><?= number_format($stats['comentarios']) ?></h5>
                    <p class="card-text small mb-0">Comentarios</p>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card text-center border-warning">
                <div class="card-body py-2">
                    <h5 class="card-title text-warning mb-1"><?= number_format($stats['crud_operations']) ?></h5>
                    <p class="card-text small mb-0">Operaciones CRUD</p>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card text-center border-danger">
                <div class="card-body py-2">
                    <h5 class="card-title text-danger mb-1"><?= number_format($stats['operaciones_usuarios']) ?></h5>
                    <p class="card-text small mb-0">Gestión Usuarios</p>
                </div>
            </div>
        </div>
    </div>

    <form method="get" class="row g-2 mb-4" id="filtros-bitacora">
        <div class="col-md-3">
            <label for="usuario" class="form-label">Usuario</label>
            <select name="usuario" id="usuario" class="form-select">
                <option value="">Todos</option>
                <?php foreach ($usuarios_result as $u): ?>
                    <option value="<?= $u['ID'] ?>" <?= ($filtro_usuario == $u['ID']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['cuenta']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label for="accion" class="form-label">Tipo de Acción</label>
            <select name="accion" id="accion" class="form-select">
                <option value="">Todas</option>
                <optgroup label="Operaciones Generales">
                    <option value="INSERT" <?= $filtro_accion == 'INSERT' ? 'selected' : '' ?>>INSERT</option>
                    <option value="UPDATE" <?= $filtro_accion == 'UPDATE' ? 'selected' : '' ?>>UPDATE</option>
                    <option value="DELETE" <?= $filtro_accion == 'DELETE' ? 'selected' : '' ?>>DELETE</option>
                    <option value="ACTIVAR" <?= $filtro_accion == 'ACTIVAR' ? 'selected' : '' ?>>ACTIVAR</option>
                    <option value="DESACTIVAR" <?= $filtro_accion == 'DESACTIVAR' ? 'selected' : '' ?>>DESACTIVAR</option>
                </optgroup>
                <optgroup label="Sistema">
                    <option value="DESCARGA" <?= $filtro_accion == 'DESCARGA' ? 'selected' : '' ?>>DESCARGA PDF</option>
                    <option value="COMENTARIO" <?= $filtro_accion == 'COMENTARIO' ? 'selected' : '' ?>>COMENTARIO</option>
                </optgroup>
                <optgroup label="Gestión de Usuarios">
                    <option value="usuario_crear" <?= $filtro_accion == 'usuario_crear' ? 'selected' : '' ?>>CREAR USUARIO</option>
                    <option value="usuario_editar" <?= $filtro_accion == 'usuario_editar' ? 'selected' : '' ?>>EDITAR USUARIO</option>
                    <option value="usuario_reset" <?= $filtro_accion == 'usuario_reset' ? 'selected' : '' ?>>RESET CONTRASEÑA</option>
                </optgroup>
                <optgroup label="Gestión de Personas">
                    <option value="persona_crear" <?= $filtro_accion == 'persona_crear' ? 'selected' : '' ?>>CREAR PERSONA</option>
                    <option value="persona_editar" <?= $filtro_accion == 'persona_editar' ? 'selected' : '' ?>>EDITAR PERSONA</option>
                    <option value="persona_toggle" <?= $filtro_accion == 'persona_toggle' ? 'selected' : '' ?>>CAMBIAR ESTADO PERSONA</option>
                </optgroup>
                <optgroup label="Gestión de Permisos">
                    <option value="permiso_crear" <?= $filtro_accion == 'permiso_crear' ? 'selected' : '' ?>>CREAR PERMISO</option>
                    <option value="permiso_editar" <?= $filtro_accion == 'permiso_editar' ? 'selected' : '' ?>>EDITAR PERMISO</option>
                    <option value="permiso_toggle" <?= $filtro_accion == 'permiso_toggle' ? 'selected' : '' ?>>CAMBIAR ESTADO PERMISO</option>
                </optgroup>
                <optgroup label="Gestión de Módulos">
                    <option value="modulo_crear" <?= $filtro_accion == 'modulo_crear' ? 'selected' : '' ?>>CREAR MÓDULO</option>
                    <option value="modulo_editar" <?= $filtro_accion == 'modulo_editar' ? 'selected' : '' ?>>EDITAR MÓDULO</option>
                    <option value="modulo_toggle" <?= $filtro_accion == 'modulo_toggle' ? 'selected' : '' ?>>CAMBIAR ESTADO MÓDULO</option>
                </optgroup>
            </select>
        </div>
        <div class="col-md-3">
            <label for="fecha" class="form-label">Fecha</label>
            <input type="date" name="fecha" id="fecha" value="<?= htmlspecialchars($filtro_fecha) ?>" class="form-control">
        </div>
        <div class="col-md-3">
            <label for="tabla" class="form-label">Tabla Afectada</label>
            <select name="tabla" id="tabla" class="form-select">
                <option value="">Todas</option>
                <?php foreach ($tablas_result as $t): ?>
                    <option value="<?= $t['Tabla_Afectada'] ?>" <?= ($filtro_tabla == $t['Tabla_Afectada']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['Tabla_Afectada']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-12 mt-3">
            <div class="row justify-content-center">
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-outline-secondary w-100" onclick="limpiarFiltros()">
                        <i class="bi bi-x-circle"></i> Limpiar Filtros
                    </button>
                </div>
            </div>
        </div>
    </form>

    <div class="table-card">
        <div class="table-responsive">
            <table class="table table-hover table-brand align-middle m-0" style="table-layout: fixed; width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 60px !important;">ID</th>
                        <th style="width: 120px !important;">Usuario</th>
                        <th style="width: 120px !important;">Tabla</th>
                        <th style="width: 80px !important;">ID Reg.</th>
                        <th style="width: 120px !important;">Acción</th>
                        <th style="width: 400px !important;">Descripción</th>
                        <th style="width: 140px !important;">Fecha</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($result as $row): 
                    // Determinar icono según el tipo de acción
                    $icono = '';
                    switch($row['Tipo_Accion']) {
                        case 'INSERT':
                            $icono = '<i class="bi bi-plus-circle-fill text-success"></i>';
                            break;
                        case 'UPDATE':
                            $icono = '<i class="bi bi-pencil-square text-warning"></i>';
                            break;
                        case 'DELETE':
                            $icono = '<i class="bi bi-trash-fill text-danger"></i>';
                            break;
                        case 'ACTIVAR':
                            $icono = '<i class="bi bi-toggle-on text-success"></i>';
                            break;
                        case 'DESACTIVAR':
                            $icono = '<i class="bi bi-toggle-off text-secondary"></i>';
                            break;
                        case 'DESCARGA':
                            $icono = '<i class="bi bi-download text-primary"></i>';
                            break;
                        case 'COMENTARIO':
                            $icono = '<i class="bi bi-chat-dots-fill text-info"></i>';
                            break;
                        // Nuevas acciones para módulo usuarios
                        case 'usuario_crear':
                            $icono = '<i class="bi bi-person-plus-fill text-success"></i>';
                            break;
                        case 'usuario_editar':
                            $icono = '<i class="bi bi-person-gear text-warning"></i>';
                            break;
                        case 'usuario_reset':
                            $icono = '<i class="bi bi-arrow-clockwise text-danger"></i>';
                            break;
                        case 'persona_crear':
                            $icono = '<i class="bi bi-person-add text-success"></i>';
                            break;
                        case 'persona_editar':
                            $icono = '<i class="bi bi-person-lines-fill text-warning"></i>';
                            break;
                        case 'persona_toggle':
                            $icono = '<i class="bi bi-person-check text-info"></i>';
                            break;
                        case 'permiso_crear':
                            $icono = '<i class="bi bi-shield-plus text-success"></i>';
                            break;
                        case 'permiso_editar':
                            $icono = '<i class="bi bi-shield-fill-exclamation text-warning"></i>';
                            break;
                        case 'permiso_toggle':
                            $icono = '<i class="bi bi-shield-check text-info"></i>';
                            break;
                        case 'modulo_crear':
                            $icono = '<i class="bi bi-puzzle text-success"></i>';
                            break;
                        case 'modulo_editar':
                            $icono = '<i class="bi bi-puzzle-fill text-warning"></i>';
                            break;
                        case 'modulo_toggle':
                            $icono = '<i class="bi bi-toggles text-info"></i>';
                            break;
                        default:
                            $icono = '<i class="bi bi-gear text-muted"></i>';
                    }
                ?>
                <tr>
                    <td><?= $row['ID'] ?></td>
                    <td><?= htmlspecialchars($row['Usuario']) ?></td>
                    <td><?= htmlspecialchars($row['Tabla_Afectada']) ?></td>
                    <td><?= $row['Id_Registro_Afectado'] ?? '-' ?></td>
                    <td class="accion-col">
                        <?= $icono ?> 
                        <span class="badge badge-soft"><?= $row['Tipo_Accion'] ?></span>
                    </td>
                    <td class="descripcion-col"><?= nl2br(htmlspecialchars($row['Descripcion'])) ?></td>
                    <td class="fecha-col"><?= date('d/m/Y H:i', strtotime($row['Fecha_Accion'])) ?></td>
                </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Paginación -->
        <div id="paginacion" class="d-flex align-items-center justify-content-between gap-2 p-3 border-top">
            <div>
                <span id="rangeInfo" class="small text-muted">
                    Mostrando <?= number_format(min($offset + 1, $total_registros)) ?> - 
                    <?= number_format(min($offset + $registros_por_pagina, $total_registros)) ?> 
                    de <?= number_format($total_registros) ?> registros
                </span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?php if ($pagina_actual > 1): ?>
                    <button class="btn btn-outline-secondary btn-sm" 
                            onclick="goToPage(1)" title="Primera">&laquo;</button>
                    <button class="btn btn-outline-secondary btn-sm" 
                            onclick="goToPage(<?= $pagina_actual - 1 ?>)" title="Anterior">&lsaquo;</button>
                <?php else: ?>
                    <button class="btn btn-outline-secondary btn-sm" disabled title="Primera">&laquo;</button>
                    <button class="btn btn-outline-secondary btn-sm" disabled title="Anterior">&lsaquo;</button>
                <?php endif; ?>
                
                <span class="small text-muted mx-3">Página <?= $pagina_actual ?> / <?= $total_paginas ?></span>
                
                <?php if ($pagina_actual < $total_paginas): ?>
                    <button class="btn btn-outline-secondary btn-sm" 
                            onclick="goToPage(<?= $pagina_actual + 1 ?>)" title="Siguiente">&rsaquo;</button>
                    <button class="btn btn-outline-secondary btn-sm" 
                            onclick="goToPage(<?= $total_paginas ?>)" title="Última">&raquo;</button>
                <?php else: ?>
                    <button class="btn btn-outline-secondary btn-sm" disabled title="Siguiente">&rsaquo;</button>
                    <button class="btn btn-outline-secondary btn-sm" disabled title="Última">&raquo;</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="/final/mapa/server/js/bootstrap.bundle.min.js"></script>

    <script>
        // Variables globales para paginación
        let currentPage = <?= $pagina_actual ?>;
        let totalPages = <?= $total_paginas ?>;
        
        // Función para ir a una página específica
        function goToPage(page) {
            if (page < 1 || page > totalPages) return;
            
            // Obtener parámetros actuales
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('pagina', page);
            
            // Construir nueva URL
            const newUrl = window.location.pathname + '?' + urlParams.toString();
            
            // Si estamos en carga dinámica, usar cargarSeccion
            if (typeof cargarSeccion === 'function') {
                cargarSeccion('sections/bitacora.php?' + urlParams.toString());
            } else {
                // Fallback: recargar página
                window.location.href = newUrl;
            }
        }
        
        // Función para limpiar filtros
        function limpiarFiltros() {
            // Limpiar todos los campos del formulario
            const formFiltros = document.getElementById('filtros-bitacora');
            if (formFiltros) {
                formFiltros.reset();
            }
            
            // Recargar la página sin parámetros
            if (typeof cargarSeccion === 'function') {
                cargarSeccion('sections/bitacora.php');
            } else {
                // Fallback: ir a la URL base sin parámetros
                window.location.href = window.location.pathname;
            }
        }
        
        // Función para inicializar cuando se carga dinámicamente
        function initBitacoraContainer() {
            console.log('Inicializando bitácora...');
            
            // Manejar envío de filtros
            const formFiltros = document.getElementById('filtros-bitacora');
            if (formFiltros) {
                formFiltros.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Obtener datos del formulario
                    const formData = new FormData(this);
                    const params = new URLSearchParams(formData);
                    
                    // Construir URL con parámetros
                    const url = 'sections/bitacora.php?' + params.toString();
                    
                    // Recargar la sección con los filtros
                    if (typeof cargarSeccion === 'function') {
                        cargarSeccion(url);
                    } else {
                        // Fallback si no está disponible cargarSeccion
                        window.location.href = url;
                    }
                });
            }
        }

        // Evento para cuando se carga dinámicamente
        document.addEventListener('contentLoaded', function(e) {
            if (e.detail && e.detail.module === 'bitacora') {
                setTimeout(initBitacoraContainer, 100);
            }
        });

        // Si se carga como contenido dinámico, inicializar
        if (window.parent !== window || document.getElementById('main-content')) {
            setTimeout(initBitacoraContainer, 100);
        }
        
        // También inicializar cuando el DOM esté listo si se carga directamente
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initBitacoraContainer);
        } else {
            initBitacoraContainer();
        }
    </script>
</body>
