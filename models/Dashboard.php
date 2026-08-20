<?php

declare(strict_types=1);

class Dashboard {

    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getResumenGeneral(?string $fechaDesde = null, ?string $fechaHasta = null): array {
        [$filtroProyecto, $params] = $this->filtroFechaProyecto($fechaDesde, $fechaHasta);

        $stmt = $this->pdo->prepare("
            SELECT
                COUNT(*) AS proyectos_totales,
                COALESCE(SUM(fecha_vencimiento_global >= NOW()), 0) AS proyectos_vigentes,
                COALESCE(SUM(fecha_vencimiento_global < NOW()), 0) AS proyectos_vencidos,
                (SELECT COUNT(*) FROM tareas t INNER JOIN columnas c ON c.id = t.columna_id INNER JOIN proyectos p ON p.id = c.proyecto_id WHERE p.activo = 1) AS tareas_totales,
                (SELECT COUNT(*) FROM tareas t INNER JOIN columnas c ON c.id = t.columna_id INNER JOIN proyectos p ON p.id = c.proyecto_id WHERE p.activo = 1 AND t.entregada = 1) AS tareas_completadas,
                (SELECT COUNT(*) FROM usuarios WHERE activo = 1) AS usuarios_activos,
                (SELECT COUNT(*) FROM iphys) AS accesos_registrados
            FROM proyectos
            WHERE activo = 1 {$filtroProyecto}
        ");
        $stmt->execute($params);

        $data = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $tareas = (int)($data['tareas_totales'] ?? 0);
        $completadas = (int)($data['tareas_completadas'] ?? 0);
        $data['porcentaje_tareas'] = $tareas > 0
            ? round(($completadas / $tareas) * 100, 1)
            : 0.0;

        foreach (['proyectos_totales', 'proyectos_vigentes', 'proyectos_vencidos', 'tareas_totales', 'tareas_completadas', 'usuarios_activos', 'accesos_registrados'] as $field) {
            $data[$field] = (int)($data[$field] ?? 0);
        }

        return $data;
    }

    public function getOcupacionPorTipo(?string $fechaDesde = null, ?string $fechaHasta = null): array {
        [$filtroProyecto, $params] = $this->filtroFechaProyecto($fechaDesde, $fechaHasta);
        $stmt = $this->pdo->prepare("SELECT
                COALESCE(SUM(fecha_vencimiento_global >= NOW()), 0) AS vigentes,
                COALESCE(SUM(fecha_vencimiento_global < NOW()), 0) AS vencidos
            FROM proyectos WHERE activo = 1 {$filtroProyecto}");
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return ['VIGENTES' => (int)($row['vigentes'] ?? 0), 'VENCIDOS' => (int)($row['vencidos'] ?? 0)];
    }

    public function getIngresosUltimos7Dias(?string $fechaDesde = null, ?string $fechaHasta = null): array {
        [$filtroProyecto, $params] = $this->filtroFechaProyecto($fechaDesde, $fechaHasta);
        $stmt = $this->pdo->prepare("SELECT DATE(fecha_inicio_global) AS fecha, COUNT(*) AS proyectos
            FROM proyectos WHERE activo = 1 {$filtroProyecto}
            GROUP BY DATE(fecha_inicio_global) ORDER BY fecha ASC");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function filtroFechaProyecto(?string $fechaDesde, ?string $fechaHasta): array {
        $filtros = [];
        $params = [];
        if ($fechaDesde !== null && $fechaDesde !== '') {
            $nombreDesde = ':proyecto_fecha_desde';
            $filtros[] = "AND fecha_inicio_global >= {$nombreDesde}";
            $params[$nombreDesde] = $fechaDesde . ' 00:00:00';
        }
        if ($fechaHasta !== null && $fechaHasta !== '') {
            $nombreHasta = ':proyecto_fecha_hasta';
            $filtros[] = "AND fecha_inicio_global < {$nombreHasta}";
            $params[$nombreHasta] = date('Y-m-d', strtotime($fechaHasta . ' +1 day')) . ' 00:00:00';
        }
        return [implode(' ', $filtros), $params];
    }


    /**
     * Registra el acceso de un usuario con su IP en la tabla iphys.
     */
    public function registrarAccesoIp(string $username, string $rol, string $ip): bool {
        $stmt = $this->pdo->prepare("
            INSERT INTO iphys (usuario_username, rol_name, ip_address, fecha_acceso) 
            VALUES (:username, :rol, :ip, NOW())
        ");

        return $stmt->execute([
            ':username' => $username,
            ':rol'      => $rol,
            ':ip'       => $ip
        ]);
    }

    /**
     * Obtiene el historial completo de IPs registradas con el nombre del usuario.
     */
    public function getHistorialIps(): array {
        $stmt = $this->pdo->query("
            SELECT 
                i.id,
                i.usuario_username,
                CONCAT(u.nombres, ' ', u.apellidos) AS usuario_nombre_completo,
                i.rol_name,
                i.ip_address,
                i.fecha_acceso
            FROM iphys i
            INNER JOIN usuarios u ON i.usuario_username = u.username
            ORDER BY i.fecha_acceso DESC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el detalle específico de un registro de IP.
     */
    public function getIpDetalleById(int $id): array|false {
        $stmt = $this->pdo->prepare("
            SELECT 
                i.id,
                i.usuario_username,
                CONCAT(u.nombres, ' ', u.apellidos) AS usuario_nombre_completo,
                u.correo,
                u.telefono,
                i.rol_name,
                i.ip_address,
                i.fecha_acceso
            FROM iphys i
            INNER JOIN usuarios u ON i.usuario_username = u.username
            WHERE i.id = ?
        ");

        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    
}