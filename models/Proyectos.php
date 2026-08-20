<?php

declare(strict_types=1);

class Proyectos {
    public function __construct(private PDO $pdo) {}

    public function getAll(string $estado = 'ACTIVO', bool $soloPropios = false, ?string $username = null): array {
        $where = $estado === 'INACTIVO' ? 'p.activo = 0' : 'p.activo = 1';
        $params = [];
        if ($soloPropios && $username !== null) {
            $where .= ' AND (p.lider_name = :username OR EXISTS (SELECT 1 FROM proyecto_usuario pu WHERE pu.proyecto_id = p.id AND pu.usuario_username = :member_username))';
            $params[':username'] = $username;
            $params[':member_username'] = $username;
        }

        $stmt = $this->pdo->prepare("SELECT p.id, p.titulo, p.descripcion, p.fecha_inicio_global,
                p.fecha_vencimiento_global, p.lider_name,
            p.activo,
                CONCAT(u.nombres, ' ', u.apellidos) AS lider_nombre,
                (SELECT COUNT(*) FROM proyecto_usuario pu WHERE pu.proyecto_id = p.id) AS miembros,
                (SELECT COUNT(*) FROM columnas c WHERE c.proyecto_id = p.id) AS columnas,
                (SELECT COUNT(*) FROM tareas t INNER JOIN columnas c ON c.id = t.columna_id WHERE c.proyecto_id = p.id) AS tareas
            FROM proyectos p INNER JOIN usuarios u ON u.username = p.lider_name
            WHERE {$where} ORDER BY p.fecha_vencimiento_global ASC, p.id DESC");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id, bool $incluirInactivo = false): array|false {
        $filtroActivo = $incluirInactivo ? '' : ' AND activo = 1';
        $stmt = $this->pdo->prepare("SELECT id, titulo, descripcion, fecha_inicio_global, fecha_vencimiento_global, lider_name, activo FROM proyectos WHERE id = ?{$filtroActivo}");
        $stmt->execute([$id]);
        $proyecto = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$proyecto) {
            return false;
        }

        $stmt = $this->pdo->prepare('SELECT usuario_username FROM lideres_proyecto WHERE proyecto_id = ? ORDER BY usuario_username');
        $stmt->execute([$id]);
        $proyecto['lideres_adicionales'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $this->pdo->prepare('SELECT usuario_username FROM proyecto_usuario WHERE proyecto_id = ? ORDER BY usuario_username');
        $stmt->execute([$id]);
        $proyecto['miembros_usuarios'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return $proyecto;
    }

    public function save(array $data): bool {
        $this->pdo->beginTransaction();
        try {
            if ((int)($data['id'] ?? 0) > 0) {
                $stmt = $this->pdo->prepare('UPDATE proyectos SET titulo = ?, descripcion = ?, fecha_inicio_global = ?, fecha_vencimiento_global = ?, lider_name = ? WHERE id = ? AND activo = 1');
                $stmt->execute([$data['titulo'], $data['descripcion'], $data['fecha_inicio_global'], $data['fecha_vencimiento_global'], $data['lider_name'], (int)$data['id']]);
                $proyectoId = (int)$data['id'];
            } else {
                $stmt = $this->pdo->prepare('INSERT INTO proyectos (titulo, descripcion, fecha_inicio_global, fecha_vencimiento_global, lider_name) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$data['titulo'], $data['descripcion'], $data['fecha_inicio_global'], $data['fecha_vencimiento_global'], $data['lider_name']]);
                $proyectoId = (int)$this->pdo->lastInsertId();
            }

            $stmt = $this->pdo->prepare('DELETE FROM lideres_proyecto WHERE proyecto_id = ?');
            $stmt->execute([$proyectoId]);
            $insertarLider = $this->pdo->prepare('INSERT INTO lideres_proyecto (proyecto_id, usuario_username) VALUES (?, ?)');
            foreach ($data['lideres_adicionales'] as $lider) {
                $insertarLider->execute([$proyectoId, $lider]);
            }

            $stmt = $this->pdo->prepare('DELETE FROM proyecto_usuario WHERE proyecto_id = ?');
            $stmt->execute([$proyectoId]);
            $insertarMiembro = $this->pdo->prepare('INSERT INTO proyecto_usuario (proyecto_id, usuario_username) VALUES (?, ?)');
            foreach ($data['miembros_usuarios'] as $miembro) {
                $insertarMiembro->execute([$proyectoId, $miembro]);
            }

            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function deactivate(int $id): bool {
        $stmt = $this->pdo->prepare('UPDATE proyectos SET activo = 0 WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function restore(int $id): bool {
        $stmt = $this->pdo->prepare('UPDATE proyectos SET activo = 1 WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
