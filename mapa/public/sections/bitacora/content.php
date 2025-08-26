<?php
// Filtros
$filtro_usuario = $_GET['usuario'] ?? '';
$filtro_accion = $_GET['accion'] ?? '';
$filtro_fecha  = $_GET['fecha'] ?? '';
$filtro_tabla  = $_GET['tabla'] ?? '';

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
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener listas para filtros
$usuarios_result = $pdo->query("SELECT ID, cuenta FROM usuario ORDER BY cuenta");
$tablas_result = $pdo->query("SELECT DISTINCT Tabla_Afectada FROM bitacora ORDER BY Tabla_Afectada");
?>

<style>
:root {
    --brand:#7b1e2b; 
    --brand-600:#8e2433; 
    --brand-700:#661822; 
    --brand-rgb:123,30,43;
    --ink:#1f2937; 
    --muted:#6b7280;
    --header-bg:#ffffff; 
    --header-border:#e5e7eb; 
    --table-border:#e5e7eb;
}

.card-filter {
    background: #fff;
    border: 1px solid var(--header-border);
    border-radius: 14px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,.05);
}

.table-container {
    background: #fff;
    border: 1px solid var(--table-border);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0,0,0,.05);
}

.table-responsive {
    margin: 0;
}

.table {
    margin-bottom: 0;
}

.table thead th {
    background: var(--header-bg);
    border-bottom: 2px solid var(--header-border);
    color: var(--ink);
    font-weight: 600;
    text-transform: uppercase;
    font-size: .85rem;
    letter-spacing: .5px;
}

.btn-brand {
    background-color: var(--brand);
    border-color: var(--brand);
    color: #fff;
}

.btn-brand:hover {
    background-color: var(--brand-600);
    border-color: var(--brand-600);
    color: #fff;
}

.pagination-container {
    background: #fff;
    padding: 1rem;
    border-top: 1px solid var(--table-border);
}

.badge-action {
    font-size: .75rem;
    padding: .35em .65em;
    font-weight: 600;
}

