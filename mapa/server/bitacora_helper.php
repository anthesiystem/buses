<?php
/**
 * Funciones helper para registro en bitácora
 */

if (!function_exists('registrarBitacora')) {
    /**
     * Registra una acción en la bitácora
     * 
     * @param PDO $pdo Conexión a la base de datos
     * @param int $usuario_id ID del usuario que realiza la acción
     * @param string $tabla_afectada Tabla o módulo afectado
     * @param string $tipo_accion Tipo de acción (INSERT, UPDATE, DELETE, DESCARGA, COMENTARIO, etc.)
     * @param string $descripcion Descripción detallada de la acción
     * @param int|null $id_registro_afectado ID del registro afectado (opcional)
     * @return bool True si se registró correctamente, false en caso contrario
     */
    function registrarBitacora($pdo, $usuario_id, $tabla_afectada, $tipo_accion, $descripcion, $id_registro_afectado = null) {
        try {
            $sql = "INSERT INTO bitacora (Fk_Usuario, Tabla_Afectada, Id_Registro_Afectado, Tipo_Accion, Descripcion, Fecha_Accion)
                    VALUES (?, ?, ?, ?, ?, NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $usuario_id,
                $tabla_afectada,
                $id_registro_afectado,
                $tipo_accion,
                $descripcion
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log("Error registrando en bitácora: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('registrarDescargaPDF')) {
    /**
     * Registra la descarga de un PDF en la bitácora
     * 
     * @param PDO $pdo Conexión a la base de datos
     * @param int $usuario_id ID del usuario
     * @param string $usuario_nombre Nombre del usuario
     * @param string $tipo_pdf Tipo de PDF descargado (estado, reporte, etc.)
     * @param string $detalle Detalle adicional (nombre del estado, etc.)
     * @return bool
     */
    function registrarDescargaPDF($pdo, $usuario_id, $usuario_nombre, $tipo_pdf, $detalle = '') {
        $descripcion = "El usuario $usuario_nombre descargó un PDF de $tipo_pdf";
        if (!empty($detalle)) {
            $descripcion .= ": $detalle";
        }
        
        return registrarBitacora($pdo, $usuario_id, 'pdf_' . $tipo_pdf, 'DESCARGA', $descripcion);
    }
}

if (!function_exists('registrarComentario')) {
    /**
     * Registra la creación de un comentario en la bitácora
     * 
     * @param PDO $pdo Conexión a la base de datos
     * @param int $usuario_id ID del usuario
     * @param string $usuario_nombre Nombre del usuario
     * @param int $registro_id ID del registro comentado
     * @param string $encabezado Encabezado del comentario
     * @param string $etapa Etapa en la que se comentó
     * @return bool
     */
    function registrarComentario($pdo, $usuario_id, $usuario_nombre, $registro_id, $encabezado, $etapa = '') {
        $descripcion = "El usuario $usuario_nombre agregó un comentario al registro ID $registro_id";
        if (!empty($encabezado)) {
            $descripcion .= " - Encabezado: $encabezado";
        }
        if (!empty($etapa)) {
            $descripcion .= " - Etapa: $etapa";
        }
        
        return registrarBitacora($pdo, $usuario_id, 'comentario', 'COMENTARIO', $descripcion, $registro_id);
    }
}

if (!function_exists('registrarAccionRegistro')) {
    /**
     * Registra acciones sobre registros (CRUD)
     * 
     * @param PDO $pdo Conexión a la base de datos
     * @param int $usuario_id ID del usuario
     * @param string $usuario_nombre Nombre del usuario
     * @param int $registro_id ID del registro
     * @param string $accion Tipo de acción (INSERT, UPDATE, DELETE, ACTIVAR, DESACTIVAR)
     * @param array $detalles Detalles adicionales del registro
     * @return bool
     */
    function registrarAccionRegistro($pdo, $usuario_id, $usuario_nombre, $registro_id, $accion, $detalles = []) {
        $descripcion = "El usuario $usuario_nombre realizó acción $accion en registro ID $registro_id";
        
        if (!empty($detalles)) {
            $info_adicional = [];
            if (isset($detalles['entidad'])) $info_adicional[] = "Entidad: " . $detalles['entidad'];
            if (isset($detalles['dependencia'])) $info_adicional[] = "Dependencia: " . $detalles['dependencia'];
            if (isset($detalles['bus'])) $info_adicional[] = "Bus: " . $detalles['bus'];
            
            if (!empty($info_adicional)) {
                $descripcion .= " - " . implode(", ", $info_adicional);
            }
        }
        
        return registrarBitacora($pdo, $usuario_id, 'registro', $accion, $descripcion, $registro_id);
    }
}

if (!function_exists('obtenerUsuarioSession')) {
    /**
     * Obtiene el ID del usuario de la sesión
     * 
     * @return int ID del usuario
     */
    function obtenerUsuarioSession() {
        return (int)($_SESSION['usuario_id'] ?? $_SESSION['ID'] ?? 0);
    }
}

if (!function_exists('obtenerNombreUsuarioSession')) {
    /**
     * Obtiene el nombre del usuario de la sesión
     * 
     * @return string Nombre del usuario
     */
    function obtenerNombreUsuarioSession() {
        return (string)($_SESSION['usuario']['cuenta'] ?? $_SESSION['cuenta'] ?? $_SESSION['usuario'] ?? 'Usuario Desconocido');
    }
}
?>
