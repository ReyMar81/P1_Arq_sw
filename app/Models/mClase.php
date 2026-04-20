<?php
// ============================================================
//  mClase.php — Modelo de Clase  (CU4)
//  Operaciones CRUD sobre la tabla: clase
// ============================================================

class mClase {

    private $con;

    public function __construct() {
        $this->con = Conexion::conectar();
    }

    /**
     * Retorna todas las clases de un grupo.
     */
    public function get_clases_por_grupo($grupo_id) {
        $sql  = "SELECT * FROM clase WHERE grupo_id = :grupo_id ORDER BY fecha DESC, hora_inicio ASC";
        $stmt = $this->con->prepare($sql);
        $stmt->execute([':grupo_id' => (int)$grupo_id]);
        return $stmt->fetchAll();
    }

    /**
     * Retorna una clase por su ID.
     */
    public function get_clase_por_id($id) {
        $sql  = "SELECT * FROM clase WHERE id = :id";
        $stmt = $this->con->prepare($sql);
        $stmt->execute([':id' => (int)$id]);
        return $stmt->fetch();
    }

    /**
     * Inserta una nueva clase.
     */
    public function insertar_clase($fecha, $hora_inicio, $hora_fin, $grupo_id) {
        $sql  = "INSERT INTO clase (fecha, hora_inicio, hora_fin, grupo_id)
                 VALUES (:fecha, :hora_inicio, :hora_fin, :grupo_id)";
        $stmt = $this->con->prepare($sql);
        return $stmt->execute([
            ':fecha'       => $fecha,
            ':hora_inicio' => $hora_inicio,
            ':hora_fin'    => $hora_fin,
            ':grupo_id'    => (int)$grupo_id,
        ]);
    }

    /**
     * Actualiza una clase.
     */
    public function actualizar_clase($id, $fecha, $hora_inicio, $hora_fin) {
        $sql  = "UPDATE clase SET fecha = :fecha, hora_inicio = :hora_inicio, hora_fin = :hora_fin WHERE id = :id";
        $stmt = $this->con->prepare($sql);
        return $stmt->execute([
            ':fecha'       => $fecha,
            ':hora_inicio' => $hora_inicio,
            ':hora_fin'    => $hora_fin,
            ':id'          => (int)$id,
        ]);
    }

    /**
     * Elimina una clase por ID.
     */
    public function eliminar_clase($id) {
        $sql  = "DELETE FROM clase WHERE id = :id";
        $stmt = $this->con->prepare($sql);
        return $stmt->execute([':id' => (int)$id]);
    }
}
