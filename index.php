<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

/*
|--------------------------------------------------------------------------
| CONTROLADOR Y ACCIÓN
|--------------------------------------------------------------------------
*/

$controllerName = isset($_GET['c'])
    ? strtolower((string)$_GET['c'])
    : 'auth';

$actionName = isset($_GET['a'])
    ? strtolower((string)$_GET['a'])
    : ($controllerName === 'auth' ? 'login' : 'index');

/*
|--------------------------------------------------------------------------
| EVITAR MÉTODOS MÁGICOS
|--------------------------------------------------------------------------
*/

if (strpos($actionName, '__') === 0) {
    http_response_code(400);
    exit;
}

/*
|--------------------------------------------------------------------------
| SANITIZACIÓN
|--------------------------------------------------------------------------
*/

$controllerName = preg_replace('/[^a-z0-9_]/', '', $controllerName);
$actionName     = preg_replace('/[^a-z0-9_]/', '', $actionName);

if ($controllerName === '') {
    $controllerName = 'auth';
}

if ($actionName === '') {
    $actionName = 'index';
}

/*
|--------------------------------------------------------------------------
| PETICIONES AJAX
|--------------------------------------------------------------------------
*/

$isAjaxRequest =
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

/*
|--------------------------------------------------------------------------
| CONTROLADORES PÚBLICOS
|--------------------------------------------------------------------------
|
| Estos controladores pueden utilizarse sin iniciar sesión.
|
| auth:
|   Login
|
*/

$publicControllers = [
    'auth'
];

/*
|--------------------------------------------------------------------------
| PROTECCIÓN DEL SISTEMA ADMINISTRATIVO
|--------------------------------------------------------------------------
|
| Todo controlador que NO sea público requiere sesión.
|
*/

if (
    !isset($_SESSION['user_id']) &&
    !in_array($controllerName, $publicControllers, true)
) {
    if ($isAjaxRequest) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Su sesión ha expirado. Inicie sesión nuevamente.'
        ]);
        exit;
    }

    header('Location: ' . BASE_URL . '?c=auth&a=login');
    exit;
}

/*
|--------------------------------------------------------------------------
| CONVERTIR NOMBRE DEL CONTROLADOR
|--------------------------------------------------------------------------
|
*/

$formattedControllerName = str_replace(
    ' ',
    '',
    ucwords(str_replace('_', ' ', $controllerName))
);

$className = $formattedControllerName . 'Controller';

/*
|--------------------------------------------------------------------------
| UBICAR CONTROLADOR
|--------------------------------------------------------------------------
*/

$controllerFile = __DIR__ . '/controllers/' . $className . '.php';

if (!file_exists($controllerFile)) {
    http_response_code(404);
    exit('Controlador no encontrado.');
}

require_once $controllerFile;

/*
|--------------------------------------------------------------------------
| VERIFICAR CLASE
|--------------------------------------------------------------------------
*/

if (!class_exists($className)) {
    http_response_code(500);
    exit('La clase del controlador no existe.');
}

/*
|--------------------------------------------------------------------------
| CREAR CONTROLADOR
|--------------------------------------------------------------------------
*/

$controller = new $className();

/*
|--------------------------------------------------------------------------
| VERIFICAR ACCIÓN
|--------------------------------------------------------------------------
*/

if (!method_exists($controller, $actionName)) {
    http_response_code(404);
    exit('Acción no encontrada.');
}

/*
|--------------------------------------------------------------------------
| EJECUTAR ACCIÓN
|--------------------------------------------------------------------------
*/

$controller->$actionName($pdo);