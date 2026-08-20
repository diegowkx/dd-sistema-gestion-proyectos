<?php

declare(strict_types=1);

require_once __DIR__ . '/KanbanController.php';
require_once __DIR__ . '/../models/GestionKanban.php';

class GestionKanbanController extends KanbanController
{
	protected function modelo(PDO $pdo): Kanban
	{
		return new GestionKanban($pdo);
	}

	protected function permisoModulo(): string
	{
		return 'GESTIONAR_PROYECTOS';
	}

	public function index(PDO $pdo): void
	{
		$this->requierePermiso('GESTIONAR_PROYECTOS');
		$proyectos = $this->modelo($pdo)->getProyectosLiderados((string)($_SESSION['user_name'] ?? ''));
		$title = 'Gestión Kanban';
		require_once __DIR__ . '/../views/gestion_kanban/index.php';
	}
}
