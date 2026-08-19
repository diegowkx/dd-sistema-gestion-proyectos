<?php

// Modo de depuración (true en local, false en producción)
define('MODO_DEBUG', true);

if (MODO_DEBUG) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// URL Base
define('BASE_URL', 'http://localhost/dd-sistema-gestion-proyectos');

// Nombre del sistema
define('APP_NAME', 'KanbanPanel');
define('APP_VERSION', '1.0');

// Credenciales de la base de datos 
define('DB_HOST', 'localhost');
define('DB_NAME', 'dd_gestion_proyectos_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configuración de sesión segura
if (session_status() === PHP_SESSION_NONE) {
    session_name('KanbanPanel_APP_SESSION');

    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443);

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

// Zona horaria
date_default_timezone_set('America/Lima');