<?php
// ============================================================
//  mDocente.php — Modelo de Docente
//  Accede a las tablas: persona + docente (herencia)
// ============================================================

class mDocente {

    private $con;

    public function __construct() {
        $this->con = Conexion::conectar();
    }

    /**
     * Busca un docente por su código de identificación.
     * JOIN con persona para obtener nombre y apellidos.
     *
     * @param  int|string $codigo  Código del docente (codigo_d)
     * @return array|false  Fila del docente o false si no existe
     */
    public function obtenerPorCodigo($codigo) {
        $sql = "SELECT p.id,
                       p.nombre,
                       p.apellido_p,
                       p.apellido_m,
                       d.codigo_d
                FROM   persona p
                INNER  JOIN docente d ON d.persona_id = p.id
                WHERE  d.codigo_d = :codigo
                LIMIT  1";

        $stmt = $this->con->prepare($sql);
        $stmt->execute([':codigo' => (int)$codigo]);
        return $stmt->fetch();
    }
}
