<?php
if (!isset($proyectos) || !is_array($proyectos)) {
    $proyectos = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include __DIR__ . '/../layouts/head.php'; ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/views/kanban/kanban.css">
</head>
<body class="font-primary-family">
<div id="main-wrapper">
    <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
    <?php include __DIR__ . '/../layouts/topbar.php'; ?>

    <div class="content-body">
        <div class="container-fluid">
            <section class="kanban-shell">
                <div class="kanban-toolbar">
                    <div class="kanban-header-info">
                        <span class="luxury-subtitle">MI ESPACIO DE TRABAJO</span>
                        <h1 class="kanban-title">Mi Kanban</h1>
                        <p class="kanban-description">Consulta tus tareas y registra tus avances.</p>
                    </div>
                    <div class="kanban-actions">
                        <select id="kanban_proyecto" class="form-select custom-select-kanban">
                            <option value="">Selecciona un proyecto</option>
                            <?php foreach ($proyectos as $proyecto): ?>
                                <option value="<?= (int)$proyecto['id'] ?>"><?= htmlspecialchars($proyecto['titulo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div id="kanban_miembros" class="kanban-member-strip d-none"></div>

                <div id="kanban_empty" class="kanban-empty">
                    <div class="kanban-empty-icon">
                        <i class="fas fa-hand-pointer"></i>
                    </div>
                    <h2>Selecciona un proyecto</h2>
                    <p class="text-muted">Elige un proyecto para ver tus tareas asignadas.</p>
                </div>

                <div id="kanban_board" class="kanban-board" aria-live="polite"></div>
            </section>
        </div>
    </div>

    <?php include __DIR__ . '/../layouts/footer.php'; ?>
</div>

<?php include __DIR__ . '/../layouts/scripts.php'; ?>
<script src="<?php echo BASE_URL; ?>/views/kanban/kanban.js"></script>

<!-- Modal Lista Entregas -->
<div class="modal fade" id="modalEntregas" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content entrega-modal shadow-lg border-0">
            <div class="modal-header bg-dark-blue text-white">
                <h5 class="modal-title text-white" id="tituloModalEntregas">Entregas</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body entrega-modal-body p-4">
                <div id="listaEntregas" class="row g-3"></div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-luxury-primary" id="btnNuevaEntrega">
                    <i class="fas fa-plus me-1"></i>Nueva Entrega
                </button>
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Registrar Entrega -->
<div class="modal fade" id="modalEntrega" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content shadow-lg border-0" id="formEntrega">
            <div class="modal-header bg-dark-blue text-white">
                <h5 class="modal-title text-white" id="tituloModalEntrega">Registrar entrega</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="id">
                <input type="hidden" name="tarea_id">
                <label class="form-label fw-semibold">Mensaje de entrega</label>
                <textarea class="form-control" name="mensaje_entrega" rows="5" placeholder="Escribe los detalles o enlace de tu entrega..." required></textarea>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-luxury-primary" type="submit">Guardar entrega</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>