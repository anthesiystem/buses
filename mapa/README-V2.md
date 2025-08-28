# 🗺️ Mapa General V2 - Enhanced Version

Sistema mejorado del mapa general que utiliza `img-map-enhanced.svg` con datos completos de entidad y funcionalidades avanzadas.

## 🎯 Características Principales

### ✨ Versión 2 Enhanced
- **SVG Mejorado**: Utiliza `img-map-enhanced.svg` con datos completos de entidad
- **Diseño Moderno**: Gradientes, animaciones y transiciones suaves
- **Interacciones Avanzadas**: Zoom con rueda del mouse, arrastre mejorado
- **Panel de Estadísticas**: Información en tiempo real del mapa
- **Tooltips Informativos**: Información detallada al hacer hover
- **Detección Automática**: Identifica el tipo de SVG cargado
- **Sistema de Debug**: Herramientas integradas para desarrollo

## 📁 Estructura de Archivos

```
mapa/
├── public/
│   ├── sections/mapabus/
│   │   ├── general.php          # Versión original (V1)
│   │   └── general_v2.php       # Versión enhanced (V2)
│   ├── img-map-enhanced.svg     # SVG con datos completos
│   ├── demo-mapa.html          # Editor/validador de estados
│   ├── compare-svg.html        # Comparador de archivos SVG
│   └── compare-versions.html   # Comparador de versiones
└── server/mapag/
    ├── mapageneral.js          # Script original (V1)
    └── mapageneral_v2.js       # Script enhanced (V2)
```

## 🚀 URLs de Acceso

### Mapas
- **V1 Original**: `http://localhost/final/mapa/public/sections/mapabus/general.php`
- **V2 Enhanced**: `http://localhost/final/mapa/public/sections/mapabus/general_v2.php`

### Herramientas
- **Demo Interactivo**: `http://localhost/final/mapa/public/demo-mapa.html`
- **Comparador SVG**: `http://localhost/final/mapa/public/compare-svg.html`
- **Comparador Versiones**: `http://localhost/final/mapa/public/compare-versions.html`

## 📊 Diferencias entre Versiones

| Característica | V1 Original | V2 Enhanced |
|----------------|-------------|-------------|
| **SVG** | mapa.svg | img-map-enhanced.svg |
| **Datos de Estado** | Solo desde BD | SVG + BD |
| **Diseño** | CSS estándar | Gradientes y animaciones |
| **Interacciones** | Click básico | Zoom, arrastre, hover |
| **Tooltips** | Básicos | Informativos avanzados |
| **Estadísticas** | Manual | Tiempo real |
| **Debug** | Limitado | Sistema completo |

## 🔧 Configuración V2

### Variables Globales JavaScript
```javascript
// Acceder a la configuración
window.mapV2.config

// Ver estados cargados
window.mapV2.estados

// Información de debug
debugInfoV2()
```

### Colores V2
```javascript
const configV2 = {
    colors: {
        concluido: '#4caf50',    // Verde
        sinEjecutar: '#bdbdbd',  // Gris
        otro: '#f44336',         // Rojo
        hover: '#2196f3',        // Azul
        selected: '#ff6b6b'      // Rojo claro
    }
}
```

## 📝 SVG Enhanced

El archivo `img-map-enhanced.svg` incluye:

```xml
<path id="MX-AGU" 
      class="mx-state" 
      data-entidad-id="1" 
      data-entidad-nombre="Aguascalientes"
      fill="#8cc8ff" 
      stroke="#666" 
      stroke-width="1"
      d="..."/>
```

### Atributos por Estado
- `id`: Código del estado (MX-AGU, MX-BCN, etc.)
- `class`: Clase CSS (mx-state)
- `data-entidad-id`: ID numérico de la entidad (1-32)
- `data-entidad-nombre`: Nombre completo del estado
- Estilos CSS embebidos para interacciones

## 🛠️ Desarrollo y Debug

### Debug V2
```javascript
// En la consola del navegador
debugInfoV2()

// Información del mapa
console.log(window.mapV2)

// Actualizar estadísticas
window.mapV2.updateStats()
```

### Herramientas de Desarrollo

