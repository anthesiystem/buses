// Manejador del modal para la vista general
(function() {
    const modalComentarios = document.getElementById('modalComentarios');
    if (!modalComentarios) return;

    // Limpiar cualquier event listener previo
    const clone = modalComentarios.cloneNode(true);
    modalComentarios.parentNode.replaceChild(clone, modalComentarios);

    clone.addEventListener('show.bs.modal', async function(event) {
        const button = event.relatedTarget;
        if (!button) return;

        const id = button.getAttribute('data-bs-id');
        const busNombre = button.getAttribute('data-bus-nombre') || '';
        const estado = button.getAttribute('data-bs-estado') || '';

        const modalDialog = this.querySelector('.modal-dialog');

        // Mostrar spinner mientras carga
        modalDialog.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Comentarios de ${busNombre}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="d-flex align-items-center justify-content-center gap-2">
                        <div class="spinner-border spinner-border-sm text-primary"></div>
                        <div>Cargando comentarios...</div>
                    </div>
                </div>
            </div>`;

        try {
            const url = '/final/mapa/public/sections/lineadetiempo/comentarios_general_modal.php';
            const response = await fetch(`${url}?${new URLSearchParams({
                id,
                estado,
                nombre: busNombre,
                view_only: '1'
            })}`, {
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Cache-Control': 'no-store'
                },
                cache: 'no-store'
            });

            if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
            
            const html = await response.text();
            
            // Reemplazar contenido
            modalDialog.innerHTML = html;

            // Inicializar filtros y comportamiento después de cargar
            const stepper = modalDialog.querySelector('#barEtapas');
            const lista = modalDialog.querySelector('#listaComentarios');
            
            if (stepper && lista) {
                // Botón "Todos"
                modalDialog.querySelector('#btnAll')?.addEventListener('click', () => {
                    lista.querySelectorAll('.tl-item').forEach(item => item.style.display = '');
                    stepper.querySelectorAll('.step').forEach(step => step.classList.remove('current'));
                });

                // Click en steps
                stepper.addEventListener('click', (e) => {
                    const step = e.target.closest('.step[data-id]');
                    if (!step) return;

                    const etapaId = step.getAttribute('data-id');
                    
                    lista.querySelectorAll('.tl-item').forEach(item => {
                        item.style.display = item.getAttribute('data-etapa-id') === etapaId ? '' : 'none';
                    });

                    stepper.querySelectorAll('.step').forEach(s => {
                        s.classList.toggle('current', s === step);
                    });
                });
            }

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
