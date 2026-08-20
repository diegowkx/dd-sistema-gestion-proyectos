<?php

declare(strict_types=1);

class DashboardDefault {

    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    
    public function getDatosGeneralesDefault(): array {
        $stmt = $this->pdo->query("
            SELECT
                COUNT(*) AS proyectos_totales,
                COALESCE(SUM(fecha_vencimiento_global >= NOW()), 0) AS proyectos_vigentes,
                COALESCE(SUM(fecha_vencimiento_global < NOW()), 0) AS proyectos_vencidos
            FROM proyectos
            WHERE activo = 1
        ");
        $data = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        foreach (['proyectos_totales', 'proyectos_vigentes', 'proyectos_vencidos'] as $field) {
            $data[$field] = (int)($data[$field] ?? 0);
        }
        return $data;
    }
}