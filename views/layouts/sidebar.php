<div class="deznav font-primary-family">
    <div class="nav-header">
        <a href="?c=dashboard" class="brand-logo text-decoration-none">
            <img src="<?php echo BASE_URL; ?>/assets/images/logo-word.png" alt="<?php echo APP_NAME; ?>" class="sidebar-logo">
            <img src="<?php echo BASE_URL; ?>/assets/images/logo-image.png" alt="<?php echo APP_NAME; ?>" class="sidebar-logo-icon">
        </a>
    </div>

    <div class="deznav-scroll position-relative">
        <ul class="metismenu px-2" id="menu">

            <?php
            $currentController = strtolower((string)($_GET['c'] ?? $_GET['controller'] ?? 'dashboard'));

            if (!function_exists('tienePermisoSidebar')) {
                function tienePermisoSidebar(string $permiso): bool {
                    $permisosSession = $_SESSION['permisos'] ?? [];
                    return is_array($permisosSession) && in_array(strtoupper(trim($permiso)), $permisosSession, true);
                }
            }

            $grupoActual = null;
            $dashboardUrl = tienePermisoSidebar('DASHBOARD') ? '?c=dashboard' : '?c=dashboard_default';

            if (in_array($currentController, ['roles', 'usuarios'], true)) {
                $grupoActual = 'administracion';
            } elseif (in_array($currentController, ['proyectos', 'gestion_kanban'], true)) {
                $grupoActual = 'gestion_proyectos';
            }
            ?>

            <!-- DASHBOARD -->
            <li class="<?= ($currentController === 'dashboard') ? 'mm-active' : '' ?>">
                <a class="ai-icon" href="<?php echo $dashboardUrl; ?>">
                    <i class="fas fa-th-large"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>

            <!-- ADMINISTRACIÓN -->
            <?php
            $tieneAdministracion = tienePermisoSidebar('ROLES') || tienePermisoSidebar('USUARIOS');
            ?>
            <?php if ($tieneAdministracion): ?>
                <li class="sidebar-group <?= ($grupoActual === 'administracion') ? 'sidebar-group-open' : '' ?>" data-group="administracion">
                    <a href="javascript:void(0);" class="sidebar-group-toggle">
                        <i class="fas fa-cogs"></i>
                        <span class="nav-text">Administración</span>
                        <i class="fas fa-chevron-down sidebar-group-arrow"></i>
                    </a>
                    <ul class="sidebar-submenu">
                        <?php if (tienePermisoSidebar('ROLES')): ?>
                            <li class="<?= ($currentController === 'roles') ? 'mm-active' : '' ?>">
                                <a class="ai-icon" href="?c=roles">
                                    <i class="fas fa-user-shield"></i>
                                    <span class="nav-text">Roles</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if (tienePermisoSidebar('USUARIOS')): ?>
                            <li class="<?= ($currentController === 'usuarios') ? 'mm-active' : '' ?>">
                                <a class="ai-icon" href="?c=usuarios">
                                    <i class="fas fa-users"></i>
                                    <span class="nav-text">Usuarios</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </li>
            <?php endif; ?>

            <!-- GESTIÓN DE PROYECTOS -->
            <?php if (tienePermisoSidebar('GESTIONAR_PROYECTOS')): ?>
                <li class="sidebar-group <?= ($grupoActual === 'gestion_proyectos') ? 'sidebar-group-open' : '' ?>" data-group="gestion_proyectos">
                    <a href="javascript:void(0);" class="sidebar-group-toggle">
                        <i class="fas fa-project-diagram"></i>
                        <span class="nav-text">Gestión de Proyectos</span>
                        <i class="fas fa-chevron-down sidebar-group-arrow"></i>
                    </a>
                    <ul class="sidebar-submenu">
                        <?php if (tienePermisoSidebar('GESTIONAR_PROYECTOS')): ?>
                            <li class="<?= ($currentController === 'proyectos') ? 'mm-active' : '' ?>">
                                <a class="ai-icon" href="?c=proyectos">
                                    <i class="fas fa-folder-open"></i>
                                    <span class="nav-text">Proyectos</span>
                                </a>
                            </li>
                            <li class="<?= ($currentController === 'gestion_kanban') ? 'mm-active' : '' ?>">
                                <a class="ai-icon" href="?c=gestion_kanban">
                                    <i class="fas fa-columns"></i>
                                    <span class="nav-text">Gestión Kanban</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </li>
            <?php endif; ?>

            <?php if (tienePermisoSidebar('KANBAN')): ?>
                <li class="sidebar-group" data-group="kanban-miembro">
                    <a class="ai-icon" href="?c=kanban">
                        <i class="fas fa-tasks"></i>
                        <span class="nav-text">Mi Kanban</span>
                    </a>
                </li>
            <?php endif; ?>

        </ul>
    </div>
</div>

<div id="sidebar_overlay" class="sidebar-overlay"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const grupos = document.querySelectorAll('.sidebar-group');

    grupos.forEach(function (grupo) {
        const boton = grupo.querySelector('.sidebar-group-toggle');
        if (!boton) return;

        boton.addEventListener('click', function (e) {
            e.preventDefault();

            if (window.innerWidth > 991.98 && document.body.classList.contains('sidebar-collapsed')) {
                document.body.classList.remove('sidebar-collapsed');
                localStorage.setItem('sidebar-collapsed', 'false');
            }

            const estabaAbierto = grupo.classList.contains('sidebar-group-open');
            grupos.forEach(function (otroGrupo) { otroGrupo.classList.remove('sidebar-group-open'); });
            if (!estabaAbierto) grupo.classList.add('sidebar-group-open');
        });
    });

    const enlacesDirectos = document.querySelectorAll('.deznav .metismenu > li > a:not(.sidebar-group-toggle), .sidebar-submenu a');

    enlacesDirectos.forEach(function (enlace) {
        enlace.addEventListener('click', function (e) {
            const url = enlace.getAttribute('href');
            if (url && url !== '#' && !url.startsWith('javascript:')) {
                if (window.innerWidth <= 991.98) {
                    e.preventDefault();
                    document.body.classList.remove('sidebar-show');
                    document.body.classList.remove('sidebar-collapsed');
                    setTimeout(function () { window.location.href = url; }, 250);
                }
            }
        });
    });

    const overlay = document.getElementById('sidebar_overlay');
    if (overlay) {
        overlay.addEventListener('click', function () {
            document.body.classList.remove('sidebar-show');
            document.body.classList.remove('sidebar-collapsed');
        });
    }
});
</script>