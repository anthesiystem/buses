// Funcionalidad combinada de script.js y scriptg.js
function cargarSeccion(ruta) {
    console.log("🔄 Cargando sección:", ruta);
    const contenedor = document.getElementById('main-content');
    contenedor.innerHTML = '<div class="text-center p-4">Cargando...</div>';

    // Guardar ruta actual
    localStorage.setItem('seccionActual', ruta);

    fetch(ruta)
        .then(response => {
            if (!response.ok) throw new Error(`Error al cargar ${ruta}`);
            return response.text();
        })
        .then(html => {
            const temp = document.createElement('div');
            temp.innerHTML = html;

            // Cargar contenido visible
            contenedor.innerHTML = temp.innerHTML;

            // Procesar scripts en orden específico
            const scripts = [];
            const inlineScripts = [];

            // 1. Recolectar scripts
            temp.querySelectorAll("script").forEach(script => {
                const src = script.getAttribute('src');
                if (src) {
                    // Script externo
                    const newScript = document.createElement("script");
                    
                    // Manejar rutas relativas y absolutas
                    if (src.startsWith('http') || src.startsWith('/')) {
                        newScript.src = src;
                    } else {
                        const rutaBase = ruta.substring(0, ruta.lastIndexOf('/') + 1);
                        newScript.src = rutaBase + src.replace(/^\.\//, '');
                    }

                    // Copiar atributos data-*
                    Array.from(script.attributes).forEach(attr => {
                        if (attr.name !== 'src') {
                            newScript.setAttribute(attr.name, attr.value);
                        }
                    });

                    scripts.push(newScript);
                    console.log("📜 Script externo detectado:", newScript.src);
                } else if (script.textContent.trim()) {
                    // Script inline
                    const inlineScript = document.createElement("script");
                    inlineScript.textContent = script.textContent;
                    inlineScripts.push(inlineScript);
                }
            });

            // 2. Cargar scripts externos secuencialmente
            function loadScriptsSequentially(index = 0) {
                if (index >= scripts.length) {
                    // Cuando todos los scripts externos están cargados, ejecutar los inline
                    inlineScripts.forEach(script => {
                        try {
                            document.body.appendChild(script);
                        } catch (err) {
                            console.error("❌ Error en script inline:", err);
                        }
                    });
                    
                    // Después de cargar todos los scripts, intentar inicializar componentes específicos
                    setTimeout(() => {
                        // Inicializar catálogos si existe la función
                        if (typeof window.initCatalogos === 'function') {
                            console.log('🔄 Inicializando catálogos desde script-combined');
                            window.initCatalogos();
                        }
                        
                        // Inicializar registros si existe la función
                        if (typeof window.reinitRegistros === 'function') {
                            console.log('🔄 Reinicializando registros desde script-combined');
                            window.reinitRegistros();
                        }
                    }, 100);
                    
                    return;
                }

                const script = scripts[index];
                script.onload = () => loadScriptsSequentially(index + 1);
                script.onerror = (error) => {
                    console.error("❌ Error cargando script:", script.src, error);
                    loadScriptsSequentially(index + 1);
                };
                document.body.appendChild(script);
            }

            // Iniciar carga secuencial
            loadScriptsSequentially();
            
            // Scroll al inicio
            window.scrollTo(0, 0);
        })
        .catch(error => {
            contenedor.innerHTML = `
                <div class="alert alert-danger m-4">
                    <h4 class="alert-heading">Error al cargar la vista</h4>
                    <p>${error.message}</p>
                </div>`;
            console.error("❌ Error:", error);
        });
}

// Función para cargar última sección o la inicial
function cargarUltimaPagina() {
    const ultimaSeccion = localStorage.getItem('seccionActual');
    const seccionInicial = ultimaSeccion || 'sections/inicio.php';
    cargarSeccion(seccionInicial);
}

// Inicialización cuando el DOM está listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', cargarUltimaPagina);
} else {
    cargarUltimaPagina();
}

// Manejar navegación
window.addEventListener('popstate', () => {
    const ultimaSeccion = localStorage.getItem('seccionActual');
    if (ultimaSeccion) {
        cargarSeccion(ultimaSeccion);
    }
});
