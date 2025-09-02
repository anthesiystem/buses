# Sistema de Permisos por Lotes

Este sistema permite la gestión avanzada de permisos de usuario mediante la creación, edición y eliminación de lotes de permisos. Cada lote agrupa múltiples combinaciones de entidad×bus bajo un token único.

## Estructura de la Base de Datos

La tabla `permiso_usuario` ha sido extendida con un campo `group_token` para agrupar permisos relacionados:

```sql
CREATE TABLE `permiso_usuario` (
    `ID` INT NOT NULL AUTO_INCREMENT,
    `group_token` CHAR(36) NULL DEFAULT NULL,  -- Nuevo campo para agrupar permisos
    `Fk_usuario` INT NOT NULL,
    `Fk_modulo` INT NOT NULL,
    `FK_entidad` VARCHAR(45) NULL DEFAULT NULL,
    `FK_bus` INT NULL DEFAULT NULL,
    `accion` VARCHAR(15) NULL DEFAULT NULL,
    `activo` BIT(1) NOT NULL DEFAULT (b'1'),
    PRIMARY KEY (`ID`),
    -- ... índices y constraints existentes
);
```

## Archivos del Sistema

### APIs Backend (PHP)

1. **`permiso_lote.php`** - API principal para operaciones de lotes
   - Crear lotes: `POST action=crear`
   - Editar lotes: `POST action=editar`
   - Eliminar lotes: `POST action=eliminar`

2. **`permisos_grupos.php`** - Lista permisos agrupados por token
   - Agrupa permisos por `group_token`
   - Incluye estadísticas de combinaciones activas/inactivas
   - Soporta filtros por usuario, módulo, entidad, bus

3. **`entidades_listar.php`** - Lista entidades disponibles
   - Endpoint auxiliar para catálogos

### Interfaces Frontend (HTML)

1. **`demo-lotes.html`** - Interfaz completa y moderna
   - Diseño tipo dashboard con dark theme
   - Gestión completa de lotes con matriz visual
   - Notificaciones en tiempo real
   - Filtros avanzados

2. **`demo.html`** - Interfaz actualizada (demo original mejorado)
   - Integra APIs reales en lugar de datos de ejemplo
   - Mantiene la funcionalidad de matriz de switches

3. **`test-lotes.html`** - Página de pruebas para desarrolladores
   - Interfaz simple para probar todas las operaciones
   - Logs detallados de requests y responses
   - Útil para debugging y desarrollo

## Funcionalidades Principales

### Crear Lote
```javascript
// Ejemplo de uso
const formData = new FormData();
formData.append('action', 'crear');
formData.append('Fk_usuario', 1);
formData.append('Fk_modulo', 2);
formData.append('accion', 'READ');
formData.append('activo', 1);
formData.append('entidades', JSON.stringify(['1', '2', 'ALL']));
formData.append('buses', JSON.stringify(['1', '2']));

fetch('sections/usuarios/api/permiso_lote.php', {
    method: 'POST',
    body: formData
});
```

### Editar Lote
```javascript
// Similar a crear, pero incluye group_token
formData.append('action', 'editar');
formData.append('group_token', 'uuid-del-grupo');
```

### Eliminar Lote
```javascript
formData.append('action', 'eliminar');
formData.append('group_token', 'uuid-del-grupo');
```

## Lógica de Negocio

### Validaciones
- Usuario y módulo son obligatorios
- Si se selecciona "ALL" (Todas/Todos), se excluyen las selecciones específicas
- Se valida que no existan duplicados antes de insertar

### Producto Cartesiano
El sistema genera automáticamente todas las combinaciones posibles entre entidades y buses seleccionados:

```
Entidades: [1, 2]
Buses: [1, 2, 3]
Resultado: 6 combinaciones
- (Entidad 1, Bus 1)
- (Entidad 1, Bus 2)
- (Entidad 1, Bus 3)
- (Entidad 2, Bus 1)
- (Entidad 2, Bus 2)
- (Entidad 2, Bus 3)
```

### Gestión de Cambios
Al editar un lote:
1. Se comparan las combinaciones existentes vs las nuevas
2. Se insertan las combinaciones nuevas
3. Se eliminan las combinaciones que ya no aplican
4. Se actualizan los metadatos (usuario, módulo, acción, estado) de todo el grupo

### Transacciones
Todas las operaciones usan transacciones de base de datos para garantizar consistencia:
- Si falla una inserción, se hace rollback completo
- Se registra en bitácora cada operación exitosa

## Integraciones

### Bitácora
El sistema integra con `bitacora_helper.php` para registrar:
- Creación de lotes: `permiso_lote_crear`
- Edición de lotes: `permiso_lote_editar`
- Eliminación de lotes: `permiso_lote_eliminar`

### Sesiones
Utiliza `obtenerUsuarioSession()` para registrar quién realiza cada operación.

## Uso Recomendado

### Para Administradores
1. Usar `demo-lotes.html` para gestión diaria
2. Crear lotes para asignaciones masivas de permisos
3. Usar filtros para encontrar grupos específicos
4. Editar lotes cuando cambien los requerimientos

### Para Desarrolladores
1. Usar `test-lotes.html` para probar integraciones
2. Consultar logs de bitácora para auditoría
3. Extender `permisos_grupos.php` para reportes personalizados

## Compatibilidad

### Retrocompatibilidad
- Los permisos existentes sin `group_token` siguen funcionando
- Se mantienen las APIs originales (`permiso_guardar.php`, `permiso_toggle.php`)
- Los permisos individuales se pueden migrar a lotes si es necesario

### Migración
Para migrar permisos existentes a lotes:
1. Identificar permisos que deberían estar agrupados
2. Crear lotes nuevos con las combinaciones correspondientes
3. Eliminar permisos individuales una vez validados los lotes

## Consideraciones Técnicas

### Performance
- Usar índices en `group_token` para consultas rápidas
- Las consultas agrupadas pueden ser intensivas con muchos registros
- Considerar paginación para interfaces con muchos grupos

### Seguridad
- Validación de permisos antes de mostrar opciones
- Sanitización de inputs en todas las APIs
- Logs de auditoría completos

### Escalabilidad
- El sistema soporta miles de combinaciones por lote
- UUID v4 garantiza unicidad de tokens
- Estructura preparada para sharding futuro si es necesario
