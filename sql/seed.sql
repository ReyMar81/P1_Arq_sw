-- Insertar docente de prueba
INSERT INTO persona (nombre, apellido_p, apellido_m) VALUES
('Juan', 'Perez', 'Arteaga');

-- Insertar docente (vinculando con persona)
INSERT INTO docente (persona_id, codigo_d) VALUES
(1, 1001);

