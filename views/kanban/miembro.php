<?php
if (!isset($proyectos) || !is_array($proyectos)) $proyectos = [];
$extra_css = ['css/custom/views/miembro.css'];
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
            <div class="kanban-toolbar"><div><span class="luxury-subtitle">Mi espacio de trabajo</span><h1 class="h3 mb-1 fw-bold text-dark-blue">Mi Kanban</h1><p class="text-muted extra-small mb-0">Consulta tus tareas y registra tus avances.</p></div><select id="kanban_proyecto" class="form-select" style="max-width:320px"><option value="">Selecciona un proyecto</option><?php foreach ($proyectos as $proyecto): ?><option value="<?= (int)$proyecto['id'] ?>"><?= htmlspecialchars($proyecto['titulo']) ?></option><?php endforeach; ?></select></div>
            <div id="kanban_miembros" class="kanban-member-strip d-none"></div>
            <div id="kanban_empty" class="kanban-empty"><i class="fas fa-hand-pointer"></i><h2>Selecciona un proyecto</h2><p>Elige un proyecto para ver tus tareas asignadas.</p></div>
            <div id="kanban_board" class="kanban-board"></div>
        </section>
    </div></div>
    <?php include __DIR__ . '/../layouts/footer.php'; ?>
</div>
<?php include __DIR__ . '/../layouts/scripts.php'; ?>
<script src="<?php echo BASE_URL; ?>/views/kanban/miembro.js"></script>
<div class="modal fade" id="modalEntregas" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content entrega-modal"><div class="modal-header"><h5 class="modal-title" id="tituloModalEntregas">Entregas</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body entrega-modal-body"><div id="listaEntregas" class="row g-3"></div></div><div class="modal-footer"><button type="button" class="btn btn-luxury-primary" id="btnNuevaEntrega"><i class="fas fa-plus me-1"></i>Entrega</button><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button></div></div></div></div>
<div class="modal fade" id="modalEntrega" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content" id="formEntrega"><div class="modal-header"><h5 class="modal-title" id="tituloModalEntrega">Registrar entrega</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="hidden" name="id"><input type="hidden" name="tarea_id"><label class="form-label">Mensaje de entrega</label><textarea class="form-control" name="mensaje_entrega" rows="5" required></textarea></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-luxury-primary" type="submit">Guardar entrega</button></div></form></div></div></div>
</body>
</html>
