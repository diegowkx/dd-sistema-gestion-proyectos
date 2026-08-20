-- =======================================================
-- MÓDULO 1: SEGURIDAD, ROLES Y AUDITORÍA
-- =======================================================

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    activo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP(),
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP()
);

CREATE TABLE permisos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE, -- DASHBOARD, ROLES, USUARIOS, GESTIONAR_PROYECTOS, KANBAN
    descripcion VARCHAR(255)
);

CREATE TABLE permisos_roles (
    rol_nombre VARCHAR(50) NOT NULL,
    permiso_nombre VARCHAR(50) NOT NULL,
    PRIMARY KEY (rol_nombre, permiso_nombre),
    FOREIGN KEY (rol_nombre) REFERENCES roles(nombre) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (permiso_nombre) REFERENCES permisos(nombre) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE usuarios (
    documento_id VARCHAR(15) PRIMARY KEY,
    rol_name VARCHAR(50) NOT NULL DEFAULT 'OPERADOR',
    username VARCHAR(20) NOT NULL UNIQUE,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    correo VARCHAR(100) NOT NULL UNIQUE,
    telefono VARCHAR(15),
    password_hash VARCHAR(100) NOT NULL,
    activo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP(),
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
    FOREIGN KEY (rol_name) REFERENCES roles(nombre) ON UPDATE CASCADE
);

CREATE TABLE iphys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_username VARCHAR(20) NOT NULL,
    rol_name VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    fecha_acceso DATETIME DEFAULT CURRENT_TIMESTAMP(),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP(),
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
    FOREIGN KEY (usuario_username) REFERENCES usuarios(username) ON UPDATE CASCADE,
    FOREIGN KEY (rol_name) REFERENCES roles(nombre) ON UPDATE CASCADE
);

-- =======================================================
-- MÓDULO 2: GESTIÓN DE PROYECTOS Y TABLERO KANBAN
-- =======================================================

CREATE TABLE proyectos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    descripcion TEXT,
    fecha_inicio_global DATETIME NOT NULL,
    fecha_vencimiento_global DATETIME NOT NULL,
    lider_name VARCHAR(20) NOT NULL, -- Líder principal del proyecto
    
    activo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP(),
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),

    FOREIGN KEY (lider_name) REFERENCES usuarios(username) ON UPDATE CASCADE,
    INDEX idx_proyectos_lider (lider_name),
    INDEX idx_proyectos_activo (activo)
);

-- Relación N:M para múltiples líderes en un proyecto
CREATE TABLE lideres_proyecto (
    proyecto_id INT NOT NULL,
    usuario_username VARCHAR(20) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP(),

    PRIMARY KEY (proyecto_id, usuario_username),
    FOREIGN KEY (proyecto_id) REFERENCES proyectos(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (usuario_username) REFERENCES usuarios(username) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_lideres_proyecto (proyecto_id),
    INDEX idx_lideres_usuario (usuario_username)
);

-- Relación N:M para miembros/participantes asignados a un proyecto
CREATE TABLE proyecto_usuario (
    proyecto_id INT NOT NULL,
    usuario_username VARCHAR(20) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP(),

    PRIMARY KEY (proyecto_id, usuario_username),
    FOREIGN KEY (proyecto_id) REFERENCES proyectos(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (usuario_username) REFERENCES usuarios(username) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_proyecto_usuario_proyecto (proyecto_id),
    INDEX idx_proyecto_usuario_usuario (usuario_username)
);

-- Columnas personalizables del tablero Kanban por proyecto
CREATE TABLE columnas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proyecto_id INT NOT NULL,
    nombre VARCHAR(50) NOT NULL,
    posicion INT NOT NULL DEFAULT 1, -- Para controlar el orden (1, 2, 3...)
    
    activo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP(),
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),

    FOREIGN KEY (proyecto_id) REFERENCES proyectos(id) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY idx_proyecto_posicion (proyecto_id, posicion),
    INDEX idx_columnas_proyecto (proyecto_id)
);

-- Tarjetas / Tareas dentro de cada columna
CREATE TABLE tareas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    columna_id INT NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    descripcion TEXT NULL,
    fecha_vencimiento DATETIME NULL,
    entregada TINYINT(1) DEFAULT 0,
    fecha_entrega DATETIME NULL,
    
    activo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP(),
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),

    FOREIGN KEY (columna_id) REFERENCES columnas(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_tareas_columna (columna_id),
    INDEX idx_tareas_entregada (entregada)
);

-- Relación N:M para múltiples personas asignadas a una tarea específica
CREATE TABLE asignados_tarea (
    tarea_id INT NOT NULL,
    usuario_username VARCHAR(20) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP(),

    PRIMARY KEY (tarea_id, usuario_username),
    FOREIGN KEY (tarea_id) REFERENCES tareas(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (usuario_username) REFERENCES usuarios(username) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_asignados_tarea (tarea_id),
    INDEX idx_asignados_usuario (usuario_username)
);

-- Historial / Registro de entregas o avances de una tarea
CREATE TABLE entregas_tarea (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tarea_id INT NOT NULL,
    usuario_username VARCHAR(20) NOT NULL,
    mensaje_entrega TEXT NOT NULL, -- Texto plano, notas o URLs adjuntas
    fecha_entregada DATETIME DEFAULT CURRENT_TIMESTAMP(),
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP(),
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),

    FOREIGN KEY (tarea_id) REFERENCES tareas(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (usuario_username) REFERENCES usuarios(username) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_entregas_tarea (tarea_id),
    INDEX idx_entregas_usuario (usuario_username)
);