.badge-insert { background-color: #10B981; color: #fff; }
.badge-update { background-color: #3B82F6; color: #fff; }
.badge-delete { background-color: #EF4444; color: #fff; }
.badge-activate { background-color: #8B5CF6; color: #fff; }
.badge-deactivate { background-color: #6B7280; color: #fff; }
</style>

<div class="container-fluid py-4">
    <h2 class="mb-4">Bitácora de Auditoría</h2>

    <div class="card-filter">
        <form method="get" class="row g-3" id="filterForm">
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
                    <option value="INSERT" <?= $filtro_accion == 'INSERT' ? 'selected' : '' ?>>INSERT</option>
                    <option value="UPDATE" <?= $filtro_accion == 'UPDATE' ? 'selected' : '' ?>>UPDATE</option>
                    <option value="DELETE" <?= $filtro_accion == 'DELETE' ? 'selected' : '' ?>>DELETE</option>
                    <option value="ACTIVAR" <?= $filtro_accion == 'ACTIVAR' ? 'selected' : '' ?>>ACTIVAR</option>
                    <option value="DESACTIVAR" <?= $filtro_accion == 'DESACTIVAR' ? 'selected' : '' ?>>DESACTIVAR</option>
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
            <div class="col-12">
                <button type="submit" class="btn btn-brand"><i class="bi bi-filter me-2"></i>Aplicar Filtros</button>
            </div>
        </form>
    </div>

    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tablaBitacora">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Tabla Afectada</th>
                        <th>ID Registro</th>
                        <th>Acción</th>
                        <th>Descripción</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result as $row): 
                        $badgeClass = 'badge-action badge ';
                        switch (strtoupper($row['Tipo_Accion'])) {
                            case 'INSERT': $badgeClass .= 'badge-insert'; break;
                            case 'UPDATE': $badgeClass .= 'badge-update'; break;
                            case 'DELETE': $badgeClass .= 'badge-delete'; break;
                            case 'ACTIVAR': $badgeClass .= 'badge-activate'; break;
                            case 'DESACTIVAR': $badgeClass .= 'badge-deactivate'; break;
                            default: $badgeClass .= 'bg-secondary';
                        }
                    ?>
                    <tr>
                        <td><?= $row['ID'] ?></td>
                        <td><?= htmlspecialchars($row['Usuario']) ?></td>
                        <td><?= htmlspecialchars($row['Tabla_Afectada']) ?></td>
                        <td><?= $row['Id_Registro_Afectado'] ?? '-' ?></td>
                        <td><span class="<?= $badgeClass ?>"><?= $row['Tipo_Accion'] ?></span></td>
                        <td><?= nl2br(htmlspecialchars($row['Descripcion'])) ?></td>
                        <td><?= $row['Fecha_Accion'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="pagination-container">
            <nav id="tablePagination"></nav>
            <div class="text-muted text-center mt-2 small" id="tableInfo"></div>
        </div>
    </div>
</div>

<script>
// Variables de paginación
let currentPage = 1;
const rowsPerPage = 10;
let allRows = [];
let filteredRows = [];

// Inicializar paginación
function initPagination() {
    allRows = Array.from(document.querySelectorAll('#tablaBitacora tbody tr'));
    filteredRows = allRows;
    updatePagination();
}

// Actualizar paginación
function updatePagination() {
    const totalRows = filteredRows.length;
    const totalPages = Math.ceil(totalRows / rowsPerPage);
    const start = (currentPage - 1) * rowsPerPage;
    const end = Math.min(start + rowsPerPage, totalRows);

    // Ocultar/mostrar filas
    allRows.forEach(row => row.classList.add('d-none'));
    filteredRows.slice(start, end).forEach(row => row.classList.remove('d-none'));

    // Actualizar información
    document.getElementById('tableInfo').textContent = 
        `Mostrando ${totalRows ? start + 1 : 0} - ${end} de ${totalRows} registros`;

    // Generar paginación
    const pagination = document.getElementById('tablePagination');
    if (totalPages <= 1) {
        pagination.innerHTML = '';
        return;
    }

    let html = '<ul class="pagination justify-content-center">';

    // Botón anterior
    html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
              <a class="page-link" href="#" onclick="return changePage(${currentPage - 1})">&laquo;</a>
            </li>`;

    // Números de página
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
            html += `<li class="page-item ${currentPage === i ? 'active' : ''}">
                      <a class="page-link" href="#" onclick="return changePage(${i})">${i}</a>
                    </li>`;
        } else if (i === currentPage - 2 || i === currentPage + 2) {
            html += `<li class="page-item disabled"><a class="page-link" href="#">...</a></li>`;
        }
    }

    // Botón siguiente
    html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
              <a class="page-link" href="#" onclick="return changePage(${currentPage + 1})">&raquo;</a>
            </li>`;

    html += '</ul>';
    pagination.innerHTML = html;
}

// Cambiar página
function changePage(newPage) {
    const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
    if (newPage >= 1 && newPage <= totalPages) {
        currentPage = newPage;
        updatePagination();
    }
    return false;
}

// Buscar en la tabla
function searchTable(value) {
    value = value.toLowerCase();
    filteredRows = allRows.filter(row => 
        row.textContent.toLowerCase().includes(value)
    );
    currentPage = 1;
    updatePagination();
}

// Inicializar cuando el documento esté listo
document.addEventListener('DOMContentLoaded', () => {
    initPagination();
});

// Manejar el envío del formulario
document.getElementById('filterForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const searchParams = new URLSearchParams(formData);
    
    // Actualizar la URL sin recargar la página
    const newUrl = window.location.pathname + '?' + searchParams.toString();
    window.history.pushState({ path: newUrl }, '', newUrl);
    
    // Recargar los datos
    fetch(newUrl)
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newTbody = doc.querySelector('#tablaBitacora tbody');
            document.querySelector('#tablaBitacora tbody').innerHTML = newTbody.innerHTML;
            
            // Reiniciar paginación
            initPagination();
        });
});
</script>
