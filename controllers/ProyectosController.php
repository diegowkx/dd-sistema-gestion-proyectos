<?php

declare(strict_types=1);

require_once __DIR__ . '/ChecksPermisos.php';

class ProyectosController {
    use ChecksPermisos;

    public function index(PDO $pdo): void {
        $this->requierePermiso('GESTIONAR_PROYECTOS');
        require_once __DIR__ . '/../models/Proyectos.php';
        $proyectosModel = new Proyectos($pdo);
                $lideresDisponibles = $pdo->query("SELECT u.username, CONCAT(u.nombres, ' ', u.apellidos) AS nombre
                        FROM usuarios u
                        WHERE u.activo = 1
                            AND EXISTS (
                                    SELECT 1 FROM permisos_roles pr
                                    WHERE pr.rol_nombre = u.rol_name
                                        AND pr.permiso_nombre = 'GESTIONAR_PROYECTOS'
                            )
                        ORDER BY u.nombres, u.apellidos")->fetchAll(PDO::FETCH_ASSOC);
        $miembrosDisponibles = $pdo->query("SELECT username, CONCAT(nombres, ' ', apellidos) AS nombre FROM usuarios WHERE activo = 1 ORDER BY nombres, apellidos")->fetchAll(PDO::FETCH_ASSOC);
        $title = 'Gestión de Proyectos';
        require_once __DIR__ . '/../views/proyectos/index.php';
    }

    public function listar(PDO $pdo): void {
        $this->requierePermisoApi('GESTIONAR_PROYECTOS');
        require_once __DIR__ . '/../models/Proyectos.php';
        $estado = strtoupper(trim((string)($_GET['estado'] ?? 'ACTIVO')));
        if (!in_array($estado, ['ACTIVO', 'INACTIVO'], true)) {
            $estado = 'ACTIVO';
        }
        $model = new Proyectos($pdo);
        echo json_encode(['success' => true, 'data' => $model->getAll($estado)]);
        exit;
    }

    public function obtener(PDO $pdo): void {
        $this->requierePermisoApi('GESTIONAR_PROYECTOS');
        require_once __DIR__ . '/../models/Proyectos.php';
        $proyecto = (new Proyectos($pdo))->getById((int)($_GET['id'] ?? 0));
        echo json_encode($proyecto ? ['success' => true, 'data' => $proyecto] : ['success' => false, 'message' => 'Proyecto no encontrado.']);
        exit;
    }

    public function guardar(PDO $pdo): void {
        $this->requierePermisoApi('GESTIONAR_PROYECTOS');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit;
        }

        $data = [
            'id' => (int)($_POST['id'] ?? 0),
            'titulo' => trim((string)($_POST['titulo'] ?? '')),
            'descripcion' => trim((string)($_POST['descripcion'] ?? '')),
            'fecha_inicio_global' => trim((string)($_POST['fecha_inicio_global'] ?? '')),
            'fecha_vencimiento_global' => trim((string)($_POST['fecha_vencimiento_global'] ?? '')),
            'lider_name' => trim((string)($_POST['lider_name'] ?? '')),
            'lideres_adicionales' => is_array($_POST['lideres_adicionales'] ?? null) ? array_values(array_filter(array_map('trim', $_POST['lideres_adicionales']))) : [],
            'miembros_usuarios' => is_array($_POST['miembros_usuarios'] ?? null) ? array_values(array_filter(array_map('trim', $_POST['miembros_usuarios']))) : [],
        ];
        if ($data['titulo'] === '' || $data['fecha_inicio_global'] === '' || $data['fecha_vencimiento_global'] === '' || $data['lider_name'] === '') {
            echo json_encode(['success' => false, 'message' => 'Completa los campos obligatorios.']);
            exit;
        }
        $fechaInicio = DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i', $data['fecha_inicio_global']);
        $fechaVencimiento = DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i', $data['fecha_vencimiento_global']);
        $inicioValido = $fechaInicio && $fechaInicio->format('Y-m-d\\TH:i') === $data['fecha_inicio_global'];
        $vencimientoValido = $fechaVencimiento && $fechaVencimiento->format('Y-m-d\\TH:i') === $data['fecha_vencimiento_global'];
        if (!$inicioValido || !$vencimientoValido) {
            echo json_encode(['success' => false, 'message' => 'Las fechas del proyecto no tienen un formato válido.']);
            exit;
        }
        $ahora = new DateTimeImmutable();
        if ($fechaVencimiento <= $ahora) {
            echo json_encode(['success' => false, 'message' => 'La fecha de vencimiento debe ser posterior a la fecha y hora actual.']);
            exit;
        }
        if ($fechaVencimiento <= $fechaInicio) {
            echo json_encode(['success' => false, 'message' => 'La fecha de vencimiento debe ser posterior al inicio.']);
            exit;
        }

                $lideresValidos = $pdo->query("SELECT u.username
                        FROM usuarios u
                        WHERE u.activo = 1
                            AND EXISTS (
                                    SELECT 1 FROM permisos_roles pr
                                    WHERE pr.rol_nombre = u.rol_name
                                        AND pr.permiso_nombre = 'GESTIONAR_PROYECTOS'
                            )")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array($data['lider_name'], $lideresValidos, true)) {
                        echo json_encode(['success' => false, 'message' => 'El líder global debe tener el permiso GESTIONAR_PROYECTOS y estar activo.']);
            exit;
        }
        $data['lideres_adicionales'] = array_values(array_unique(array_diff($data['lideres_adicionales'], [$data['lider_name']])));
        if (array_diff($data['lideres_adicionales'], $lideresValidos)) {
            echo json_encode(['success' => false, 'message' => 'Solo usuarios activos con el permiso GESTIONAR_PROYECTOS pueden ser líderes adicionales.']);
            exit;
        }
        $usuariosValidos = $pdo->query('SELECT username FROM usuarios WHERE activo = 1')->fetchAll(PDO::FETCH_COLUMN);
        if (array_diff($data['miembros_usuarios'], $usuariosValidos)) {
            echo json_encode(['success' => false, 'message' => 'Uno o más miembros seleccionados no están activos.']);
            exit;
        }
        if (in_array($data['lider_name'], $data['miembros_usuarios'], true)) {
            echo json_encode(['success' => false, 'message' => 'El líder global no puede ser miembro del mismo proyecto.']);
            exit;
        }
        if (array_intersect($data['lideres_adicionales'], $data['miembros_usuarios'])) {
            echo json_encode(['success' => false, 'message' => 'Un líder adicional no puede ser miembro del mismo proyecto.']);
            exit;
        }

        require_once __DIR__ . '/../models/Proyectos.php';
        try {
            $ok = (new Proyectos($pdo))->save($data);
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Proyecto guardado correctamente.' : 'No se pudo guardar el proyecto.']);
        } catch (PDOException $e) {
            error_log('Error en ProyectosController::guardar: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'No se pudo guardar el proyecto.']);
        }
        exit;
    }

    public function eliminar(PDO $pdo): void {
        $this->requierePermisoApi('GESTIONAR_PROYECTOS');
        require_once __DIR__ . '/../models/Proyectos.php';
        $ok = (new Proyectos($pdo))->deactivate((int)($_POST['id'] ?? 0));
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Proyecto desactivado.' : 'No se pudo desactivar el proyecto.']);
        exit;
    }

    public function restaurar(PDO $pdo): void {
        $this->requierePermisoApi('GESTIONAR_PROYECTOS');
        require_once __DIR__ . '/../models/Proyectos.php';
        $ok = (new Proyectos($pdo))->restore((int)($_POST['id'] ?? 0));
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Proyecto restaurado.' : 'No se pudo restaurar el proyecto.']);
        exit;
    }
}
