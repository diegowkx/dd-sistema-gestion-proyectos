<!--==================================================-->
<!--                    SCRIPTS VENDORS CORE          -->
<!--==================================================-->

<!-- 1. JS por defecto global -->
<script src="<?php echo BASE_URL; ?>/assets/default/default.js"></script>

<!-- 2. jQuery Core (Local) -->
<script src="<?php echo BASE_URL; ?>/assets/js/jquery.min.js"></script>

<!-- 3. Bootstrap 5 JS Bundle Local -->
<script src="<?php echo BASE_URL; ?>/assets/js/bootstrap.bundle.min.js"></script>

<!-- 4. DataTables JS (Local) -->
<script src="<?php echo BASE_URL; ?>/assets/js/jquery.dataTables.min.js"></script>

<!-- 5. SweetAlert2 JS (Local) -->
<script src="<?php echo BASE_URL; ?>/assets/js/sweetalert2.all.min.js"></script>

<?php if (in_array(strtolower((string)($_GET['c'] ?? '')), ['kanban', 'gestion_kanban'], true)): ?>
<!-- SortableJS local para el tablero Kanban -->
<script src="<?php echo BASE_URL; ?>/assets/js/vendor/Sortable.min.js"></script>
<?php endif; ?>

<!-- 6. Configuración centralizada de JS -->
<script src="<?php echo BASE_URL; ?>/assets/js/config.js"></script>

<!-- 7. Lógica interactiva del Sidebar (Toggle/Animación/LocalStorage) -->
<script src="<?php echo BASE_URL; ?>/assets/js/custom/sidebar.js"></script>


<!--==================================================-->
<!--     CARGA AUTOMÁTICA DE SCRIPTS EN JS/CUSTOM/    -->
<!--==================================================-->
<?php
$customJsDir = __DIR__ . '/../../assets/js/custom/';
if (is_dir($customJsDir)) {
    foreach (glob($customJsDir . '*.js') as $file) {
        $fileName = basename($file);
        // Excluimos el sidebar.js para que no se cargue dos veces si ya está declarado arriba
        if ($fileName !== 'sidebar.js') {
            echo '<script src="' . BASE_URL . '/assets/js/custom/' . $fileName . '?v=' . filemtime($file) . '"></script>' . PHP_EOL;
        }
    }
}
?>


<!--==================================================-->
<!--     CARGA CONDICIONAL DE JS POR MÓDULO ACTUAL    -->
<!--==================================================-->
<?php
// Detecta el controlador actual
$controllerName = strtolower((string)($_GET['c'] ?? $_GET['controller'] ?? 'dashboard'));

// Carga automática del JS correspondiente al módulo actual dentro de custom/views/ (si aplica tu estructura)
$moduleJs = __DIR__ . '/../../assets/js/custom/views/' . $controllerName . '.js';

if (file_exists($moduleJs)) {
    echo '<script src="' . BASE_URL . '/assets/js/custom/views/' . $controllerName . '.js?v=' . filemtime($moduleJs) . '"></script>' . PHP_EOL;
}

// JS adicionales opcionales
if (isset($extra_js) && is_array($extra_js)) {
    foreach ($extra_js as $jsFile) {
        $filePath = __DIR__ . '/../../assets/js/' . $jsFile;
        if (file_exists($filePath)) {
            echo '<script src="' . BASE_URL . '/assets/js/' . $jsFile . '?v=' . filemtime($filePath) . '"></script>' . PHP_EOL;
        }
    }
}
?>

<!--==================================================-->
<!--             SCRIPTS FUNCIONALES GLOBALES         -->
<!--==================================================-->

<!-- Perfil de usuario global (Cargado al final de la cadena) -->
<script src="<?php echo BASE_URL; ?>/views/profile/profile.js"></script>