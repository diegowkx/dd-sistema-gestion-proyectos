<?php
if (!isset($lideresDisponibles) || !is_array($lideresDisponibles)) {
    $lideresDisponibles = [];
}
if (!isset($miembrosDisponibles) || !is_array($miembrosDisponibles)) {
    $miembrosDisponibles = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head><?php include __DIR__ . '/../layouts/head.php'; ?></head>
<body class="font-primary-family">
<div id="main-wrapper">
    <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
    <?php include __DIR__ . '/../layouts/topbar.php'; ?>
    <div class="content-body"><div class="container-fluid">
        <div class="card card-luxury border-0 shadow-sm rounded-3 bg-white">
            <div class="card-header-luxury p-4 border-bottom border-light d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div><span class="luxury-subtitle">Gestión de proyectos</span><h1 class="h3 mb-1 fw-bold text-dark-blue">Proyectos</h1><p class="text-muted extra-small mb-0">Organiza equipos, fechas y entregables del trabajo.</p></div>
                <button type="button" class="btn btn-luxury-primary rounded-2 px-4 btn-nuevo-proyecto"><i class="fas fa-plus me-2"></i>Nuevo proyecto</button>
            </div>
            <div class="card-body p-4">
                <div class="w-100 text-start mb-4"><ul class="nav nav-tabs-luxury gap-3" id="pills-tab-proyectos" role="tablist">
                    <li class="nav-item"><button class="nav-link tab-estado active fw-semibold" data-estado="ACTIVO" type="button"><i class="fas fa-folder-open me-2"></i>Proyectos activos</button></li>
                    <li class="nav-item"><button class="nav-link tab-estado fw-semibold" data-estado="INACTIVO" type="button"><i class="fas fa-folder-minus me-2"></i>Proyectos inactivos</button></li>
                </ul></div>
                <div class="table-responsive"><table id="datatable_proyectos" class="table table-hover align-middle w-100"><thead><tr><th>Proyecto</th><th>Líder</th><th>Fechas</th><th>Equipo</th><th>Tareas</th><th class="text-center">Acciones</th></tr></thead><tbody></tbody></table></div>
            </div>
        </div>
    </div></div>
    <?php include __DIR__ . '/../layouts/footer.php'; ?>
</div>
<?php include __DIR__ . '/../layouts/scripts.php'; ?>
<script src="<?php echo BASE_URL; ?>/views/proyectos/proyecto.js"></script>
<div class="modal fade modal-luxury" id="modalProyecto" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content border-0 shadow-lg rounded-3"><form id="formProyecto" novalidate><div class="modal-header"><h5 class="modal-title" id="tituloModalProyecto">Nuevo proyecto</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="hidden" name="id" id="proyecto_id"><ul class="nav nav-tabs mb-4" id="tabs-modal-proyecto" role="tablist"><li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#panel-datos-proyecto" type="button">Datos</button></li><li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#panel-equipo-proyecto" type="button">Equipo</button></li></ul><div class="tab-content"><div class="tab-pane fade show active" id="panel-datos-proyecto"><div class="row g-3"><div class="col-12"><label class="form-label">Título *</label><input class="form-control" name="titulo" id="proyecto_titulo" maxlength="150" required></div><div class="col-12"><label class="form-label">Descripción</label><textarea class="form-control" name="descripcion" id="proyecto_descripcion" rows="3"></textarea></div><div class="col-md-6"><label class="form-label">Fecha de inicio *</label><input type="datetime-local" class="form-control" name="fecha_inicio_global" id="proyecto_inicio" required></div><div class="col-md-6"><label class="form-label">Fecha de vencimiento *</label><input type="datetime-local" class="form-control" name="fecha_vencimiento_global" id="proyecto_vencimiento" required></div><div class="col-12"><label class="form-label">Líder global *</label><select class="form-select" name="lider_name" id="proyecto_lider" required><option value="">Selecciona un líder</option><?php foreach ($lideresDisponibles as $lider): ?><option value="<?= htmlspecialchars($lider['username']) ?>"><?= htmlspecialchars($lider['nombre']) ?> (<?= htmlspecialchars($lider['username']) ?>)</option><?php endforeach; ?></select><small class="text-muted">Solo usuarios activos con permiso GESTIONAR_PROYECTOS.</small></div></div></div><div class="tab-pane fade" id="panel-equipo-proyecto"><div class="row g-4"><div class="col-md-6"><label class="form-label">Líderes disponibles</label><select class="form-select" id="proyecto_lideres_disponibles" size="6"><?php foreach ($lideresDisponibles as $lider): ?><option value="<?= htmlspecialchars($lider['username']) ?>"><?= htmlspecialchars($lider['nombre']) ?> (<?= htmlspecialchars($lider['username']) ?>)</option><?php endforeach; ?></select><button type="button" class="btn btn-outline-primary btn-sm mt-2 w-100" id="btn-agregar-lider"><i class="fas fa-plus me-1"></i>Agregar líder adicional</button><div class="mt-3"><label class="form-label">Líderes agregados</label><div id="proyecto_lideres_agregados" class="border rounded-2 p-2 bg-light"></div></div></div><div class="col-md-6"><label class="form-label">Miembros disponibles</label><select class="form-select" id="proyecto_miembros_disponibles" size="6"><?php foreach ($miembrosDisponibles as $miembro): ?><option value="<?= htmlspecialchars($miembro['username']) ?>"><?= htmlspecialchars($miembro['nombre']) ?> (<?= htmlspecialchars($miembro['username']) ?>)</option><?php endforeach; ?></select><button type="button" class="btn btn-outline-primary btn-sm mt-2 w-100" id="btn-agregar-miembro"><i class="fas fa-plus me-1"></i>Agregar miembro</button><div class="mt-3"><label class="form-label">Miembros agregados</label><div id="proyecto_miembros_agregados" class="border rounded-2 p-2 bg-light"></div></div></div></div></div></div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary-custom" type="submit">Guardar proyecto</button></div></form></div></div></div>
</body>
</html>
