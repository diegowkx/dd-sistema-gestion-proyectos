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
<head>
    <?php include __DIR__ . '/../layouts/head.php'; ?>
    <style>
        /* Estilos optimizados para la pestaña Equipo */
        .equipo-section-card {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 0.5rem;
            padding: 1rem;
        }
        .equipo-list-container {
            max-height: 180px;
            overflow-y: auto;
            background-color: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.5rem;
        }
        .equipo-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            padding: 0.4rem 0.75rem;
            border-radius: 0.375rem;
            margin-bottom: 0.375rem;
            font-size: 0.875rem;
        }
        .equipo-item:last-child {
            margin-bottom: 0;
        }
    </style>
</head>
<body class="font-primary-family">
<div id="main-wrapper">
    <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
    <?php include __DIR__ . '/../layouts/topbar.php'; ?>
    <div class="content-body">
        <div class="container-fluid">
            <div class="card card-luxury border-0 shadow-sm rounded-3 bg-white">
                <div class="card-header-luxury p-4 border-bottom border-light d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <span class="luxury-subtitle">Gestión de proyectos</span>
                        <h1 class="h3 mb-1 fw-bold text-dark-blue">Proyectos</h1>
                        <p class="text-muted extra-small mb-0">Organiza equipos, fechas y entregables del trabajo.</p>
                    </div>
                    <button type="button" class="btn btn-luxury-primary rounded-2 px-4 btn-nuevo-proyecto">
                        <i class="fas fa-plus me-2"></i>Nuevo proyecto
                    </button>
                </div>
                <div class="card-body p-4">
                    <div class="w-100 text-start mb-4">
                        <ul class="nav nav-tabs-luxury gap-3" id="pills-tab-proyectos" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link tab-estado active fw-semibold" data-estado="ACTIVO" type="button">
                                    <i class="fas fa-folder-open me-2"></i>Proyectos activos
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link tab-estado fw-semibold" data-estado="INACTIVO" type="button">
                                    <i class="fas fa-folder-minus me-2"></i>Proyectos inactivos
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="table-responsive">
                        <table id="datatable_proyectos" class="table table-hover align-middle w-100">
                            <thead>
                                <tr>
                                    <th>Proyecto</th>
                                    <th>Líder</th>
                                    <th>Fechas</th>
                                    <th>Equipo</th>
                                    <th>Tareas</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include __DIR__ . '/../layouts/footer.php'; ?>
</div>
<?php include __DIR__ . '/../layouts/scripts.php'; ?>
<script src="<?php echo BASE_URL; ?>/views/proyectos/proyecto.js"></script>

<!-- MODAL PROYECTO -->
<div class="modal fade modal-luxury" id="modalProyecto" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <form id="formProyecto" novalidate>
                <div class="modal-header border-bottom px-4 py-3">
                    <h5 class="modal-title fw-bold" id="tituloModalProyecto">Nuevo proyecto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="proyecto_id">
                    
                    <!-- Navegación por pestañas -->
                    <ul class="nav nav-tabs mb-4" id="tabs-modal-proyecto" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-semibold" data-bs-toggle="tab" data-bs-target="#panel-datos-proyecto" type="button">
                                <i class="fas fa-info-circle me-1"></i> Datos
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#panel-equipo-proyecto" type="button">
                                <i class="fas fa-users me-1"></i> Equipo
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- PESTAÑA 1: DATOS -->
                        <div class="tab-pane fade show active" id="panel-datos-proyecto">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
                                    <input class="form-control" name="titulo" id="proyecto_titulo" placeholder="Ej. Implementación de Sistema CRM" maxlength="150" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Descripción</label>
                                    <textarea class="form-control" name="descripcion" id="proyecto_descripcion" rows="3" placeholder="Añade detalles sobre el objetivo de este proyecto..."></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Fecha de inicio <span class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control" name="fecha_inicio_global" id="proyecto_inicio" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Fecha de vencimiento <span class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control" name="fecha_vencimiento_global" id="proyecto_vencimiento" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Líder global <span class="text-danger">*</span></label>
                                    <select class="form-select" name="lider_name" id="proyecto_lider" required>
                                        <option value="">Selecciona un líder principal</option>
                                        <?php foreach ($lideresDisponibles as $lider): ?>
                                            <option value="<?= htmlspecialchars($lider['username']) ?>"><?= htmlspecialchars($lider['nombre']) ?> (<?= htmlspecialchars($lider['username']) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted"><i class="fas fa-shield-alt me-1"></i>Solo usuarios activos con permiso GESTIONAR_PROYECTOS.</small>
                                </div>
                            </div>
                        </div>

                        <!-- PESTAÑA 2: EQUIPO -->
                        <div class="tab-pane fade" id="panel-equipo-proyecto">
                            <div class="row g-4">
                                <!-- SECCIÓN LÍDERES ADICIONALES -->
                                <div class="col-md-6">
                                    <div class="equipo-section-card h-100">
                                        <label class="form-label fw-bold text-dark d-flex align-items-center mb-2">
                                            <i class="fas fa-user-shield me-2 text-primary"></i>Líderes adicionales
                                        </label>
                                        <div class="input-group mb-3">
                                            <select class="form-select" id="proyecto_lideres_disponibles">
                                                <option value="" selected disabled>Seleccionar líder...</option>
                                                <?php foreach ($lideresDisponibles as $lider): ?>
                                                    <option value="<?= htmlspecialchars($lider['username']) ?>"><?= htmlspecialchars($lider['nombre']) ?> (<?= htmlspecialchars($lider['username']) ?>)</option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="button" class="btn btn-primary" id="btn-agregar-lider" title="Agregar líder">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                        
                                        <label class="form-label small fw-semibold text-muted mb-1">Líderes en este proyecto:</label>
                                        <div id="proyecto_lideres_agregados" class="equipo-list-container">
                                            <!-- Renderizado vía JS -->
                                        </div>
                                    </div>
                                </div>

                                <!-- SECCIÓN MIEMBROS -->
                                <div class="col-md-6">
                                    <div class="equipo-section-card h-100">
                                        <label class="form-label fw-bold text-dark d-flex align-items-center mb-2">
                                            <i class="fas fa-users me-2 text-primary"></i>Miembros del equipo
                                        </label>
                                        <div class="input-group mb-3">
                                            <select class="form-select" id="proyecto_miembros_disponibles">
                                                <option value="" selected disabled>Seleccionar miembro...</option>
                                                <?php foreach ($miembrosDisponibles as $miembro): ?>
                                                    <option value="<?= htmlspecialchars($miembro['username']) ?>"><?= htmlspecialchars($miembro['nombre']) ?> (<?= htmlspecialchars($miembro['username']) ?>)</option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="button" class="btn btn-primary" id="btn-agregar-miembro" title="Agregar miembro">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>

                                        <label class="form-label small fw-semibold text-muted mb-1">Miembros asignados:</label>
                                        <div id="proyecto_miembros_agregados" class="equipo-list-container">
                                            <!-- Renderizado vía JS -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top bg-light px-4 py-3">
                    <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary px-4" type="submit"><i class="fas fa-save me-1"></i>Guardar proyecto</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>