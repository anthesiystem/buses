# Módulo de Registros - Estructura Modular

## Descripción
Sistema modular para gestión de registros con paginación AJAX, filtros dinámicos y formularios modales.

## Estructura de Archivos

### Archivos Principales
- **`index.php`** - Archivo principal que integra todos los módulos
- **`config.php`** - Configuración, sesión y funciones helper
- **`actions.php`** - Manejo de acciones POST (crear, editar, desactivar)
- **`catalogos.php`** - Obtención de datos para catálogos y selectores
- **`mis_registros.php`** - Endpoint AJAX para datos paginados y filtrados

### Archivos de Vista
- **`modal.php`** - HTML del modal de registro
- **`styles.php`** - Estilos CSS del módulo

### Archivos JavaScript
- **`pagination.js`** - Funcionalidad de paginación, filtros y tabla
- **`modal.js`** - Funcionalidad del modal y validaciones

### Archivos de Datos
- **`mis_registros_new.php`** - (Archivo auxiliar/backup)

## Funcionalidades

### Gestión de Registros
- ✅ Crear nuevos registros
- ✅ Editar registros existentes
- ✅ Desactivar registros individuales
- ✅ Validaciones dinámicas por estado

### Interfaz de Usuario
- ✅ Paginación AJAX (10 registros por página)
- ✅ 7 filtros simultáneos (estado, entidad, categoría, bus, engine, tecnología, etapa)
- ✅ Layout responsivo (Bootstrap 5)
- ✅ Modal moderno con validaciones

### Características Técnicas
- ✅ Arquitectura modular y reutilizable
- ✅ Separación de responsabilidades
- ✅ Compatibilidad con carga dinámica y directa
- ✅ Rutas adaptativas según contexto
- ✅ Bitácora de acciones integrada
- ✅ Manejo de errores robusto

## Rutas de Acceso

### Acceso Directo
```
/final/mapa/public/sections/registros/index.php
```

### Acceso Dinámico (desde index.php)
```
/final/mapa/public/index.php → cargarSeccion('sections/registros/index.php')
```

## Dependencias

### Backend
- PHP 7.4+
- MySQL/MariaDB
- PDO
- Sesiones PHP

### Frontend
- Bootstrap 5.3.3
- Bootstrap Icons 1.11.3
- JavaScript ES6+

### Archivos Externos
- `../../../server/config.php` - Configuración de BD
- `../../../server/bitacora_helper.php` - Sistema de bitácora
- `../../assets/js/bitacora_tracker.js` - Tracker de vistas
- `../../img/escudospiner.gif` - Imagen de loading

## Base de Datos

### Tabla Principal
- `registro` - Tabla principal de registros

### Tablas Relacionadas
- `dependencia`, `entidad`, `bus`, `motor_base`
- `tecnologia`, `estado_bus`, `categoria`, `etapa`

### Campos Especiales
- `activo` (BIT) - Control de registros activos/inactivos
- `fecha_creacion`, `fecha_modificacion` - Timestamps automáticos

## Notas de Desarrollo

### Ventajas de la Modularización
1. **Mantenibilidad**: Cada archivo tiene una responsabilidad específica
2. **Reutilización**: Los módulos pueden ser reutilizados en otros proyectos
3. **Debugging**: Más fácil localizar y corregir errores
4. **Escalabilidad**: Fácil agregar nuevas funcionalidades
5. **Colaboración**: Múltiples desarrolladores pueden trabajar simultáneamente

### Buenas Prácticas Implementadas
- Validación de entrada y escape de salida
- Manejo centralizado de errores
- Logs de acciones para auditoría
- Código comentado y documentado
- Estructura consistente de archivos

## Autor
Sistema desarrollado para gestión integral de registros con arquitectura modular.
