(function () {
  const modalComentarios = document.getElementById('modalComentarios');
  if (!modalComentarios) return;

  modalComentarios.addEventListener('show.bs.modal', async function (event) {
    const button = event.relatedTarget;
    if (!button) return;

    const id          = button.getAttribute('data-bs-id');
    const busNombre   = button.getAttribute('data-bus-nombre') || '';
    const estado      = button.getAttribute('data-estado') || '';
    
    const modalDialog = this.querySelector('.modal-dialog');
    
    // Mostrar spinner mientras carga
    modalDialog.innerHTML = `
      <div class="modal-content">
        <div class="modal-body p-3">
          <div class="d-flex align-items-center gap-2">
            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
            <div>Cargando comentarios...</div>
          </div>
        </div>
      </div>`;

    try {
      // Cargar contenido de forma más eficiente
      const url = '/final/mapa/public/sections/lineadetiempo/comentarios_general_modal.php';
      const params = new URLSearchParams({
        id: id,
        estado: estado,
        nombre: busNombre,
        puede_crear: '0'
      });

      const response = await fetch(`${url}?${params.toString()}`, {
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'text/html',
          'Cache-Control': 'no-cache'
        },
        cache: 'no-store'
      });

      if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
      
      const html = await response.text();
      
      // Insertar contenido de forma más eficiente
      const tempDiv = document.createElement('div');
      tempDiv.innerHTML = html.trim();
      
      // Reemplazar contenido
      modalDialog.replaceChildren(...tempDiv.firstElementChild.children);
      
    } catch (error) {
      console.error('Error cargando comentarios:', error);
      modalDialog.innerHTML = `
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Error</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="text-danger">Error al cargar los comentarios. Por favor, intente nuevamente.</div>
          </div>
        </div>`;
    }
  });
})();
