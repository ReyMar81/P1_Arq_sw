<?php
// ============================================================
//  mQR.php — Modelo de QR  (CU5)
//  Operaciones para generación y gestión de códigos QR
// ============================================================

class mQR {

    private $con;

    public function __construct() {
        $this->con = Conexion::conectar();
    }

    /**
     * Obtiene todas las clases de un docente con información del grupo y materia.
     * Retorna las clases más recientes primero.
     *
     * @param  int $docente_id
     * @return array
     */
    public function get_clases_por_docente($docente_id) {
        $sql = "SELECT  c.id,
                        c.fecha,
                        c.hora_inicio,
                        c.hora_fin,
                        c.qr_token,
                        c.qr_fecha_creacion,
                        c.qr_estado,
                        g.id AS grupo_id,
                        g.nombre AS grupo_nombre,
                        g.ciclo AS grupo_ciclo,
                        m.nombre AS materia_nombre
                FROM    clase c
                INNER   JOIN grupo g ON g.id = c.grupo_id
                INNER   JOIN materia m ON m.id = g.materia_id
                WHERE   g.docente_id = :docente_id
                ORDER   BY c.fecha DESC, c.hora_inicio DESC";

        $stmt = $this->con->prepare($sql);
        $stmt->execute([':docente_id' => (int)$docente_id]);
        return $stmt->fetchAll();
    }

    /**
     * Genera un token QR único y lo asigna a una clase.
     * Invalida el QR anterior (si existe vigente).
     *
     * @param  int $clase_id
     * @return string El token generado
     */
    public function generar_qr_token($clase_id) {
        $clase_id = (int)$clase_id;

        // Generar token único
        $qr_token = bin2hex(random_bytes(16));

        // Invalidar QR anterior si existe y está vigente
        $sql_invalidar = "UPDATE clase
                          SET qr_estado = 'expirado'
                          WHERE id = :clase_id AND qr_estado = 'vigente'";
        $stmt_inv = $this->con->prepare($sql_invalidar);
        $stmt_inv->execute([':clase_id' => $clase_id]);

        // Generar nuevo QR
        $sql = "UPDATE clase
                SET qr_token = :qr_token,
                    qr_fecha_creacion = NOW(),
                    qr_estado = 'vigente'
                WHERE id = :clase_id";

        $stmt = $this->con->prepare($sql);
        return $stmt->execute([
            ':qr_token' => $qr_token,
            ':clase_id' => $clase_id,
        ]) ? $qr_token : null;
    }

    /**
     * Obtiene el QR de una clase (vigente o expirado).
     *
     * @param  int $clase_id
     * @return array|false
     */
    public function get_qr_por_clase($clase_id) {
        $sql = "SELECT  qr_token,
                        qr_fecha_creacion,
                        qr_estado
                FROM    clase
                WHERE   id = :clase_id";

        $stmt = $this->con->prepare($sql);
        $stmt->execute([':clase_id' => (int)$clase_id]);
        return $stmt->fetch();
    }

    /**
     * Valida si un token QR es vigente y retorna la clase asociada.
     * Utilizado en CU6 para marcar asistencia.
     *
     * @param  string $qr_token
     * @return array|false Retorna fila de clase si es vigente
     */
    public function validar_qr_token($qr_token) {
        $sql = "SELECT  c.id,
                        c.fecha,
                        c.grupo_id,
                        c.qr_estado
                FROM    clase c
                WHERE   c.qr_token = :qr_token AND c.qr_estado = 'vigente'
                LIMIT 1";

        $stmt = $this->con->prepare($sql);
        $stmt->execute([':qr_token' => trim($qr_token)]);
        return $stmt->fetch();
    }
}
