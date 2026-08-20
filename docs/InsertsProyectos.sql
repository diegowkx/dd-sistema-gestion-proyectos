-- =======================================================
-- POBLADO DE DATOS INICIALES
-- =======================================================

-- 1. ROLES
INSERT INTO roles (nombre, activo) VALUES
('ADMINISTRADOR', 1),
('LIDER', 1),
('OPERADOR', 1);

-- 2. PERMISOS
INSERT INTO permisos (nombre, descripcion) VALUES
('DASHBOARD', 'Acceso a las métricas y reportes generales del sistema'),
('ROLES', 'Gestión de roles y asignación de permisos'),
('USUARIOS', 'Gestión de usuarios del sistema'),
('GESTIONAR_PROYECTOS', 'Creación, edición y asignación de proyectos'),
('KANBAN', 'Gestión de tableros, columnas y tareas');

-- 3. PERMISOS POR ROL
-- Rol: ADMINISTRADOR (Todos los permisos)
INSERT INTO permisos_roles (rol_nombre, permiso_nombre) VALUES
('ADMINISTRADOR', 'DASHBOARD'),
('ADMINISTRADOR', 'ROLES'),
('ADMINISTRADOR', 'USUARIOS'),
('ADMINISTRADOR', 'GESTIONAR_PROYECTOS'),
('ADMINISTRADOR', 'KANBAN');

-- Rol: LIDER
INSERT INTO permisos_roles (rol_nombre, permiso_nombre) VALUES
('LIDER', 'DASHBOARD'),
('LIDER', 'GESTIONAR_PROYECTOS'),
('LIDER', 'KANBAN');

-- Rol: OPERADOR
INSERT INTO permisos_roles (rol_nombre, permiso_nombre) VALUES
('OPERADOR', 'KANBAN');

-- 4. USUARIOS INICIALES
-- Contraseña para todos los usuarios: "clave"
-- Hash: $2y$10$VNgHI7pLLJc8otgTE/MMX.vLCVejzBT4mWskGyQ5AhA5vj2LSTZHG
INSERT INTO usuarios (documento_id, rol_name, username, nombres, apellidos, correo, telefono, password_hash) VALUES
('10000001', 'ADMINISTRADOR', 'admin', 'Carlos', 'Administrador', 'admin@kanbanpanel.com', '999111222', '$2y$10$VNgHI7pLLJc8otgTE/MMX.vLCVejzBT4mWskGyQ5AhA5vj2LSTZHG'),
('10000002', 'LIDER', 'lider1', 'Ana', 'Martínez', 'ana.martinez@kanbanpanel.com', '999222333', '$2y$10$VNgHI7pLLJc8otgTE/MMX.vLCVejzBT4mWskGyQ5AhA5vj2LSTZHG'),
('10000003', 'OPERADOR', 'opr1', 'Juan', 'Pérez', 'juan.perez@kanbanpanel.com', '999333444', '$2y$10$VNgHI7pLLJc8otgTE/MMX.vLCVejzBT4mWskGyQ5AhA5vj2LSTZHG'),
('10000004', 'OPERADOR', 'opr2', 'Maria', 'Gómez', 'maria.gomez@kanbanpanel.com', '999444555', '$2y$10$VNgHI7pLLJc8otgTE/MMX.vLCVejzBT4mWskGyQ5AhA5vj2LSTZHG');

-- 5. PROYECTOS
INSERT INTO proyectos (titulo, descripcion, fecha_inicio_global, fecha_vencimiento_global, lider_name) VALUES
('Sistema Web ERP', 'Desarrollo del módulo de inventario y ventas', '2026-03-01 08:00:00', '2026-06-30 18:00:00', 'lider1');

-- 6. LÍDERES ADICIONALES DEL PROYECTO
INSERT INTO lideres_proyecto (proyecto_id, usuario_username) VALUES
(1, 'admin');

-- 7. MIEMBROS/USUARIOS DEL PROYECTO
INSERT INTO proyecto_usuario (proyecto_id, usuario_username) VALUES
(1, 'lider1'),
(1, 'opr1'),
(1, 'opr2');

-- 8. COLUMNAS DEL TABLERO (Kanban)
INSERT INTO columnas (proyecto_id, nombre, posicion) VALUES
(1, 'Por Hacer', 1),
(1, 'En Proceso', 2),
(1, 'Terminado', 3);

-- 9. TAREAS
INSERT INTO tareas (columna_id, titulo, descripcion, fecha_vencimiento, entregada, fecha_entrega) VALUES
(1, 'Diseño de base de datos', 'Crear el script SQL inicial', '2026-03-10 23:59:59', 0, NULL),
(2, 'Autenticación JWT', 'Implementar Login y Middleware de roles', '2026-03-15 23:59:59', 0, NULL),
(3, 'Levantamiento de requerimientos', 'Reunión con el cliente y documento técnico', '2026-03-05 18:00:00', 1, '2026-03-04 16:30:00');

-- 10. ASIGNADOS A TAREAS
INSERT INTO asignados_tarea (tarea_id, usuario_username) VALUES
(1, 'opr1'),
(2, 'opr1'),
(2, 'opr2'),
(3, 'lider1');

-- 11. ENTREGAS / AVANCES DE TAREAS
INSERT INTO entregas_tarea (tarea_id, usuario_username, mensaje_entrega) VALUES
(3, 'lider1', 'Se adjunta el acta de la reunión aprobada por el cliente: https://docs.google.com/document/d/example');