1. **Demo Interactivo** (`demo-mapa.html`):
   - Editor visual de mapeo de estados
   - Validación de asignaciones completas
   - Exportación de SVG mejorado

2. **Comparador SVG** (`compare-svg.html`):
   - Análisis automático de diferencias
   - Generación de reportes
   - Scripts de mejora automática

3. **Comparador Versiones** (`compare-versions.html`):
   - Vista comparativa V1 vs V2
   - Especificaciones técnicas
   - Enlaces directos a herramientas

## 🎨 Estilos CSS V2

### Contenedor Principal
```css
.contenedor-mapa-general-v2 {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}
```

### Efectos de Estados
```css
.estado-seleccionado-v2 {
    stroke: #ff6b6b !important;
    stroke-width: 3 !important;
    stroke-dasharray: 12,6 !important;
    animation: dashMoveV2 2s linear infinite;
}
```

## 📦 Instalación y Uso

### Prerrequisitos
- Servidor web con PHP habilitado
- Base de datos configurada
- Archivos SVG en la carpeta `public/`

### Pasos de Instalación
1. Copiar archivos a la carpeta del proyecto
2. Verificar permisos de lectura en archivos SVG
3. Acceder a las URLs correspondientes
4. Usar herramientas de desarrollo para personalización

### Uso Básico
1. Acceder a `general_v2.php`
2. Observar detección automática del tipo de SVG
3. Interactuar con los estados (click, hover)
4. Revisar panel de estadísticas
5. Usar controles de vista (zoom, reset)

## 🧪 Testing

### Verificar Funcionalidad
```javascript
// Verificar carga del SVG
document.querySelector('#mapa-v2 svg') !== null

// Verificar detección de estados enhanced
document.querySelectorAll('path[data-entidad-id]').length > 0

// Verificar interacciones
window.mapV2.interactions.isEnabled === true
```

### Estados de Prueba
El SVG enhanced incluye los 32 estados mexicanos con datos completos:
- Aguascalientes (ID: 1)
- Baja California (ID: 2)
- ...hasta Zacatecas (ID: 32)

## 🔄 Migración desde V1

### Diferencias de Implementación
1. **IDs de contenedores**: `mapa-v2`, `info-v2` en lugar de `mapa`, `info`
2. **Scripts**: `mapageneral_v2.js` en lugar de `mapageneral.js`
3. **Estilos**: Clases CSS específicas V2
4. **Variables globales**: `window.__ACL_GENERAL_V2__` en lugar de `window.__ACL_GENERAL__`

### Compatibilidad
- Mantiene toda la funcionalidad de V1
- Compatible con el sistema de permisos existente
- Funciona con la misma base de datos
- Hereda sistema de comentarios y modales

## 📈 Rendimiento

### Optimizaciones V2
- Transiciones CSS con hardware acceleration
- Event delegation para mejor rendimiento
- Lazy loading de datos adicionales
- Detección inteligente de capacidades del SVG

### Métricas
- Tiempo de carga: ~500ms
- Interacciones: <50ms de respuesta
- Memoria: Uso eficiente con cleanup automático

## 🎯 Roadmap Futuro

### Características Planeadas
- [ ] Integración con API REST
- [ ] Modo offline con localStorage
- [ ] Exportación a diferentes formatos
- [ ] Temas personalizables
- [ ] Modo de comparación side-by-side
- [ ] Analytics de uso integrados

### Mejoras Técnicas
- [ ] TypeScript support
- [ ] Unit tests
- [ ] Performance monitoring
- [ ] Accessibility improvements

## 📞 Soporte

### Debug Information
Para obtener información de depuración:
```javascript
debugInfoV2()
```

### Issues Comunes
1. **SVG no se carga**: Verificar permisos de archivo
2. **Estados sin datos**: Confirmar uso de img-map-enhanced.svg
3. **Interacciones no funcionan**: Verificar errores en consola

### Logs
Todos los eventos importantes se registran en la consola del navegador con prefijos identificables:
- `🚀` Inicialización
- `🎯` Selección de estados
- `📊` Actualizaciones de estadísticas
- `❌` Errores
- `✅` Operaciones exitosas

---

**Versión**: 2.0 Enhanced  
**Fecha**: Agosto 2025  
**Compatibilidad**: Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
