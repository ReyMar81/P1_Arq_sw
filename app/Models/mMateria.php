<?php
// ============================================================
//  mMateria.php — Modelo de Materia  (CU1)
//  Operaciones CRUD sobre la tabla: materia
// ============================================================

class mMateria {

    private $con;

    public function __construct() {
        $this->con = Conexion::conectar();
    }

    /**
     * Retorna todas las materias ordenadas por nombre.
     */
    public function get_materias() {
        $sql  = "SELECT * FROM materia ORDER BY nombre ASC";
        $stmt = $this->con->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Retorna una materia por su ID.
     */
    public function get_materia_por_id($id) {
        $sql  = "SELECT * FROM materia WHERE id = :id";
        $stmt = $this->con->prepare($sql);
        $stmt->execute([':id' => (int)$id]);
        return $stmt->fetch();
    }

    /**
     * Inserta una nueva materia. Lanza PDOException si el nombre ya existe.
     */
    public function insertar_materia($nombre) {
        $sql  = "INSERT INTO materia (nombre) VALUES (:nombre)";
        $stmt = $this->con->prepare($sql);
        return $stmt->execute([':nombre' => $nombre]);
    }

    /**
     * Actualiza el nombre de una materia. Lanza PDOException si ya existe.
     */
    public function actualizar_materia($id, $nombre) {
        $sql  = "UPDATE materia SET nombre = :nombre WHERE id = :id";
        $stmt = $this->con->prepare($sql);
        return $stmt->execute([':nombre' => $nombre, ':id' => (int)$id]);
    }

    /**
     * Elimina una materia por ID. Lanza PDOException si tiene grupos asociados.
     */
    public function eliminar_materia($id) {
        $sql  = "DELETE FROM materia WHERE id = :id";
        $stmt = $this->con->prepare($sql);
        return $stmt->execute([':id' => (int)$id]);
    }
}
