/**
 * Registro automático de vistas en bitácora
 * Este script se incluye en las páginas para registrar automáticamente
 * cuando un usuario accede a una vista específica
 */

// Configuración de vistas a registrar - ampliada
const VISTAS_BITACORA = {
  'buses': 'Vista de administración de buses',
  'catalogos': 'Vista de administración de catálogos', 
  'registros': 'Vista de administración de registros',
  'bitacora': 'Vista de bitácora de auditoría',
  'usuarios': 'Vista de administración de usuarios',
  'general': 'Vista general del sistema (debug)'
};

/**
 * Registra la vista actual en la bitácora
 * @param {string} vista - Nombre de la vista
 * @param {string} descripcion_adicional - Descripción adicional opcional
 */
function registrarVistaEnBitacora(vista, descripcion_adicional = '') {
  console.log('📝 Iniciando registro de vista:', vista);
  
  // Solo registrar si está en la lista de vistas a auditar
  if (!VISTAS_BITACORA[vista]) {
    console.log('❌ Vista no está en la lista de auditoría:', vista);
    return;
  }

  const datos = {
    vista: vista,
    descripcion: VISTAS_BITACORA[vista],
    url: window.location.href,
    timestamp: new Date().toISOString()
  };

  if (descripcion_adicional) {
    datos.descripcion += ' - ' + descripcion_adicional;
  }

  console.log('📤 Enviando datos:', datos);

  // Registrar via AJAX (fire and forget)
  fetch('/final/mapa/public/sections/registrar_vista_bitacora.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: new URLSearchParams(datos)
  })
  .then(response => {
    console.log('📥 Respuesta recibida:', response.status);
    return response.text();
  })
  .then(data => {
    console.log('✅ Registro exitoso:', data);
  })
  .catch(error => {
    // Error silencioso - no interrumpir la experiencia del usuario
    console.error('❌ Error registrando vista en bitácora:', error);
  });
}

/**
 * Auto-registro basado en la URL actual
 */
function autoRegistrarVista() {
  console.log('🔍 Iniciando auto-registro de vista...');
  const url = window.location.pathname;
  console.log('📍 URL actual:', url);
  
  let vista = null;
  let descripcion_adicional = '';

  // Detectar vista basada en la URL - más permisivo
  if (url.includes('buses') || url.includes('bus.php')) {
    vista = 'buses';
    console.log('🚌 Detectada vista: buses');
  } else if (url.includes('catalogos') || url.includes('catalogo')) {
    vista = 'catalogos';
    console.log('📋 Detectada vista: catalogos');
  } else if (url.includes('regprueba') || url.includes('registro')) {
    vista = 'registros';
    console.log('📝 Detectada vista: registros');
  } else if (url.includes('bitacora')) {
    vista = 'bitacora';
    console.log('📊 Detectada vista: bitacora');
  } else if (url.includes('usuarios') || url.includes('usuario')) {
    vista = 'usuarios';
    console.log('👥 Detectada vista: usuarios');
  } else {
    console.log('❌ No se detectó vista específica para:', url);
    // Forzar registro de cualquier página como 'general' para debug
    vista = 'general';
    console.log('🔧 Forzando registro como vista general para debug');
  }

  // Registrar si se detectó una vista válida
  if (vista) {
    // Agregar parámetros de la URL como contexto adicional
    const params = new URLSearchParams(window.location.search);
    const filtros = [];
    
    params.forEach((value, key) => {
      if (value && key !== '_' && key !== 'v') { // Excluir parámetros temporales
        filtros.push(`${key}=${value}`);
      }
    });

    if (filtros.length > 0) {
      descripcion_adicional = 'Filtros: ' + filtros.join(', ');
    }

    console.log('✅ Registrando vista:', vista, 'con descripción adicional:', descripcion_adicional);
    registrarVistaEnBitacora(vista, descripcion_adicional);
  }
}

/**
 * Registrar acciones específicas del usuario
 */
function registrarAccionUsuario(accion, detalle = '') {
  console.log('🎬 Registrando acción de usuario:', accion, 'detalle:', detalle);
  
  const datos = {
    accion: accion,
    detalle: detalle,
    url: window.location.href,
    timestamp: new Date().toISOString()
  };

  console.log('📤 Enviando acción:', datos);

  fetch('/final/mapa/public/sections/registrar_accion_usuario.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: new URLSearchParams(datos)
  })
  .then(response => {
    console.log('📥 Respuesta acción recibida:', response.status);
    return response.text();
  })
  .then(data => {
    console.log('✅ Acción registrada:', data);
  })
  .catch(error => {
    console.error('❌ Error registrando acción:', error);
  });
}

// Auto-ejecutar cuando la página carga
document.addEventListener('DOMContentLoaded', function() {
  // Registrar la vista después de un breve delay para asegurar que la página esté completamente cargada
  setTimeout(autoRegistrarVista, 1000);
});

// También registrar cuando se carga dinámicamente via AJAX
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', function() {
    setTimeout(autoRegistrarVista, 1000);
  });
} else {
  // Si el documento ya está cargado, registrar inmediatamente
  setTimeout(autoRegistrarVista, 500);
}

// Registrar acciones comunes automáticamente
document.addEventListener('click', function(e) {
  const elemento = e.target;
  
  // Registrar clicks en botones importantes
  if (elemento.matches('[data-bs-target="#modalRegistro"]')) {
    registrarAccionUsuario('abrir_modal_registro', 'Nuevo registro');
  } else if (elemento.matches('.btn-edit, .editar-btn')) {
    registrarAccionUsuario('abrir_editar', elemento.dataset.id || 'ID no disponible');
  } else if (elemento.matches('.btn-toggle, .cambiar-estado')) {
    registrarAccionUsuario('cambiar_estado', elemento.dataset.id || 'ID no disponible');
  } else if (elemento.matches('[href*="exportar"]')) {
    registrarAccionUsuario('exportar_datos', elemento.textContent?.trim() || 'Exportar');
  }
});

// Registrar cambios de filtros
document.addEventListener('change', function(e) {
  const elemento = e.target;
  
  if (elemento.matches('select[name*="filtro"], input[name*="filtro"], select#usuario, select#accion, input#fecha')) {
    registrarAccionUsuario('aplicar_filtro', `${elemento.name || elemento.id}=${elemento.value}`);
  }
});

// Exponer funciones globalmente para uso manual
window.registrarVistaEnBitacora = registrarVistaEnBitacora;
window.registrarAccionUsuario = registrarAccionUsuario;

console.log('✅ Sistema de registro de vistas en bitácora inicializado');
console.log('🔧 Funciones disponibles: registrarVistaEnBitacora, registrarAccionUsuario');
console.log('📋 Vistas configuradas:', Object.keys(VISTAS_BITACORA));
