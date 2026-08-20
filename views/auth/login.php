<!DOCTYPE html>
<html lang="es">
<head>
    <?php include __DIR__ . '/../layouts/head.php'; ?>

    <!-- CSS Exclusivo del Login -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/custom/views/login.css?v=<?php echo filemtime(__DIR__ . '/../../assets/css/custom/views/login.css'); ?>">
</head>
<body class="font-primary-family login-body">

    <div class="login-wrapper">
        <!-- Elementos Circulares Palpitantes de Fondo -->
        <div class="pulse-ring ring-1"></div>
        <div class="pulse-ring ring-2"></div>
        <div class="pulse-ring ring-3"></div>

        <!-- Esferas Decorativas Flotantes -->
        <div class="floating-orb orb-1"></div>
        <div class="floating-orb orb-2"></div>
        <div class="floating-orb orb-3"></div>

        <!-- Login Circular Central -->
        <div class="login-card-box">
            <div class="login-header text-center">
                <a href="index.php" class="brand-logo text-bold">
                    <?php echo APP_NAME; ?>
                </a>
                <h4 class="login-title text-bold">Iniciar Sesión</h4>
            </div>

            <form id="loginForm" action="<?php echo BASE_URL; ?>/?c=auth&a=process_login" method="POST" class="login-form">
                <!-- Campo de Correo Electrónico -->
                <div class="form-group mb-3">
                    <label for="emailInput" class="text-bold mb-1">Correo electrónico</label>
                    <input type="email" class="form-control custom-input" id="emailInput" name="email" placeholder="tucorreo@example.com" required autocomplete="email">
                </div>

                <!-- Campo de Contraseña Encapsulado -->
                <div class="form-group mb-4">
                    <label for="passwordInput" class="text-bold mb-1">Contraseña</label>
                    <div class="password-wrapper">
                        <input type="password" class="form-control custom-input" id="passwordInput" name="password" placeholder="••••••••" required autocomplete="current-password">
                        <button type="button" id="togglePassword" class="password-toggle-btn" tabindex="-1">
                            <i class="fa fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="form-action">
                    <button type="submit" class="btn btn-custom-submit text-bold">Ingresar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 1. Scripts globales -->
    <?php include __DIR__ . '/../layouts/scripts.php'; ?>

    <!-- 2. Alertas dinámicas con SweetAlert2 -->
    <?php if (isset($error) && $error): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: '¡Acceso Denegado!',
            text: '<?php echo addslashes($error); ?>',
            confirmButtonColor: (typeof AppConfig !== 'undefined' && AppConfig.sweetAlertDefaults) 
                ? AppConfig.sweetAlertDefaults.confirmButtonColor 
                : '#907C6A',
            background: '#ffffff',
            customClass: { popup: 'shadow-lg' },
            heightAuto: false
        }).then(() => {
            window.history.replaceState({}, document.title, window.location.pathname);
        });
    </script>
    <?php endif; ?>

    <?php if (isset($success) && $success): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: '¡Completado!',
            text: '<?php echo addslashes($success); ?>',
            timer: 3000,
            showConfirmButton: false,
            timerProgressBar: true,
            heightAuto: false
        }).then(() => {
            window.history.replaceState({}, document.title, window.location.pathname);
        });
    </script>
    <?php endif; ?>

    <!-- 3. JS Exclusivo del Login -->
    <?php 
    $loginJsPath = __DIR__ . '/../../assets/js/custom/views/login.js';
    if (file_exists($loginJsPath)) {
        echo '<script src="' . BASE_URL . '/assets/js/custom/views/login.js?v=' . filemtime($loginJsPath) . '"></script>' . PHP_EOL;
    }
    
    $viewLoginJsPath = __DIR__ . '/../../views/auth/login.js';
    if (file_exists($viewLoginJsPath)) {
        echo '<script src="' . BASE_URL . '/views/auth/login.js?v=' . filemtime($viewLoginJsPath) . '"></script>' . PHP_EOL;
    }
    ?>
</body>
</html>