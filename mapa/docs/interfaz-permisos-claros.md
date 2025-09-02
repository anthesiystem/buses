# Interfaz de Permisos con Tonos Claros

## Descripción General

La nueva interfaz `permisos-claros.html` implementa un diseño moderno con tonos claros que permite gestionar permisos por lotes con funcionalidad avanzada de activación/desactivación individual.

## Características Principales

### 🎨 Diseño Visual
- **Paleta de colores claros**: Fondos blancos, grises suaves y acentos azules
- **Iconografía moderna**: Emojis y SVGs para mejor UX
- **Diseño responsive**: Adaptable a dispositivos móviles y tablets
- **Sombras sutiles**: Efectos visuales modernos sin sobrecargar

### 🔧 Funcionalidades Principales

#### 1. Gestión de Lotes
- **Crear lotes**: Nuevos grupos de permisos con token único
- **Editar lotes**: Modificar configuraciones existentes
- **Eliminar lotes**: Borrado completo con confirmación

#### 2. Control Individual de Permisos
- **Toggle switches**: Activar/desactivar permisos específicos desde la tabla
- **Vista de matriz**: Combinaciones entidad × bus con switches individuales
- **Estados visuales**: Indicadores claros de activo/inactivo

#### 3. Filtros y Búsqueda
- **Filtros múltiples**: Por usuario, módulo, entidad y bus
- **Búsqueda en tiempo real**: Filtrado instantáneo por texto
- **Limpieza de filtros**: Reset fácil de criterios

## Componentes Técnicos

### APIs Utilizadas
```
GET  permisos_grupos.php        - Lista grupos con filtros
POST permiso_lote.php           - CRUD de lotes (crear/editar/eliminar)
POST permiso_toggle_individual.php - Toggle individual de permisos
GET  permisos_listar.php        - Catálogos de usuarios/módulos/buses
GET  entidades_listar.php       - Lista de entidades
```

### Estructura CSS
```css
:root {
  --bg: #f8fafc;          /* Fondo principal */
  --card: #ffffff;        /* Tarjetas y modales */
  --text: #1e293b;        /* Texto principal */
  --brand: #3b82f6;       /* Color de marca (azul) */
  --ok: #10b981;          /* Verde para éxito */
  --danger: #ef4444;      /* Rojo para peligro */
  --muted: #64748b;       /* Texto secundario */
}
```

### Componentes JavaScript Principales

#### 1. Multiselect con "Todos/Todas"
```javascript
function makeMultibox(container, options) {
  // Implementa selección múltiple con opción especial "ALL"
  // Maneja lógica de exclusión mutua
}
```

#### 2. Toggle Individual
```javascript
async function togglePermiso(groupToken, entidad, bus, currentState) {
  // Cambio de estado individual vía API
  // Actualización inmediata de la interfaz
}
```

#### 3. Sistema de Notificaciones
```javascript
function showNotification(message, type = 'success') {
  // Notificaciones toast modernas
  // Auto-dismiss después de 4 segundos
}
```

## Flujo de Uso

### Crear Nuevo Lote
1. Click en "Nuevo lote de permisos"
2. Seleccionar usuario, módulo y acción
3. Elegir entidades y buses (con soporte para "Todas/Todos")
4. Configurar matriz de combinaciones con switches
5. Guardar lote con token generado automáticamente

### Editar Lote Existente
1. Click en botón "Editar" en la tabla
2. Modificar selecciones según necesidad
3. Ajustar estados individuales en la matriz
4. Guardar cambios

### Toggle Individual
1. En la columna "Combinaciones", click en cualquier combinación
2. El estado cambia inmediatamente (✅ ↔ ❌)
3. Actualización en base de datos vía API
4. Notificación de confirmación

## Ventajas sobre la Interfaz Anterior

### 1. Mejor UX
- **Tonos claros**: Menos fatiga visual
- **Iconografía**: Mejor reconocimiento visual
- **Feedback inmediato**: Estados claros y notificaciones

### 2. Funcionalidad Avanzada
- **Toggle individual**: No necesitas abrir modal para cambios simples
- **Filtros avanzados**: Búsqueda más precisa
- **Vista de matriz**: Control granular de permisos

### 3. Performance
- **Carga asíncrona**: APIs optimizadas
- **Cache inteligente**: Menos llamadas al servidor
- **Renderizado eficiente**: Solo actualiza lo necesario

## Configuración de Entorno

### Archivos Requeridos
```
permisos-claros.html                          - Interfaz principal
sections/usuarios/api/permiso_lote.php        - API de lotes (existente)
sections/usuarios/api/permiso_toggle_individual.php - API toggle (nuevo)
sections/usuarios/api/permisos_grupos.php     - API listado (existente)
```

### Base de Datos
La interfaz utiliza la estructura existente con:
- Tabla `permiso_usuario` con campo `group_token`
- Soporte para valores NULL en `FK_entidad` y `FK_bus` (representa "ALL")

## Casos de Uso

### Administrador General
- Crear lotes masivos con "Todas las entidades" y "Todos los buses"
- Toggle rápido para desactivar accesos temporalmente
- Vista completa de todos los grupos

### Gestor Regional
- Filtrar por entidades específicas
- Gestionar permisos de su región
- Control granular por bus

### Administrador de Sistema
- Monitoring de estados de permisos
- Auditoría visual de configuraciones
- Gestión de usuarios específicos

## Próximas Mejoras

1. **Export/Import**: Exportar configuraciones a CSV/Excel
2. **Plantillas**: Guardar configuraciones como plantillas reutilizables
3. **Historial**: Ver cambios realizados en cada lote
4. **Notificaciones push**: Alertas en tiempo real
5. **Bulk actions**: Operaciones masivas en múltiples lotes

## Notas Técnicas

- Compatible con PHP 7.4+
- Requiere JavaScript ES6+
- Base de datos MySQL/MariaDB
- Responsive design (Bootstrap-like sin dependencias)
- Accesibilidad ARIA básica implementada
