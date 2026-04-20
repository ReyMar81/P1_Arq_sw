<?php
// ============================================================
//  mRegistroEstudiante.php — Modelo de RegistroEstudiante  (CU3)
//  Tabla: registroEstudiante
//  Relaciones: estudiante (estudiante_id) y grupo (grupo_id)
// ============================================================

class mRegistroEstudiante {

    private $con;

    public function __construct() {
        $this->con = Conexion::conectar();
    }

    /**
     * Retorna todos los estudiantes registrados en un grupo.
     * JOIN a estudiante y persona para mostrar datos completos.
     *
     * @param  int $grupo_id
     * @return array
     */
    public function get_estudiantes_por_grupo($grupo_id) {
        $sql = "SELECT  re.id,
                        re.fecha_asignacion,
                        re.estudiante_id,
                        p.nombre,
                        p.apellido_p,
                        p.apellido_m,
                        e.registro_e
                FROM    registroEstudiante re
                INNER   JOIN estudiante e ON e.persona_id = re.estudiante_id
                INNER   JOIN persona    p ON p.id          = e.persona_id
                WHERE   re.grupo_id = :grupo_id
                ORDER   BY p.apellido_p ASC, p.apellido_m ASC, p.nombre ASC";

        $stmt = $this->con->prepare($sql);
        $stmt->execute([':grupo_id' => (int)$grupo_id]);
        return $stmt->fetchAll();
    }

    /**
     * Verifica si un estudiante ya está registrado en un grupo.
     *
     * @param  int $estudiante_id  (= persona.id = estudiante.persona_id)
     * @param  int $grupo_id
     * @return bool
     */
    public function ya_registrado($estudiante_id, $grupo_id) {
        $sql  = "SELECT COUNT(*)
                 FROM   registroEstudiante
                 WHERE  estudiante_id = :eid AND grupo_id = :gid";
        $stmt = $this->con->prepare($sql);
        $stmt->execute([':eid' => (int)$estudiante_id, ':gid' => (int)$grupo_id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Inserta un registro de estudiante en el grupo.
     * La transacción la gestiona el controlador (NO abre transacción propia).
     *
     * @param  int $estudiante_id
     * @param  int $grupo_id
     * @return bool
     */
    public function insertar_registro($estudiante_id, $grupo_id) {
        $sql  = "INSERT INTO registroEstudiante (estudiante_id, grupo_id)
                 VALUES (:eid, :gid)";
        $stmt = $this->con->prepare($sql);
        return $stmt->execute([
            ':eid' => (int)$estudiante_id,
            ':gid' => (int)$grupo_id,
        ]);
    }

    /**
     * Elimina un registro de estudiante del grupo por ID.
     *
     * @param  int $id
     * @return bool
     */
    public function eliminar_registro($id) {
        $sql  = "DELETE FROM registroEstudiante WHERE id = :id";
        $stmt = $this->con->prepare($sql);
        return $stmt->execute([':id' => (int)$id]);
    }
}
