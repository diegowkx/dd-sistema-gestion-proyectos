<!DOCTYPE html>
<html lang="es">
<head>
    <?php include __DIR__ . '/../layouts/head.php'; ?>
</head>
<body class="font-primary-family">
    <?php $esAdministrador = $esAdministrador ?? strtoupper((string)($_SESSION['user_rol'] ?? '')) === 'ADMINISTRADOR'; ?>

    <div id="main-wrapper">
        <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
        <?php include __DIR__ . '/../layouts/topbar.php'; ?>

        <div class="content-body">
            <div class="container-fluid">

                <div class="row mb-4 align-items-center">
                    <div class="col-md-6">
                        <h1 class="text-bold mb-0 fs-2">Panel de Control</h1>
                        <p class="text-muted small mb-0 mt-1">
                            Hola, <span class="text-bold text-dark"><?php echo htmlspecialchars(trim(($_SESSION['user_nombres'] ?? '') . ' ' . ($_SESSION['user_apellidos'] ?? ''))); ?></span>
                            (Rol: <span class="badge bg-secondary"><?php echo htmlspecialchars($_SESSION['user_rol'] ?? ''); ?></span>)
                        </p>
                    </div>
                    <div class="col-md-6 text-md-end mt-2 mt-md-0">
                        <span class="fs-5 text-bold text-accent">Sistema de Proyectos</span>
                    </div>
                </div>

                <form id="filtro-dashboard" class="row g-2 align-items-end w-100 mb-4">
                    <div class="col-12 col-md-5">
                        <label for="fecha_desde" class="form-label extra-small text-muted text-uppercase fw-bold">Desde</label>
                        <input type="date" class="form-control form-control-luxury rounded-2 border" id="fecha_desde" name="fecha_desde">
                    </div>
                    <div class="col-12 col-md-5">
                        <label for="fecha_hasta" class="form-label extra-small text-muted text-uppercase fw-bold">Hasta</label>
                        <input type="date" class="form-control form-control-luxury rounded-2 border" id="fecha_hasta" name="fecha_hasta">
                    </div>
                    <div class="col-12 col-md-2 d-flex gap-2 align-items-end">
                        <button type="submit" class="btn btn-primary-custom flex-fill" style="height: 44px;"><i class="fas fa-filter me-1"></i>Filtrar</button>
                        <button type="button" id="limpiar-filtro-dashboard" class="btn btn-light-creative flex-fill" style="height: 44px; background-color: #ffffff;">Limpiar</button>
                    </div>
                </form>

                <!-- KPIs -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-lg-3">
                        <div class="card card-custom border-0 shadow-sm rounded-4 h-100">
                            <div class="card-body p-3 p-md-4">
                                <span class="extra-small text-muted text-uppercase fw-bold">Proyectos totales</span>
                                    <h2 class="fw-bold mb-0 mt-1" id="kpi_proyectos_totales">—</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card card-custom border-0 shadow-sm rounded-4 h-100">
                            <div class="card-body p-3 p-md-4">
                                <span class="extra-small text-muted text-uppercase fw-bold">Proyectos vigentes</span>
                                    <h2 class="fw-bold mb-0 mt-1 text-success" id="kpi_proyectos_vigentes">—</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card card-custom border-0 shadow-sm rounded-4 h-100">
                            <div class="card-body p-3 p-md-4">
                                <span class="extra-small text-muted text-uppercase fw-bold">Tareas totales</span>
                                <h2 class="fw-bold mb-0 mt-1" id="kpi_tareas_totales">—</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card card-custom border-0 shadow-sm rounded-4 h-100">
                            <div class="card-body p-3 p-md-4">
                                <span class="extra-small text-muted text-uppercase fw-bold">Tareas completadas</span>
                                <h2 class="fw-bold mb-0 mt-1 text-success" id="kpi_tareas_completadas">—</h2>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">

                    <!-- ESTADO DE PUBLICACIÓN -->
                    <div class="col-12 col-lg-4 mb-4">
                        <div class="card card-custom border-0 shadow-sm rounded-4 h-100">
                            <div class="card-body p-4">
                                <h5 class="fw-bold mb-1">Estado de proyectos</h5>
                                <p class="extra-small text-muted mb-4">Proyectos activos según su fecha de vencimiento.</p>
                                <div id="lista-publicacion">
                                    <p class="text-muted extra-small mb-0">Cargando...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-4 mb-4">
                        <div class="card card-custom border-0 shadow-sm rounded-4 h-100">
                            <div class="card-body p-4">
                                <h5 class="fw-bold mb-1">Avance del trabajo</h5>
                                <p class="extra-small text-muted mb-4">Tareas completadas dentro de los proyectos.</p>
                                <div id="lista-asistencia">
                                    <p class="text-muted extra-small mb-0">Cargando...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-4 mb-4">
                        <div class="card card-custom border-0 shadow-sm rounded-4 h-100">
                            <div class="card-body p-4">
                                <h5 class="fw-bold mb-1">Equipo activo</h5>
                                <p class="extra-small text-muted mb-4">Usuarios habilitados para trabajar en el sistema.</p>
                                <div id="lista-certificaciones">
                                    <p class="text-muted extra-small mb-0">Cargando...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 mb-4">
                        <div class="card card-custom border-0 shadow-sm rounded-4">
                            <div class="card-body p-4">
                                <h5 class="fw-bold mb-1">Proyectos iniciados por fecha</h5>
                                <p class="extra-small text-muted mb-4">Distribución de proyectos por fecha de inicio.</p>
                                <canvas id="chart_ingresos" height="90"></canvas>
                                <p id="mensaje-chart-ingresos" class="text-muted extra-small mb-0 d-none">No hay ingresos para el rango seleccionado.</p>
                            </div>
                        </div>
                    </div>

                    <?php if ($esAdministrador): ?>
                    <!-- HISTORIAL DE IPS (solo administradores) -->
                    <div class="col-12">
                        <div class="card card-custom border-0 shadow-sm rounded-4">
                            <div class="card-body p-4">
                                <h5 class="fw-bold mb-1">Auditoría de Accesos</h5>
                                <p class="extra-small text-muted mb-4">Historial de IPs registradas por usuario.</p>
                                <div class="table-responsive">
                                    <table id="datatable_ips" class="table table-hover align-middle w-100">
                                        <thead>
                                            <tr>
                                                <th>Usuario</th>
                                                <th>Nombre Completo</th>
                                                <th>Rol</th>
                                                <th>IP</th>
                                                <th>Fecha de Acceso</th>
                                                <th class="text-center">Detalle</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>

            </div>
        </div>

        <?php if ($esAdministrador): ?>
        <div class="modal fade" id="modalIpDetalle" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-bold">Detalle del Registro de IP</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center"><strong>ID Registro:</strong> <span id="det_ip_id">-</span></li>
                            <li class="list-group-item d-flex justify-content-between align-items-center"><strong>Username:</strong> <span id="det_ip_usuario">-</span></li>
                            <li class="list-group-item d-flex justify-content-between align-items-center"><strong>Nombre Completo:</strong> <span id="det_ip_nombre">-</span></li>
                            <li class="list-group-item d-flex justify-content-between align-items-center"><strong>Correo:</strong> <span id="det_ip_correo">-</span></li>
                            <li class="list-group-item d-flex justify-content-between align-items-center"><strong>Rol:</strong> <span id="det_ip_rol" class="badge bg-dark">-</span></li>
                            <li class="list-group-item d-flex justify-content-between align-items-center"><strong>Dirección IP:</strong> <span id="det_ip_address" class="text-bold text-dark">-</span></li>
                            <li class="list-group-item d-flex justify-content-between align-items-center"><strong>Fecha de Acceso:</strong> <span id="det_ip_fecha">-</span></li>
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php include __DIR__ . '/../layouts/footer.php'; ?>
    </div>

    <?php include __DIR__ . '/../layouts/scripts.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/views/dashboard/dashboard.js?v=<?php echo filemtime(__DIR__ . '/dashboard.js'); ?>"></script>
</body>
</html>