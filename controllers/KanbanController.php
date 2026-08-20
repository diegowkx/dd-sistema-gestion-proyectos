<?php

declare(strict_types=1);

require_once __DIR__ . '/ChecksPermisos.php';

class KanbanController {
    use ChecksPermisos;

    protected function modelo(PDO $pdo): Kanban {
        require_once __DIR__ . '/../models/Kanban.php';
        return new Kanban($pdo);
    }

    protected function permisoModulo(): string {
        return 'KANBAN';
    }

    private function usuarioActual(): string {
        return trim((string)($_SESSION['user_name'] ?? ''));
    }

    private function responder(array $respuesta): never {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($respuesta);
        exit;
    }

    private function exigirAcceso(PDO $pdo, int $proyectoId): Kanban {
        $this->requierePermisoApi($this->permisoModulo());
        $modelo = $this->modelo($pdo);
        if ($proyectoId <= 0 || !$modelo->tieneAcceso($proyectoId, $this->usuarioActual())) {
            $this->responder(['success' => false, 'message' => 'No tienes acceso de liderazgo a este proyecto.']);
        }
        return $modelo;
    }

    private function exigirAccesoMiembro(PDO $pdo, int $proyectoId): Kanban {
        $this->requierePermisoApi('KANBAN');
        $modelo = $this->modelo($pdo);
        if ($proyectoId <= 0 || !$modelo->tieneAccesoMiembro($proyectoId, $this->usuarioActual())) {
            $this->responder(['success' => false, 'message' => 'No tienes acceso a este proyecto.']);
        }
        return $modelo;
    }

