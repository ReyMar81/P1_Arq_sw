<?php
// ============================================================
//  mGrupo.php — Modelo de Grupo  (CU2)
//  Operaciones CRUD sobre la tabla: grupo
//  Relaciones: docente (docente_id) y materia (materia_id)
// ============================================================

class mGrupo {

    private $con;

    public function __construct() {
        $this->con = Conexion::conectar();
    }

    /**
     * Retorna todos los grupos de un docente con el nombre de la materia.
     * JOIN materia para mostrar nombre en la vista.
     *
     * @param  int $docente_id  ID del docente (persona_id de la sesión)
     * @return array
     */
    public function get_grupos_por_docente($docente_id) {
        $sql = "SELECT  g.id,
                        g.nombre,
                        g.ciclo,
                        g.materia_id,
                        g.fecha_creacion,
                        m.nombre AS materia_nombre
                FROM    grupo g
                INNER   JOIN materia m ON m.id = g.materia_id
                WHERE   g.docente_id = :docente_id
                ORDER   BY m.nombre ASC, g.nombre ASC";

        $stmt = $this->con->prepare($sql);
        $stmt->execute([':docente_id' => (int)$docente_id]);
        return $stmt->fetchAll();
    }

    /**
     * Retorna un grupo por ID con el nombre de la materia asociada.
     *
     * @param  int $id
     * @return array|false
     */
    public function get_grupo_por_id($id) {
        $sql = "SELECT  g.*,
                        m.nombre AS materia_nombre
                FROM    grupo g
                INNER   JOIN materia m ON m.id = g.materia_id
                WHERE   g.id = :id";

        $stmt = $this->con->prepare($sql);
        $stmt->execute([':id' => (int)$id]);
        return $stmt->fetch();
    }

    /**
     * Inserta un nuevo grupo.
     * La combinación (nombre, docente_id, materia_id) es única por constraint.
     * Lanza PDOException en caso de duplicado.
     *
     * @param  string $nombre
     * @param  string $ciclo      Puede ser vacío (nullable)
     * @param  int    $docente_id Viene de $_SESSION['usuario_id']
     * @param  int    $materia_id Seleccionado en el formulario
     * @return bool
     */
    public function insertar_grupo($nombre, $ciclo, $docente_id, $materia_id) {
        $sql = "INSERT INTO grupo (nombre, ciclo, docente_id, materia_id)
                VALUES (:nombre, :ciclo, :docente_id, :materia_id)";

        $stmt = $this->con->prepare($sql);
        return $stmt->execute([
            ':nombre'     => $nombre,
            ':ciclo'      => !empty($ciclo) ? $ciclo : null,
            ':docente_id' => (int)$docente_id,
            ':materia_id' => (int)$materia_id,
        ]);
    }

    /**
     * Actualiza nombre, ciclo y materia de un grupo.
     * El docente_id no cambia (pertenece al docente que lo creó).
     * Lanza PDOException en caso de duplicado.
     *
     * @param  int    $id
     * @param  string $nombre
     * @param  string $ciclo
     * @param  int    $materia_id
     * @return bool
     */
    public function actualizar_grupo($id, $nombre, $ciclo, $materia_id) {
        $sql = "UPDATE  grupo
                SET     nombre      = :nombre,
                        ciclo       = :ciclo,
                        materia_id  = :materia_id
                WHERE   id = :id";

        $stmt = $this->con->prepare($sql);
        return $stmt->execute([
            ':nombre'     => $nombre,
            ':ciclo'      => !empty($ciclo) ? $ciclo : null,
            ':materia_id' => (int)$materia_id,
            ':id'         => (int)$id,
        ]);
    }

    /**
     * Elimina un grupo por ID.
     * Lanza PDOException si tiene clases o estudiantes registrados (FK constraint).
     *
     * @param  int $id
     * @return bool
     */
    public function eliminar_grupo($id) {
        $sql  = "DELETE FROM grupo WHERE id = :id";
        $stmt = $this->con->prepare($sql);
        return $stmt->execute([':id' => (int)$id]);
    }
}
