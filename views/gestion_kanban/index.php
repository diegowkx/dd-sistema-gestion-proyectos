<?php
if (!isset($proyectos) || !is_array($proyectos)) {
    $proyectos = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include __DIR__ . '/../layouts/head.php'; ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/views/gestion_kanban/gestion_kanban.css">
</head>
<body class="font-primary-family bg-light-app">
<div id="main-wrapper">
    <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
    <?php include __DIR__ . '/../layouts/topbar.php'; ?>
    
    <div class="content-body">
        <div class="container-fluid">
            <section class="kanban-shell">
                <div class="kanban-toolbar">
                    <div class="kanban-header-info">
                        <span class="luxury-subtitle">GESTIÓN DE PROYECTOS</span>
                        <h1 class="kanban-title">Gestión Kanban</h1>
                        <p class="kanban-description">Organiza columnas, tareas y responsables del proyecto.</p>
                    </div>
                    
                    <div class="kanban-actions">
                        <select id="kanban_proyecto" class="form-select custom-select-kanban">
                            <option value="">Selecciona un proyecto</option>
                            <?php foreach ($proyectos as $proyecto): ?>
                                <option value="<?= (int)$proyecto['id'] ?>"><?= htmlspecialchars($proyecto['titulo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="kanban-buttons-group">
                            <button class="btn btn-primary-custom btn-kanban-action" id="btn-nueva-columna" type="button" disabled>
                                <i class="fas fa-columns me-2"></i>Nueva columna
                            </button>
                            <button class="btn btn-luxury-primary btn-kanban-action" id="btn-nueva-tarea" type="button" disabled>
                                <i class="fas fa-plus me-2"></i>Nueva tarea
                            </button>
                        </div>
                    </div>
                </div>

                <div id="kanban_empty" class="kanban-empty">
                    <div class="kanban-empty-icon">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <h2>Selecciona un proyecto</h2>
                    <p>Elige un proyecto para cargar sus columnas y tarjetas de trabajo.</p>
                </div>

                <div id="kanban_board" class="kanban-board" aria-live="polite"></div>
            </section>
        </div>
    </div>

    <?php include __DIR__ . '/../layouts/footer.php'; ?>
</div>

<?php include __DIR__ . '/../layouts/scripts.php'; ?>
<script src="<?php echo BASE_URL; ?>/views/gestion_kanban/gestion_kanban.js"></script>

<div class="modal fade" id="modalColumna" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content shadow-lg border-0" id="formColumna">
            <div class="modal-header bg-dark-blue text-white">
                <h5 class="modal-title text-white" id="tituloModalColumna">Nueva columna</h5>
                <button class="btn-close btn-close-white" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="id">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nombre de la columna</label>
                    <input class="form-control" name="nombre" maxlength="50" placeholder="Ej. En Revisión, QA..." required>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button class="btn btn-light border" type="button" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary-custom" type="submit">Guardar columna</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalTarea" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form class="modal-content shadow-lg border-0" id="formTarea">
            <div class="modal-header bg-dark-blue text-white">
                <h5 class="modal-title text-white" id="tituloModalTarea">Nueva tarea</h5>
                <button class="btn-close btn-close-white" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="id">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Título</label>
                        <input class="form-control" name="titulo" maxlength="150" placeholder="Título descriptivo de la tarea" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Columna</label>
                        <select class="form-select" name="columna_id" id="tarea_columna" required></select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Descripción</label>
                        <textarea class="form-control" name="descripcion" rows="3" placeholder="Detalles de la tarea..."></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Fecha de vencimiento</label>
                        <input class="form-control" type="datetime-local" name="fecha_vencimiento">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Asignar miembros</label>
                        <select class="form-select" name="asignados[]" id="tarea_asignados" multiple size="4" required></select>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button class="btn btn-light border" type="button" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-luxury-primary" type="submit">Guardar tarea</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalEntregas" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark-blue text-white">
                <h5 class="modal-title text-white" id="tituloModalEntregas">Entregas de la Tarea</h5>
                <button class="btn-close btn-close-white" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="contenidoEntregas"></div>
            <div class="modal-footer bg-light">
                <button class="btn btn-light border" type="button" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
</body>
</html>