    private function obtenerEquipo(PDO $pdo, int $proyectoId): array {
        $stmt = $pdo->prepare("SELECT u.username, CONCAT(u.nombres, ' ', u.apellidos) AS nombre, 'LIDER GLOBAL' AS tipo
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

    public function index(PDO $pdo): void {
        $this->requierePermiso($this->permisoModulo());
        $modelo = $this->modelo($pdo);
        $proyectos = $modelo->getProyectosParticipante($this->usuarioActual());
        $title = 'Mi Kanban';
        require_once __DIR__ . '/../views/kanban/miembro.php';
    }

    public function datos(PDO $pdo): void {
        $proyectoId = (int)($_GET['proyecto_id'] ?? 0);
        $modelo = $this->exigirAcceso($pdo, $proyectoId);
        $this->responder(['success' => true, 'columnas' => $modelo->getTablero($proyectoId), 'miembros' => $modelo->getMiembros($proyectoId)]);
    }

    public function datosMiembro(PDO $pdo): void {
        $proyectoId = (int)($_GET['proyecto_id'] ?? 0);
        $modelo = $this->exigirAccesoMiembro($pdo, $proyectoId);
        $this->responder(['success' => true, 'columnas' => $modelo->getTableroMiembro($proyectoId, $this->usuarioActual()), 'miembros' => $this->obtenerEquipo($pdo, $proyectoId)]);
    }

    public function guardarEntrega(PDO $pdo): void {
        $proyectoId = (int)($_POST['proyecto_id'] ?? 0);
        $modelo = $this->exigirAccesoMiembro($pdo, $proyectoId);
        $tareaId = (int)($_POST['tarea_id'] ?? 0);
        $mensaje = trim((string)($_POST['mensaje_entrega'] ?? ''));
        $id = (int)($_POST['id'] ?? 0);
        if ($tareaId <= 0 || $mensaje === '') {
            $this->responder(['success' => false, 'message' => 'La tarea y el mensaje de entrega son obligatorios.']);
        }
        $ok = $modelo->guardarEntrega($tareaId, $proyectoId, $this->usuarioActual(), $mensaje, $id > 0 ? $id : null);
        $this->responder(['success' => $ok, 'message' => $ok ? ($id > 0 ? 'Entrega actualizada.' : 'Entrega registrada.') : 'No se pudo guardar la entrega.']);
    }

    public function eliminarEntrega(PDO $pdo): void {
        $proyectoId = (int)($_POST['proyecto_id'] ?? 0);
        $modelo = $this->exigirAccesoMiembro($pdo, $proyectoId);
        $ok = $modelo->eliminarEntrega((int)($_POST['id'] ?? 0), $proyectoId, $this->usuarioActual());
        $this->responder(['success' => $ok, 'message' => $ok ? 'Entrega eliminada.' : 'No se pudo eliminar la entrega.']);
    }

    public function guardarColumna(PDO $pdo): void {
        $proyectoId = (int)($_POST['proyecto_id'] ?? 0);
        $modelo = $this->exigirAcceso($pdo, $proyectoId);
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        if ($nombre === '' || mb_strlen($nombre) > 50) {
            $this->responder(['success' => false, 'message' => 'El nombre de la columna es obligatorio y no debe superar 50 caracteres.']);
        }
        $id = (int)($_POST['id'] ?? 0);
        try {
            $ok = $id > 0 ? $modelo->editarColumna($id, $proyectoId, $nombre) : $modelo->crearColumna($proyectoId, $nombre);
            $this->responder(['success' => $ok, 'message' => $id > 0 ? 'Columna actualizada correctamente.' : 'Columna creada correctamente.']);
        } catch (PDOException $e) {
            $this->responder(['success' => false, 'message' => 'No se pudo crear la columna.']);
        }
    }

    public function eliminarColumna(PDO $pdo): void {
        $proyectoId = (int)($_POST['proyecto_id'] ?? 0);
        $modelo = $this->exigirAcceso($pdo, $proyectoId);
        $ok = $modelo->eliminarColumna((int)($_POST['id'] ?? 0), $proyectoId);
        $this->responder(['success' => $ok, 'message' => $ok ? 'Columna eliminada.' : 'No se pudo eliminar la columna.']);
    }

    public function ordenarColumnas(PDO $pdo): void {
        $proyectoId = (int)($_POST['proyecto_id'] ?? 0);
        $modelo = $this->exigirAcceso($pdo, $proyectoId);
        $orden = is_array($_POST['orden'] ?? null) ? $_POST['orden'] : [];
        $this->responder(['success' => $modelo->ordenarColumnas($proyectoId, $orden), 'message' => 'Orden de columnas actualizado.']);
    }

    public function guardarTarea(PDO $pdo): void {
        $proyectoId = (int)($_POST['proyecto_id'] ?? 0);
        $modelo = $this->exigirAcceso($pdo, $proyectoId);
        $columnaId = (int)($_POST['columna_id'] ?? 0);
        $titulo = trim((string)($_POST['titulo'] ?? ''));
        $descripcion = trim((string)($_POST['descripcion'] ?? ''));
        $fecha = trim((string)($_POST['fecha_vencimiento'] ?? ''));
        $asignados = is_array($_POST['asignados'] ?? null) ? array_values(array_unique(array_filter(array_map('trim', $_POST['asignados']))) ) : [];
        if ($titulo === '' || mb_strlen($titulo) > 150 || $columnaId <= 0 || count($asignados) === 0) {
            $this->responder(['success' => false, 'message' => 'La tarea, columna y al menos un miembro asignado son obligatorios.']);
        }
        $stmt = $pdo->prepare('SELECT 1 FROM columnas WHERE id = ? AND proyecto_id = ? AND activo = 1');
        $stmt->execute([$columnaId, $proyectoId]);
        if (!$stmt->fetchColumn()) {
            $this->responder(['success' => false, 'message' => 'La columna no pertenece al proyecto seleccionado.']);
        }
        $miembrosValidos = array_column($modelo->getMiembros($proyectoId), 'username');
        if (array_diff($asignados, $miembrosValidos)) {
            $this->responder(['success' => false, 'message' => 'Solo puedes asignar miembros activos del proyecto.']);
        }
        if ($fecha !== '') {
            $fechaObjeto = DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i', $fecha);
            if (!$fechaObjeto || $fechaObjeto->format('Y-m-d\\TH:i') !== $fecha) {
                $this->responder(['success' => false, 'message' => 'La fecha de la tarea no es válida.']);
            }
            $fecha = $fechaObjeto->format('Y-m-d H:i:s');
        } else {
            $fecha = null;
        }
        $tareaId = (int)($_POST['id'] ?? 0);
        try {
            $ok = $tareaId > 0
                ? $modelo->editarTarea($tareaId, $proyectoId, $columnaId, $titulo, $descripcion, $fecha, $asignados)
                : $modelo->crearTarea($columnaId, $titulo, $descripcion, $fecha, $asignados);
            $this->responder(['success' => $ok, 'message' => $tareaId > 0 ? 'Tarea actualizada correctamente.' : 'Tarea creada correctamente.']);
        } catch (PDOException $e) {
            $this->responder(['success' => false, 'message' => 'No se pudo crear la tarea.']);
        }
    }

    public function obtenerTarea(PDO $pdo): void {
        $proyectoId = (int)($_GET['proyecto_id'] ?? 0);
        $modelo = $this->exigirAcceso($pdo, $proyectoId);
        $tarea = $modelo->getTarea((int)($_GET['id'] ?? 0), $proyectoId);
        $this->responder($tarea ? ['success' => true, 'data' => $tarea] : ['success' => false, 'message' => 'Tarea no encontrada.']);
    }

    public function eliminarTarea(PDO $pdo): void {
        $proyectoId = (int)($_POST['proyecto_id'] ?? 0);
        $modelo = $this->exigirAcceso($pdo, $proyectoId);
        $ok = $modelo->eliminarTarea((int)($_POST['id'] ?? 0), $proyectoId);
        $this->responder(['success' => $ok, 'message' => $ok ? 'Tarea eliminada.' : 'No se pudo eliminar la tarea.']);
    }

    public function moverTarea(PDO $pdo): void {
        $proyectoId = (int)($_POST['proyecto_id'] ?? 0);
        $modelo = $this->exigirAcceso($pdo, $proyectoId);
        $tareaId = (int)($_POST['tarea_id'] ?? 0);
        $columnaId = (int)($_POST['columna_id'] ?? 0);
        $orden = is_array($_POST['orden'] ?? null) ? $_POST['orden'] : [];
        $stmt = $pdo->prepare('SELECT t.id FROM tareas t INNER JOIN columnas c ON c.id = t.columna_id WHERE t.id = ? AND c.proyecto_id = ? AND t.activo = 1');
        $stmt->execute([$tareaId, $proyectoId]);
        if (!$stmt->fetchColumn()) {
            $this->responder(['success' => false, 'message' => 'La tarea no pertenece al proyecto seleccionado.']);
        }
        $stmt = $pdo->prepare('SELECT 1 FROM columnas WHERE id = ? AND proyecto_id = ? AND activo = 1');
        $stmt->execute([$columnaId, $proyectoId]);
        if (!$stmt->fetchColumn()) {
            $this->responder(['success' => false, 'message' => 'La columna no pertenece al proyecto seleccionado.']);
        }
        try {
            $this->responder(['success' => $modelo->moverTarea($tareaId, $columnaId, $orden), 'message' => 'Orden actualizado.']);
        } catch (PDOException $e) {
            $this->responder(['success' => false, 'message' => 'No se pudo actualizar el tablero.']);
        }
    }
}
