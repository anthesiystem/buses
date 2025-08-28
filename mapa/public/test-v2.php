<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba Mapa General V2</title>
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        
        .test-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #333;
            text-align: center;
        }
        
        .status {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #2196f3;
        }
        
        #mapa-v2 {
            width: 100%;
            height: 400px;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #e3f2fd;
        }
        
        #mapa-v2 svg {
            max-width: 95%;
            max-height: 95%;
            width: auto;
            height: auto;
        }
        
        .debug-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            border: 1px solid #dee2e6;
            font-family: monospace;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🧪 Prueba Mapa General V2</h1>
        
        <div class="status">
            <h3>📊 Estado de la Prueba</h3>
            <p id="status-text">Inicializando...</p>
        </div>
        
        <div id="mapa-v2">
            <!-- SVG se cargará aquí -->
            <?php 
            // Cargar SVG para prueba
            $svgPaths = [
                "img-map-enhanced.svg",
                "img-map.svg", 
                "mapa.svg"
            ];
            
            $svgLoaded = false;
            foreach ($svgPaths as $path) {
                if (file_exists($path)) {
                    echo "<!-- Cargando: $path -->\n";
                    echo file_get_contents($path);
                    $svgLoaded = true;
                    break;
                }
            }
            
            if (!$svgLoaded) {
                echo '<div style="text-align: center; color: #dc3545; padding: 40px;">';
                echo '<h3>⚠️ No se encontró ningún archivo SVG</h3>';
                echo '<p>Archivos buscados:</p><ul>';
                foreach ($svgPaths as $path) {
                    echo "<li>$path</li>";
                }
                echo '</ul></div>';
            }
            ?>
        </div>
        
        <div class="debug-info">
            <h4>🐛 Información de Debug</h4>
            <div id="debug-output">Cargando información de debug...</div>
        </div>
    </div>
    
    <script>
        // Script de prueba básico
        console.log('🧪 Iniciando prueba del Mapa General V2');
        
        function updateStatus(message) {
            document.getElementById('status-text').textContent = message;
            console.log('📊', message);
        }
        
        function updateDebug() {
            const debugOutput = document.getElementById('debug-output');
            const svg = document.querySelector('#mapa-v2 svg');
            
            const info = {
                'SVG encontrado': !!svg,
                'Paths': svg ? svg.querySelectorAll('path').length : 0,
                'Paths con data-entidad-id': svg ? svg.querySelectorAll('path[data-entidad-id]').length : 0,
                'ViewBox': svg ? svg.getAttribute('viewBox') : 'N/A',
                'Dimensiones': svg ? `${svg.getAttribute('width')} x ${svg.getAttribute('height')}` : 'N/A',
                'URL actual': window.location.href,
                'Protocolo': window.location.protocol,
                'Timestamp': new Date().toLocaleString()
            };
            
            debugOutput.innerHTML = Object.entries(info)
                .map(([key, value]) => `<strong>${key}:</strong> ${value}`)
                .join('<br>');
        }
        
        // Verificaciones
        document.addEventListener('DOMContentLoaded', function() {
            updateStatus('DOM cargado, verificando SVG...');
            
            setTimeout(() => {
                const svg = document.querySelector('#mapa-v2 svg');
                if (svg) {
                    const paths = svg.querySelectorAll('path');
                    const pathsWithData = svg.querySelectorAll('path[data-entidad-id]');
                    
                    let message = `✅ SVG cargado correctamente (${paths.length} paths`;
                    if (pathsWithData.length > 0) {
                        message += `, ${pathsWithData.length} enhanced`;
                    }
                    message += ')';
                    
                    updateStatus(message);
                    
                    // Añadir interacciones básicas
                    paths.forEach((path, index) => {
                        path.addEventListener('click', () => {
                            const entidadNombre = path.getAttribute('data-entidad-nombre');
                            const entidadId = path.getAttribute('data-entidad-id');
                            const pathId = path.id;
                            
                            let info = `Path #${index + 1}`;
                            if (entidadNombre) info += ` - ${entidadNombre}`;
                            if (entidadId) info += ` (ID: ${entidadId})`;
                            if (pathId) info += ` [${pathId}]`;
                            
                            alert(info);
                        });
                        
                        path.style.cursor = 'pointer';
                        path.addEventListener('mouseenter', () => {
                            path.style.stroke = '#2196f3';
                            path.style.strokeWidth = '2px';
                        });
                        
                        path.addEventListener('mouseleave', () => {
                            path.style.stroke = '';
                            path.style.strokeWidth = '';
                        });
                    });
                    
                } else {
                    updateStatus('❌ Error: No se encontró el SVG');
                }
                
                updateDebug();
            }, 500);
        });
        
        // Actualizar debug cada 2 segundos
        setInterval(updateDebug, 2000);
    </script>
</body>
</html>
