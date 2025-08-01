<?php if (!isset($dependencias)) die('Acceso no autorizado'); ?>

<div class="card mb-3">
  <div class="card-body">
    <form id="filtrosForm" class="row row-cols-1 row-cols-md-auto g-2 align-items-end justify-content-start">

      <div class="col">
        <label class="form-label" for="filtro_entidad">Entidad</label>
        <select class="form-select form-select-sm" name="entidad" id="filtro_entidad">
          <option value="">Todas</option>
          <?php foreach ($entidades as $e): ?>
            <option value="<?= $e['ID'] ?>"><?= $e['descripcion'] ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col">
        <label class="form-label" for="filtro_dependencia">Dependencia</label>
        <select class="form-select form-select-sm" name="dependencia" id="filtro_dependencia">
          <option value="">Todas</option>
          <?php foreach ($dependencias as $d): ?>
            <option value="<?= $d['ID'] ?>"><?= $d['descripcion'] ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col">
        <label class="form-label" for="filtro_bus">Bus</label>
        <select class="form-select form-select-sm" name="bus" id="filtro_bus">
          <option value="">Todos</option>
          <?php foreach ($buses as $b): ?>
            <option value="<?= $b['ID'] ?>"><?= $b['descripcion'] ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col">
        <label class="form-label" for="filtro_engine">Engine</label>
        <select class="form-select form-select-sm" name="engine" id="filtro_engine">
          <option value="">Todos</option>
          <?php foreach ($engines as $en): ?>
            <option value="<?= $en['ID'] ?>"><?= $en['descripcion'] ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col">
        <label class="form-label" for="filtro_version">Versión</label>
        <select class="form-select form-select-sm" name="version" id="filtro_version">
          <option value="">Todas</option>
          <?php foreach ($versiones as $v): ?>
            <option value="<?= $v['ID'] ?>"><?= $v['descripcion'] ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col">
        <label class="form-label" for="filtro_estado_bus">Estatus</label>
        <select class="form-select form-select-sm" name="estatus" id="filtro_estado_bus">
          <option value="">Todos</option>
          <?php foreach ($estatuses as $e): ?>
            <option value="<?= $e['ID'] ?>"><?= $e['descripcion'] ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col">
        <label class="form-label" for="filtro_categoria">Categoría</label>
        <select class="form-select form-select-sm" name="categoria" id="filtro_categoria">
          <option value="">Todas</option>
          <?php foreach ($categorias as $c): ?>
            <option value="<?= $c['ID'] ?>"><?= $c['descripcion'] ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col">
        <label class="form-label" for="fecha_inicio">Desde inicio</label>
        <input type="date" class="form-control form-control-sm" name="fecha_inicio" id="fecha_inicio" max="<?= date('Y-m-d') ?>">
      </div>

      <div class="col">
        <label class="form-label" for="fecha_migracion">Desde migración</label>
        <input type="date" class="form-control form-control-sm" name="fecha_migracion" id="fecha_migracion" max="<?= date('Y-m-d') ?>">
      </div>

      <div class="col">
        <button type="submit" class="btn btn-primary btn-sm w-100">
          <i class="bi bi-funnel"></i> Filtrar
        </button>
      </div>

    </form>
  </div>
</div>
