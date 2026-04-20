<?php
// ============================================================
//  mAsistencia.php — Modelo de Asistencia  (CU6)
//  Operaciones para marcar asistencia mediante código QR
// ============================================================

class mAsistencia {

    private $con;

    public function __construct() {
        $this->con = Conexion::conectar();
    }

    /**
     * Obtiene los datos del estudiante por su registro_e.
     * Retorna id, nombre, apellido_p, apellido_m.
     *
     * @param  string $registro_e
     * @return array|false
     */
    public function obtener_estudiante_por_registro($registro_e) {
        $sql = "SELECT  e.persona_id AS id,
                        p.nombre,
                        p.apellido_p,
                        p.apellido_m,
                        e.registro_e
                FROM    estudiante e
                INNER   JOIN persona p ON p.id = e.persona_id
                WHERE   e.registro_e = :registro_e
                LIMIT 1";

        $stmt = $this->con->prepare($sql);
        $stmt->execute([':registro_e' => trim($registro_e)]);
        return $stmt->fetch();
    }

    /**
     * Verifica si un estudiante está registrado en un grupo.
     * Retorna true si el estudiante_id está en registroEstudiante con ese grupo_id.
     *
     * @param  int $estudiante_id (persona_id del estudiante)
     * @param  int $grupo_id
     * @return bool
     */
    public function esta_registrado_en_grupo($estudiante_id, $grupo_id) {
        $sql = "SELECT 1 FROM registroEstudiante
                WHERE estudiante_id = :estudiante_id
                  AND grupo_id = :grupo_id
                LIMIT 1";

        $stmt = $this->con->prepare($sql);
        $stmt->execute([
            ':estudiante_id' => (int)$estudiante_id,
            ':grupo_id' => (int)$grupo_id
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Obtiene los datos de la clase para verificación.
     * Retorna id, fecha, grupo_id, docente_id, grupo_nombre, materia_nombre.
     *
     * @param  int $clase_id
     * @return array|false
     */
    public function obtener_clase_completa($clase_id) {
        $sql = "SELECT  c.id,
                        c.fecha,
                        c.hora_inicio,
                        c.hora_fin,
                        c.grupo_id,
                        g.docente_id,
                        g.nombre AS grupo_nombre,
                        m.nombre AS materia_nombre
                FROM    clase c
                INNER   JOIN grupo g ON g.id = c.grupo_id
                INNER   JOIN materia m ON m.id = g.materia_id
                WHERE   c.id = :clase_id
                LIMIT 1";

        $stmt = $this->con->prepare($sql);
        $stmt->execute([':clase_id' => (int)$clase_id]);
        return $stmt->fetch();
    }

    /**
     * Verifica si un estudiante ya marcó asistencia en una clase.
     *
     * @param  int $estudiante_id
     * @param  int $clase_id
     * @return bool
     */
    public function ya_marco_asistencia($estudiante_id, $clase_id) {
        $sql = "SELECT 1 FROM asistencia
                WHERE estudiante_id = :estudiante_id
                  AND clase_id = :clase_id
                LIMIT 1";

        $stmt = $this->con->prepare($sql);
        $stmt->execute([
            ':estudiante_id' => (int)$estudiante_id,
            ':clase_id' => (int)$clase_id
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Registra la asistencia de un estudiante en una clase.
     * La combinación (estudiante_id, clase_id) es única, por lo que
     * solo puede registrarse una vez por clase.
     *
     * @param  int $estudiante_id
     * @param  int $clase_id
     * @return bool
     */
    public function registrar_asistencia($estudiante_id, $clase_id) {
        $sql = "INSERT INTO asistencia (estudiante_id, clase_id, qr_verificado)
                VALUES (:estudiante_id, :clase_id, 1)";

        $stmt = $this->con->prepare($sql);
        return $stmt->execute([
            ':estudiante_id' => (int)$estudiante_id,
            ':clase_id' => (int)$clase_id
        ]);
    }

    /**
     * Obtiene los registros de asistencia de un estudiante.
     * Retorna clase, fecha, hora, grupo, materia, fecha_hora_registro.
     *
     * @param  int $estudiante_id
     * @return array
     */
    public function obtener_asistencias_estudiante($estudiante_id) {
        $sql = "SELECT  a.id,
                        a.fecha_hora_registro,
                        c.fecha,
                        c.hora_inicio,
                        c.hora_fin,
                        g.nombre AS grupo_nombre,
                        m.nombre AS materia_nombre
                FROM    asistencia a
                INNER   JOIN clase c ON c.id = a.clase_id
                INNER   JOIN grupo g ON g.id = c.grupo_id
                INNER   JOIN materia m ON m.id = g.materia_id
                WHERE   a.estudiante_id = :estudiante_id
                ORDER   BY a.fecha_hora_registro DESC";

        $stmt = $this->con->prepare($sql);
        $stmt->execute([':estudiante_id' => (int)$estudiante_id]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene un registro de asistencia específico.
     *
     * @param  int $asistencia_id
     * @return array|false
     */
    public function obtener_asistencia($asistencia_id) {
        $sql = "SELECT  a.*,
                        c.fecha,
                        c.hora_inicio,
                        g.nombre AS grupo_nombre,
                        m.nombre AS materia_nombre,
                        p.nombre,
                        p.apellido_p,
                        p.apellido_m
                FROM    asistencia a
                INNER   JOIN clase c ON c.id = a.clase_id
                INNER   JOIN grupo g ON g.id = c.grupo_id
                INNER   JOIN materia m ON m.id = g.materia_id
                INNER   JOIN estudiante e ON e.persona_id = a.estudiante_id
                INNER   JOIN persona p ON p.id = e.persona_id
                WHERE   a.id = :asistencia_id
                LIMIT 1";

        $stmt = $this->con->prepare($sql);
        $stmt->execute([':asistencia_id' => (int)$asistencia_id]);
        return $stmt->fetch();
    }
}
