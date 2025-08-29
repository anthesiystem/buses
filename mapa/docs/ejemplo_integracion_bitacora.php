<?php
/**
 * EJEMPLO DE INTEGRACIÓN DE BITÁCORA
 * 
 * Este archivo muestra cómo integrar el registro de bitácora en diferentes partes del sistema
 */

// Ejemplo 1: Al guardar un registro
function ejemploGuardarRegistro($pdo) {
    require_once '../server/bitacora_helper.php';
    
    // ... código para guardar el registro ...
    
    // Registrar en bitácora
    $usuario_info = obtenerUsuarioSession();
    $detalles = [
        'entidad' => 'Nombre de la entidad',
        'dependencia' => 'Nombre de la dependencia',
        'bus' => 'Nombre del bus'
    ];
    
    registrarAccionRegistro(
        $pdo, 
        $usuario_info['user_id'], 
        $usuario_info['user_name'], 
        $registro_id, 
        'INSERT', 
        $detalles
    );
}

// Ejemplo 2: Al descargar un reporte PDF
function ejemploDescargaReporte($pdo) {
    require_once '../server/bitacora_helper.php';
    
    $usuario_info = obtenerUsuarioSession();
    
    registrarDescargaPDF(
        $pdo, 
        $usuario_info['user_id'], 
        $usuario_info['user_name'], 
        'reporte', 
        'Reporte General de Estados'
    );
}

// Ejemplo 3: Al eliminar un registro
function ejemploEliminarRegistro($pdo, $registro_id) {
    require_once '../server/bitacora_helper.php';
    
    // Obtener información del registro antes de eliminarlo
    $stmt = $pdo->prepare("SELECT e.descripcion AS entidad, d.descripcion AS dependencia 
                          FROM registro r 
                          LEFT JOIN entidad e ON e.ID = r.Fk_entidad 
                          LEFT JOIN dependencia d ON d.ID = r.Fk_dependencia 
                          WHERE r.ID = ?");
    $stmt->execute([$registro_id]);
    $info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // ... código para eliminar ...
    
    // Registrar en bitácora
    $usuario_info = obtenerUsuarioSession();
    $descripcion = "Registro eliminado - Entidad: " . ($info['entidad'] ?? 'N/A') . 
                   ", Dependencia: " . ($info['dependencia'] ?? 'N/A');
    
    registrarBitacora(
        $pdo, 
        $usuario_info['user_id'], 
        'registro', 
        'DELETE', 
        $descripcion, 
        $registro_id
    );
}

// Ejemplo 4: Al cambiar estado de un registro
function ejemploCambiarEstado($pdo, $registro_id, $nuevo_estado) {
    require_once '../server/bitacora_helper.php';
    
    $usuario_info = obtenerUsuarioSession();
    $accion = $nuevo_estado ? 'ACTIVAR' : 'DESACTIVAR';
    $descripcion = "Registro " . ($nuevo_estado ? 'activado' : 'desactivado');
    
    registrarBitacora(
        $pdo, 
        $usuario_info['user_id'], 
        'registro', 
        $accion, 
        $descripcion, 
        $registro_id
    );
}

// Ejemplo 5: Para acciones en catálogos
function ejemploAccionCatalogo($pdo, $tabla, $registro_id, $accion, $nombre_item) {
    require_once '../server/bitacora_helper.php';
    
    $usuario_info = obtenerUsuarioSession();
    $descripcion = "Acción $accion en $tabla: $nombre_item";
    
    registrarBitacora(
        $pdo, 
        $usuario_info['user_id'], 
        $tabla, 
        $accion, 
        $descripcion, 
        $registro_id
    );
}

/**
 * INTEGRACIÓN EN JAVASCRIPT (para llamadas AJAX)
 */
?>

<script>
// Función para registrar descarga de PDF desde JavaScript
function registrarDescargaPDF(estado) {
    fetch('/final/mapa/public/registrar_descarga_pdf.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'estado=' + encodeURIComponent(estado)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Descarga registrada en bitácora');
        } else {
            console.warn('Error registrando descarga:', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Ejemplo de uso en la generación de PDF
function generarPDF(nombreEstado) {
    // ... código para generar el PDF ...
    
    // Registrar en bitácora
    registrarDescargaPDF(nombreEstado);
}

// Interceptar todos los enlaces de descarga PDF
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('a[href*=".pdf"], button[data-pdf]').forEach(link => {
        link.addEventListener('click', function() {
            const estado = this.dataset.estado || this.textContent || 'PDF';
            registrarDescargaPDF(estado);
        });
    });
});
</script>
