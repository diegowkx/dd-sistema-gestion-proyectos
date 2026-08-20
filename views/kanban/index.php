<?php
if (!isset($proyectos) || !is_array($proyectos)) {
    $proyectos = [];
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
        <section class="kanban-shell">
            <div class="kanban-toolbar">
                <div><span class="luxury-subtitle">Gestión de Proyectos</span><h1 class="h3 mb-1 fw-bold text-dark-blue">Gestión Kanban</h1><p class="text-muted extra-small mb-0">Organiza columnas, tareas y responsables del proyecto.</p></div>
                <div class="kanban-actions"><select id="kanban_proyecto" class="form-select"><option value="">Selecciona un proyecto</option><?php foreach ($proyectos as $proyecto): ?><option value="<?= (int)$proyecto['id'] ?>"><?= htmlspecialchars($proyecto['titulo']) ?></option><?php endforeach; ?></select><button class="btn btn-primary-custom" id="btn-nueva-columna" type="button" disabled><i class="fas fa-columns me-2"></i>Nueva columna</button><button class="btn btn-luxury-primary" id="btn-nueva-tarea" type="button" disabled><i class="fas fa-plus me-2"></i>Nueva tarea</button></div>
            </div>
            <div id="kanban_empty" class="kanban-empty"><i class="fas fa-hand-pointer"></i><h2>Selecciona un proyecto</h2><p>Elige un proyecto que lideres para cargar su tablero.</p></div>
            <div id="kanban_board" class="kanban-board" aria-live="polite"></div>
        </section>
    </div></div>
    <?php include __DIR__ . '/../layouts/footer.php'; ?>
</div>
<?php include __DIR__ . '/../layouts/scripts.php'; ?>
<script src="<?php echo BASE_URL; ?>/views/kanban/kanban.js"></script>
<div class="modal fade" id="modalColumna" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content" id="formColumna"><div class="modal-header"><h5 class="modal-title" id="tituloModalColumna">Nueva columna</h5><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="hidden" name="id"><label class="form-label">Nombre</label><input class="form-control" name="nombre" maxlength="50" required></div><div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary-custom" type="submit">Guardar columna</button></div></form></div></div></div>
<div class="modal fade" id="modalTarea" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><form class="modal-content" id="formTarea"><div class="modal-header"><h5 class="modal-title" id="tituloModalTarea">Nueva tarea</h5><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="hidden" name="id"><div class="row g-3"><div class="col-md-8"><label class="form-label">Título</label><input class="form-control" name="titulo" maxlength="150" required></div><div class="col-md-4"><label class="form-label">Columna</label><select class="form-select" name="columna_id" id="tarea_columna" required></select></div><div class="col-12"><label class="form-label">Descripción</label><textarea class="form-control" name="descripcion" rows="3"></textarea></div><div class="col-md-6"><label class="form-label">Fecha de vencimiento</label><input class="form-control" type="datetime-local" name="fecha_vencimiento"></div><div class="col-md-6"><label class="form-label">Asignar miembros</label><select class="form-select" name="asignados[]" id="tarea_asignados" multiple size="5" required></select><small class="text-muted">Selecciona uno o varios miembros.</small></div></div></div><div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-luxury-primary" type="submit">Guardar tarea</button></div></form></div></div></div>
<div class="modal fade" id="modalEntregas" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="tituloModalEntregas">Entregas</h5><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div><div class="modal-body" id="contenidoEntregas"></div><div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Cerrar</button></div></div></div></div>
</body>
</html>
