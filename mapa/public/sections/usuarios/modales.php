<?php
// Ruta: /final/mapa/public/sections/usuarios/modales.php
// Modales extraídos para mantener el código organizado
?>
<!-- Modal PERSONA -->
<div class="modal fade" id="modalPersona" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formPersona">
        <div class="modal-header">
          <h5 class="modal-title" id="tituloPersona">Nueva persona</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="ID" id="personaID">
          <div class="row g-2">
            <div class="col-md-6"><label class="form-label">Nombre</label><input type="text" class="form-control" name="nombre" id="personaNombre" required></div>
            <div class="col-md-6"><label class="form-label">Apellido paterno</label><input type="text" class="form-control" name="apaterno" id="personaApaterno" required></div>
            <div class="col-md-6"><label class="form-label">Apellido materno</label><input type="text" class="form-control" name="amaterno" id="personaAmaterno" required></div>
            <div class="col-md-6"><label class="form-label">No. empleado</label><input type="text" class="form-control" name="numero_empleado" id="personaNumero" required></div>
            <div class="col-md-6"><label class="form-label">Correo</label><input type="email" class="form-control" name="correo" id="personaCorreo" required></div>
            <div class="col-md-6"><label class="form-label">Dependencia</label><select class="form-select" name="Fk_dependencia" id="personaDep" required></select></div>
            <div class="col-md-6"><label class="form-label">Entidad</label><select class="form-select" name="Fk_entidad" id="personaEnt" required></select></div>
            <div class="col-md-6"><label class="form-label">Activo</label><select class="form-select" name="activo" id="personaActivo"><option value="1">Sí</option><option value="0">No</option></select></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          <button class="btn btn-primary" type="submit">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal USUARIO -->
<div class="modal fade" id="modalUsuario" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formUsuario">
        <div class="modal-header">
          <h5 class="modal-title" id="tituloUsuario">Nuevo usuario</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="ID" id="usuarioID">
          <div class="row g-2">
            <div class="col-md-6"><label class="form-label">Persona</label><select class="form-select" name="Fk_persona" id="usuarioPersona" required></select></div>
            <div class="col-md-6"><label class="form-label">Cuenta</label><input type="text" class="form-control" name="cuenta" id="usuarioCuenta" required></div>
            <div class="col-md-6">
              <label class="form-label">Nivel</label>
              <select class="form-select" name="nivel" id="usuarioNivel" required>
                <option value="0">Enlace externo (0)</option>
                <option value="1">Enlace local (1)</option>
                <option value="2">General (2)</option>
                <option value="3">Admin (3)</option>
                <option value="4">Supersu (4)</option>
              </select>
            </div>
            <div class="col-md-6"><label class="form-label">Contraseña</label><input type="password" class="form-control" name="contrasenia" id="usuarioPass" placeholder="••••••"><small class="text-muted">Déjalo vacío para no cambiarla al editar.</small></div>
            <div class="col-md-6"><label class="form-label">Activo</label><select class="form-select" name="activo" id="usuarioActivo"><option value="1">Sí</option><option value="0">No</option></select></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          <button class="btn btn-primary" type="submit">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal PERMISO -->
<div class="modal fade" id="modalPermiso" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formPermiso">
        <div class="modal-header">
          <h5 class="modal-title" id="tituloPermiso">Nuevo permiso</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="ID" id="permisoID">
          <div class="row g-2">
            <div class="col-md-6"><label class="form-label">Usuario</label><select class="form-select" name="Fk_usuario" id="permUsuario" required></select></div>
            <div class="col-md-6"><label class="form-label">Módulo</label><select class="form-select" name="Fk_modulo" id="permModulo" required></select></div>
            <div class="col-md-6"><label class="form-label">Entidad</label><select class="form-select" name="FK_entidad" id="permEntidad"></select></div>
            <div class="col-md-6"><label class="form-label">Bus</label><select class="form-select" name="FK_bus" id="permBus"></select></div>
            <div class="col-md-6"><label class="form-label">Acción</label><select class="form-select" name="accion" id="permAccion"><option>CREATE</option><option>READ</option><option>UPDATE</option><option>DELETE</option></select></div>
            <div class="col-md-6"><label class="form-label">Activo</label><select class="form-select" name="activo" id="permActivo"><option value="1">Sí</option><option value="0">No</option></select></div>
          </div>
          <div class="mt-2 small text-muted">La combinación usuario+módulo+acción+entidad+bus debe ser única.</div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          <button class="btn btn-primary" type="submit">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal MÓDULO -->
<div class="modal fade" id="modalModulo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formModulo">
        <div class="modal-header">
          <h5 class="modal-title" id="tituloModulo">Nuevo módulo</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="ID" id="moduloID">
          <div class="row g-2">
            <div class="col-12">
              <label class="form-label">Descripción (slug)</label>
              <input type="text" class="form-control" name="descripcion" id="moduloDesc" placeholder="rnl_licencias" required>
              <small class="text-muted">Usa minúsculas, sin espacios ni acentos (ej. <code>mapa_general</code>).</small>
            </div>
            <div class="col-12">
              <label class="form-label">Activo</label>
              <select class="form-select" name="activo" id="moduloActivo">
                <option value="1">Sí</option>
                <option value="0">No</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          <button class="btn btn-primary" type="submit">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>
