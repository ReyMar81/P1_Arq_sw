<?php
// ============================================================
//  mEstudiante.php — Modelo de Estudiante
//  Tablas: persona + estudiante (herencia)
// ============================================================

class mEstudiante {

    private $con;

    public function __construct() {
        $this->con = Conexion::conectar();
    }

    /**
     * Busca un estudiante por número de registro (usado por Auth y CU3).
     * Retorna persona.id como 'id' (= estudiante.persona_id = estudiante_id).
     *
     * @param  int|string $registro
     * @return array|false
     */
    public function obtenerPorRegistro($registro) {
        $sql = "SELECT p.id,
                       p.nombre,
                       p.apellido_p,
                       p.apellido_m,
                       e.registro_e
                FROM   persona p
                INNER  JOIN estudiante e ON e.persona_id = p.id
                WHERE  e.registro_e = :registro
                LIMIT  1";

        $stmt = $this->con->prepare($sql);
        $stmt->execute([':registro' => trim($registro)]);
        return $stmt->fetch();
    }

    /**
     * Crea una persona y su estudiante asociado dentro de la transacción
     * que gestiona el controlador externo (NO abre transacción propia).
     *
     * @param  string     $nombre
     * @param  string     $apellido_p
     * @param  string     $apellido_m   Puede estar vacío
     * @param  int|string $registro_e
     * @return int  persona_id (=== estudiante.persona_id === estudiante_id)
     */
    public function crear_persona_estudiante($nombre, $apellido_p, $apellido_m, $registro_e) {
        // 1. Insertar persona
        $sql  = "INSERT INTO persona (nombre, apellido_p, apellido_m)
                 VALUES (:nombre, :apellido_p, :apellido_m)";
        $stmt = $this->con->prepare($sql);
        $stmt->execute([
            ':nombre'     => trim($nombre),
            ':apellido_p' => trim($apellido_p),
            ':apellido_m' => !empty(trim($apellido_m)) ? trim($apellido_m) : null,
        ]);
        $persona_id = (int)$this->con->lastInsertId();

        // 2. Insertar estudiante vinculado a persona
        $sql  = "INSERT INTO estudiante (persona_id, registro_e)
                 VALUES (:persona_id, :registro_e)";
        $stmt = $this->con->prepare($sql);
        $stmt->execute([
            ':persona_id' => $persona_id,
            ':registro_e' => trim($registro_e),
        ]);

        return $persona_id; // persona_id = estudiante.persona_id = estudiante_id
    }
}
