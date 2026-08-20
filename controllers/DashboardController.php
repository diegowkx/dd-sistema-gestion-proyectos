<?php

declare(strict_types=1);

require_once __DIR__ . '/ChecksPermisos.php';

class DashboardController {

    use ChecksPermisos;

    /**
     * Renderiza la vista principal del Dashboard
     */
    public function index(PDO $pdo): void {

        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '?c=auth&a=login');
            exit;
        }

        if (!$this->tienePermiso('DASHBOARD')) {
            header('Location: ' . BASE_URL . '?c=dashboard_default');
            exit;
        }

        $this->requierePermiso('DASHBOARD');

        $title = "Panel Principal";
        $esAdministrador = strtoupper((string)($_SESSION['user_rol'] ?? '')) === 'ADMINISTRADOR';
        require_once __DIR__ . '/../views/dashboard/index.php';
    }

    /**
     * Endpoint API: tarjetas resumen + ocupación por tipo + ingresos de los últimos 7 días,
     * todo en una sola respuesta para minimizar peticiones desde el dashboard.
     */
    public function resumen(PDO $pdo): void {
        $this->requierePermisoApi('DASHBOARD');
        header('Content-Type: application/json');

        require_once __DIR__ . '/../models/Dashboard.php';
        $dashboardModel = new Dashboard($pdo);

        $fechaDesde = $this->obtenerFechaFiltro($_GET['fecha_desde'] ?? null) ?? date('Y-01-01');
        $fechaHasta = $this->obtenerFechaFiltro($_GET['fecha_hasta'] ?? null) ?? date('Y-12-31');
        if ($fechaDesde !== null && $fechaHasta !== null && $fechaDesde > $fechaHasta) {
            echo json_encode(['success' => false, 'message' => 'El rango de fechas no es válido.']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'resumen' => $dashboardModel->getResumenGeneral($fechaDesde, $fechaHasta),
                'publicacion' => $dashboardModel->getOcupacionPorTipo($fechaDesde, $fechaHasta),
                'ingresos_por_fecha' => $dashboardModel->getIngresosUltimos7Dias($fechaDesde, $fechaHasta),
            ]
        ]);
        exit;
    }

    private function obtenerFechaFiltro(mixed $valor): ?string {
        $fecha = is_string($valor) ? trim($valor) : '';
        if ($fecha === '') {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        return $date !== false && $date->format('Y-m-d') === $fecha ? $fecha : null;
    }

    /**
     * Endpoint API: Listado DataTable de historial de IPs
     */
    public function listarIps(PDO $pdo): void {
        $this->requierePermisoApi('DASHBOARD');
        header('Content-Type: application/json');

        require_once __DIR__ . '/../models/Dashboard.php';
        $dashboardModel = new Dashboard($pdo);

        echo json_encode([
            'success' => true,
            'data'    => $dashboardModel->getHistorialIps()
        ]);
        exit;
    }

    /**
     * Endpoint API: Consulta de detalle por ID de un registro de IP
     */
    public function getIpById(PDO $pdo): void {
        $this->requierePermisoApi('DASHBOARD');
        header('Content-Type: application/json');

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID de registro no válido']);
            exit;
        }

        require_once __DIR__ . '/../models/Dashboard.php';
        $dashboardModel = new Dashboard($pdo);

        $detalle = $dashboardModel->getIpDetalleById($id);

        if ($detalle !== false) {
            echo json_encode(['success' => true, 'data' => $detalle]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Registro de IP no encontrado']);
        }
        exit;
    }
}