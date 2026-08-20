<?php

declare(strict_types=1);

class Kanban {
    public function __construct(private PDO $pdo) {}

    public function getProyectosLiderados(string $username): array {
        $stmt = $this->pdo->prepare("SELECT p.id, p.titulo
            FROM proyectos p
            WHERE p.activo = 1 AND (p.lider_name = :username OR EXISTS (
                SELECT 1 FROM lideres_proyecto lp
                WHERE lp.proyecto_id = p.id AND lp.usuario_username = :additional_username
            ))
            ORDER BY p.titulo");
        $stmt->execute([':username' => $username, ':additional_username' => $username]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProyectosParticipante(string $username): array {
        $stmt = $this->pdo->prepare("SELECT DISTINCT p.id, p.titulo
            FROM proyectos p
            LEFT JOIN proyecto_usuario pu ON pu.proyecto_id = p.id
            LEFT JOIN lideres_proyecto lp ON lp.proyecto_id = p.id
            WHERE p.activo = 1 AND (pu.usuario_username = ? OR p.lider_name = ? OR lp.usuario_username = ?)
            ORDER BY p.titulo");
        $stmt->execute([$username, $username, $username]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function tieneAccesoMiembro(int $proyectoId, string $username): bool {
        $stmt = $this->pdo->prepare('SELECT 1 FROM proyectos p LEFT JOIN proyecto_usuario pu ON pu.proyecto_id = p.id LEFT JOIN lideres_proyecto lp ON lp.proyecto_id = p.id WHERE p.id = ? AND p.activo = 1 AND (pu.usuario_username = ? OR p.lider_name = ? OR lp.usuario_username = ?) LIMIT 1');
        $stmt->execute([$proyectoId, $username, $username, $username]);
        return (bool)$stmt->fetchColumn();
    }

    public function getTableroMiembro(int $proyectoId, string $username): array {
        $tablero = $this->getTablero($proyectoId);
        $stmt = $this->pdo->prepare('SELECT 1 FROM proyectos p LEFT JOIN lideres_proyecto lp ON lp.proyecto_id = p.id WHERE p.id = ? AND (p.lider_name = ? OR lp.usuario_username = ?) LIMIT 1');
        $stmt->execute([$proyectoId, $username, $username]);
        $esLider = (bool)$stmt->fetchColumn();
        foreach ($tablero as &$columna) {
            $columna['tareas'] = array_values(array_filter(array_map(static function (array $tarea) use ($username, $esLider): array {
                if (!$esLider && !in_array($username, $tarea['asignados'], true)) return [];
                $tarea['mi_entrega'] = array_values(array_filter($tarea['entregas_detalle'], static fn(array $entrega): bool => $entrega['usuario_username'] === $username))[0] ?? null;
                return $tarea;
            }, $columna['tareas']), static fn(array $tarea): bool => $tarea !== []));
        }
        unset($columna);
        if (!$esLider) {
            foreach ($tablero as &$columna) {
                foreach ($columna['tareas'] as &$tarea) {
                    $tarea['entregas_detalle'] = $tarea['mi_entrega'] ? [$tarea['mi_entrega']] : [];
                }
                unset($tarea);
            }
            unset($columna);
        }
        return $tablero;
    }

    public function guardarEntrega(int $tareaId, int $proyectoId, string $username, string $mensaje, ?int $id = null): bool {
        $this->pdo->beginTransaction();
        try {
        if ($id !== null && $id > 0) {
            $stmt = $this->pdo->prepare('UPDATE entregas_tarea et INNER JOIN tareas t ON t.id = et.tarea_id INNER JOIN columnas c ON c.id = t.columna_id SET et.mensaje_entrega = ? WHERE et.id = ? AND et.tarea_id = ? AND et.usuario_username = ? AND c.proyecto_id = ?');
            $ok = $stmt->execute([$mensaje, $id, $tareaId, $username, $proyectoId]);
        } else {
            $stmt = $this->pdo->prepare('INSERT INTO entregas_tarea (tarea_id, usuario_username, mensaje_entrega) SELECT ?, ?, ? FROM tareas t INNER JOIN columnas c ON c.id = t.columna_id WHERE t.id = ? AND c.proyecto_id = ? AND t.activo = 1');
            $ok = $stmt->execute([$tareaId, $username, $mensaje, $tareaId, $proyectoId]);
        }
        if ($ok) $this->pdo->prepare('UPDATE tareas t INNER JOIN columnas c ON c.id = t.columna_id SET t.entregada = 1, t.fecha_entrega = NOW() WHERE t.id = ? AND c.proyecto_id = ?')->execute([$tareaId, $proyectoId]);
        $this->pdo->commit();
        return $ok;
        } catch (Throwable $e) { $this->pdo->rollBack(); throw $e; }
    }

    public function eliminarEntrega(int $id, int $proyectoId, string $username): bool {
        $consulta = $this->pdo->prepare('SELECT et.tarea_id FROM entregas_tarea et INNER JOIN tareas t ON t.id = et.tarea_id INNER JOIN columnas c ON c.id = t.columna_id WHERE et.id = ? AND et.usuario_username = ? AND c.proyecto_id = ?');
        $consulta->execute([$id, $username, $proyectoId]);
        $tareaId = (int)$consulta->fetchColumn();
        $stmt = $this->pdo->prepare('DELETE et FROM entregas_tarea et INNER JOIN tareas t ON t.id = et.tarea_id INNER JOIN columnas c ON c.id = t.columna_id WHERE et.id = ? AND et.usuario_username = ? AND c.proyecto_id = ?');
        $stmt->execute([$id, $username, $proyectoId]);
        if ($tareaId > 0 && $stmt->rowCount() > 0) {
            $restantes = $this->pdo->prepare('SELECT COUNT(*) FROM entregas_tarea WHERE tarea_id = ?');
            $restantes->execute([$tareaId]);
            if ((int)$restantes->fetchColumn() === 0) {
                $this->pdo->prepare('UPDATE tareas SET entregada = 0, fecha_entrega = NULL WHERE id = ?')->execute([$tareaId]);
            }
        }
        return $stmt->rowCount() > 0;
    }

    public function tieneAcceso(int $proyectoId, string $username): bool {
        $stmt = $this->pdo->prepare("SELECT 1 FROM proyectos p
            WHERE p.id = ? AND p.activo = 1 AND (p.lider_name = ? OR EXISTS (
                SELECT 1 FROM lideres_proyecto lp WHERE lp.proyecto_id = p.id AND lp.usuario_username = ?
            )) LIMIT 1");
        $stmt->execute([$proyectoId, $username, $username]);
        return (bool)$stmt->fetchColumn();
    }

    public function getTablero(int $proyectoId): array {
        $columnas = $this->pdo->prepare('SELECT id, nombre, posicion FROM columnas WHERE proyecto_id = ? AND activo = 1 ORDER BY posicion, id');
        $columnas->execute([$proyectoId]);
        $resultado = [];
        foreach ($columnas->fetchAll(PDO::FETCH_ASSOC) as $columna) {
                $stmt = $this->pdo->prepare("SELECT t.id, t.columna_id, t.titulo, t.descripcion, t.fecha_vencimiento, t.entregada, t.fecha_entrega,
                    (SELECT COUNT(*) FROM entregas_tarea et WHERE et.tarea_id = t.id) AS entregas,
                    GROUP_CONCAT(at.usuario_username ORDER BY at.usuario_username SEPARATOR ',') AS asignados
                FROM tareas t LEFT JOIN asignados_tarea at ON at.tarea_id = t.id
                WHERE t.columna_id = ? AND t.activo = 1
                GROUP BY t.id ORDER BY t.created_at, t.id");
            $stmt->execute([$columna['id']]);
            $columna['tareas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($columna['tareas'] as &$tarea) {
                $tarea['asignados'] = $tarea['asignados'] ? explode(',', $tarea['asignados']) : [];
                $tarea['estado'] = $this->estadoTarea($tarea);
                $tarea['entregas_detalle'] = $this->getEntregas((int)$tarea['id']);
            }
            unset($tarea);
            $resultado[] = $columna;
        }
        return $resultado;
    }

    public function getMiembros(int $proyectoId): array {
        $stmt = $this->pdo->prepare("SELECT u.username, CONCAT(u.nombres, ' ', u.apellidos) AS nombre
            FROM usuarios u INNER JOIN proyecto_usuario pu ON pu.usuario_username = u.username
            WHERE pu.proyecto_id = ? AND u.activo = 1 ORDER BY u.nombres, u.apellidos");
        $stmt->execute([$proyectoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEquipoProyecto(int $proyectoId): array {
        $stmt = $this->pdo->prepare("SELECT u.username, CONCAT(u.nombres, ' ', u.apellidos) AS nombre, 'LIDER GLOBAL' AS tipo
            FROM usuarios u INNER JOIN proyectos p ON p.lider_name = u.username WHERE p.id = ?
            UNION
            SELECT u.username, CONCAT(u.nombres, ' ', u.apellidos) AS nombre, 'LIDER ADICIONAL' AS tipo
            FROM usuarios u INNER JOIN lideres_proyecto lp ON lp.usuario_username = u.username WHERE lp.proyecto_id = ?
            UNION
            SELECT u.username, CONCAT(u.nombres, ' ', u.apellidos) AS nombre, 'MIEMBRO' AS tipo
            FROM usuarios u INNER JOIN proyecto_usuario pu ON pu.usuario_username = u.username WHERE pu.proyecto_id = ?
            ORDER BY tipo, nombre");
        $stmt->execute([$proyectoId, $proyectoId, $proyectoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crearColumna(int $proyectoId, string $nombre): bool {
        $stmt = $this->pdo->prepare('SELECT COALESCE(MAX(posicion), 0) + 1 FROM columnas WHERE proyecto_id = ?');
        $stmt->execute([$proyectoId]);
        $posicion = (int)$stmt->fetchColumn();
        return $this->pdo->prepare('INSERT INTO columnas (proyecto_id, nombre, posicion) VALUES (?, ?, ?)')->execute([$proyectoId, $nombre, $posicion]);
    }

    public function editarColumna(int $id, int $proyectoId, string $nombre): bool {
        return $this->pdo->prepare('UPDATE columnas SET nombre = ? WHERE id = ? AND proyecto_id = ? AND activo = 1')->execute([$nombre, $id, $proyectoId]);
    }

    public function eliminarColumna(int $id, int $proyectoId): bool {
        return $this->pdo->prepare('UPDATE columnas SET activo = 0 WHERE id = ? AND proyecto_id = ? AND activo = 1')->execute([$id, $proyectoId]);
    }

    public function ordenarColumnas(int $proyectoId, array $orden): bool {
        $orden = array_values(array_unique(array_map('intval', $orden)));
        if (!$orden) return true;

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('UPDATE columnas SET posicion = ? WHERE id = ? AND proyecto_id = ? AND activo = 1');
            foreach ($orden as $indice => $id) {
                $stmt->execute([-(($indice + 1) * 1000), $id, $proyectoId]);
            }
            foreach ($orden as $indice => $id) {
                $stmt->execute([$indice + 1, $id, $proyectoId]);
            }
            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function crearTarea(int $columnaId, string $titulo, string $descripcion, ?string $fecha, array $asignados): bool {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('INSERT INTO tareas (columna_id, titulo, descripcion, fecha_vencimiento) VALUES (?, ?, ?, ?)');
            $stmt->execute([$columnaId, $titulo, $descripcion ?: null, $fecha ?: null]);
            $tareaId = (int)$this->pdo->lastInsertId();
            $insertar = $this->pdo->prepare('INSERT INTO asignados_tarea (tarea_id, usuario_username) VALUES (?, ?)');
            foreach ($asignados as $username) {
                $insertar->execute([$tareaId, $username]);
            }
            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function editarTarea(int $tareaId, int $proyectoId, int $columnaId, string $titulo, string $descripcion, ?string $fecha, array $asignados): bool {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('UPDATE tareas t INNER JOIN columnas c ON c.id = t.columna_id SET t.columna_id = ?, t.titulo = ?, t.descripcion = ?, t.fecha_vencimiento = ? WHERE t.id = ? AND c.proyecto_id = ? AND t.activo = 1');
            $stmt->execute([$columnaId, $titulo, $descripcion ?: null, $fecha, $tareaId, $proyectoId]);
            $this->pdo->prepare('DELETE at FROM asignados_tarea at INNER JOIN tareas t ON t.id = at.tarea_id INNER JOIN columnas c ON c.id = t.columna_id WHERE at.tarea_id = ? AND c.proyecto_id = ?')->execute([$tareaId, $proyectoId]);
            $insertar = $this->pdo->prepare('INSERT INTO asignados_tarea (tarea_id, usuario_username) VALUES (?, ?)');
            foreach ($asignados as $username) $insertar->execute([$tareaId, $username]);
            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function eliminarTarea(int $tareaId, int $proyectoId): bool {
        $stmt = $this->pdo->prepare('UPDATE tareas t INNER JOIN columnas c ON c.id = t.columna_id SET t.activo = 0 WHERE t.id = ? AND c.proyecto_id = ?');
        return $stmt->execute([$tareaId, $proyectoId]);
    }

    public function getTarea(int $tareaId, int $proyectoId): array|false {
        foreach ($this->getTablero($proyectoId) as $columna) {
            foreach ($columna['tareas'] as $tarea) {
                if ((int)$tarea['id'] === $tareaId) return $tarea + ['columna_nombre' => $columna['nombre']];
            }
        }
        return false;
    }

    private function getEntregas(int $tareaId): array {
        $stmt = $this->pdo->prepare("SELECT et.id, et.usuario_username, et.mensaje_entrega, et.fecha_entregada,
                CONCAT(u.nombres, ' ', u.apellidos) AS usuario_nombre
            FROM entregas_tarea et INNER JOIN usuarios u ON u.username = et.usuario_username
            WHERE et.tarea_id = ? ORDER BY et.fecha_entregada DESC");
        $stmt->execute([$tareaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function estadoTarea(array $tarea): string {
        if ((int)$tarea['entregada'] === 1) {
            return $tarea['fecha_entrega'] && $tarea['fecha_vencimiento'] && $tarea['fecha_entrega'] <= $tarea['fecha_vencimiento'] ? 'A_TIEMPO' : 'ATRASADA';
        }
        return $tarea['fecha_vencimiento'] && $tarea['fecha_vencimiento'] < date('Y-m-d H:i:s') ? 'VENCIDA' : 'PENDIENTE';
    }

    public function moverTarea(int $tareaId, int $columnaId, array $orden): bool {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare('UPDATE tareas SET columna_id = ? WHERE id = ? AND activo = 1')->execute([$columnaId, $tareaId]);
            $stmt = $this->pdo->prepare('UPDATE tareas SET created_at = ? WHERE id = ? AND activo = 1');
            foreach ($orden as $indice => $id) {
                $stmt->execute([date('Y-m-d H:i:s', time() + $indice), (int)$id]);
            }
            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
