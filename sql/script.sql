-- PERSONA
CREATE TABLE persona (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    apellido_p VARCHAR(100) NOT NULL,
    apellido_m VARCHAR(100),
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- DOCENTE
CREATE TABLE docente (
    persona_id INT PRIMARY KEY,
    codigo_d INT UNIQUE NOT NULL,
    FOREIGN KEY (persona_id) REFERENCES persona(id) ON DELETE CASCADE
);

-- ESTUDIANTE
CREATE TABLE estudiante (
    persona_id INT PRIMARY KEY,
    registro_e VARCHAR(50) UNIQUE NOT NULL,
    FOREIGN KEY (persona_id) REFERENCES persona(id) ON DELETE CASCADE
);

-- MATERIA
CREATE TABLE materia (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- GRUPO
CREATE TABLE grupo (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    ciclo VARCHAR(50),
    docente_id INT NOT NULL,
    materia_id INT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (docente_id) REFERENCES docente(persona_id) ON DELETE CASCADE,
    FOREIGN KEY (materia_id) REFERENCES materia(id) ON DELETE CASCADE,
    UNIQUE KEY uq_grupo (nombre, docente_id, materia_id)
);

-- REGISTRO ESTUDIANTE
CREATE TABLE registroEstudiante (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fecha_asignacion DATE NOT NULL DEFAULT (CURRENT_DATE),
    estudiante_id INT NOT NULL,
    grupo_id INT NOT NULL,
    FOREIGN KEY (estudiante_id) REFERENCES estudiante(persona_id) ON DELETE CASCADE,
    FOREIGN KEY (grupo_id) REFERENCES grupo(id) ON DELETE CASCADE,
    UNIQUE KEY uq_registro (estudiante_id, grupo_id)
);

-- CLASE
CREATE TABLE clase (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fecha DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    grupo_id INT NOT NULL,
    qr_token VARCHAR(255) UNIQUE NULL COMMENT 'Token único para el código QR vigente',
    qr_fecha_creacion DATETIME NULL COMMENT 'Fecha y hora en que se generó el QR',
    qr_estado ENUM('vigente', 'expirado', 'invalidado') DEFAULT 'vigente' COMMENT 'Estado del QR actual',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (grupo_id) REFERENCES grupo(id) ON DELETE CASCADE
);

-- ASISTENCIA
CREATE TABLE asistencia (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fecha_hora_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    qr_verificado BOOLEAN DEFAULT TRUE,
    estudiante_id INT NOT NULL,
    clase_id INT NOT NULL,
    FOREIGN KEY (estudiante_id) REFERENCES estudiante(persona_id) ON DELETE CASCADE,
    FOREIGN KEY (clase_id) REFERENCES clase(id) ON DELETE CASCADE,
    UNIQUE KEY uq_asistencia (estudiante_id, clase_id)
);

-- Índices para optimización
CREATE INDEX idx_grupo_docente ON grupo(docente_id);
CREATE INDEX idx_grupo_materia ON grupo(materia_id);
CREATE INDEX idx_registroEstudiante_grupo ON registroEstudiante(grupo_id);
CREATE INDEX idx_clase_grupo ON clase(grupo_id);
CREATE INDEX idx_clase_fecha ON clase(fecha);
CREATE INDEX idx_clase_qr_token ON clase(qr_token);
CREATE INDEX idx_clase_qr_estado ON clase(qr_estado);
CREATE INDEX idx_asistencia_clase ON asistencia(clase_id);
CREATE INDEX idx_asistencia_estudiante ON asistencia(estudiante_id);
CREATE INDEX idx_asistencia_fecha ON asistencia(fecha_hora_registro);
