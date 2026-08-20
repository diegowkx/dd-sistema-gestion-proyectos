<?php

declare(strict_types=1);

class GestionKanban
{
	public function __construct(private PDO $pdo) {}

	public function getProyectosLiderados(string $username): array
	{
		$stmt = $this->pdo->prepare("SELECT p.id, p.titulo FROM proyectos p
			WHERE p.activo = 1 AND (p.lider_name = :username OR EXISTS (
				SELECT 1 FROM lideres_proyecto lp
				WHERE lp.proyecto_id = p.id AND lp.usuario_username = :additional_username
			)) ORDER BY p.titulo");
		$stmt->execute([':username' => $username, ':additional_username' => $username]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function tieneAcceso(int $proyectoId, string $username): bool
	{
		$stmt = $this->pdo->prepare("SELECT 1 FROM proyectos p
			WHERE p.id = ? AND p.activo = 1 AND (p.lider_name = ? OR EXISTS (
				SELECT 1 FROM lideres_proyecto lp WHERE lp.proyecto_id = p.id AND lp.usuario_username = ?
			)) LIMIT 1");
		$stmt->execute([$proyectoId, $username, $username]);
		return (bool)$stmt->fetchColumn();
	}

	public function getTablero(int $proyectoId): array
	{
		$columnas = $this->pdo->prepare('SELECT id, nombre, posicion FROM columnas WHERE proyecto_id = ? AND activo = 1 ORDER BY posicion, id');
		$columnas->execute([$proyectoId]);
		$resultado = [];
		foreach ($columnas->fetchAll(PDO::FETCH_ASSOC) as $columna) {
			$stmt = $this->pdo->prepare("SELECT t.id, t.columna_id, t.titulo, t.descripcion, t.fecha_vencimiento, t.entregada, t.fecha_entrega,
					(SELECT COUNT(*) FROM entregas_tarea et WHERE et.tarea_id = t.id) AS entregas,
					GROUP_CONCAT(at.usuario_username ORDER BY at.usuario_username SEPARATOR ',') AS asignados
				FROM tareas t LEFT JOIN asignados_tarea at ON at.tarea_id = t.id
				WHERE t.columna_id = ? AND t.activo = 1 GROUP BY t.id ORDER BY t.created_at, t.id");
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

	public function getMiembros(int $proyectoId): array
	{
		$stmt = $this->pdo->prepare("SELECT u.username, CONCAT(u.nombres, ' ', u.apellidos) AS nombre
			FROM usuarios u INNER JOIN proyecto_usuario pu ON pu.usuario_username = u.username
			WHERE pu.proyecto_id = ? AND u.activo = 1 ORDER BY u.nombres, u.apellidos");
		$stmt->execute([$proyectoId]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function crearColumna(int $proyectoId, string $nombre): bool
	{
		$stmt = $this->pdo->prepare('SELECT COALESCE(MAX(posicion), 0) + 1 FROM columnas WHERE proyecto_id = ?');
		$stmt->execute([$proyectoId]);
		$posicion = (int)$stmt->fetchColumn();
		return $this->pdo->prepare('INSERT INTO columnas (proyecto_id, nombre, posicion) VALUES (?, ?, ?)')->execute([$proyectoId, $nombre, $posicion]);
	}

	public function editarColumna(int $id, int $proyectoId, string $nombre): bool { return $this->pdo->prepare('UPDATE columnas SET nombre = ? WHERE id = ? AND proyecto_id = ? AND activo = 1')->execute([$nombre, $id, $proyectoId]); }
	public function eliminarColumna(int $id, int $proyectoId): bool { return $this->pdo->prepare('UPDATE columnas SET activo = 0 WHERE id = ? AND proyecto_id = ? AND activo = 1')->execute([$id, $proyectoId]); }

	public function ordenarColumnas(int $proyectoId, array $orden): bool
	{
		$orden = array_values(array_unique(array_map('intval', $orden)));
		if (!$orden) return true;
		$this->pdo->beginTransaction();
		try {
			$stmt = $this->pdo->prepare('UPDATE columnas SET posicion = ? WHERE id = ? AND proyecto_id = ? AND activo = 1');
			foreach ($orden as $indice => $id) $stmt->execute([-($indice + 1) * 1000, $id, $proyectoId]);
			foreach ($orden as $indice => $id) $stmt->execute([$indice + 1, $id, $proyectoId]);
			$this->pdo->commit();
			return true;
		} catch (Throwable $e) { $this->pdo->rollBack(); throw $e; }
	}

	public function crearTarea(int $columnaId, string $titulo, string $descripcion, ?string $fecha, array $asignados): bool
	{
		$this->pdo->beginTransaction();
		try {
			$stmt = $this->pdo->prepare('INSERT INTO tareas (columna_id, titulo, descripcion, fecha_vencimiento) VALUES (?, ?, ?, ?)');
			$stmt->execute([$columnaId, $titulo, $descripcion ?: null, $fecha ?: null]);
			$tareaId = (int)$this->pdo->lastInsertId();
			$insertar = $this->pdo->prepare('INSERT INTO asignados_tarea (tarea_id, usuario_username) VALUES (?, ?)');
			foreach ($asignados as $username) $insertar->execute([$tareaId, $username]);
			$this->pdo->commit();
			return true;
		} catch (Throwable $e) { $this->pdo->rollBack(); throw $e; }
	}

	public function editarTarea(int $tareaId, int $proyectoId, int $columnaId, string $titulo, string $descripcion, ?string $fecha, array $asignados): bool
	{
		$this->pdo->beginTransaction();
		try {
			$stmt = $this->pdo->prepare('UPDATE tareas t INNER JOIN columnas c ON c.id = t.columna_id SET t.columna_id = ?, t.titulo = ?, t.descripcion = ?, t.fecha_vencimiento = ? WHERE t.id = ? AND c.proyecto_id = ? AND t.activo = 1');
			$stmt->execute([$columnaId, $titulo, $descripcion ?: null, $fecha, $tareaId, $proyectoId]);
			$this->pdo->prepare('DELETE at FROM asignados_tarea at INNER JOIN tareas t ON t.id = at.tarea_id INNER JOIN columnas c ON c.id = t.columna_id WHERE at.tarea_id = ? AND c.proyecto_id = ?')->execute([$tareaId, $proyectoId]);
			$insertar = $this->pdo->prepare('INSERT INTO asignados_tarea (tarea_id, usuario_username) VALUES (?, ?)');
			foreach ($asignados as $username) $insertar->execute([$tareaId, $username]);
			$this->pdo->commit();
			return true;
		} catch (Throwable $e) { $this->pdo->rollBack(); throw $e; }
	}

	public function eliminarTarea(int $tareaId, int $proyectoId): bool { return $this->pdo->prepare('UPDATE tareas t INNER JOIN columnas c ON c.id = t.columna_id SET t.activo = 0 WHERE t.id = ? AND c.proyecto_id = ?')->execute([$tareaId, $proyectoId]); }

	public function getTarea(int $tareaId, int $proyectoId): array|false
	{
		foreach ($this->getTablero($proyectoId) as $columna) foreach ($columna['tareas'] as $tarea) if ((int)$tarea['id'] === $tareaId) return $tarea + ['columna_nombre' => $columna['nombre']];
		return false;
	}

	public function moverTarea(int $tareaId, int $columnaId, array $orden): bool
	{
		$this->pdo->beginTransaction();
		try {
			$this->pdo->prepare('UPDATE tareas SET columna_id = ? WHERE id = ? AND activo = 1')->execute([$columnaId, $tareaId]);
			$stmt = $this->pdo->prepare('UPDATE tareas SET created_at = ? WHERE id = ? AND activo = 1');
			foreach ($orden as $indice => $id) $stmt->execute([date('Y-m-d H:i:s', time() + $indice), (int)$id]);
			$this->pdo->commit();
			return true;
		} catch (Throwable $e) { $this->pdo->rollBack(); throw $e; }
	}

	private function getEntregas(int $tareaId): array
	{
		$stmt = $this->pdo->prepare("SELECT et.id, et.usuario_username, et.mensaje_entrega, et.fecha_entregada, CONCAT(u.nombres, ' ', u.apellidos) AS usuario_nombre
			FROM entregas_tarea et INNER JOIN usuarios u ON u.username = et.usuario_username WHERE et.tarea_id = ? ORDER BY et.fecha_entregada DESC");
		$stmt->execute([$tareaId]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	private function estadoTarea(array $tarea): string
	{
		if ((int)$tarea['entregada'] === 1) return $tarea['fecha_entrega'] && $tarea['fecha_vencimiento'] && $tarea['fecha_entrega'] <= $tarea['fecha_vencimiento'] ? 'A_TIEMPO' : 'ATRASADA';
		return $tarea['fecha_vencimiento'] && $tarea['fecha_vencimiento'] < date('Y-m-d H:i:s') ? 'VENCIDA' : 'PENDIENTE';
	}
}
